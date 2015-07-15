<?php

namespace WPKB\Rating;

class Admin {

	/**
	 * @var Rater
	 */
	protected $rating;

	public function __construct() {
		global $wpkb;
		$this->rating = $wpkb->rating;
	}

	public function add_hooks() {
		add_filter( 'manage_wpkb-article_posts_columns', array( $this, 'column_header' ), 10);
		add_filter( 'manage_edit-wpkb-article_sortable_columns', array( $this, 'sortable_columns' ) );
		add_action( 'manage_wpkb-article_posts_custom_column', array( $this, 'column_content' ), 10, 2);
		add_filter( 'pre_get_posts', array( $this, 'sortable_orderby' ) );
		add_action( 'add_meta_boxes', array( $this, 'add_meta_box' ) );
	}

	/**
	 * Adds a box to the main column on the Post and Page edit screens.
	 */
	public function add_meta_box() {
		add_meta_box(
			'wpkb-ratings',
			__( 'Article Ratings', 'wpkb-ratings' ),
			array( $this, 'show_meta_box' ),
			'wpkb-article'
		);
	}

	/**
	 * @param \WP_Post $post
	 */
	public function show_meta_box( $post ) {

		$ratings = $this->rating->get_post_ratings( $post->ID );

		if( count( $ratings ) === 0) {
			echo 'No ratings for this article.';
			return;
		}

		echo '<style type="text/css" scoped>.wpkb-ratings-table { border-collapse: collapse; } .wpkb-ratings-table th, .wpkb-ratings-table td{ border: 1px solid #eee; padding: 3px 6px; }</style>';
		echo sprintf( '<p>The following %d ratings were left for this article.</p>', count( $ratings ) );
		echo '<table class="wpkb-ratings-table" border="0">';
		echo '<tr><th>Rating</th><th>IP address</th><th>Time</th><th>Message</th></tr>';
		foreach( $ratings as $rating ) {
			printf( '<tr><td>%d</td><td>%s</td><td>%s</td><td>%s</td></tr>', $rating->rating, $rating->ip, date( 'Y-m-d H:i', $rating->timestamp ), $rating->message );
		}
		echo '</table>';
	}

	/**
	 * Add our rating column to the array of sortable columns
	 *
	 * @param $columns
	 *
	 * @return mixed
	 */
	public function sortable_columns( $columns ) {
		$columns['wpkb-rating'] = 'wpkb-rating';
		return $columns;
	}

	/**
	 * Tell WordPress how to order our rating column
	 *
	 * @param $query
	 */
	public function sortable_orderby( $query ) {
		$orderby = $query->get( 'orderby');

		if( 'wpkb-rating' === $orderby ) {
			$query->set( 'meta_key', 'wpkb_rating_perc' );
			$query->set( 'orderby', 'meta_value_num' );
		}
	}

	/**
	 * Add our rating column
	 *
	 * @param $defaults
	 *
	 * @return mixed
	 */
	public function column_header( $defaults ) {
		$defaults['wpkb-rating'] = 'Rating';
		return $defaults;
	}

	/**
	 * Output the rating percentage in the column
	 *
	 * @param $column_name
	 * @param $post_id
	 */
	public function column_content( $column_name, $post_id ) {

		if ($column_name !== 'wpkb-rating') {
			return;
		}

		$rating = $this->rating->get_post_rating_perc( $post_id );
		if( $rating === 0 ) {
			echo '-';
			return;
		}
		$color = $this->percent2Color( $rating, 200 );
		echo sprintf( '<span style="color: #%s">%s%%</span> (%d)', $color, $rating, count( $this->rating->get_post_ratings( $post_id ) ) );
	}

	/**
	 * Returns a HEX color from a percentage (red to green)
	 *
	 * @param        $value
	 * @param int    $brightness
	 * @param int    $max
	 * @param int    $min
	 * @param string $thirdColorHex
	 *
	 * @return string
	 */
	protected function percent2Color($value,$brightness = 255, $max = 100,$min = 0, $thirdColorHex = '00') {
		// Calculate first and second color (Inverse relationship)
		$first = (1-($value/$max))*$brightness;
		$second = ($value/$max)*$brightness;

		// Find the influence of the middle color (yellow if 1st and 2nd are red and green)
		$diff = abs($first-$second);
		$influence = ($brightness-$diff)/2;
		$first = intval($first + $influence);
		$second = intval($second + $influence);

		// Convert to HEX, format and return
		$firstHex = str_pad(dechex($first),2,0,STR_PAD_LEFT);
		$secondHex = str_pad(dechex($second),2,0,STR_PAD_LEFT);

		return $firstHex . $secondHex . $thirdColorHex ;
	}
}