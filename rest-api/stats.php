<?php
/*
Description: REST-API - Stats output

So other people can access our stats data

- Shows
- Characters
- Death
- Stations
- Nations

Version: 1.2
Author: Mika Epstein
*/

if ( ! defined( 'WPINC' ) ) {
	die;
}

/**
 * class LWTV_Stats_JSON
 *
 * The basic constructor class that will set up our JSON API.
 */
class LWTV_Stats_JSON {

	/**
	 * Constructor
	 */
	public function __construct() {
		add_action( 'rest_api_init', array( $this, 'rest_api_init' ) );
	}

	/**
	 * Rest API init
	 *
	 * Creates callbacks
	 *   - /lwtv/v1/stats/[shows|characters|death]/[simple|complex|years]
	 */
	public function rest_api_init() {

		// Basic Stats
		register_rest_route( 'lwtv/v1', '/stats/', array(
			'methods'  => 'GET',
			'callback' => array(
				$this,
				'stats_rest_api_callback',
			),
		) );

		// Stat Types
		register_rest_route( 'lwtv/v1', '/stats/(?P<type>[a-zA-Z.\-]+)', array(
			'methods'  => 'GET',
			'callback' => array(
				$this,
				'stats_rest_api_callback',
			),
		) );

		// Stat Types and Format
		register_rest_route( 'lwtv/v1', '/stats/(?P<type>[a-zA-Z]+)/(?P<format>[a-zA-Z]+)', array(
			'methods'  => 'GET',
			'callback' => array(
				$this,
				'stats_rest_api_callback',
			),
		) );

		// Stat Types and Format AND PER PAGE
		register_rest_route( 'lwtv/v1', '/stats/(?P<type>[a-zA-Z]+)/(?P<format>[a-zA-Z]+)/page=(?P<page>[a-z0-9.\-]+)', array(
			'methods'  => 'GET',
			'callback' => array( $this, 'stats_rest_api_callback' ),
		) );

	}

	/**
	 * Rest API Callback for Statistics
	 *
	 * @access public
	 * @param mixed $data - string.
	 * @return array
	 */
	public function stats_rest_api_callback( $data ) {
		$params    = $data->get_params();
		$stat_type = ( isset( $params['type'] ) && '' !== $params['type'] ) ? sanitize_title_for_query( $params['type'] ) : 'none';
		$format    = ( isset( $params['format'] ) && '' !== $params['format'] ) ? sanitize_title_for_query( $params['format'] ) : 'simple';
		$page      = ( isset( $params['page'] ) && '' !== $params['page'] ) ? intval( $params['page'] ) : '1';
		$response  = $this->statistics( $stat_type, $format, $page );

		return $response;
	}

	/**
	 * Generate Statistics
	 *
	 * @return array with stats data
	 */
	public static function statistics( $stat_type = 'characters', $format = 'simple', $page = 1 ) {

		// Remove <!--fwp-loop--> from output
		add_filter( 'facetwp_is_main_query', function( $is_main_query, $query ) {
			return false;
		}, 10, 2 );

		// Valid Data
		$valid_type   = array( 'characters', 'actors', 'shows', 'death', 'first-year', 'stations', 'nations' );
		$valid_format = array( 'simple', 'complex', 'years', 'cliches', 'tropes', 'worth-it', 'stars', 'formats', 'triggers', 'loved', 'nations', 'sexuality', 'gender', 'romantic', 'genres', 'queer-irl', 'intersections' );

		// Per Page Check
		if ( 0 === $page ) {
			$page = 1;
		}

		// Sanity Check
		if ( ! in_array( $stat_type, $valid_type, true ) || ! in_array( $format, $valid_format, true ) ) {
			return wp_send_json_error( 'No route was found matching the URL and request method.', 404 );
		}

		switch ( $stat_type ) {
			case 'first-year':
				$stats_array = array(
					'first' => FIRST_LWTV_YEAR,
				);
				break;
			case 'shows':
				$stats_array = self::get_shows( $format, $page );
				break;
			case 'characters':
				$stats_array = self::get_characters( $format, $page );
				break;
			case 'actors':
				$stats_array = self::get_actors( $format, $page );
				break;
			case 'death':
				$stats_array = self::get_death( $format );
				break;
			case 'stations':
				$stats_array = self::get_show_taxonomy( 'stations', $format, $page );
				break;
			case 'nations':
				$stats_array = self::get_show_taxonomy( 'country', $format, $page );
				break;
			default:
				$stats_array = self::get_characters( 'simple' );
		}

		return $stats_array;
	}

