<?php
// Pandora FMS - http://pandorafms.com
// ==================================================
// Copyright (c) 2005-2010 Artica Soluciones Tecnologicas
// Please see http://pandorafms.org for full contribution list

// This program is free software; you can redistribute it and/or
// modify it under the terms of the GNU General Public License
// as published by the Free Software Foundation for version 2.
// This program is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
include_once("../include/functions_users.php");

class Agent {
	private $correct_acl = false;
	private $id = 0;
	private $agent = null;
	
	function __construct() {
		$system = System::getInstance();
		
		$this->id = $system->getRequest('id', 0);
		
		global $config;

		echo "<script>
		var ismobile = / mobile/i.test(navigator.userAgent);
		var iswindows = /Windows/i.test(navigator.userAgent);
		var ismac = /Macintosh/i.test(navigator.userAgent);
		var isubuntu = /Ubuntu/i.test(navigator.userAgent);
		var isfedora = /Fedora/i.test(navigator.userAgent);
		var isredhat = /Red Hat/i.test(navigator.userAgent);
		var isdebian = /Debian/i.test(navigator.userAgent);
		var isgentoo = /Gentoo/i.test(navigator.userAgent);
		var iscentos = /CentOS/i.test(navigator.userAgent);
		var issuse = /SUSE/i.test(navigator.userAgent);
		
		if(!(ismobile) && !(iswindows) && !(ismac) && !(isubuntu) && !(isfedora) && !(isredhat) && !(isdebian) && !(isgentoo) && !(iscentos) 
		&& !(issuse)){
			 window.location.href = '".$config['homeurl']."index.php?sec=estado&sec2=operation/agentes/ver_agente&id_agente=".$this->id."';
			";
			echo "
		}
		</script>";
		
		if (!$system->getConfig('metaconsole')) {
			$this->agent = agents_get_agents(array(
				'disabled' => 0,
				'id_agente' => $this->id), array('*'));
		}
		else {
			$this->agent = agents_get_meta_agents(array(
				'disabled' => 0,
				'id_agente' => $this->id), array('*'));
		}
		
		if (!empty($this->agent)) {
			$this->agent = $this->agent[0];
			
			
			if ($system->checkACL('AR', $this->agent['id_grupo'])) {
				$this->correct_acl = true;
			}
			else {
				$this->correct_acl = false;
			}
		}
		else {
			$this->agent = null;
			$this->correct_acl = true;
		}
	}
	
	public function show() {
		if (!$this->correct_acl) {
			$this->show_fail_acl();
		}
		else {
			$this->show_agent();
		}
	}
	
	private function show_fail_acl() {
		$error['type'] = 'onStart';
		$error['title_text'] = __('You don\'t have access to this page');
		$error['content_text'] = System::getDefaultACLFailText();
		if (class_exists("HomeEnterprise"))
			$home = new HomeEnterprise();
		else
			$home = new Home();
		$home->show($error);
	}
	
	
	public function ajax($parameter2 = false) {
		$system = System::getInstance();

		if (!$this->correct_acl) {
			return;
		}
		else {
			switch ($parameter2) {
				case 'render_events_bar':
					$agent_id = $system->getRequest('agent_id', '0');
					$width = $system->getRequest('width', '400');
					graph_graphic_agentevents(
						$agent_id, $width, 30, SECONDS_1DAY, ui_get_full_url(false));
					exit;
			}
		}
	}
	
