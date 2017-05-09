<?php

namespace WPKB\Rating;

use WP_User;

class Rater {

	public function __construct() {}

	/**
	 * Add hooks
	 */
	public function add_hooks() {
		add_filter( 'the_content', array( $this, 'add_voting_options' ) );

		add_action( 'wp_head', array( $this, 'print_styles' ) );
		add_action( 'wp_footer', array( $this, 'print_scripts' ) );
		add_action( 'init', array( $this, 'listen' ) );
	}

	/**
	 * Create Rating from the current request
	 *
	 * @return Rating
	 */
	public function create_from_request() {

		$rating_number = ( isset( $_POST['rating'] ) ) ? absint( $_POST['rating'] ) : 0;
		$post_id = ( isset( $_POST['id'] ) ) ? absint( $_POST['id'] ) : 0;
		$message = ( isset( $_POST['message'] ) ) ? nl2br( sanitize_text_field( substr( $_POST['message'], 0, 255 ) ) ) : '';

		// rating must be given, post id must be given, rating must be between 1 and 5
		if( ! $rating_number || ! $post_id || $rating_number < 1 || $rating_number > 5) {
			return false;
		}

		$args = array(
			'message' => $message,
			'author_IP' => $this->get_client_ip(),
			'author_agent' => ( isset( $_SERVER['HTTP_USER_AGENT'] ) ) ? $_SERVER['HTTP_USER_AGENT'] : '',
		);

		$user = wp_get_current_user();
		if( $user instanceof WP_User ) {
			$args['author_name'] = $user->display_name;
			$args['author_user_ID'] = $user->ID;
			$args['author_email'] = $user->user_email;
		}

		$rating = new Rating( $post_id, $rating_number, $args );

		return $rating;
	}

	/**
	 * @param Rating $rating
	 *
	 * @return bool
	 */
	public function save_rating( Rating $rating ) {
		// save rating
		$id = wp_insert_comment( $rating->to_comment() );

		if( $id ) {
			$rating->comment_ID = $id;
			add_comment_meta( $id, '_wpkb_rating', $rating->rating, true );
			return true;
		}

		return false;
	}

	/**
	 * @param $post_id
	 *
	 * @return false|int
	 */
	public function delete_post_ratings( $post_ID ) {
		global $wpdb;
		return $wpdb->delete( $wpdb->comments, array( 'comment_post_ID' => $post_ID, 'comment_type' => '_wpkb_rating' ), array( '%d', '%s' ) );
	}

	/**
	 * @return int
	 */
	public function get_number_of_ratings( $post_ID ) {
		global $wpdb;
		return $wpdb->get_var( $wpdb->prepare( "SELECT COUNT(*) FROM $wpdb->comments WHERE comment_post_ID = %d AND comment_type = %s", $post_ID, '_wpkb_rating' ) );
	}

	/**
	 * @param $post_ID
	 * @return int
	 */
	public function get_post_average( $post_ID ) {
		global $wpdb;

		$sql = "SELECT ( AVG( ( cm.meta_value * 20 ) - 10 ) ) AS average_rating FROM $wpdb->comments c RIGHT JOIN $wpdb->commentmeta cm ON cm.comment_id = c.comment_ID WHERE cm.meta_key = '%s' AND c.comment_post_ID = %d";
		$query = $wpdb->prepare( $sql, '_wpkb_rating', $post_ID );

		$var = $wpdb->get_var( $query );
		if( $var ) {
			return round( $var );
		}

		return 0;
	}

	/**
	 * @param array $args
	 *
	 * @return array
	 */
	public function get_ratings( $args = array() ) {
		$args = array_merge( array(
			'type' => '_wpkb_rating',
			'orderby' => 'comment_date',
			'order' => 'DESC'
		), $args );
		$comments = get_comments( $args );
		$ratings = array();

		foreach( $comments as $comment ) {
			$rating = Rating::from_comment( $comment );
			$ratings[] = $rating;
		}

		return $ratings;
	}

	/**
	 * @param $post_ID
	 *
	 * @return array
	 */
	public function get_post_ratings( $post_ID ) {
		return $this->get_ratings( array( 'post_id' => $post_ID ) );
	}

	/**
	 * @return bool
	 */
	protected function is_bot() {

		// make sure to block out bots
		if( empty( $_SERVER['HTTP_USER_AGENT'] ) || preg_match( '/bot|crawl|slurp|spider/i', $_SERVER['HTTP_USER_AGENT'] ) ) {
			return true;
		}

		// if POST request, abandon if honeypot is not set or not empty
		if( $_SERVER['REQUEST_METHOD'] === 'POST' && ( ! isset( $_POST['url'] ) || ! empty( $_POST['url'] ) ) ) {
			return true;
		}

		return false;
	}