	/**
	 * get_actors function.
	 *
	 * @access public
	 * @static
	 * @param string $format (default: 'simple')
	 * @return array
	 */
	public static function get_actors( $format = 'simple', $page = 1 ) {

		// Remove <!--fwp-loop--> from output
		add_filter( 'facetwp_is_main_query', function( $is_main_query, $query ) {
			return false;
		}, 10, 2 );

		// Validate Data
		$valid_format = array( 'simple', 'complex', 'sexuality', 'gender', 'queer-irl' );

		// Sanity Check
		if ( ! in_array( $format, $valid_format, true ) ) {
			return wp_send_json_error( 'No route was found matching the URL and request method.', 404 );
		}

		$stats_array = array();

		switch ( $format ) {
			case 'queer-irl':
				$stats_array = LWTV_Stats::generate( 'actors', 'queer-irl', 'array' );
				break;
			case 'gender':
				$stats_array = LWTV_Stats::generate( 'actors', 'actor_gender', 'array' );
				break;
			case 'sexuality':
				$stats_array = LWTV_Stats::generate( 'actors', 'actor_sexuality', 'array' );
				break;
			case 'complex':
				$queery = LWTV_Loops::post_type_query( 'post_type_actors', $page );

				if ( $queery->have_posts() ) {
					while ( $queery->have_posts() ) {
						$queery->the_post();
						$post                                      = get_post();
						$stats_array[ get_the_title( $post->ID ) ] = array(
							'id'         => $post->ID,
							'characters' => get_post_meta( $post->ID, 'lezactors_char_count', true ),
							'dead_chars' => get_post_meta( $post->ID, 'lezactors_dead_count', true ),
							'gender'     => implode( ', ', wp_get_post_terms( $post->ID, 'lez_actor_gender', array( 'fields' => 'names' ) ) ),
							'sexuality'  => implode( ', ', wp_get_post_terms( $post->ID, 'lez_actor_sexuality', array( 'fields' => 'names' ) ) ),
							'queer'      => LWTV_Loops::is_actor_queer( $post->ID ),
							'url'        => get_the_permalink( $post->ID ),
						);
					}
					wp_reset_query();
				}
				break;
			case 'simple':
				$stats_array = array(
					'total'     => wp_count_posts( 'post_type_actors' )->publish,
					'gender'    => wp_count_terms( 'lez_actor_gender' ),
					'sexuality' => wp_count_terms( 'lez_actor_sexuality' ),
				);
				break;
		}

		return $stats_array;
	}

