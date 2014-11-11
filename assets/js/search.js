window.WPDocs = (function($) {

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
		getSearchResults( $context );
		return false;
	} );

	/**
	 * Get Search Results
	 */
	function getSearchResults( $context, force ) {

		// if we're still running another request, do nothing
		if( busy ) {
			return;
		}

		var force = ( !! ( typeof force === "undefined" ) );

		var data = {
			action: 'wpdocs_search',
			search: $context.find('.wpdocs-search-term').val()
		};

		if( ! force ) {
			// don't query if search term is empty
			if( data.search == '' || data.search.length < 3 ) {
				return;
			}

			// don't query if search term is similar to last search term
			if( data.search.indexOf( lastSearchTerm ) === 0 && data.search.length < ( lastSearchTerm.length + 2 ) && data.search.length > ( lastSearchTerm.length - 2 ) ) {
				//console.log( 'WP Docs: search not firing, term too similar.' );
				return;
			}
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