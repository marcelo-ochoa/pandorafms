import { AnyObject, Size, Position, WithModuleProps } from "./lib/types";
import {
  parseBoolean,
  sizePropsDecoder,
  parseIntOr,
  notEmptyStringOr,
  itemMetaDecoder,
  t,
  ellipsize
} from "./lib";
import Item, {
  ItemType,
  ItemProps,
  ItemClickEvent,
  ItemRemoveEvent,
  ItemMovedEvent,
  ItemResizedEvent,
  ItemSelectionChangedEvent
} from "./Item";
import StaticGraph, { staticGraphPropsDecoder } from "./items/StaticGraph";
import Icon, { iconPropsDecoder } from "./items/Icon";
import ColorCloud, { colorCloudPropsDecoder } from "./items/ColorCloud";
import Group, { groupPropsDecoder } from "./items/Group";
import Clock, { clockPropsDecoder } from "./items/Clock";
import Box, { boxPropsDecoder } from "./items/Box";
import Line, { linePropsDecoder, LineMovedEvent } from "./items/Line";
import Label, { labelPropsDecoder } from "./items/Label";
import SimpleValue, { simpleValuePropsDecoder } from "./items/SimpleValue";
import EventsHistory, {
  eventsHistoryPropsDecoder
} from "./items/EventsHistory";
import Percentile, { percentilePropsDecoder } from "./items/Percentile";
import TypedEvent, { Disposable, Listener } from "./lib/TypedEvent";
import DonutGraph, { donutGraphPropsDecoder } from "./items/DonutGraph";
import BarsGraph, { barsGraphPropsDecoder } from "./items/BarsGraph";
import ModuleGraph, { moduleGraphPropsDecoder } from "./items/ModuleGraph";
import Service, { servicePropsDecoder } from "./items/Service";
import { FormContainer } from "./Form";

// TODO: Document.
// eslint-disable-next-line @typescript-eslint/explicit-function-return-type
function itemInstanceFrom(data: AnyObject) {
  const type = parseIntOr(data.type, null);
  if (type == null) throw new TypeError("missing item type.");

  const meta = itemMetaDecoder(data);

  switch (type as ItemType) {
    case ItemType.STATIC_GRAPH:
      return new StaticGraph(staticGraphPropsDecoder(data), meta);
    case ItemType.MODULE_GRAPH:
      return new ModuleGraph(moduleGraphPropsDecoder(data), meta);
    case ItemType.SIMPLE_VALUE:
    case ItemType.SIMPLE_VALUE_MAX:
    case ItemType.SIMPLE_VALUE_MIN:
    case ItemType.SIMPLE_VALUE_AVG:
      return new SimpleValue(simpleValuePropsDecoder(data), meta);
    case ItemType.PERCENTILE_BAR:
    case ItemType.PERCENTILE_BUBBLE:
    case ItemType.CIRCULAR_PROGRESS_BAR:
    case ItemType.CIRCULAR_INTERIOR_PROGRESS_BAR:
      return new Percentile(percentilePropsDecoder(data), meta);
    case ItemType.LABEL:
      return new Label(labelPropsDecoder(data), meta);
    case ItemType.ICON:
      return new Icon(iconPropsDecoder(data), meta);
    case ItemType.SERVICE:
      return new Service(servicePropsDecoder(data), meta);
    case ItemType.GROUP_ITEM:
      return new Group(groupPropsDecoder(data), meta);
    case ItemType.BOX_ITEM:
      return new Box(boxPropsDecoder(data), meta);
    case ItemType.LINE_ITEM:
      return new Line(linePropsDecoder(data), meta);
    case ItemType.AUTO_SLA_GRAPH:
      return new EventsHistory(eventsHistoryPropsDecoder(data), meta);
    case ItemType.DONUT_GRAPH:
      return new DonutGraph(donutGraphPropsDecoder(data), meta);
    case ItemType.BARS_GRAPH:
      return new BarsGraph(barsGraphPropsDecoder(data), meta);
    case ItemType.CLOCK:
      return new Clock(clockPropsDecoder(data), meta);
    case ItemType.COLOR_CLOUD:
      return new ColorCloud(colorCloudPropsDecoder(data), meta);
    default:
      throw new TypeError("item not found");
  }
}