	/**
	 * get_characters function.
	 *
	 * @access public
	 * @static
	 * @param string $format (default: 'simple')
	 * @return array
	 */
	public static function get_characters( $format = 'simple', $page = 1 ) {

		// Remove <!--fwp-loop--> from output
		add_filter( 'facetwp_is_main_query', function( $is_main_query, $query ) {
			return false;
		}, 10, 2 );

		// Validate Data
		$valid_format = array( 'simple', 'complex', 'sexuality', 'gender', 'romantic', 'cliches' );

		// Sanity Check
		if ( ! in_array( $format, $valid_format, true ) ) {
			return wp_send_json_error( 'No route was found matching the URL and request method.', 404 );
		}

		$stats_array = array();

		switch ( $format ) {
			case 'cliches':
				$stats_array = LWTV_Stats::generate( 'characters', 'cliches', 'array' );
				break;
			case 'sexuality':
				$stats_array = LWTV_Stats::generate( 'characters', 'sexuality', 'array' );
				break;
			case 'gender':
				$stats_array = LWTV_Stats::generate( 'characters', 'gender', 'array' );
				break;
			case 'romantic':
				$stats_array = LWTV_Stats::generate( 'characters', 'romantic', 'array' );
				break;
			case 'complex':
				$stats_array    = array();
				$charactersloop = LWTV_Loops::post_type_query( 'post_type_characters', $page );
				if ( $charactersloop->have_posts() ) {
					while ( $charactersloop->have_posts() ) {
						$charactersloop->the_post();

						$post   = get_post();
						$died   = get_post_meta( $post->ID, 'lezchars_death_year', true );
						$died   = ( ! is_array( $died ) ) ? array( $died ) : $died;
						$shows  = count( get_post_meta( $post->ID, 'lezchars_show_group', true ) );
						$actors = count( get_post_meta( $post->ID, 'lezchars_actor', true ) );

						$stats_array[ get_the_title() ] = array(
							'id'        => $post->ID,
							'died'      => $died,
							'actors'    => $actors,
							'shows'     => $shows,
							'gender'    => implode(', ', wp_get_post_terms( $post->ID, 'lez_gender', array(
								'fields' => 'names',
							) ) ),
							'sexuality' => implode(', ', wp_get_post_terms( $post->ID, 'lez_sexuality', array(
								'fields' => 'names',
							) ) ),
							'url'       => get_the_permalink(),
						);
					}
					wp_reset_query();
				}
				break;
			case 'simple':
				$dead_count  = get_term_by( 'slug', 'dead', 'lez_cliches' );
				$stats_array = array(
					'total'                => wp_count_posts( 'post_type_characters' )->publish,
					'dead'                 => $dead_count->count,
					'genders'              => wp_count_terms( 'lez_gender' ),
					'sexualities'          => wp_count_terms( 'lez_sexuality' ),
					'romantic_orientation' => wp_count_terms( 'lez_romantic' ),
					'cliches'              => wp_count_terms( 'lez_cliches' ),
				);
				break;
		}

		return $stats_array;
	}


	/**
	 * get_death function.
	 *
	 * @access public
	 * @static
	 * @param string $format (default: 'simple')
	 * @return void
	 */
	public static function get_death( $format = 'simple' ) {
		// Remove <!--fwp-loop--> from output
		add_filter( 'facetwp_is_main_query', function( $is_main_query, $query ) {
			return false;
		}, 10, 2 );

		// Validate Data
		$valid_format = array( 'simple', 'complex', 'years' );
		$format       = ( ! in_array( $format, $valid_format, true ) ) ? 'simple' : $format;
		$stats_array  = array();

		switch ( $format ) {
			case 'complex':
				$stats_array = array(
					'shows'     => LWTV_Stats::generate( 'characters', 'dead-shows', 'array' ),
					'sexuality' => LWTV_Stats::generate( 'characters', 'dead-sex', 'array' ),
					'gender'    => LWTV_Stats::generate( 'characters', 'dead-gender', 'array' ),
					// Current broken :(
					//'roles'     => LWTV_Stats::generate( 'characters', 'dead-roles', 'array' ),
				);
				break;
			case 'years':
				$stats_array = LWTV_Stats::generate( 'characters', 'dead-years', 'array' );
				break;
			case 'simple':
				$dead_chars  = get_term_by( 'slug', 'dead', 'lez_cliches' );
				$dead_shows  = get_term_by( 'slug', 'dead-queers', 'lez_tropes' );
				$stats_array = array(
					'characters' => array(
						'dead'  => $dead_chars->count,
						'alive' => ( wp_count_posts( 'post_type_characters' )->publish - $dead_chars->count ),
					),
					'shows'      => array(
						'death'    => $dead_shows->count,
						'no-death' => ( wp_count_posts( 'post_type_shows' )->publish - $dead_shows->count ),
					),
				);
				break;
		}

		return $stats_array;
	}

