<?php
namespace WPKB\Rating;

class Rating {

	/**
	 * @var int
	 */
	public $rating = 1;

	/**
	 * @var string
	 */
	public $ip;

	/**
	 * @var int
	 */
	public $timestamp;

	/**
	 * @var string
	 */
	public $message = '';

	/**
	 * @param      $rating
	 * @param string $message
	 * @param null $ip
	 * @param null $timestamp
	 */
	public function __construct( $rating, $message = '', $ip = null, $timestamp = null ) {
		$this->rating = $rating;
		$this->message = $message;
		$this->ip = $ip;
		$this->timestamp = $timestamp;

		if( $timestamp === null ) {
			$this->timestamp = time();
		}

	}

	/**
	 * @param $array
	 *
	 * @return Rating
	 */
	public static function fromArray( $array ) {
		$object = new Rating( $array['rating'] );

		// optional keys
		$keys = array( 'message', 'ip', 'timestamp' );
		foreach( $keys as $key ) {
			if( ! empty( $array[ $key ] ) ) {
				$object->{$key} = $array[ $key ];
			}
		}

		return $object;
	}
}