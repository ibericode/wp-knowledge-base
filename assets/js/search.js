window.WPDocs = (function($) {
	'use strict';

	/**
	 * Holds the setTimeout ID to limit the number of search requests fired
	 *
	 * @type {number}
	 */
	var timer = 0;

	/**
	 * If set to true, an AJAX request is busy
	 *
	 * @type {boolean}
	 */
	var busy = false;

	/**
	 * The search term that was last used
	 *
	 * @type {string}
	 */
	var lastSearchTerm = '';


	/**
	 * An object cache of search results.
	 *
	 * @type {{}}
	 */
	var cache = {};

	$('.wpdocs-search-term').keydown( function() {

		window.clearTimeout( timer );

		// save context for later use
		var $context = $(this).parents('.wpdocs-search');

		timer = window.setTimeout( function() {
			getSearchResults( $context );
		}, 300 );
	});

	$('.wpdocs-search-form').submit( function() {
		var $context = $(this).parents('.wpdocs-search');
		getSearchResults( $context, true );
		return false;
	} );

	/**
	 * Get Search Results
	 */
	function getSearchResults( $context, force ) {

		var data = {
			action: 'wpdocs_search',
			search: $context.find('.wpdocs-search-term').val()
		};

		// Force query?
		force = ( typeof force !== "undefined" && force );
		if( ! force ) {

			// If result is in cache object, use that
			if( typeof( cache[ data.search ] ) !== "undefined" ) {
				$context.find('.wpdocs-search-results').html( cache[ data.search ] );
				return;
			}

			// don't query if search term is empty or very short
			if( data.search == '' || data.search.length < 3 ) {
				return;
			}

			// don't query if search term is similar to last search term
			if( data.search.indexOf( lastSearchTerm ) === 0 && data.search.length < ( lastSearchTerm.length + 2 ) && data.search.length > ( lastSearchTerm.length - 2 ) ) {
				//console.log( 'WP Docs: search not firing, term too similar.' );
				return;
			}
		}

		// if we're still running another request, do nothing
		if( busy ) {
			return;
		}

		// store search term to check next request
		lastSearchTerm = data.search;

		// set busy to true so no more shots will be fired
		busy = true;

		$.ajax({
			url: wpdocs_vars.ajaxurl,
			data: data,
			complete: function() {
				busy = false;
			},
			success: function( response ) {
				if( response.success ) {
					// store response in cache object
					cache[data.search] = response.data;

					// show response
					$context.find('.wpdocs-search-results').html( response.data );
				} else {
					// something failed
				}
			},
			dataType: 'json'
		});
	}

	return {}
})(window.jQuery);