// TODO: Document.
// eslint-disable-next-line @typescript-eslint/explicit-function-return-type
function decodeProps(data: AnyObject) {
  const type = parseIntOr(data.type, null);
  if (type == null) throw new TypeError("missing item type.");

  switch (type as ItemType) {
    case ItemType.STATIC_GRAPH:
      return staticGraphPropsDecoder(data);
    case ItemType.MODULE_GRAPH:
      return moduleGraphPropsDecoder(data);
    case ItemType.SIMPLE_VALUE:
    case ItemType.SIMPLE_VALUE_MAX:
    case ItemType.SIMPLE_VALUE_MIN:
    case ItemType.SIMPLE_VALUE_AVG:
      return simpleValuePropsDecoder(data);
    case ItemType.PERCENTILE_BAR:
    case ItemType.PERCENTILE_BUBBLE:
    case ItemType.CIRCULAR_PROGRESS_BAR:
    case ItemType.CIRCULAR_INTERIOR_PROGRESS_BAR:
      return percentilePropsDecoder(data);
    case ItemType.LABEL:
      return labelPropsDecoder(data);
    case ItemType.ICON:
      return iconPropsDecoder(data);
    case ItemType.SERVICE:
      return servicePropsDecoder(data);
    case ItemType.GROUP_ITEM:
      return groupPropsDecoder(data);
    case ItemType.BOX_ITEM:
      return boxPropsDecoder(data);
    case ItemType.LINE_ITEM:
      return linePropsDecoder(data);
    case ItemType.AUTO_SLA_GRAPH:
      return eventsHistoryPropsDecoder(data);
    case ItemType.DONUT_GRAPH:
      return donutGraphPropsDecoder(data);
    case ItemType.BARS_GRAPH:
      return barsGraphPropsDecoder(data);
    case ItemType.CLOCK:
      return clockPropsDecoder(data);
    case ItemType.COLOR_CLOUD:
      return colorCloudPropsDecoder(data);
    default:
      throw new TypeError("decoder not found");
  }
}

// Base properties.
export interface VisualConsoleProps extends Size {
  readonly id: number;
  name: string;
  groupId: number;
  backgroundURL: string | null; // URL?
  backgroundColor: string | null;
  isFavorite: boolean;
  relationLineWidth: number;
}

/**
 * Build a valid typed object from a raw object.
 * This will allow us to ensure the type safety.
 *
 * @param data Raw object.
 * @return An object representing the Visual Console props.
 * @throws Will throw a TypeError if some property
 * is missing from the raw object or have an invalid type.
 */
export function visualConsolePropsDecoder(
  data: AnyObject
): VisualConsoleProps | never {
  // Object destructuring: http://es6-features.org/#ObjectMatchingShorthandNotation
  const {
    id,
    name,
    groupId,
    backgroundURL,
    backgroundColor,
    isFavorite,
    relationLineWidth
  } = data;

  if (id == null || isNaN(parseInt(id))) {
    throw new TypeError("invalid Id.");
  }
  if (typeof name !== "string" || name.length === 0) {
    throw new TypeError("invalid name.");
  }
  if (groupId == null || isNaN(parseInt(groupId))) {
    throw new TypeError("invalid group Id.");
  }

  return {
    id: parseInt(id),
    name,
    groupId: parseInt(groupId),
    backgroundURL: notEmptyStringOr(backgroundURL, null),
    backgroundColor: notEmptyStringOr(backgroundColor, null),
    isFavorite: parseBoolean(isFavorite),
    relationLineWidth: parseIntOr(relationLineWidth, 0),
    ...sizePropsDecoder(data)
  };
}

