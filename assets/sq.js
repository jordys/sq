;

/**
 * sq.js
 *
 * sq.js provides a handfull of javascript utilities for interacting with the sq
 * PHP framework. Provides methods for interacting with view contexts and forms.
 * View contexts can be set up on the backend to enable loading certain pieces
 * of UI without the performance hit of returning the entire page and without
 * the complexity of multiple urls.
 */

sq = function(sq, $) {
	'use strict';
	
	// Private variable to hold callback functions
	var callbacks = {
		'load': {'any': []},
		'save': {'any': []}
	};
	
	
	// sq.slug sub namespace. Facilitates general handling of url slugs. Allows 
	// site js to get and set the current url slug and handles the back button 
	// to redirect to the correct page.
	var slug = function() {
		
		// Handle popstate events by redirecting to the correct page
		window.onpopstate = function(e) {
			if (e.state) {
				window.location = e.state.url;
			}
		}
		
		
		/*** sq.slug public object ***/
		
		return {
			get: function() {
				return window.location.href;
			},
			
			set: function(url) {
				url = url.split('?')[0];
				history.pushState({url: url}, null, url);
			}
		};
	}();
	
	
	// Utility function for ajax calls
	function call(options, data, callback) {
		if (options.context) {
			options.url += '?sqContext=' + options.context;
			
			var $context = $('#sq-context-' + options.context);
			
			if (!$context.children('.is-loading').length) {
				$context.append('<div class="is-loading"></div>');
			}
		}
		
		$.ajax(options.url, {
			method: options.method,
			data: data,
			contentType: false,
			processData: false,
			success: function(data) {
				if (options.context) {
					$context.html(data);
				}
				
				if (options.slug) {
					slug.set(options.url);
				}
				
				if (typeof callback === 'function') {
					callback(data);
				}
			}
		});
	}
	
	// Calls the correct callback functions based on type and context
	function triggerCallbacks(type, context, data) {
		if (callbacks[type][context] !== undefined) {
			$.each(callbacks[type][context], function(index, value) {
				value(data);
			});
		}
		
		$.each(callbacks[type]['any'], function(index, value) {
			value(data);
		});
	}
	
	// Check if the passed in argument is a string or a jQuery object. If it's a
	// string make it a jQuery object.
	function parseElement($element) {
		if (typeof $element === 'string') {
			$element = $($element);
		}
		
		return $element;
	}
	
	
	/*** sq public object ***/
	
	return {
		
		// Data object passed from view
		data: sq.data,
		
		// sq.slug sub namespace
		slug: slug,
		
		// Allows registration of callback functions before they are needed so
		// they don't have to be called explicitly every time an operation is
		// performed. Useful for reinit operations after a load for example.
		register: function(type, context, callback) {
			if (typeof context === 'function') {
				callback = context;
				context = 'any';
			}
			
			if (callbacks[type][context] === undefined) {
				callbacks[type][context] = [];
			}
			
			callbacks[type][context].push(callback);
		},
		
		// Posts a form to the server and places the returned content into the
		// specified view context
		save: function($form, context, options, callback) {
			$form = parseElement($form);
			
			var data;
			try {
				data = new FormData($form[0]);
			} catch (e) {
				data = $form.serialize();
			}
			
			if (typeof options === 'string') {
				options = {url: options};
			} else if (typeof options === 'function') {
				callback = options;
				options = {};
			}
			
			call({
				context: context,
				url: options.url || $form.attr('action'),
				method: options.method || $form.attr('method'),
				slug: ('slug' in options) ? options.slug : true
			}, data, function(data) {
				triggerCallbacks('save', context, data);
				
				if (typeof callback === 'function') {
					callback(data);
				}
			});
		},
		
		// Show or hide a loading indicator on the passed in element
		loading: function($element, show) {
			$element = parseElement($element);
			
			if (show || show === undefined) {
				if (!$element.children('.is-loading').length) {
					$element.append('<div class="is-loading"></div>');
				}
			} else {
				$element.children('.is-loading').remove();
			}
		},
		
		// Load content from a url into a view context
		load: function(context, options, callback) {
			if (typeof options === 'string') {
				options = {url: options};
			}
			
			call({
				context: context,
				url: options.url,
				method: options.method || 'GET',
				slug: ('slug' in options) ? options.slug : true
			}, {}, function(data) {
				triggerCallbacks('load', context, data);
				
				if (typeof callback === 'function') {
					callback(data);
				}
			});
		}
	};
}(sq, jQuery);