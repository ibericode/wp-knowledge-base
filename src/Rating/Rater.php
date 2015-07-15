<?php

namespace WPKB\Rating;

class Rater {

	public function __construct() {

	}

	public function add_hooks() {
		add_filter( 'the_content', array( $this, 'add_voting_options' ) );
		add_action( 'init', array( $this, 'listen' ) );
	}

	/**
	 * @param $post_id
	 *
	 * @return Collection
	 */
	public function get_post_ratings( $post_id ) {
		$ratings = (array) get_post_meta( $post_id, 'wpkb_ratings', true );
		return Collection::fromArray( $ratings );
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

		$rating_number = ( isset( $_GET['rating'] ) ) ? absint( $_GET['rating'] ) : 0;
		$post_id = ( isset( $_GET['id'] ) ) ? absint( $_GET['id'] ) : 0;
		$message = ( isset( $_REQUEST['message'] ) ) ? nl2br( sanitize_text_field( substr( $_REQUEST['message'], 0, 255 ) ) ) : '';

		// rating must be given, post id must be given, rating must be between 1 and 5
		if( ! $rating_number || ! $post_id || $rating_number < 1 || $rating_number > 5) {
			return false;
		}

		$rating = new Rating( $rating_number, $message );
		$ratings = $this->get_post_ratings( $post_id );

		// add to array
		$ratings->add( $rating );

		// calculate new percentage
		$percentage = $ratings->average();

		update_post_meta( $post_id, 'wpkb_ratings', $ratings->toArray() );
		update_post_meta( $post_id, 'wpkb_rating_perc', $percentage );

		// clean output buffer so we can redirect
		if( ob_get_level() > 0 ) {
			ob_clean();
		}

		// respond
		if( defined( 'DOING_AJAX' ) && DOING_AJAX ) {

		} else {

			if( ! isset( $_REQUEST['message'] ) && $rating_number <= 2 ) {
				// ask for further feedback
				wp_die( $this->get_feedback_form(), 'Thanks for rating! - ' . get_bloginfo( 'name' ), 200 );
			}

			$url = remove_query_arg( array( 'wpkb_action', 'id', 'rating' ) );
			$url = add_query_arg( array( 'wpkb-rated' => 1 ), $url );
			wp_safe_redirect( $url );
			exit;
		}

		return true;
	}

	/**
	 * @return string
	 */
	protected function get_feedback_form() {
		ob_start();

		?>
		<form method="POST">
			<h3>What should we do to improve this article?</h3>
			<p><label for="message">Please explain in short why you did not find this article helpful. We would like to improve it based on your feedback!</label></p>
			<p><textarea id="message" rows="10" name="message" maxlength="255" style="width: 100%;"></textarea></p>
			<p><input type="submit" class="button" value="Submit"></p>
		</form>
		<?php
		return ob_get_clean();
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

		$html = '';

		if( isset( $_GET['wpkb-rated'] ) ) {
			$text = __( 'Thank you for your feedback!', 'wp-knowledge-base' );
			$html .= '<div class="wpkb-alert info">'. $text .'</div>';
		}

		$link = add_query_arg( array(
				'wpkb_action' => 'rate',
				'id' => get_the_ID(),
			)
		);

		$html .= '<p class="wpkb-rating">' . sprintf( 'Was this article helpful? <a href="%s" class="wpkb-rating-option wpkb-rating-5">Yes</a> &middot; <a href="%s" class="wpkb-rating-option wpkb-rating-1">No</a>', $link . '&rating=5', $link . '&rating=1' ) . '</p>';
		return $content . PHP_EOL . $html;
	}
}