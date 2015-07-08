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
	 * @param      $rating
	 * @param null $ip
	 * @param null $timestamp
	 */
	public function __construct( $rating, $ip = null, $timestamp = null ) {
		$this->rating = $rating;
		$this->ip = $ip;
		$this->timestamp = $timestamp;

		if( $ip === null ) {
			$this->ip = $this->get_client_ip();
		}

		if( $timestamp === null ) {
			$this->timestamp = time();
		}

	}

	/**
	 * @return mixed
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
}