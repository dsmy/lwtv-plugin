<?php
/*
Description: REST-API - Alexa Skills - Newest

Generate the newest shows or characters (or deaths)

Version: 1.0
*/

if ( ! defined('WPINC' ) ) die;

/**
 * class LWTV_Alexa_Newest
 */
class LWTV_Alexa_Newest {

	/**
	 * What's New Overall
	 * 
	 * @access public
	 * @return string
	 */
	public function whats_new() {
		$character = self::characters();
		$show      = self::shows();
		$death     = self::death();

		$output = 'The latest character added to Lez Watch T. V. was ' . $character . '. The latest show added was ' . $show . '. And the latest character who died was ' . $death . '.';
		
		return $output;
	}

	/**
	 * Newest Character.
	 * 
	 * @access public
	 * @return string
	 */
	public function characters() {
		$post_args = array(
			'post_type'      => 'post_type_characters',
			'posts_per_page' => '1', 
			'orderby'        => 'date', 
			'order'          => 'DESC'
		);

		$queery = new WP_Query( $post_args );

		while ( $queery->have_posts() ) {
			$queery->the_post();
			$id = get_the_ID();
			$data['name'] = get_the_title( $id );
			$data['date'] = get_the_date( 'l F j, Y', $id );
		}
		wp_reset_postdata();
		$output = $data['name'] .' on '. $data['date'];
		
		return $output;
	}

	/**
	 * Newest Show.
	 * 
	 * @access public
	 * @return string
	 */
	public function shows() {
		$post_args = array(
			'post_type'      => 'post_type_shows',
			'posts_per_page' => '1', 
			'orderby'        => 'date', 
			'order'          => 'DESC'
		);

		$queery = new WP_Query( $post_args );

		while ( $queery->have_posts() ) {
			$queery->the_post();
			$id = get_the_ID();
			$data['name'] = get_the_title( $id );
			$data['date'] = get_the_date( 'l F j, Y', $id );
		}
		wp_reset_postdata();
		$output = $data['name'] .' on '. $data['date'];
		
		return $output;
	}

	/**
	 * Newest Death.
	 * 
	 * @access public
	 * @return string
	 */
	public function death() {
		$data   = LWTV_BYQ_JSON::last_death();
		$name   = $data['name'];
		$output = $name .' on '. date( 'F j, Y', $data['died'] );
		
		return $output;
	}

}

new LWTV_Alexa_Newest();