	/**
	 * get_shows function.
	 *
	 * @access public
	 * @static
	 * @param string $format (default: 'simple')
	 * @return array
	 */
	public static function get_shows( $format = 'simple', $page = 1 ) {

		global $wpdb;

		// Remove <!--fwp-loop--> from output
		add_filter( 'facetwp_is_main_query', function( $is_main_query, $query ) {
			return false;
		}, 10, 2 );

		// Validate Data
		$valid_format = array( 'simple', 'complex', 'nations', 'formats', 'stars', 'triggers', 'loved', 'worth-it', 'tropes', 'genres' );

		// Sanity Check
		if ( ! in_array( $format, $valid_format, true ) ) {
			return wp_send_json_error( 'No route was found matching the URL and request method.', 404 );
		}

		$stats_array = array();

		switch ( $format ) {
			case 'tropes':
				$stats_array = LWTV_Stats::generate( 'shows', 'tropes', 'array' );
				break;
			case 'nations':
				$stats_array = LWTV_Stats::generate( 'shows', 'country', 'array' );
				break;
			case 'genres':
				$stats_array = LWTV_Stats::generate( 'shows', 'genres', 'array' );
				break;
			case 'triggers':
				$stats_array = LWTV_Stats::generate( 'shows', 'triggers', 'array' );
				break;
			case 'formats':
				$stats_array = LWTV_Stats::generate( 'shows', 'formats', 'array' );
				break;
			case 'stars':
				$stats_array = LWTV_Stats::generate( 'shows', 'stars', 'array' );
				break;
			case 'loved':
				$stats_array = LWTV_Stats::generate( 'shows', 'weloveit', 'array' );
				break;
			case 'worth-it':
				$stats_array = LWTV_Stats::generate( 'shows', 'thumbs', 'array' );
				break;
			case 'intersections':
				$stats_array = LWTV_Stats::generate( 'shows', 'intersections', 'array' );
				break;
			case 'complex':
				$showsloop = LWTV_Loops::post_type_query( 'post_type_shows', $page );
				if ( $showsloop->have_posts() ) {
					while ( $showsloop->have_posts() ) {
						$showsloop->the_post();
						$post                                      = get_post();
						$stats_array[ get_the_title( $post->ID ) ] = array(
							'id'              => $post->ID,
							'nations'         => implode( ', ', wp_get_post_terms( $post->ID, 'lez_country', array( 'fields' => 'names' ) ) ),
							'stations'        => implode( ', ', wp_get_post_terms( $post->ID, 'lez_stations', array( 'fields' => 'names' ) ) ),

							'worth_it'        => get_post_meta( $post->ID, 'lezshows_worthit_rating', true ),
							'trigger'         => implode( ', ', wp_get_post_terms( $post->ID, 'lez_triggers', array( 'fields' => 'names' ) ) ),
							'star'            => implode( ', ', wp_get_post_terms( $post->ID, 'lez_stars', array( 'fields' => 'names' ) ) ),
							'loved'           => ( ( get_post_meta( $post->ID, 'lezshows_worthit_show_we_love', true ) ) ? 'yes' : 'no' ),
							'chars_total'     => get_post_meta( $post->ID, 'lezshows_char_count', true ),
							'chars_dead'      => get_post_meta( $post->ID, 'lezshows_dead_count', true ),
							'chars_sexuality' => get_post_meta( $post->ID, 'lezshows_char_sexuality', true ),
							'chars_gender'    => get_post_meta( $post->ID, 'lezshows_char_gender', true ),
							'url'             => get_the_permalink( $post->ID ),
						);
					}
					wp_reset_query();
				}
				break;
			case 'simple':
				$stats_array = array(
					'total'    => wp_count_posts( 'post_type_shows' )->publish,
					'stations' => wp_count_terms( 'lez_stations' ),
					'nations'  => wp_count_terms( 'lez_country' ),
					'formats'  => wp_count_terms( 'lez_formats' ),
					'genres'   => wp_count_terms( 'lez_genres' ),
				);
				break;
		}

		return $stats_array;
	}

