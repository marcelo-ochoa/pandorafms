<?php
//Pandora FMS- http://pandorafms.com
// ==================================================
// Copyright (c) 2005-2018 Artica Soluciones Tecnologicas
// Please see http://pandorafms.org for full contribution list

// This program is free software; you can redistribute it and/or
// modify it under the terms of the  GNU Lesser General Public License
// as published by the Free Software Foundation; version 2

// This program is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.

global $config;

require_once($config['homedir']."/include/class/Tree.class.php");

class TreeGroup extends Tree {

	protected $propagateCounters = true;
	protected $displayAllGroups  = false;

	public function  __construct($type, $rootType = '', $id = -1, $rootID = -1, $serverID = false, $childrenMethod = "on_demand", $access = 'AR') {

		global $config;

		parent::__construct($type, $rootType, $id, $rootID, $serverID, $childrenMethod, $access);

		$this->L1fieldName = "id_group";
		$this->L1extraFields = array(
			"tg.nombre AS `name`",
			"tg.icon",
			"tg.id_grupo AS gid"
		);

		$this->L2conditionInside = "AND (
			ta.id_grupo = " . $this->id . "
			OR tasg.id_group = " . $this->id . "
		)";
	}

	public function setPropagateCounters($value) {
		$this->propagateCounters = (bool)$value;
	}

	public function setDisplayAllGroups($value) {
		$this->displayAllGroups = (bool)$value;
	}

	protected function getData() {
		if ($this->id == -1) {
			$this->getFirstLevel();
		} elseif ($this->type == 'group') {
			$this->getSecondLevel();
		} elseif ($this->type == 'agent') {
			$this->getThirdLevel();
		}
	}

	protected function getGroupSearchFilter() {
		return "";
	}

	protected function getFirstLevel() {
		$processed_items = $this->getProcessedGroups();

		if (!empty($processed_items)) {
			// Filter by group name. This should be done after rerieving the items cause we need the possible items descendants
			if (!empty($this->filter['searchGroup'])) {
				// Save the groups which intersect with the user groups
				$groups = db_get_all_rows_filter('tgrupo', array('nombre' => '%' . $this->filter['searchGroup'] . '%'));
				if ($groups == false) $groups = array();
				$userGroupsACL = $this->userGroupsACL;
				$ids_hash = array_reduce($groups, function ($userGroups, $group) use ($userGroupsACL) {
					$group_id = $group['id_grupo'];
					if (isset($userGroupsACL[$group_id])) {
						$userGroups[$group_id] = $userGroupsACL[$group_id];
					}

					return $userGroups;
				}, array());

				$result = self::extractGroupsWithIDs($processed_items, $ids_hash);

				$processed_items = ($result === false) ? array() : $result;
			}

			// groupID filter. To access the view from tactical views f.e.
			if (!empty($this->filter['groupID'])) {
				$result = self::extractItemWithID($processed_items, $this->filter['groupID'], "group", $this->strictACL);

				$processed_items = ($result === false) ? array() : array($result);
			}
		}

		$this->tree = $processed_items;
	}

	protected function getProcessedGroups () {
		$processed_groups = array();
		// Index and process the groups
		$groups = $this->getGroupCounters();

		// If user have not permissions in parent, set parent node to 0 (all)
		// Avoid to do foreach for admins
		if (!users_can_manage_group_all("AR")) {
			foreach ($groups as $id => $group) {
				if (!isset($this->userGroups[$groups[$id]['parent']])) {
					$groups[$id]['parent'] = 0;
				}
			}
		}
		// Build the group hierarchy
		foreach ($groups as $id => $group) {
			if (isset($groups[$id]['parent']) && ($groups[$id]['parent'] != 0)) {
				$parent = $groups[$id]['parent'];
				// Parent exists
				if (!isset($groups[$parent]['children'])) {
					$groups[$parent]['children'] = array();
				}
				// Store a reference to the group into the parent
				$groups[$parent]['children'][] = &$groups[$id];
				// This group was introduced into a parent
				$groups[$id]['have_parent'] = true;
			}
		}
		// Sort the children groups
		foreach ($groups as $id => $group) {
			if (isset($groups[$id]['children'])) {
				usort($groups[$id]['children'], array("Tree", "cmpSortNames"));
			}
		}
		//Filter groups and eliminates the reference to children groups out of her parent
		$groups = array_filter($groups, function ($group) {
			return !$group['have_parent'];
		});
		// Propagate child counters to her parents

		if ($this->propagateCounters) {
			TreeGroup::processCounters($groups);
			// Filter groups and eliminates the reference to empty groups
			$groups = $this->deleteEmptyGroups($groups);
		} else {
			$groups = $this->deleteEmptyGroupsNotPropagate($groups);
		}

		usort($groups, array("Tree", "cmpSortNames"));
		return $groups;
	}

	protected function getGroupCounters() {
		$fields = $this->getFirstLevelFields();
		$inside_fields = $this->getFirstLevelFieldsInside();

		$group_acl = "";
		$secondary_group_acl = "";
		if (!users_can_manage_group_all("AR")) {
			$user_groups_str = implode(",", $this->userGroupsArray);
			$group_acl = " AND ta.id_grupo IN ($user_groups_str)";
			$secondary_group_acl = " AND tasg.id_group IN ($user_groups_str)";
		}
		$agent_search_filter = $this->getAgentSearchFilter();
		$agent_search_filter = preg_replace("/%/", "%%", $agent_search_filter);
		$agent_status_filter = $this->getAgentStatusFilter();
		$module_status_filter = $this->getModuleStatusFilter();

		$module_search_inner = "";
		$module_search_filter = "";
		if (!empty($this->filter['searchModule'])) {
			$module_search_inner = "
				INNER JOIN tagente_modulo tam
					ON ta.id_agente = tam.id_agente
				INNER JOIN tagente_estado tae
					ON tae.id_agente_modulo = tam.id_agente_modulo";
			$module_search_filter = "AND tam.disabled = 0
				AND tam.nombre LIKE '%%" . $this->filter['searchModule'] . "%%' " .
				$this->getModuleStatusFilterFromTestado()
			;
		}

		$table = is_metaconsole() ? "tmetaconsole_agent" : "tagente";
		$table_sec = is_metaconsole() ? "tmetaconsole_agent_secondary_group" : "tagent_secondary_group";

		$sql_model = "SELECT %s FROM
			(
				SELECT COUNT(DISTINCT(ta.id_agente)) AS total, id_grupo AS g
					FROM $table ta
					$module_search_inner
					WHERE ta.disabled = 0
						%s
						$agent_search_filter
						$agent_status_filter
						$module_status_filter
						$module_search_filter
						$group_acl
					GROUP BY id_grupo
				UNION ALL
				SELECT COUNT(DISTINCT(ta.id_agente)) AS total, id_group AS g
					FROM $table ta INNER JOIN $table_sec tasg
						ON ta.id_agente = tasg.id_agent
					$module_search_inner
					WHERE ta.disabled = 0
						%s
						$agent_search_filter
						$agent_status_filter
						$module_status_filter
						$module_search_filter
						$secondary_group_acl
					GROUP BY id_group
			) x GROUP BY g";
		$sql_array = array();
		foreach ($inside_fields as $inside_field) {
			$sql_array[] = sprintf(
				$sql_model,
				$inside_field['header'],
				$inside_field['condition'],
				$inside_field['condition']
			);
		}
		$sql = "SELECT $fields FROM (" . implode(" UNION ALL ", $sql_array) . ") x2
			RIGHT JOIN tgrupo tg
				ON x2.g = tg.id_grupo
			GROUP BY tg.id_grupo";
		$stats = db_get_all_rows_sql($sql);

		$group_stats = array();
		foreach ($stats as $group) {
			$group_stats[$group['gid']]['total_count'] = (int)$group['total_count'];
			$group_stats[$group['gid']]['total_critical_count'] = (int)$group['total_critical_count'];
			$group_stats[$group['gid']]['total_unknown_count'] = (int)$group['total_unknown_count'];
			$group_stats[$group['gid']]['total_warning_count'] = (int)$group['total_warning_count'];
			$group_stats[$group['gid']]['total_not_init_count'] = (int)$group['total_not_init_count'];
			$group_stats[$group['gid']]['total_normal_count'] = (int)$group['total_normal_count'];
			$group_stats[$group['gid']]['total_fired_count'] = (int)$group['total_alerts_count'];
			$group_stats[$group['gid']]['name'] = $group['name'];
			$group_stats[$group['gid']]['parent'] = $group['parent'];
			$group_stats[$group['gid']]['icon'] = $group['icon'];
			$group_stats[$group['gid']]['id'] = $group['gid'];
			$group_stats[$group['gid']] = $this->getProcessedItem($group_stats[$group['gid']]);
		}

		return $group_stats;
	}

	protected function getFirstLevelFields() {
		$fields = parent::getFirstLevelFields();
		$parent = $this->getDisplayHierarchy() ? 'tg.parent' : '0 as parent';
		return "$fields, $parent";
	}

	protected function getProcessedModules($modules_tree) {

		$groups = array();
		foreach ($modules_tree as $group) {
			$groups[$group["id"]] = $group;
		}

		// Build the module hierarchy
		foreach ($groups as $id => $group) {
			if (isset($groups[$id]['parent']) && ($groups[$id]['parent'] != 0)) {
				$parent = $groups[$id]['parent'];
				// Parent exists
				if (!isset($groups[$parent]['children'])) {
					$groups[$parent]['children'] = array();
				}
				// Store a reference to the group into the parent
				$groups[$parent]['children'][] = &$groups[$id];
				// This group was introduced into a parent
				$groups[$id]['have_parent'] = true;
			}
		}

		// Sort the children groups
		foreach ($groups as $id => $group) {
			if (isset($groups[$id]['children'])) {
				usort($groups[$id]['children'], array("Tree", "cmpSortNames"));
			}
		}
		//Filter groups and eliminates the reference to children groups out of her parent
		$groups = array_filter($groups, function ($group) {
			return !$group['have_parent'];
		});

		return array_values($groups);
	}

	// FIXME: Hierarchy lops is broken
	protected function getProcessedModules_old($modules_tree) {

		$tree_modules = array();
		$new_modules_root = array_filter($modules_tree, function ($module) {
			return (isset($module['parent']) && ($module['parent'] == 0));
		});

		$new_modules_child = array_filter($modules_tree, function ($module) {
			return (isset($module['parent']) && ($module['parent'] != 0));
		});

		$i = 0;
		while (!empty($new_modules_child)) {
			foreach ($new_modules_child as $i => $child) {
				TreeGroup::recursive_modules_tree_view($new_modules_root, $new_modules_child, $i, $child);
			}
		}

		foreach ($new_modules_root as $m) {
			$tree_modules[] = $m;
		}
		return $tree_modules;
	}

	// FIXME with getProcessedModules_old
	static function recursive_modules_tree_view (&$new_modules, &$new_modules_child, $i, $child) {
		foreach ($new_modules as $index => $module) {
			if ($module['id'] == $child['parent']) {
				$new_modules[$index]['children'][] = $child;
				unset($new_modules_child[$i]);
				break;
			}
			else if (isset($new_modules[$index]['children'])) {
				TreeGroup::recursive_modules_tree_view ($new_modules[$index]['children'], $new_modules_child, $i, $child);
			}
		}
	}

	static function processCounters(&$groups) {
		$all_counters = array();
		foreach ($groups as $id => $group) {
			$child_counters = array();
			if (!empty($groups[$id]['children'])) {
				$child_counters = TreeGroup::processCounters($groups[$id]['children']);
			}
			if (!empty($child_counters)) {
				foreach($child_counters as $type => $value) {
					$groups[$id]['counters'][$type] += $value;
				}
			}
			foreach($groups[$id]['counters'] as $type => $value) {
				$all_counters[$type] += $value;
			}
		}
		return $all_counters;
	}

	/**
	 * @brief Recursive function to remove the empty groups
	 *
	 * @param groups All groups structure
	 *
	 * @return new_groups A new groups structure without empty groups
	 */
	protected function deleteEmptyGroups ($groups) {
		if($this->displayAllGroups) return $groups;
		$new_groups = array();
		foreach ($groups as $group) {
			// If a group is empty, do not add to new_groups.
			if (!isset($group['counters']['total']) || $group['counters']['total'] == 0) {
				continue;
			}
			// Tray to remove the children groups
			if (!empty($group['children'])) {
				$children = $this->deleteEmptyGroups ($group['children']);
				if (empty($children)) unset($group['children']);
				else $group['children'] = $children;
			}
			$new_groups[] = $group;
		}
		return $new_groups;
	}

	protected function deleteEmptyGroupsNotPropagate ($groups) {
		if($this->displayAllGroups) return $groups;
		$new_groups = array();
		foreach ($groups as $group) {
			// Tray to remove the children groups
			if (!empty($group['children'])) {
				$children = $this->deleteEmptyGroupsNotPropagate ($group['children']);
				if (empty($children)) {
					unset($group['children']);
					// If a group is empty, do not add to new_groups.
					if (isset($group['counters']['total']) && $group['counters']['total'] != 0) {
						$new_groups[] = $group;
					}
				} else {
					$group['children'] = $children;
					$new_groups[] = $group;
				}
			} else {
				// If a group is empty, do not add to new_groups.
				if (isset($group['counters']['total']) && $group['counters']['total'] != 0) {
					$new_groups[] = $group;
				}
			}
		}
		return $new_groups;
	}

	private static function extractGroupsWithIDs ($groups, $ids_hash) {
		$result_groups = array();
		foreach ($groups as $group) {
			if (isset($ids_hash[$group['id']])) {
				$result_groups[] = $group;
			}
			else if (!empty($group['children'])) {
				$result = self::extractGroupsWithIDs($group['children'], $ids_hash);

				// Item found on children
				if (!empty($result)) {
					$result_groups = array_merge($result_groups, $result);
				}
			}
		}

		return $result_groups;
	}

	private static function extractItemWithID ($items, $item_id, $item_type = "group", $strictACL = false) {
		foreach ($items as $item) {
			if ($item["type"] != $item_type)
				continue;

			// Item found
			if ($strictACL && is_metaconsole()) {
				foreach ($item["id"] as $server_id => $id) {
					if ($id == $item_id)
						return $item;
				}
			}
			else {
				if ($item["id"] == $item_id)
					return $item;
			}

			if ($item["type"] == "group" && !empty($item["children"])) {
				$result = self::extractItemWithID($item["children"], $item_id, $item_type, $strictACL);

				// Item found on children
				if ($result !== false)
					return $result;
			}
		}

		// Item not found
		return false;
	}

	protected function getDisplayHierarchy() {
		return $this->filter['searchHirearchy'] ||
			(empty($this->filter['searchAgent']) && empty($this->filter['searchModule']));
	}

}

?>

