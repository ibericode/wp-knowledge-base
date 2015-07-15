<?php

namespace WPKB\Rating;

class Collection implements \Iterator, \Countable {

	/**
	 * @var array
	 */
	protected $elements = array();

	/**
	 * @var int
	 */
	private $position = 0;

	/**
	 * @param array $elements
	 */
	public function __construct( array $elements ) {
		$this->elements = $elements;
		$this->position = 0;
	}

	function rewind() {
		$this->position = 0;
	}

	function current() {
		return $this->elements[ $this->position ];
	}

	function key() {
		return $this->position;
	}

	function next() {
		++$this->position;
	}

	function valid() {
		return isset( $this->elements[ $this->position ] );
	}

	/**
	 * @param array $arrays
	 * @return Collection
	 */
	public static function fromArray( array $arrays ) {

		$collection = new self( array() );
		foreach( $arrays as $array ) {

			// skip empty ratings
			if( empty( $array['rating'] ) ) {
				continue;
			}

			$rating = Rating::fromArray( $array );
			$collection->add( $rating );
		}

		return $collection;
	}

	/**
	 * @return array
	 */
	public function toArray() {
		$arrays = array();

		foreach( $this->elements as $element ) {
			$arrays[] = (array) $element;
		}

		return $arrays;
	}

	/**
	 * @param Rating $rating
	 */
	public function add( Rating $rating ) {

		// find existing ratings from this IP
		$existing = $this->find(function($r) use($rating) { return $r->ip === $rating->ip; });
		if( $existing ) {
			$this->remove( $existing );
		}

		// add to ratings (at start of array)
		array_unshift( $this->elements, $rating );

		// limit array to 20 elements
		$this->elements = array_slice( $this->elements, 0, 20 );
	}

	/**
	 * @param Rating $rating
	 */
	public function remove( Rating $rating ) {
		foreach( $this->elements as $key => $element ) {
			if( $element === $rating ) {
				unset( $this->elements[ $key ] );
				return;
			}
		}
	}

	/**
	 * @param $callback
	 *
	 * @return array
	 */
	function map($callback) {
		$result = array();

		foreach( $this->elements as $element ) {
			$result[] = $callback( $element );
		}

		return $result;
	}

	/**
	 * @param $callback
	 *
	 * @return null
	 */
	function find($callback) {

		foreach( $this->elements as $element ) {
			if( $callback( $element ) ) {
				return $element;
			}
		}

		return null;
	}

	/**
	 * (PHP 5 &gt;= 5.1.0)<br/>
	 * Count elements of an object
	 * @link http://php.net/manual/en/countable.count.php
	 * @return int The custom count as an integer.
	 * </p>
	 * <p>
	 * The return value is cast to an integer.
	 */
	public function count() {
		return count( $this->elements );
	}

	/**
	 * @return float
	 */
	public function average() {
		$total = 0;
		$count = count( $this->elements );

		foreach( $this->elements as $element ) {
			$total = $total + $element->rating;
		}

		return round( $total / $count * 20 );

	}
}