	/**
	 * get_show_taxonomy function.
	 *
	 * @access public
	 * @static
	 * @return array
	 */
	public static function get_show_taxonomy( $type, $format = 'simple', $page = 1 ) {

		$valid_types   = array( 'stations', 'country' );
		$valid_formats = array( 'simple', 'complex' );

		// Early bail
		if ( ! in_array( $type, $valid_types, true ) ) {
			return wp_send_json_error( 'Invalid input. No such type.', 404 );
		}

		// Get our defaults
		$return         = array();
		$valid_subtaxes = array( 'gender', 'sexuality', 'romantic' );

		// This is a list of all stations or nations
		// If stats are complex, use pagination
		switch ( $format ) {
			case 'simple':
				$taxonomy = get_terms( array( 'taxonomy' => 'lez_' . $type ) );
				break;
			case 'complex':
				$offset = ( $page - 1 ) * 20;
				$taxonomy = get_terms( array(
					'taxonomy' => 'lez_' . $type,
					'number'   => 20,
					'offset' => $offset,
				) );
				break;
		}

		// Build out the default arrays for character data:
		foreach ( $valid_subtaxes as $subtax ) {
			$terms = get_terms( 'lez_' . $subtax, array(
				'orderby'    => 'count',
				'order'      => 'DESC',
				'hide_empty' => 0,
			) );
			if ( ! empty( $terms ) && ! is_wp_error( $terms ) ) {
				foreach ( $terms as $term ) {
					$char_data[ $term->slug ] = 0;
				}
			}
		}

		// Parse the taxonomy
		// Loop through the terms (i.e. USA, ABC, The CW) and generate the stats for each one
		foreach ( $taxonomy as $the_tax ) {
			$characters = 0;
			$shows      = 0;
			$dead       = 0;
			$char_data  = array();

			$slug = ( ! isset( $the_tax->slug ) ) ? $the_tax['slug'] : $the_tax->slug;
			$name = ( ! isset( $the_tax->name ) ) ? $the_tax['name'] : $the_tax->name;

			// Get the posts for this singular term (i.e. a specific station)
			$queery = LWTV_Loops::tax_query( 'post_type_shows', 'lez_' . $type, 'slug', $slug, 'IN' );

			// If we have anyone assigned to this station/nation, let's process
			if ( $queery->have_posts() ) {
				foreach ( $queery->posts as $show ) {

					// Increase the show count
					$shows++;
					$dead       += get_post_meta( $show->ID, 'lezshows_dead_count', true );
					$characters += get_post_meta( $show->ID, 'lezshows_char_count', true );

					// Get the sub taxonomy counts based on post meta
					foreach ( $valid_subtaxes as $meta ) {
						$char_data_array    = get_post_meta( $show->ID, 'lezshows_char_' . $meta );
						$char_data[ $meta ] = array_shift( $char_data_array );
					}

					// If we have a complex format, let's get ALL the data too!
					if ( 'complex' === $format ) {
						foreach ( $valid_subtaxes as $meta ) {
							$char_data_array = get_post_meta( $show->ID, 'lezshows_char_' . $meta );
							foreach ( array_shift( $char_data_array ) as $char_data_meta => $char_data_count ) {
								$char_data[ $char_data_meta ] += $char_data_count;
								unset( $char_data[ $meta ] );
							}
						}
					}

					// Build our return array
					// Show Count
					$return[ $slug ]['shows'] = $shows;

					// Only run this if we're complex...
					if ( 'complex' === $format ) {
						$return[ $slug ]['onair']           = LWTV_Stats::showcount( 'onair', $type, $slug );
						$return[ $slug ]['avg_score']       = LWTV_Stats::showcount( 'score', $type, $slug );
						$return[ $slug ]['avg_onair_score'] = LWTV_Stats::showcount( 'onairscore', $type, $slug );
					}

					// Character counts
					$return[ $slug ]['characters'] = $characters;
					$return[ $slug ]['dead']       = $dead;

					// If we have a complex format, we need to add that data...
					if ( 'complex' === $format ) {
						foreach ( $char_data as $ctax_name => $ctax_count ) {
							$return[ $slug ][ $ctax_name ] = $ctax_count;
						}
					}
				}

				wp_reset_query();
			}
		}

		return $return;

	}

}

new LWTV_Stats_JSON();