export default class VisualConsole {
  // Reference to the DOM element which will contain the items.
  private readonly containerRef: HTMLElement;
  // Properties.
  private _props: VisualConsoleProps;
  // Visual Console Item instances by their Id.
  private elementsById: {
    [key: number]: Item<ItemProps>;
  } = {};
  // Visual Console Item Ids.
  private elementIds: ItemProps["id"][] = [];
  // Dictionary which store the created lines.
  private relations: {
    [key: string]: Line;
  } = {};
  // Event manager for click events.
  private readonly clickEventManager = new TypedEvent<ItemClickEvent>();
  // Event manager for double click events.
  private readonly dblClickEventManager = new TypedEvent<ItemClickEvent>();
  // Event manager for move events.
  private readonly movedEventManager = new TypedEvent<ItemMovedEvent>();
  // Event manager for line move events.
  private readonly lineMovedEventManager = new TypedEvent<LineMovedEvent>();
  // Event manager for resize events.
  private readonly resizedEventManager = new TypedEvent<ItemResizedEvent>();
  // Event manager for remove events.
  private readonly selectionChangedEventManager = new TypedEvent<
    ItemSelectionChangedEvent
  >();
  // List of references to clean the event listeners.
  private readonly disposables: Disposable[] = [];

  /**
   * React to a click on an element.
   * @param e Event object.
   */
  private handleElementClick: (e: ItemClickEvent) => void = e => {
    this.clickEventManager.emit(e);
    // console.log(`Clicked element #${e.data.id}`, e);
  };

  /**
   * React to a double click on an element.
   * @param e Event object.
   */
  private handleElementDblClick: (e: ItemClickEvent) => void = e => {
    this.dblClickEventManager.emit(e);
    // console.log(`Double clicked element #${e.data.id}`, e);
  };

  /**
   * React to a movement on an element.
   * @param e Event object.
   */
  private handleElementMovement: (e: ItemMovedEvent) => void = e => {
    // Move their relation lines.
    const itemId = e.item.props.id;
    const relations = this.getItemRelations(itemId);

    relations.forEach(relation => {
      if (relation.parentId === itemId) {
        // Move the line start.
        relation.line.props = {
          ...relation.line.props,
          startPosition: this.getVisualCenter(e.newPosition, e.item)
        };
      } else if (relation.childId === itemId) {
        // Move the line end.
        relation.line.props = {
          ...relation.line.props,
          endPosition: this.getVisualCenter(e.newPosition, e.item)
        };
      }
    });

    // console.log(`Moved element #${e.item.props.id}`, e);
  };

  /**
   * React to a movement finished on an element.
   * @param e Event object.
   */
  private handleElementMovementFinished: (e: ItemMovedEvent) => void = e => {
    this.movedEventManager.emit(e);
    // console.log(`Movement finished for element #${e.item.props.id}`, e);
  };

  /**
   * React to a line movement.
   * @param e Event object.
   */
  private handleLineElementMovementFinished: (
    e: LineMovedEvent
  ) => void = e => {
    this.lineMovedEventManager.emit(e);
    // console.log(`Movement finished for element #${e.item.props.id}`, e);
  };

  /**
   * React to a resizement on an element.
   * @param e Event object.
   */
  private handleElementResizement: (e: ItemResizedEvent) => void = e => {
    // Move their relation lines.
    const item = e.item;
    const props = item.props;
    const itemId = props.id;
    const relations = this.getItemRelations(itemId);

    const position = {
      x: props.x,
      y: props.y
    };

    const meta = this.elementsById[itemId].meta;

    this.elementsById[itemId].meta = {
      ...meta,
      isUpdating: true
    };

    relations.forEach(relation => {
      if (relation.parentId === itemId) {
        // Move the line start.
        relation.line.props = {
          ...relation.line.props,
          startPosition: this.getVisualCenter(position, item)
        };
      } else if (relation.childId === itemId) {
        // Move the line end.
        relation.line.props = {
          ...relation.line.props,
          endPosition: this.getVisualCenter(position, item)
        };
      }
    });

    // console.log(`Resized element #${e.item.props.id}`, e);
  };

  /**
   * React to a finished resizement on an element.
   * @param e Event object.
   */
  private handleElementResizementFinished: (
    e: ItemResizedEvent
  ) => void = e => {
    this.resizedEventManager.emit(e);
    // console.log(`Resize  fonished for element #${e.item.props.id}`, e);
  };

