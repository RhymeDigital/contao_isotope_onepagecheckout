
/**
 * Rhyme Contao Isotope One Page Checkout Bundle
 * Copyright (c) 2021 Rhyme.Digital
 * @license GPL-3.0+
 */

//Namespace
var Rhyme = window.Rhyme || {};

//Encapsulate
(function ($) {

    let self;

    Rhyme.OnePageCheckout = {

        //Properties
        wrapper:        null,
        form:           null,
        stepsPanel:     null,
        actionPanel:    null,
        summaryPanel:   null,
        messagesPanel:  null,
        currentState:   '',
        baseURL:        '',
        headScripts:    [],
        bodyScriptsWrapper:    null,

        /**
         * Register the service
         */
        init: function(id, options){
            self = this;
            self.options = options || {};
            self.wrapper = jQuery('#'+id);
            self.stepsPanel = self.wrapper.find('.stepsPanel');
            self.actionPanel = self.wrapper.find('.actionPanel');
            self.summaryPanel = self.wrapper.find('.summaryPanel');
            self.messagesPanel = self.wrapper.find('.messagesPanel');
            self.baseURL = window.location.origin;
            self.bodyScriptsWrapper = jQuery('<div id="rhyme_scripts"></div>');
            jQuery('body').append(self.bodyScriptsWrapper);

            let initialHeadScripts = jQuery('head script');
            initialHeadScripts.each(function(i, el){
                let src = jQuery(el).attr('src');
                let arrSrc = src.split('?');
                self.headScripts.push(arrSrc[0]);
            });

            self.toggleModalSpinner('on', self.wrapper);
            self.load();
        },

        /**
         * Initial load
         */
        load: function() {
            jQuery.ajax(document.location.href)
                .done( function(data, status, jqXHR){
                    self.updatePanels(data);
                }).fail(function( jqXHR, status ) {
                let redirect = jqXHR.getResponseHeader('x-ajax-location');
                if(redirect) {
                    window.location.replace(redirect);
                }
                //TODO - handle a real error and display message to user
            });
        },

        /**
         * Update all panels
         * @param data
         */
        updatePanels: function(data) {
            let response = JSON.parse(data);

            let scriptsToLoad = [];

            //Add the head scripts first, if any
            let newHeadScripts = response.components.head;
            jQuery.each(newHeadScripts, function(i, str){
                let addScript = true;
                jQuery.each(self.headScripts, function(i, el){
                    if(el.includes(str)) {
                        addScript = false;
                    }
                });
                if(addScript) {
                    let s = jQuery(str);
                    scriptsToLoad.push(s.attr('src'));
                    self.headScripts.push(str);
                }
            });

            //Add the head javascripts, if any
            let newJavaScripts = response.components.scripts;
            jQuery.each(newJavaScripts, function(i, str){
                str = str.replace('web/', ''); //Strip web/ from the original script
                let baseStr =  str.split('|')[0];
                let addScript = true;
                jQuery.each(self.headScripts, function(i, el){
                    if(el.includes(baseStr)) {
                        addScript = false;
                    }
                });
                if(addScript) {
                    scriptsToLoad.push(baseStr);
                    self.headScripts.push(baseStr);
                }
            });

            //Update everything
            self.updateWindowHistory(response.step);
            self.updatePanel(self.stepsPanel, response.components.steps);
            self.updatePanel(self.actionPanel, response.components.actions, true);
            self.updatePanel(self.summaryPanel, response.components.summary);
            self.updatePanel(self.messagesPanel, response.components.messages);

            //Clear previous body scripts (note need to go backwards)
            self.bodyScriptsWrapper.empty();

            //Load everything and wait
            self.loadScripts(scriptsToLoad, scriptsToLoad.length, loadTheRest);

            //TODO - separate into callable function?
            function loadTheRest() {

                //Add the body scripts and panel content after everything else has loaded, if any
                let newBodyScripts = response.components.body;
                jQuery.each(newBodyScripts, function (i, str) {
                    try {
                        let newBody = jQuery(str);
                        self.bodyScriptsWrapper.append(newBody);
                    } catch (e) {}

                });

                //Trigger document load function for scripts that need it
                if (self.isIE()) {
                    let evt = window.document.createEvent('Event');
                    evt.initEvent('DOMContentLoaded', false, false);
                    window.document.dispatchEvent(evt);
                } else {
                    window.document.dispatchEvent(new Event('DOMContentLoaded', {
                        bubbles: true,
                        cancelable: true
                    }));
                }

                self.toggleModalSpinner('off', self.wrapper);
            }
        },

        /**
         * Update the UI and optionally eval the scripts that come through
         * @param panel
         * @param newHtml
         * @param evalScripts
         */
        updatePanel: function (panel, newHtml, evalScripts) {
            if (panel.length > 0) {
                let keepScripts = (evalScripts === true);
                let cleanedEls = jQuery.parseHTML(self.stripComments(newHtml), null, keepScripts);
                panel.html('').append(cleanedEls);
            }
            //Add special handling for action panel
            if (panel === self.actionPanel) { self.handleFormSubmits() }
        },

        /**
         * Handle the form submits within the action panel without a page reload
         */
        handleFormSubmits() {
            self.form = self.actionPanel.find('form');
            //Only handle form submits that post back to the current URL (or no action)
            let action = self.form.attr('action');
            if(self.form && ( action && action.indexOf("http://") === -1 && action.indexOf("https://") === -1 )) {
                self.form.on('submit', function(e){
                    e.preventDefault();
                    self.toggleModalSpinner('on', self.wrapper);
                    $.ajax({
                        type: self.form.attr('method'),
                        url: self.currentState,
                        data: self.form.serialize(), // serializes the form's elements.
                        success: function(data) {
                            self.updatePanels(data);
                        }
                    });
                });
                //Also handle previous button
                let prevButton = self.form.find('input.button.previous');
                if(prevButton) {
                    prevButton.off('click touch').on('click touch', function (e){
                        e.preventDefault();
                        self.toggleModalSpinner('on', self.wrapper);
                        //Send the value back
                        $.ajax({
                            type: self.form.attr('method'),
                            url: self.currentState,
                            data: {previousStep: true},
                            success: function(data) {
                                self.updatePanels(data);
                            }
                        });
                    });
                }
            }
        },

        /**
         * Load scripts and trigger document loaded event
         * @param urls
         * @param length
         * @param callback
         */
        loadScripts: function (urls, length, callback){
            if(length > 0){
                script = document.createElement("script");
                script.src = urls[length-1];
                console.log();
                script.onload = function() {
                    //console.log('%c Script: ' + urls[length-1] + ' loaded!', 'color: #4CAF50');
                    self.loadScripts(urls, length-1, callback);
                };
                document.getElementsByTagName("head")[0].appendChild(script);
            }
            else{
                if(callback){
                    callback();
                }
            }
        },

        /**
         * Update the window history
         * @param step
         */
        updateWindowHistory: function(step) {
            self.currentState = step;
            window.history.pushState(null, document.title, self.baseURL + '/' + self.currentState);
        },

        /**
         * Turn a spinner on/off inside a designated element
         * @param state
         * @param el
         */
        toggleModalSpinner: function (state, el) {
            let container = self.getJqEl(el);
            let spinner = jQuery('#spinner');
            if(spinner.length > 0 && state==='off') {
                spinner.remove();
            } else {
                if (!spinner.length) {
                    spinner = jQuery('<div id="spinner"><div class="spinnee"></div></div>');
                    container.append(spinner);
                }
            }
        },

        /**
         * Make sure the element is a jQuery element
         * @param el
         * @returns {*}
         */
        getJqEl(el) {
            return el instanceof jQuery ? el : jQuery(el);
        },

        /**
         * Strip comments
         * @param str
         * @returns {*}
         */
        stripComments: function (str) {
            return str.replace(/<\!--.*?-->/g, ""); //Remove comments
        },

        /**
         * Check for IE
         * @returns {boolean}
         */
        isIE: function() {
            let ua = window.navigator.userAgent;
            let msie = ua.indexOf("MSIE ");
            return msie !== -1 || !!navigator.userAgent.match(/Trident.*rv\:11\./);
        }

    }

})(jQuery);