	/**
	 *
	 * @return string
	 */
	protected function get_client_ip() {
		$headers = ( function_exists( 'apache_request_headers' ) ) ? apache_request_headers() : $_SERVER;

		if ( array_key_exists( 'X-Forwarded-For', $headers ) && filter_var( $headers['X-Forwarded-For'], FILTER_VALIDATE_IP, FILTER_FLAG_IPV4 ) ) {
			$ip = $headers['X-Forwarded-For'];
		} elseif ( array_key_exists( 'HTTP_X_FORWARDED_FOR', $headers ) && filter_var( $headers['HTTP_X_FORWARDED_FOR'], FILTER_VALIDATE_IP, FILTER_FLAG_IPV4 ) ) {
			$ip = $headers['HTTP_X_FORWARDED_FOR'];
		} else {
			$ip = filter_var( $_SERVER['REMOTE_ADDR'], FILTER_VALIDATE_IP, FILTER_FLAG_IPV4 );
		}

		return $ip;
	}

	/**
	 * @param $post_ID
	 * @param $rating
	 * @return bool
	 */
	public function delete_author_post_ratings( $post_ID, $rating ) {
		global $wpdb;

		if( ! empty( $rating->author_user_ID ) ) {
			return $wpdb->delete( $wpdb->comments, array( 'comment_post_ID' => $post_ID, 'user_id' => $rating->author_user_ID, 'comment_type' => '_wpkb_rating' ), array( '%d', '%d', '%s' ) );
		}

		return $wpdb->delete( $wpdb->comments, array( 'comment_post_ID' => $post_ID, 'comment_author_IP' => $rating->author_IP, 'comment_type' => '_wpkb_rating' ), array( '%d', '%s', '%s' ) );
	}

	/**
	 * @return bool
	 */
	public function listen() {

		if( ! isset( $_POST['wpkb_action'] ) || $_POST['wpkb_action'] !== 'rate' ) {
			return false;
		}

		// don't track bots leaving ratings
		if( $this->is_bot() ) {
			return false;
		}

		$rating = $this->create_from_request();
		if( ! $rating instanceof Rating ) {
			return false;
		}

		// delete previous ratings from this user / ip
		$this->delete_author_post_ratings( $rating->post_ID, $rating );

		// save new rating
		$this->save_rating( $rating );

		// update average (for sortable columns);
		update_post_meta( $rating->post_ID, 'wpkb_rating_perc', $this->get_post_average( $rating->post_ID ) );

		// respond
		$url = remove_query_arg( array( 'wpkb_action', 'id', 'rating' ) );
		$url = add_query_arg( array( 'wpkb-rated' => 1 ), $url );
		wp_safe_redirect( $url );
		exit;
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

		// add message to content if we just rated
		if( isset( $_GET['wpkb-rated'] ) ) {
			$text = __( 'Thank you for your feedback!', 'wp-knowledge-base' );
			$content = '<div class="wpkb-alert info">'. $text .'</div>' . PHP_EOL . $content;
		}

		// create form HTML
		$html = '<form method="POST" class="wpkb-rating">';

		// Rating options
		$html .= '<p>';
		$html .= __( 'Was this article helpful?', 'wp-knowledge-base' );
		$html .= ' ';
		$html .= sprintf( '<label><input type="radio" name="rating" class="wpkb-rating-option wpkb-rating-5" value="%d">%s</label>', 5, esc_html__( 'Yes' ) );
		$html .= ' &middot; ';
		$html .= sprintf( '<label><input type="radio" name="rating" class="wpkb-rating-option wpkb-rating-1" value="%d">%s</label>', 1, esc_html__( 'No' ) );
		$html .= '</p>';

		// Message
		$html .= '<div class="wpkb-rating-message">';
		$html .= '<p>';
		$html .= '<label for="message">' . __( 'What was not helpful?', 'wp-knowledge-base' ) . '</label>';
		$html .= '<textarea id="message" rows="3" name="message" style="width: 100%;"></textarea>';
		$html .= '</p>';
		$html .= '<p><input type="submit" value="' . esc_attr__( 'Submit feedback', 'wp-knowledge-base' ) . '" /></p>';
		$html .= '</div>';

		// honeypot
		$html .= '<div style="position: absolute; left: -9999999px;"><input type="text" name="url" value="" /></div>';

		// necessary form values
		$html .= sprintf( '<input type="hidden" name="id" value="%d" />', get_the_ID() );
		$html .= '<input type="hidden" name="wpkb_action" value="rate" />';
		$html .= '</form>';

		return $content . PHP_EOL . $html;
	}

	/**
	 * Print <style>
	 */
	public function print_styles() {
		echo '<style type="text/css">';
		echo '.wpkb-rating label { display: inline-block; margin-left: 5px; cursor: pointer; }';
		echo '</style>';
	}

	/**
	 * Print <script>
	 */
	public function print_scripts() {
		echo '<script type="text/javascript">';
		require __DIR__ . '/script.js';
		echo '</script>';
	}
}