  /**
   * Clear some element references.
   * @param e Event object.
   */
  private handleElementRemove: (e: ItemRemoveEvent) => void = e => {
    // Remove the element from the list and its relations.
    this.elementIds = this.elementIds.filter(id => id !== e.item.props.id);
    delete this.elementsById[e.item.props.id];
    this.clearRelations(e.item.props.id);
  };

  /**
   * React to element selection change
   * @param e Event object.
   */
  private handleElementSelectionChanged: (
    e: ItemSelectionChangedEvent
  ) => void = e => {
    if (this.elements.filter(item => item.meta.isSelected == true).length > 0) {
      e.selected = true;
    } else {
      e.selected = false;
    }
    this.selectionChangedEventManager.emit(e);
  };

  // TODO: Document
  private handleContainerClick: (e: MouseEvent) => void = () => {
    this.unSelectItems();
  };

  public constructor(
    container: HTMLElement,
    props: AnyObject,
    items: AnyObject[]
  ) {
    this.containerRef = container;
    this._props = visualConsolePropsDecoder(props);

    // Force the first render.
    this.render();

    // Sort by id ASC
    items = items.sort(function(a, b) {
      if (a.id == null || b.id == null) return 0;
      else if (a.id > b.id) return 1;
      else return -1;
    });

    // Initialize the items.
    items.forEach(item => this.addElement(item, this));

    // Create lines.
    this.buildRelations();

    this.containerRef.addEventListener("click", this.handleContainerClick);
  }

  /**
   * Public accessor of the `elements` property.
   * @return Properties.
   */
  public get elements(): Item<ItemProps>[] {
    // Ensure the type cause Typescript doesn't know the filter removes null items.
    return this.elementIds
      .map(id => this.elementsById[id])
      .filter(_ => _ != null) as Item<ItemProps>[];
  }

  /**
   * To create a new element add it to the DOM.
   * @param item. Raw representation of the item's data.
   */
  public addElement(item: AnyObject, context: this = this) {
    try {
      const itemInstance = itemInstanceFrom(item);
      // Add the item to the list.
      context.elementsById[itemInstance.props.id] = itemInstance;
      context.elementIds.push(itemInstance.props.id);
      // Item event handlers.
      itemInstance.onRemove(context.handleElementRemove);
      itemInstance.onSelectionChanged(context.handleElementSelectionChanged);

      // TODO:Continue
      itemInstance.onClick(context.handleElementClick);
      itemInstance.onDblClick(context.handleElementDblClick);
      itemInstance.onMoved(context.handleElementMovement);
      itemInstance.onMovementFinished(context.handleElementMovementFinished);
      if (itemInstance instanceof Line) {
        itemInstance.onLineMovementFinished(
          context.handleLineElementMovementFinished
        );
      } else {
        itemInstance.onResized(context.handleElementResizement);
        itemInstance.onResizeFinished(context.handleElementResizementFinished);
      }

      // Add the item to the DOM.
      context.containerRef.append(itemInstance.elementRef);
      return itemInstance;
    } catch (error) {
      console.log("Error creating a new element:", error.message);
    }
    return;
  }

  /**
   * Public setter of the `elements` property.
   * @param items.
   */
  public updateElements(items: AnyObject[]): void {
    // Ensure the type cause Typescript doesn't know the filter removes null items.
    const itemIds = items
      .map(item => item.id || null)
      .filter(id => id != null) as number[];
    // Get the elements we should delete.
    const deletedIds = this.elementIds.filter(id => itemIds.indexOf(id) < 0);
    // Delete the elements.
    deletedIds.forEach(id => {
      if (this.elementsById[id] != null) {
        this.elementsById[id].remove();
        delete this.elementsById[id];
      }
    });
    // Replace the element ids.
    this.elementIds = itemIds;

    // Initialize the items.
    items.forEach(item => {
      if (item.id) {
        if (this.elementsById[item.id] == null) {
          // New item.
          this.addElement(item);
        } else {
          // Update item.
          try {
            this.elementsById[item.id].props = decodeProps(item);
          } catch (error) {
            console.log("Error updating an element:", error.message);
          }
        }
      }
    });

    // Re-build relations.
    this.buildRelations();
  }

