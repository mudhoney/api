/**
 * @author <a href="mailto:keith.hughitt@nasa.gov">Keith Hughitt</a> 
 * @fileOverview This class acts as a simple wrapper around the qTip library and helps
 * to normalize behavior across the 1.0 and 1.1 series.
 */
"use strict";
/*global Class, $, window */
var TooltipHelper = Class.extend(
    /** @lends TooltipHelper.prototype */
    {
    /**
     * @description Creates a new TooltipHelper. 
     * @param {Boolean} Enable legacy support
     * @constructs 
     */ 
    init: function (legacyMode) {
        if (legacyMode === undefined)
            this.legacyMode = false;
        else
            this.legacyMode = legacyMode;
        
        if (this.legacyMode) {
            loadCSS("styles/tooltips-LEGACY.css");
        }           
        else {
            loadCSS("lib/jquery/jquery-qtip-nightly/jquery.qtip.css");
            loadCSS("styles/tooltips.css");
        } 
    },
    
    /**
     * @description Creates a simple informative tooltip
     */    
    createTooltip: function (selector, text) {
        if (this.legacyMode)
            this._createLegacyTooltip(selector, text);
        else
            this._createTooltip(selector, text);
    },
    
    /**
     * @description Creates a more complex dialog used for EvenMarker meta information
     */
    createDialog: function (container, title, text) {
        if (this.legacyMode)
            this._createLegacyDialog(container, title, text);
        else
            this._createDialog(container, title, text);
    },
    
    /**
     * @description qTip 1.1x simple tooltips
     * @param {Object} selector
     * @param {Object} text
     */
    _createTooltip: function (selector, text) {
        
    },
    
    /**
     * @description qTip 1.1x dialogs
     * @param {Object} container
     * @param {Object} title
     * @param {Object} text
     */
    _createDialog: function (container, title, text) {
        container.qtip({
            position: {
                container: container,
                corner: {
                    target: 'bottomRight',
                    tooltip: 'topLeft'
                }
            },
            show: 'click',
            hide: 'click',                        
            content: {
                "title": {
                    "text": title,
                    "button": false
                },
                "text" : text
            },
            style: "qtip-helioviewer"
        });
    },
    
    /**
     * @description Create a legacy (qTip 1.0) tooltip
     * @param {Object} selector
     * @param {Object} text
     */
    _createLegacyTooltip: function (selector, text) {
        
    },

    /**
     * @description Create a legacy (qTip 1.0) dialog
     * @param {Object} selector
     * @param {Object} text
     */
    _createLegacyDialog: function (container, title, text) {
        
    }
});