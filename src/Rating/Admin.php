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

		// run upgrade routine?
		$db_version = (int) get_option( 'wpkb_version', 0 );
		if( version_compare( $db_version, WPKB_VERSION, '<' ) ) {
			$routine = new UpgradeRoutine( $db_version, WPKB_VERSION, $this->rating );
			$routine->run();
		}
	}

	public function add_hooks() {
		add_filter( 'manage_wpkb-article_posts_columns', array( $this, 'column_header' ), 10);
		add_filter( 'manage_edit-wpkb-article_sortable_columns', array( $this, 'sortable_columns' ) );
		add_action( 'manage_wpkb-article_posts_custom_column', array( $this, 'column_content' ), 10, 2);
		add_filter( 'pre_get_posts', array( $this, 'sortable_orderby' ) );
		add_action( 'add_meta_boxes', array( $this, 'add_meta_box' ) );
		add_action( 'admin_init', array( $this, 'listen' ) );
		add_action( 'wp_dashboard_setup', array( $this, 'register_dashboard_widget' ) );

		// hide ratings by default
		add_filter( 'pre_get_comments', function( $query ) {
			if( $query->query_vars['type'] === '_wpkb_rating' ) {
				return $query;
			}

			// hide by default
			$query->query_vars['type__not_in'][] = '_wpkb_rating';
			return $query;
		});
	}

	/**
	 * Registers the dashboard widget
	 */
	public function register_dashboard_widget() {

		// only show to users who can moderate comments (ie: ratings)
		if( ! current_user_can( 'moderate_comments' ) ) {
			return false;
		}

		wp_add_dashboard_widget(
			'wpkb_recent_ratings',         // Widget slug.
			'Recent KB Ratings',         // Title.
			array( $this, 'dashboard_widget' ) // Display function.
		);

		return true;
	}

	/**
	 * Dashboard widget showing the 5 most recent ratings
	 */
	public function dashboard_widget() {
		$ratings = $this->rating->get_ratings( array( 'number' => 10 ) );

		if( empty( $ratings ) ) {
			echo '<p>No ratings.</p>';
			return;
		}


		echo '<style type="text/css" scoped>.wpkb-ratings-table { border-collapse: collapse; } .wpkb-ratings-table th, .wpkb-ratings-table td{ text-align: left; border: 1px solid #eee; padding: 3px 6px; }</style>';
		echo '<table class="wpkb-ratings-table" border="0">';
		echo '<tr><th>Page</th><th>Rating</th><th>Time</th><th>Message</th></tr>';
		foreach( $ratings as $rating ) {
			printf( '<tr><td><a href="%s">%s</a></td><td>%d</td><td><span title="%s">%s ago</span></td><td>%s</td></tr>', get_edit_post_link( $rating->post_ID ), get_the_title( $rating->post_ID ), $rating->rating, '', human_time_diff( strtotime( $rating->comment->comment_date_gmt ) ), substr( $rating->message, 0, 20 ) . ( ( strlen( $rating->message ) > 20 ) ? '..' : '' ) );
		}
		echo '</table>';
	}

	/**
	 * @return bool
	 */
	public function listen() {

		// make sure user is authorized to do the stuff we're about to do
		if( ! current_user_can( 'delete_pages' ) ) {
			return false;
		}

		if( empty( $_REQUEST['wpkb_action'] ) ) {
			return false;
		}

		$action = $_REQUEST['wpkb_action'];

		if( $action === 'delete_post_ratings' ) {
			$post_id = (int) $_REQUEST['post_id'];
			$this->rating->delete_post_ratings( $post_id );
		}

		wp_safe_redirect( remove_query_arg( 'wpkb_action' ) );

		return true;
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
			echo '<p>No ratings for this article.</p>';
			return;
		}

		echo '<style type="text/css" scoped>.wpkb-ratings-table { border-collapse: collapse; } .wpkb-ratings-table th, .wpkb-ratings-table td{ border: 1px solid #eee; padding: 3px 6px; }</style>';

		echo sprintf( '<p>The following %d ratings were left for this article.</p>', count( $ratings ) );
		echo '<table class="wpkb-ratings-table" border="0">';
		echo '<tr><th>Rating</th><th>IP address</th><th>Time</th><th>Message</th></tr>';
		foreach( $ratings as $rating ) {
			printf( '<tr><td>%d</td><td>%s</td><td><span title="%s">%s ago</span></td><td>%s</td></tr>', $rating->rating, $rating->author_IP, '', human_time_diff( strtotime( $rating->comment->comment_date_gmt ) ) , $rating->message );
		}
		echo '</table>';

		// reset form
		$delete_link = add_query_arg( array(
				'wpkb_action' => 'delete_post_ratings',
				'post_id' => get_the_ID()
			)
		);
		echo '<p>Use the following button to reset all ratings for this article.</p>';
		echo '<p><a class="button" onclick="return confirm(\'Are you sure you want to delete all ratings for this article?\');" href="'. $delete_link .'">Reset Ratings</a></p>';
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


		$rating = $this->rating->get_post_average( $post_id );
		if( $rating === 0 ) {
			echo '-';
			return;
		}
		$color = $this->percent2Color( $rating, 200 );
		echo sprintf( '<span style="color: #%s">%s%%</span> (%d)', $color, $rating, $this->rating->get_number_of_ratings( $post_id ) );
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