  /**
   * Public setter of the `element` property.
   * @param item.
   */
  public updateElement(item: AnyObject): void {
    // Update item.
    try {
      this.elementsById[item.id].props = decodeProps(item);
    } catch (error) {
      console.log("Error updating element:", error.message);
    }

    // Re-build relations.
    this.buildRelations();
  }

  /**
   * Public accessor of the `props` property.
   * @return Properties.
   */
  public get props(): VisualConsoleProps {
    return { ...this._props }; // Return a copy.
  }

  /**
   * Public setter of the `props` property.
   * If the new props are different enough than the
   * stored props, a render would be fired.
   * @param newProps
   */
  public set props(newProps: VisualConsoleProps) {
    const prevProps = this.props;
    // Update the internal props.
    this._props = newProps;

    // From this point, things which rely on this.props can access to the changes.

    // Re-render.
    this.render(prevProps);
  }

  /**
   * Recreate or update the HTMLElement which represents the Visual Console into the DOM.
   * @param prevProps If exists it will be used to only DOM updates instead of a full replace.
   */
  public render(prevProps: VisualConsoleProps | null = null): void {
    if (prevProps) {
      if (prevProps.backgroundURL !== this.props.backgroundURL) {
        this.containerRef.style.backgroundImage =
          this.props.backgroundURL !== null
            ? `url(${this.props.backgroundURL})`
            : null;
      }
      if (prevProps.backgroundColor !== this.props.backgroundColor) {
        this.containerRef.style.backgroundColor = this.props.backgroundColor;
      }
      if (this.sizeChanged(prevProps, this.props)) {
        this.resizeElement(this.props.width, this.props.height);
      }
    } else {
      this.containerRef.style.backgroundImage =
        this.props.backgroundURL !== null
          ? `url(${this.props.backgroundURL})`
          : null;

      this.containerRef.style.backgroundColor = this.props.backgroundColor;
      this.resizeElement(this.props.width, this.props.height);
    }
  }

  /**
   * Compare the previous and the new size and return
   * a boolean value in case the size changed.
   * @param prevSize
   * @param newSize
   * @return Whether the size changed or not.
   */
  public sizeChanged(prevSize: Size, newSize: Size): boolean {
    return (
      prevSize.width !== newSize.width || prevSize.height !== newSize.height
    );
  }

  /**
   * Resize the DOM container.
   * @param width
   * @param height
   */
  public resizeElement(width: number, height: number): void {
    this.containerRef.style.width = `${width}px`;
    this.containerRef.style.height = `${height}px`;
  }

  /**
   * Update the size into the properties and resize the DOM container.
   * @param width
   * @param height
   */
  public resize(width: number, height: number): void {
    this.props = {
      ...this.props, // Object spread: http://es6-features.org/#SpreadOperator
      width,
      height
    };
  }

  /**
   * To remove the event listeners and the elements from the DOM.
   */
  public remove(): void {
    this.disposables.forEach(d => d.dispose()); // Arrow function.
    this.elements.forEach(e => e.remove()); // Arrow function.
    this.elementsById = {};
    this.elementIds = [];
    // Clear relations.
    this.clearRelations();
    // Remove the click event listener.
    this.containerRef.removeEventListener("click", this.handleContainerClick);
    // Clean container.
    this.containerRef.innerHTML = "";
  }

  /**
   * Create line elements which connect the elements with their parents.
   */
  public buildRelations(): void {
    // Clear relations.
    this.clearRelations();
    // Add relations.
    this.elements.forEach(item => {
      if (item.props.parentId !== null) {
        const parent = this.elementsById[item.props.parentId];
        const child = this.elementsById[item.props.id];
        if (parent && child) this.addRelationLine(parent, child);
      }
    });
  }

