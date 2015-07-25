;

'use strict';

/**
 * sq.js
 *
 * sq.js provides a handfull of javascript utilities for interacting with the sq
 * php framework. Provides methods for interacting with view contexts and forms.
 */

sq = function($, sq) {
	
	// Private variable to hold callback functions
	var callbacks = {'load': {
		'any': []
	}};
	
	// Utility function for ajax calls
	function call(options, callback) {
		var data = {};
		
		if (options.context) {
			data.sqContext = options.context;
			
			var $context = $('#sq-context-' + options.context);
			
			if (!$context.children('.is-loading').length) {
				$context.append('<div class="is-loading"></div>');
			}
		}
		
		$.ajax(options.url, {
			method: options.method,
			data: data,
			sqContext: options.context,
			success: function(data) {
				if (options.context) {
					$context.html(data);
				}
				
				if (options.slug !== false) {
					var url = options.url.split('?')[0];
					history.pushState({url: url}, null, url);
				}
				
				if (typeof callback === 'function') {
					callback();
				}
			},
		});
	}
	
	// Calls the correct callback functions based on type and context
	function triggerCallbacks(type, context) {
		if (callbacks[type][context] !== undefined) {
			$.each(callbacks[type][context], function(index, value) {
				value();
			});
		}
		
		$.each(callbacks[type]['any'], function(index, value) {
			value();
		});
	}
	
	
	/*** Public Object ***/
	
	return {
		
		// Allows registration of callback functions before they are needed so
		// they don't have to be called explicitly every time an operation is
		// performed. Useful for reinit operations after a load for example.
		register: function(type, context, callback) {
			if (typeof context === 'function') {
				callback = context;
				context = 'any';
			}
			
			if (callbacks[type][context] == undefined) {
				callbacks[type][context] = [];
			}
			
			callbacks[type][context].push(callback);
		},
		
		// Load content from a url into a view context. View contexts can be set
		// up on the backend to enable loading certain pieces of UI without the
		// performance hit of returning the entire page and without the 
		// complexity of multiple urls.
		load: function(context, url, callback) {
			call({
				context: context,
				url: url,
				method: 'get',
				slug: true
			}, function() {
				triggerCallbacks('load', context);
				
				if (typeof callback === 'function') {
					callback();
				}
			});
		}
	};
}(jQuery, sq);