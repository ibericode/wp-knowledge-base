<?php

namespace WPKB\Rating;

class UpgradeRoutine {

	/**
	 * @var int
	 */
	public $from_version = 0;

	/**
	 * @var int
	 */
	public $to_version = 0;

	/**
	 * @var Rater
	 */
	protected $rater;

	public function __construct( $from, $to, Rater $rater ) {
		$this->from_version = $from;
		$this->to_version = $to;
		$this->rater = $rater;
	}

	/**
	 * Run upgrade routine
	 */
	public function run() {
		// run migrations
		$this->post_meta_ratings();

		// store updated version number
		update_option( 'wpkb_version', $this->to_version );
	}

	/**
	 * Migrate from Post Meta to current Ratings datastore.
	 *
	 * @return bool
	 * @since 1.2
	 */
	public function post_meta_ratings() {

		// only run if coming from version lower than 1.2
		if( ! version_compare( $this->from_version, '1.2', '<' ) ) {
			return false;
		}

		// query posts with ratings
		global $wpdb;

		$results = $wpdb->get_results( "SELECT post_id FROM $wpdb->postmeta WHERE meta_key = 'wpkb_ratings' GROUP BY post_id" );
		foreach( $results as $result ) {

			// get ratings
			$ratings = get_post_meta( $result->post_id, 'wpkb_ratings', true );

			if( is_array( $ratings ) ) {
				foreach( $ratings as $array ) {

					// skip faulty serialized arrays
					if( empty( $array['rating'] ) ) { continue; }

					$args = array();

					// rename `ip` property to `author_IP`
					if( ! empty( $array['ip'] ) ) {
						$args['author_IP'] = $array['ip'];
					}

					if( ! empty( $array['timestamp'] ) ) {
						// new timestamp takes gmt offset into account
						$args['timestamp'] = $array['timestamp'] + ( get_option( 'gmt_offset' ) * HOUR_IN_SECONDS );
					}

					// create new rating object
					$rating = new Rating( $result->post_id, $array['rating'], $args );
					$this->rater->save_rating( $rating );
				}
			}

			delete_post_meta( $result->post_id, 'wpkb_ratings' );
			delete_post_meta( $result->post_id, 'wpkb_rating_count' );
			delete_post_meta( $result->post_id, 'wpkb_rating_perc' );
		}

	}

}