  /**
   * @param itemId Optional identifier of a parent or child item.
   * Remove the line elements which connect the elements with their parents.
   */
  private clearRelations(itemId?: number): void {
    if (itemId != null) {
      for (let key in this.relations) {
        const ids = key.split("|");
        const parentId = Number.parseInt(ids[0]);
        const childId = Number.parseInt(ids[1]);

        if (itemId === parentId || itemId === childId) {
          this.relations[key].remove();
          delete this.relations[key];
        }
      }
    } else {
      for (let key in this.relations) {
        this.relations[key].remove();
        delete this.relations[key];
      }
    }
  }

  /**
   * Retrieve the line element which represent the relation between items.
   * @param parentId Identifier of the parent item.
   * @param childId Itentifier of the child item.
   * @return The line element or nothing.
   */
  private getRelationLine(parentId: number, childId: number): Line | null {
    const identifier = `${parentId}|${childId}`;
    return this.relations[identifier] || null;
  }

  // TODO: Document.
  private getItemRelations(
    itemId: number
  ): {
    parentId: number;
    childId: number;
    line: Line;
  }[] {
    const itemRelations = [];

    for (let key in this.relations) {
      const ids = key.split("|");
      const parentId = Number.parseInt(ids[0]);
      const childId = Number.parseInt(ids[1]);

      if (itemId === parentId || itemId === childId) {
        itemRelations.push({
          parentId,
          childId,
          line: this.relations[key]
        });
      }
    }

    return itemRelations;
  }

  /**
   * Retrieve the visual center of the item. It's ussually the center of the
   * content, like the label doesn't exist.
   * @param position Initial position.
   * @param element Element we want to use.
   */
  private getVisualCenter(
    position: Position,
    element: Item<ItemProps>
  ): Position {
    let x = position.x + element.elementRef.clientWidth / 2;
    let y = position.y + element.elementRef.clientHeight / 2;
    if (
      typeof element.props.label !== "undefined" ||
      element.props.label !== "" ||
      element.props.label !== null
    ) {
      switch (element.props.labelPosition) {
        case "up":
          y =
            position.y +
            (element.elementRef.clientHeight +
              element.labelElementRef.clientHeight) /
              2;
          break;
        case "down":
          y =
            position.y +
            (element.elementRef.clientHeight -
              element.labelElementRef.clientHeight) /
              2;
          break;
        case "right":
          x =
            position.x +
            (element.elementRef.clientWidth -
              element.labelElementRef.clientWidth) /
              2;
          break;
        case "left":
          x =
            position.x +
            (element.elementRef.clientWidth +
              element.labelElementRef.clientWidth) /
              2;
          break;
      }
    }
    return { x, y };
  }

  /**
   * Add a new line item to represent a relation between the items.
   * @param parent Parent item.
   * @param child Child item.
   * @return Whether the line was added or not.
   */
  private addRelationLine(
    parent: Item<ItemProps>,
    child: Item<ItemProps>
  ): Line {
    const identifier = `${parent.props.id}|${child.props.id}`;
    if (this.relations[identifier] != null) {
      this.relations[identifier].remove();
    }

    // Get the items center.
    const { x: startX, y: startY } = this.getVisualCenter(parent.props, parent);
    const { x: endX, y: endY } = this.getVisualCenter(child.props, child);

    const line = new Line(
      linePropsDecoder({
        id: 0,
        type: ItemType.LINE_ITEM,
        startX,
        startY,
        endX,
        endY,
        width: 0,
        height: 0,
        lineWidth: this.props.relationLineWidth,
        color: "#CCCCCC"
      }),
      itemMetaDecoder({
        receivedAt: new Date()
      })
    );
    // Save a reference to the line item.
    this.relations[identifier] = line;

    // Add the line to the DOM.
    line.elementRef.style.zIndex = "0";
    this.containerRef.append(line.elementRef);

    return line;
  }

  /**
   * Add an event handler to the click of the linked visual console elements.
   * @param listener Function which is going to be executed when a linked console is clicked.
   */
  public onItemClick(listener: Listener<ItemClickEvent>): Disposable {
    /*
     * The '.on' function returns a function which will clean the event
     * listener when executed. We store all the 'dispose' functions to
     * call them when the item should be cleared.
     */
    const disposable = this.clickEventManager.on(listener);
    this.disposables.push(disposable);

    return disposable;
  }