	private function show_agent() {
		$ui = Ui::getInstance();
		$system = System::getInstance();
		
		
		
		
		$ui->createPage();
		
		if ($this->id != 0) {
			$agent_alias = (string) $this->agent['alias'];
			
			$agents_filter = (string) $system->getRequest('agents_filter');
			$agents_filter_q_param = empty($agents_filter) ? '' : '&agents_filter=' . $agents_filter;
			
			$ui->createDefaultHeader(
				sprintf('%s', $agent_alias),
				$ui->createHeaderButton(
					array('icon' => 'back',
						'pos' => 'left',
						'text' => __('Back'),
						'href' => 'index.php?page=agents' . $agents_filter_q_param)));
		}
		else {
			$ui->createDefaultHeader(__("Agents"));
		}
		$ui->showFooter(false);
		$ui->beginContent();
			if (empty($this->agent)) {
				$ui->contentAddHtml('<span style="color: red;">' . 
					__('No agent found') . '</span>');
			}
			else {
				$ui->contentBeginGrid();
				if ($this->agent['disabled']) {
					$agent_alias = "<em>" . $agent_alias . "</em>" . 
						ui_print_help_tip(__('Disabled'), true);
				}
				else if ($this->agent['quiet']) {
					$agent_alias = "<em>" . $agent_alias . "&nbsp;" . 
						html_print_image("images/dot_blue.png", 
						true, array("border" => '0', "title" => __('Quiet'), "alt" => "")) . "</em>";
				}
				
				
				if ($system->getConfig('metaconsole')) {
					metaconsole_connect(null, $this->agent['id_tmetaconsole_setup']);
					//~ $addresses = agents_get_addresses($this->agent['id_tagente']);
				}
				else
					$addresses = agents_get_addresses($this->id);
				
				if ($system->getConfig('metaconsole'))
					metaconsole_restore_db();
				
				$address = $this->agent['direccion'];
				//~ foreach ($addresses as $k => $add) {
					//~ if ($add == $address) {
						//~ unset($addresses[$k]);
					//~ }
				//~ }
				
				//~ $ip = html_print_image('images/world.png', 
					//~ true, array('title' => __('IP address'))) . 
					//~ '&nbsp;&nbsp;';
				$ip .= empty($address) ? '<em>' . __('N/A') . 
					'</em>' : $address;
				
				//~ if (!empty($addresses)) {
					//~ $ip .= ui_print_help_tip(__('Other IP addresses') . 
						//~ ':  ' . implode(', ',$addresses), true);
				//~ }
				
				$last_contact = '<b>' . __('Last contact') . 
					'</b>:&nbsp;' . 
					ui_print_timestamp ($this->agent["ultimo_contacto"], true);
				
				//~ $description = '<b>' . __('Description') . ':</b>&nbsp;';
				if (empty($agent["comentarios"])) {
					$description .= '<i>' . __('N/A') . '</i>';
				}
				else {
					$description .= $this->agent["comentarios"];
				}
				
				$html = '<div class="agent_details" style:"float:left;">';
				$html .= '<span class="agent_name">' . $agent_alias . 
						'</span>';
				$html .= '</div>';
				$html .= '<div class="agent_os">' . ui_print_os_icon ($this->agent["id_os"], false, true, 
					true, false, false, false, false, true) . '</div>';
				$html .= '<div class="agent_list_ips">';
				$html .= $ip . ' -  ' . 
					groups_get_name ($this->agent["id_grupo"], true);
				$html .= '</div>
						<div class="agent_last_contact">';
				$html .= $last_contact;
				$html .= '</div>
						<div class="agent_description">';
				$html .= $description;
				$html .= '</div>';
				
				if ($system->getConfig('metaconsole')) {
					metaconsole_connect(null, 
						$this->agent['id_tmetaconsole_setup']);
				}
				
				$ui->contentGridAddCell($html, 'agent_details');
				
				ob_start();

				// Fixed width non interactive charts
				$status_chart_width = 160;
				$graph_width = 160;
				
				$html = '<div class="agent_graphs">';
				$html .= "<b>" . __('Modules by status') . "</b>";
				$html .= '<div id="status_pie" style="margin: auto; width: ' . $status_chart_width . 'px;">';
				$html .= graph_agent_status ($this->id, $graph_width, 160, true);
				$html .= '</div>';
				$graph_js = ob_get_clean();
				$html = $graph_js . $html;
				
				unset($this->agent['fired_count']);
				
				if ($this->agent['total_count'] > 0) {
					$html .= '<div class="agents_tiny_stats agents_tiny_stats_tactical">' . 
						reporting_tiny_stats($this->agent, true, 'agent', '&nbsp;') . ' </div>';
				}
				
				$html .= '</div>';
				$html .= '<div class="events_bar">';
				$html .= "<b>" . __('Events (24h)') . "</b>";
				$html .= '<div id="events_bar"></div>';
				$html .= '</div>';
				
				$ui->contentGridAddCell($html, 'agent_graphs');
				$ui->contentEndGrid();
				
				if ($system->getConfig('metaconsole'))
					metaconsole_restore_db();
				
				$modules = new Modules();
				
				if ($system->getConfig('metaconsole'))
					$filters = array('id_agent' => $this->agent['id_tagente'], 'all_modules' => true, 'status' => -1);
				else
					$filters = array('id_agent' => $this->id, 'all_modules' => true, 'status' => -1);
				
				$modules->setFilters($filters);
				$modules->disabledColumns(array('agent'));
				$ui->contentBeginCollapsible(__('Modules'));
				$ui->contentCollapsibleAddItem($modules->listModulesHtml(0, true));
				$ui->contentEndCollapsible();
				
				if ($system->getConfig('metaconsole')) {
					metaconsole_connect(null, $this->agent['id_tmetaconsole_setup']);
				}
				
				$alerts = new Alerts();
				
				if ($system->getConfig('metaconsole'))
					$filters = array('id_agent' => $this->agent['id_tagente'], 'all_alerts' => true);
				else
					$filters = array('id_agent' => $this->id, 'all_alerts' => true);
				
				$alerts->setFilters($filters);
				$alerts->disabledColumns(array('agent'));
				$ui->contentBeginCollapsible(__('Alerts'));
				$ui->contentCollapsibleAddItem($alerts->listAlertsHtml(true));
				$ui->contentEndCollapsible();
				
				if ($system->getConfig('metaconsole'))
					metaconsole_restore_db();
				
				$events = new Events();
				$events->addJavascriptDialog();
				
				$options = $events->get_event_dialog_options();
				$ui->addDialog($options);
				
				$options = $events->get_event_dialog_error_options($options);
				$ui->addDialog($options);
				
				$ui->contentAddHtml("<a id='detail_event_dialog_hook' href='#detail_event_dialog' style='display:none;'>detail_event_hook</a>");
				$ui->contentAddHtml("<a id='detail_event_dialog_error_hook' href='#detail_event_dialog_error' style='display:none;'>detail_event_dialog_error_hook</a>");
			
				$ui->contentBeginCollapsible(sprintf(__('Last %s Events'), $system->getPageSize()));
				$tabledata = $events->listEventsHtml(0, true, 'last_agent_events');
				$ui->contentCollapsibleAddItem($tabledata['table']);
				$ui->contentCollapsibleAddItem($events->putEventsTableJS($this->id));
				$ui->contentEndCollapsible();
			}
					
		$ui->contentAddLinkListener('last_agent_events');
		$ui->contentAddLinkListener('list_events');
		$ui->contentAddLinkListener('list_agent_Modules');

		$ui->contentAddHtml("<script type=\"text/javascript\">
			$(document).ready(function() {
				function set_same_heigth() {
					//Set same height to boxes
					var max_height = 0;
					if ($('.agent_details').height() > $('.agent_graphs').height()) {
						max_height = $('.agent_details').height();
						$('.agent_graphs').height(max_height);
					}
					else {
						max_height = $('.agent_graphs').height();
						$('.agent_details').height(max_height);
					}
				}
									
				if ($('.ui-block-a').css('float') != 'none') {
					set_same_heigth();
				}
				
				$('.ui-collapsible').bind('expand', function () {
					refresh_link_listener_last_agent_events();
					refresh_link_listener_list_agent_Modules();
				});
				
				function ajax_load_events_bar() {
					$('#events_bar').html('<div style=\"text-align: center\"> " . __('Loading...') . "<br /><img src=\"images/ajax-loader.gif\" /></div>');
					
					var bar_width = $('.agent_graphs').width() * 0.9;

					postvars = {};
					postvars[\"action\"] = \"ajax\";
					postvars[\"parameter1\"] = \"agent\";
					postvars[\"parameter2\"] = \"render_events_bar\";
					postvars[\"agent_id\"] = \"" . $this->id . "\";
					postvars[\"width\"] = bar_width;
					$.post(\"index.php\",
						postvars,
						function (data) {
							$('#events_bar').html(data);
							if ($('.ui-block-a').css('float') != 'none') {
								set_same_heigth();
							}
						},
						\"html\");
				}
				
				ajax_load_events_bar();
				
				// Detect orientation change to refresh dinamic content
				$(window).on({
					orientationchange: function(e) {
						// Refresh events bar
						ajax_load_events_bar();
						
						// Keep same height on boxes
						if ($('.ui-block-a').css('float') == 'none') {
							$('.agent_graphs').height('auto');
							$('.agent_details').height('auto');
						}
						else {
							set_same_heigth();
						}
					  
					}
				});
										
				if ($('.ui-block-a').css('float') != 'none') {
					set_same_heigth();
				}
			});			
			</script>");
			
		$ui->endContent();
		$ui->showPage();
	}
	
}



?>