/**
 * @fileoverview Contains the class definition for an EventLayerAccordion class.
 */
/**
 * @author Keith Hughitt keith.hughitt@gmail.com
 * @class EventLayerAccordion
 * 
 * syntax: jQuery
 * 
 * @see EventLayer, LayerManager, TileLayerAccordion
 * @requires ui.dynaccordion.js
 */
var EventLayerAccordion = Class.create(Layer, {
	initialize: function (viewport, containerId) {
		this.viewport = viewport;
		this.container = jQuery('#' + containerId);

		//Setup menu UI components
		this._setupUI();

		//Initialize accordion
		this.domNode = jQuery('#EventLayerAccordion-Container');
		this.domNode.dynaccordion();

	},

	/**
	 * @function
	 * @description Adds a new entry to the event layer accordion
	 */
	addLayer: function (layer) {
		// Create accordion entry header
		var title = "VSO/CACTus";
		var visibilityBtn = "<button class='layerManagerBtn visible' id='visibilityBtn-" + layer.id + "' value=true type=button title='toggle layer visibility'></button>";
        var removeBtn = "<button class='layerManagerBtn remove' id='removeBtn-" + layer.id + "' type=button title='remove layer'></button>";
 		var head = "<div class=layer-Head><span class=event-accordion-header-left>" + title + "</span><span class=event-accordion-header-right>" + visibilityBtn + removeBtn + "</span></div>";
		
		// Create accordion entry body
		var body = '<div style="color: white;">CME and AR event data from VSO and CACTus.</div>';
		
		//Add to accordion
		this.domNode.dynaccordion("addSection", {id: layer.id, header: head, cell: body});
		
		// Event-handlers
		this._setupEventHandlers(layer);
	},

	/**
	 * @function _setupUI
	 * This method handles setting up an empty event layer accordion.
	 */ 
	_setupUI: function () {
		// Create a top-level header and an "add layer" button
		var title = jQuery('<span>Events</span>').css({'float': 'left', 'color': 'black', 'font-weight': 'bold'});
		var addLayerBtn = jQuery('<a href=#>[Add Events]</a>').css({'margin-right': '14px', 'color': '#9A9A9A', 'text-decoration': 'none', 'font-style': 'italic', 'cursor': 'default'});
		this.container.append(jQuery('<div></div>').css('text-align', 'right').append(title).append(addLayerBtn));

		var innerContainer = jQuery('<ul id=EventLayerAccordion></ul>');		
		var outerContainer = jQuery('<div id="EventLayerAccordion-Container"></div>').append(innerContainer);  
		this.container.append(outerContainer);
	},
	
	/**
	 * @function
	 * @description Removes an entry from the event layer accordion
	 */
	_setupEventHandlers: function (layer) {
		visibilityBtn = jQuery("#visibilityBtn-" + layer.id);
		removeBtn = jQuery("#removeBtn-" + layer.id);

		// Function for toggling layer visibility		
		var toggleVisibility = function (e) {
			var visible = layer.toggleVisible();
			var icon = (visible ? 'LayerManagerButton_Visibility_Visible.png' : 'LayerManagerButton_Visibility_Hidden.png');
			visibilityBtn.css('background', 'url(images/blackGlass/' + icon + ')' );
			e.stopPropagation();
		};
		
		// Function for handling layer remove button
        var removeLayer = function (e) {
        	var self = e.data;
            self.viewport.controller.layerManager.remove(self, layer);
            self.domNode.dynaccordion('removeSection', {id: layer.id});
            e.stopPropagation();
        };

		visibilityBtn.click(toggleVisibility);
		removeBtn.bind('click', this, removeLayer);
	}
});