  /**
   * Add an event handler to the double click of the linked visual console elements.
   * @param listener Function which is going to be executed when a linked console is double clicked.
   */
  public onItemDblClick(listener: Listener<ItemClickEvent>): Disposable {
    /*
     * The '.on' function returns a function which will clean the event
     * listener when executed. We store all the 'dispose' functions to
     * call them when the item should be cleared.
     */
    const disposable = this.dblClickEventManager.on(listener);
    this.disposables.push(disposable);

    return disposable;
  }

  /**
   * Add an event handler to the movement of the visual console elements.
   * @param listener Function which is going to be executed when a linked console is moved.
   */
  public onItemMoved(listener: Listener<ItemMovedEvent>): Disposable {
    /*
     * The '.on' function returns a function which will clean the event
     * listener when executed. We store all the 'dispose' functions to
     * call them when the item should be cleared.
     */
    const disposable = this.movedEventManager.on(listener);
    this.disposables.push(disposable);

    return disposable;
  }

  /**
   * Add an event handler to the movement of the visual console line elements.
   * @param listener Function which is going to be executed when a linked console is moved.
   */
  public onLineMoved(listener: Listener<LineMovedEvent>): Disposable {
    /*
     * The '.on' function returns a function which will clean the event
     * listener when executed. We store all the 'dispose' functions to
     * call them when the item should be cleared.
     */
    const disposable = this.lineMovedEventManager.on(listener);
    this.disposables.push(disposable);

    return disposable;
  }

  /**
   * Add an event handler to the resizement of the visual console elements.
   * @param listener Function which is going to be executed when a linked console is moved.
   */
  public onItemResized(listener: Listener<ItemResizedEvent>): Disposable {
    /*
     * The '.on' function returns a function which will clean the event
     * listener when executed. We store all the 'dispose' functions to
     * call them when the item should be cleared.
     */
    const disposable = this.resizedEventManager.on(listener);
    this.disposables.push(disposable);

    return disposable;
  }

  /**
   * Add an event handler to the elements selection change of the visual console .
   * @param listener Function which is going to be executed when a linked console is moved.
   */
  public onItemSelectionChanged(
    listener: Listener<ItemSelectionChangedEvent>
  ): Disposable {
    /*
     * The '.on' function returns a function which will clean the event
     * listener when executed. We store all the 'dispose' functions to
     * call them when the item should be cleared.
     */
    const disposable = this.selectionChangedEventManager.on(listener);
    this.disposables.push(disposable);

    return disposable;
  }

  /**
   * Enable the edition mode.
   */
  public enableEditMode(): void {
    this.elements.forEach(item => {
      item.meta = { ...item.meta, editMode: true };
    });
    this.containerRef.classList.add("is-editing");
  }

  /**
   * Disable the edition mode.
   */
  public disableEditMode(): void {
    this.elements.forEach(item => {
      item.meta = { ...item.meta, editMode: false };
    });
    this.containerRef.classList.remove("is-editing");
  }

  /**
   * Select an item.
   * @param itemId Item Id.
   * @param unique To remove the selection of other items or not.
   */
  public selectItem(itemId: number, unique: boolean = false): void {
    if (unique) {
      this.elementIds.forEach(currentItemId => {
        const meta = this.elementsById[currentItemId].meta;

        if (currentItemId !== itemId && meta.isSelected) {
          this.elementsById[currentItemId].unSelectItem();
        } else if (currentItemId === itemId && !meta.isSelected) {
          this.elementsById[currentItemId].selectItem();
        }
      });
    } else if (this.elementsById[itemId]) {
      this.elementsById[itemId].selectItem();
    }
  }

  /**
   * Unselect an item.
   * @param itemId Item Id.
   */
  public unSelectItem(itemId: number): void {
    if (this.elementsById[itemId]) {
      const meta = this.elementsById[itemId].meta;

      if (meta.isSelected) {
        this.elementsById[itemId].unSelectItem();
      }
    }
  }

