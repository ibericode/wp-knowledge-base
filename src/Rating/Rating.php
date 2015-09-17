<?php
namespace WPKB\Rating;

use stdClass;

class Rating {

	/**
	 * @var int
	 */
	public $rating = 1;

	/**
	 * @var string
	 */
	public $author_IP;

	/**
	 * @var int
	 */
	public $timestamp;

	/**
	 * @var string
	 */
	public $message = '';

	/**
	 * @var
	 */
	public $post_ID;

	/**
	 * @var string
	 */
	public $author_agent = '';

	/**
	 * @var string
	 */
	public $author_name = '';

	/**
	 * @var string
	 */
	public $author_email = '';

	/**
	 * @var int
	 */
	public $author_user_ID = 0;

	public $comment;


	/**
	 * @param int $post_ID
	 * @param      $rating
	 * @param array $args
	 */
	public function __construct( $post_ID, $rating, $args = array() ) {
		$this->post_ID = $post_ID;
		$this->rating = $rating;
		$this->percentage = ( $rating * 20 ) - 10;

		if( ! empty( $args['message'] ) ) {
			$this->message = $args['message'];
		}

		if( ! empty( $args['author_agent'] ) ) {
			$this->author_agent = $args['author_agent'];
		}

		if( ! empty( $args['author_IP'] ) ) {
			$this->author_IP = $args['author_IP'];
		}

		if( ! empty( $args['author_email'] ) ) {
			$this->author_email = $args['author_email'];
		}

		if( ! empty( $args['author_user_ID'] ) ) {
			$this->author_user_ID = $args['author_user_ID'];
		}

		if( ! empty( $args['author_name'] ) ) {
			$this->author_name = $args['author_name'];
		}

		if( ! empty( $args['timestamp'] ) ) {
			$this->timestamp = $args['timestamp'];
		} else {
			$this->timestamp = current_time('mysql'); //( 'Y-m-d H:i:s' );
		}

	}

	/**
	 * @return array
	 */
	public function to_comment() {
		return array(
			'comment_post_ID' => $this->post_ID,
			'comment_type' => '_wpkb_rating',
			'comment_content' => $this->message,
			'comment_agent' => $this->author_agent,
			'user_id' => $this->author_user_ID,
			'comment_author' => $this->author_name,
			'comment_author_IP' => $this->author_IP,
			'comment_author_email' => $this->author_email,
			'comment_date' => $this->timestamp
		);
	}

	/**
	 * @param array $comment
	 *
	 * @return Rating
	 */
	public static function from_comment( stdClass $comment ) {
		$rating_number = get_comment_meta( $comment->comment_ID, '_wpkb_rating', true );
		$rating = new self( $comment->comment_post_ID, $rating_number );
		$rating->comment = $comment;

		$comment = (array) $comment;

		foreach( $comment as $property => $var ) {

			// try without comment_ prefix
			$stripped_property = substr( $property, strlen( 'comment_' ) );

			if( $stripped_property && property_exists( $rating, $stripped_property ) && empty( $rating->{$stripped_property} ) ) {
				$rating->{$stripped_property} = $var;
			}
		}

		$rating->message = $comment['comment_content'];
		$rating->timestamp = $comment['comment_date'];
		$rating->author_name = $comment['comment_author'];

		return $rating;
	}

}