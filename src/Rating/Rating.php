<?php

namespace WPKB\Rating;

class Rating {

	public function __construct() {

	}

	public function add_hooks() {
		add_filter( 'the_content', array( $this, 'add_voting_options' ) );
		add_action( 'init', array( $this, 'listen' ) );
	}

	/**
	 * @param $post_id
	 *
	 * @return int
	 */
	public function get_post_rating( $post_id ) {
		return absint( get_post_meta( $post_id, 'wpkb_rating', true ) );
	}

	/**
	 * @param $post_id
	 *
	 * @return int
	 */
	public function get_post_rating_count( $post_id ) {
		return absint( get_post_meta( $post_id, 'wpkb_rating_count', true ) );
	}

	/**
	 * @param $post_id
	 *
	 * @return int
	 */
	public function get_post_rating_perc( $post_id ) {
		return absint( get_post_meta( $post_id, 'wpkb_rating_perc', true ) );
	}

	/**
	 * @return int
	 */
	public function calculate_post_rating_percentage( $rating, $count ) {

		if( $count < 1 ) {
			return 0;
		}

		return round( $rating / $count * 20 );
	}

	/**
	 * @return bool
	 */
	public function listen() {

		if( ! isset( $_GET['wpkb_action'] ) || $_GET['wpkb_action'] !== 'rate' ) {
			return false;
		}

		$rating = ( isset( $_GET['rating'] ) ) ? absint( $_GET['rating'] ) : 0;
		$post_id = ( isset( $_GET['id'] ) ) ? absint( $_GET['id'] ) : 0;

		// rating must be given, post id must be given, rating must be between 1 and 5
		if( ! $rating || ! $post_id || $rating < 1 || $rating > 5) {
			return false;
		}

		$post_rating = $this->get_post_rating( $post_id );
		$post_rating_count = $this->get_post_rating_count( $post_id );

		// increase rating & rating count (number of rates)
		$post_rating = $post_rating + $rating;
		$post_rating_count++;
		$post_rating_perc = $this->calculate_post_rating_percentage( $post_rating, $post_rating_count );

		update_post_meta( $post_id, 'wpkb_rating', $post_rating );
		update_post_meta( $post_id, 'wpkb_rating_count', $post_rating_count );
		update_post_meta( $post_id, 'wpkb_rating_perc', $post_rating_perc );

		// clean output buffer so we can redirect
		if( ob_get_level() > 0 ) {
			ob_clean();
		}

		// respond
		if( defined( 'DOING_AJAX' ) && DOING_AJAX ) {

		} else {
			wp_safe_redirect( remove_query_arg( array( 'wpkb_action', 'id', 'rating' ) ) );
			exit;
		}

		return true;
	}

	/**
	 * @param $content
	 *
	 * @return string
	 */
	public function add_voting_options( $content ) {

		if( ! is_singular( 'wpkb-article') ) {
			return $content;
		}

		$link = add_query_arg( array(
				'wpkb_action' => 'rate',
				'id' => get_the_ID(),
			)
		);

		$html = '<p class="wpkb-rating">' . sprintf( 'Was this article helpful? <a href="%s" class="wpkb-rating-option wpkb-rating-5">Yes</a> <a href="%s" class="wpkb-rating-option wpkb-rating-1">No</a>', $link . '&rating=5', $link . '&rating=1' ) . '</p>';
		return $content . PHP_EOL . $html;
	}
}