  /**
   * Unselect all items.
   */
  public unSelectItems(): void {
    this.elementIds.forEach(itemId => {
      if (this.elementsById[itemId]) {
        this.elementsById[itemId].unSelectItem();
      }
    });
  }

  // TODO: Document.
  public static items = {
    [ItemType.STATIC_GRAPH]: StaticGraph,
    [ItemType.MODULE_GRAPH]: ModuleGraph,
    [ItemType.SIMPLE_VALUE]: SimpleValue,
    [ItemType.SIMPLE_VALUE_MAX]: SimpleValue,
    [ItemType.SIMPLE_VALUE_MIN]: SimpleValue,
    [ItemType.SIMPLE_VALUE_AVG]: SimpleValue,
    [ItemType.PERCENTILE_BAR]: Percentile,
    [ItemType.PERCENTILE_BUBBLE]: Percentile,
    [ItemType.CIRCULAR_PROGRESS_BAR]: Percentile,
    [ItemType.CIRCULAR_INTERIOR_PROGRESS_BAR]: Percentile,
    [ItemType.LABEL]: Label,
    [ItemType.ICON]: Icon,
    [ItemType.SERVICE]: Service,
    [ItemType.GROUP_ITEM]: Group,
    [ItemType.BOX_ITEM]: Box,
    [ItemType.LINE_ITEM]: Line,
    [ItemType.AUTO_SLA_GRAPH]: EventsHistory,
    [ItemType.DONUT_GRAPH]: DonutGraph,
    [ItemType.BARS_GRAPH]: BarsGraph,
    [ItemType.CLOCK]: Clock,
    [ItemType.COLOR_CLOUD]: ColorCloud
  };

  /**
   * Relying type item and srcimg and agent and module
   * name convert name item representative.
   *
   * @param item Instance item from extract name.
   *
   * @return Name item.
   */
  public static itemDescriptiveName(item: Item<ItemProps>): string {
    let text: string;
    switch (item.props.type) {
      case ItemType.STATIC_GRAPH:
        text = `${t("Static graph")} - ${(item as StaticGraph).props.imageSrc}`;
        break;
      case ItemType.MODULE_GRAPH:
        text = t("Module graph");
        break;
      case ItemType.CLOCK:
        text = t("Clock");
        break;
      case ItemType.BARS_GRAPH:
        text = t("Bars graph");
        break;
      case ItemType.AUTO_SLA_GRAPH:
        text = t("Event history graph");
        break;
      case ItemType.PERCENTILE_BAR:
        text = t("Percentile bar");
        break;
      case ItemType.CIRCULAR_PROGRESS_BAR:
        text = t("Circular progress bar");
        break;
      case ItemType.CIRCULAR_INTERIOR_PROGRESS_BAR:
        text = t("Circular progress bar (interior)");
        break;
      case ItemType.SIMPLE_VALUE:
        text = t("Simple Value");
        break;
      case ItemType.LABEL:
        text = t("Label");
        break;
      case ItemType.GROUP_ITEM:
        text = t("Group");
        break;
      case ItemType.COLOR_CLOUD:
        text = t("Color cloud");
        break;
      case ItemType.ICON:
        text = `${t("Icon")} - ${(item as Icon).props.imageSrc}`;
        break;
      default:
        text = t("Item");
        break;
    }

    const linkedAgentAndModuleProps = item.props as Partial<WithModuleProps>;
    if (
      linkedAgentAndModuleProps.agentAlias != null &&
      linkedAgentAndModuleProps.moduleName != null
    ) {
      text += ` (${ellipsize(
        linkedAgentAndModuleProps.agentAlias,
        18
      )} - ${ellipsize(linkedAgentAndModuleProps.moduleName, 25)})`;
    } else if (linkedAgentAndModuleProps.agentAlias != null) {
      text += ` (${ellipsize(linkedAgentAndModuleProps.agentAlias, 25)})`;
    }

    return text;
  }
}
