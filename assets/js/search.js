window.WPKB_Search = (function($) {
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
	 * The loading indicator for when search request is firing..
	 *
	 * @type {*|HTMLElement}
	 */
	var $loadingIndicator = $("<p><em>Searching..</em>");

	/**
	 * Event hooks
	 */
	$('.wpkb-search-term').keydown( function() {

		window.clearTimeout( timer );

		// save context for later use
		var $context = $(this).parents('.wpkb-search');

		timer = window.setTimeout( function() {
			getSearchResults( $context );
		}, 300 );
	});

	$('.wpkb-search-form').submit( function() {
		var $context = $(this).parents('.wpkb-search');
		getSearchResults( $context, true );
		return false;
	} );

	/**
	 * Get Search Results
	 */
	function getSearchResults( $context, force ) {

		var data = {
			action: 'wpkb_search',
			search: $context.find('.wpkb-search-term').val()
		};
		var encodedSearchQuery = encodeURIComponent(data.search.toLowerCase());

		// Force query?
		force = ( typeof force !== "undefined" && force );
		if( ! force ) {

			// don't query if search term is empty or very short
			if( data.search == '' || data.search.length < 3 ) {
				return;
			}

			// don't query if search term is similar to last search term
			if( data.search.indexOf( lastSearchTerm ) === 0 && data.search.length < ( lastSearchTerm.length + 2 ) && data.search.length > ( lastSearchTerm.length - 2 ) ) {
				//console.log( 'WP Docs: search not firing, term too similar.' );
				return;
			}

			// If result is in cache object, use that
			var cache = lscache.get('wpkb_' + encodedSearchQuery );
			if( typeof( cache ) !== "undefined" && typeof( cache ) === "string" ) {
				$context.find('.wpkb-search-results').html( cache );
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

		// remove placeholder & show loading indicator to user
		$context.find('.wpkb-search-placeholder').remove();
		$context.find('.wpkb-search-results').prepend( $loadingIndicator );

		$.ajax({
			'url': wpkb_search_vars.ajaxurl,
			'data': data,
			'complete': function() {
				busy = false;
			},
			'success': function( response ) {
				if( response.success ) {

					// store response in local cache (valid for 10 minutes)
					lscache.set( 'wpkb_' + encodedSearchQuery, response.data, 10 );

					// show response
					$context.find('.wpkb-search-results').html( response.data );
				} else {
					// something failed
				}
			},
			'dataType': 'json'
		});

		// track event with google analytics
		if( typeof( window.ga ) === "function" ) {
			window.ga('send', 'pageview', window.location.pathname + '?wpkb-search=' + data.search);
		}
	}

	return {}
})(window.jQuery);

/**
 * lscache v1.0.5
 */
!function(a,b){"function"==typeof define&&define.amd?define([],b):"undefined"!=typeof module&&module.exports?module.exports=b():a.lscache=b()}(this,function(){function a(){var a="__lscachetest__",c=a;if(void 0!==m)return m;try{g(a,c),h(a),m=!0}catch(d){m=b(d)?!0:!1}return m}function b(a){return a&&"QUOTA_EXCEEDED_ERR"===a.name||"NS_ERROR_DOM_QUOTA_REACHED"===a.name||"QuotaExceededError"===a.name?!0:!1}function c(){return void 0===n&&(n=null!=window.JSON),n}function d(a){return a+p}function e(){return Math.floor((new Date).getTime()/r)}function f(a){return localStorage.getItem(o+t+a)}function g(a,b){localStorage.removeItem(o+t+a),localStorage.setItem(o+t+a,b)}function h(a){localStorage.removeItem(o+t+a)}function i(a){for(var b=new RegExp("^"+o+t+"(.*)"),c=localStorage.length-1;c>=0;--c){var e=localStorage.key(c);e=e&&e.match(b),e=e&&e[1],e&&e.indexOf(p)<0&&a(e,d(e))}}function j(a){var b=d(a);h(a),h(b)}function k(a){var b=d(a),c=f(b);if(c){var g=parseInt(c,q);if(e()>=g)return h(a),h(b),!0}}function l(a,b){u&&"console"in window&&"function"==typeof window.console.warn&&(window.console.warn("lscache - "+a),b&&window.console.warn("lscache - The error was: "+b.message))}var m,n,o="lscache-",p="-cacheexpiration",q=10,r=6e4,s=Math.floor(864e13/r),t="",u=!1,v={set:function(k,m,n){if(a()){if("string"!=typeof m){if(!c())return;try{m=JSON.stringify(m)}catch(o){return}}try{g(k,m)}catch(o){if(!b(o))return void l("Could not add item with key '"+k+"'",o);var p,r=[];i(function(a,b){var c=f(b);c=c?parseInt(c,q):s,r.push({key:a,size:(f(a)||"").length,expiration:c})}),r.sort(function(a,b){return b.expiration-a.expiration});for(var t=(m||"").length;r.length&&t>0;)p=r.pop(),l("Cache is full, removing item with key '"+k+"'"),j(p.key),t-=p.size;try{g(k,m)}catch(o){return void l("Could not add item with key '"+k+"', perhaps it's too big?",o)}}n?g(d(k),(e()+n).toString(q)):h(d(k))}},get:function(b){if(!a())return null;if(k(b))return null;var d=f(b);if(!d||!c())return d;try{return JSON.parse(d)}catch(e){return d}},remove:function(b){a()&&j(b)},supported:function(){return a()},flush:function(){a()&&i(function(a){j(a)})},flushExpired:function(){a()&&i(function(a){k(a)})},setBucket:function(a){t=a},resetBucket:function(){t=""},enableWarnings:function(a){u=a}};return v});


