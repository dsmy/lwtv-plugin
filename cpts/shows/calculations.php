<?php
/**
 * Name: Show Calculations
 * Description: Calculate various data points for shows
 */

if ( ! defined( 'WPINC' ) ) {
	die;
}

/**
 * class LWTV_Shows_Calculate
 *
 * @since 2.1.0
 */

class LWTV_Shows_Calculate {

	/**
	 * Calculate show rating.
	 */
	public static function show_score( $post_id ) {

		if ( ! isset( $post_id ) ) {
			return;
		}

		// Get base ratings
		// Multiply by 3 for a max of 30
		$realness   = min( (int) get_post_meta( $post_id, 'lezshows_realness_rating', true ), 5 );
		$quality    = min( (int) get_post_meta( $post_id, 'lezshows_quality_rating', true ), 5 );
		$screentime = min( (int) get_post_meta( $post_id, 'lezshows_screentime_rating', true ), 5 );
		$score      = ( $realness + $quality + $screentime ) * 2;

		// Add in Thumb Score Rating: 10, 5, -10
		switch ( get_post_meta( $post_id, 'lezshows_worthit_rating', true ) ) {
			case 'Yes':
				$score += 10;
				break;
			case 'Meh':
				$score += 5;
				break;
			case 'No':
				$score -= 10;
				break;
			default:
				$score = $score;
				break;
		}

		// Add in Star Rating: 20, 10, 5, -15
		$star_terms = get_the_terms( $post_id, 'lez_stars' );
		$color      = ( ! empty( $star_terms ) && ! is_wp_error( $star_terms ) ) ? $star_terms[0]->slug : get_post_meta( $post_id, 'lez_stars', true );

		switch ( $color ) {
			case 'gold':
				$score += 20;
				break;
			case 'silver':
				$score += 10;
				break;
			case 'bronze':
				$score += 5;
				break;
			case 'anti':
				$score -= 15;
				break;
		}

		// Trigger Warning: -5, -10, -15
		$trigger_terms = get_the_terms( $post_id, 'lez_triggers' );
		$trigger       = ( ! empty( $trigger_terms ) && ! is_wp_error( $trigger_terms ) ) ? $trigger_terms[0]->slug : get_post_meta( $post_id, 'lezshows_triggerwarning', true );
		switch ( $trigger ) {
			case 'on':
			case 'high':
				$score -= 15;
				break;
			case 'med':
			case 'medium':
				$score -= 10;
				break;
			case 'low':
				$score -= 5;
				break;
		}

		// Shows We Love: 40 points
		if ( 'on' === get_post_meta( $post_id, 'lezshows_worthit_show_we_love', true ) ) {
			$score += 40;
		}

		return $score;
	}

	/*
	 * Count Queers
	 *
	 * This will update the metakeys on save
	 *
	 * @param int $post_id The post ID.
	 */
	public static function count_queers( $post_id, $type = 'count' ) {
		$type_array = array( 'count', 'none', 'dead', 'queer-irl' );

		// If this isn't a show post, return nothing
		// // If there's no valid type, return nothing
		if ( 'post_type_shows' !== get_post_type( $post_id ) || ! in_array( esc_attr( $type ), $type_array, true ) ) {
			return;
		}

		// Loop to get the list of characters
		$charactersloop = LWTV_Loops::post_meta_query( 'post_type_characters', 'lezchars_show_group', $post_id, 'LIKE' );
		$queercount     = 0;
		$deadcount      = 0;
		$nonecount      = 0;
		$queerirlcount  = 0;

		// Store as array to defeat some stupid with counting and prevent querying the database too many times
		if ( $charactersloop->have_posts() ) {
			while ( $charactersloop->have_posts() ) {
				$charactersloop->the_post();
				$char_id     = get_the_ID();
				$shows_array = get_post_meta( $char_id, 'lezchars_show_group', true );
				if ( '' !== $shows_array && 'publish' === get_post_status( $char_id ) ) {
					foreach ( $shows_array as $char_show ) {
						if ( $char_show['show'] === $post_id ) {
							$queercount++;
							if ( has_term( 'dead', 'lez_cliches', $char_id ) ) {
								$deadcount++;
							}
							if ( has_term( 'none', 'lez_cliches', $char_id ) ) {
								$nonecount++;
							}
							if ( has_term( 'queer-irl', 'lez_cliches', $char_id ) ) {
								$queerirlcount++;
							}
						}
					}
				}
			}
			wp_reset_query();
		}

		// Return Queers!
		switch ( $type ) {
			case 'count':
				$return = $queercount;
				break;
			case 'dead':
				$return = $deadcount;
				break;
			case 'none':
				$return = $nonecount;
				break;
			case 'queer-irl':
				$return = $queerirlcount;
				break;
		}

		return $return;
	}

	/**
	 * Calculate show tropes score.
	 */
	public static function show_tropes_score( $post_id ) {

		$score        = 0;
		$count_tropes = count( wp_get_post_terms( $post_id, 'lez_tropes' ) );

		// Good tropes are always good.
		// Maybe tropes are only good IF there isn't Queer-for-Ratings
		$good_tropes  = array( 'happy-ending', 'everyones-queer' );
		$maybe_tropes = array( 'big-queer-wedding', 'coming-out', 'subtext' );
		$bad_tropes   = array( 'queerbashing', 'in-prison', 'big-bad-queers' );
		$ploy_tropes  = array( 'queer-for-ratings', 'queerbaiting', 'queer-laughs', 'happy-then-not', 'erasure' );

		if ( has_term( 'none', 'lez_tropes', $post_id ) ) {
			// No tropes: 100
			$score = 100;
		} else {

			// Calculate how many good tropes a show has
			$havegood  = 0;
			$havemaybe = 0;
			$havebad   = 0;
			$haveploy  = 0;
			foreach ( $good_tropes as $trope ) {
				if ( has_term( $trope, 'lez_tropes', $post_id ) ) {
					$havegood++;
				}
			}
			// Calculate Maybe Good Tropes
			foreach ( $maybe_tropes as $trope ) {
				if ( has_term( $trope, 'lez_tropes', $post_id ) ) {
					$havemaybe++;
				}
			}
			// Calculate Bad Tropes
			foreach ( $bad_tropes as $trope ) {
				if ( has_term( $trope, 'lez_tropes', $post_id ) ) {
					$havebad++;
				}
			}
			// Calculate Ploy Tropes
			foreach ( $ploy_tropes as $trope ) {
				if ( has_term( $trope, 'lez_tropes', $post_id ) ) {
					$haveploy++;
				}
			}

			if ( $havegood === $count_tropes ) {
				// If tropes are ONLY good, we give a 95
				$score = 95;
			} elseif ( ( $havegood + $havemaybe ) === $count_tropes ) {
				// If the tropes are only good and maybegood
				$score = 85;
			} elseif ( ( $havebad + $haveploy ) === $count_tropes ) {
				// If the tropes are all bad or ploys, then you get 25
				$score = 25;
			} elseif ( ( $havegood + $havemaybe - $havebad - $haveploy ) < 0 ) {
				// If they have more bad/ploys than good, it's a wash
				$score = 40;
			} else {
				// Otherise we just have a show that's pretty average so let's max them out at 75
				$score = ( ( ( $havegood + $havemaybe - $havebad ) / $count_tropes ) * 100 );
				if ( 0 === $haveploy ) {
					$score += 50;
				}
				if ( $score > 75 ) {
					$score = 75;
				}
			}

			// Dead Queers: remove one-third of the score
			if ( has_term( 'dead-queers', 'lez_tropes', $post_id ) ) {
				$score = ( $score * .66 );
			}
		}

		// Sanity Check
		if ( $score > 100 ) {
			$score = 100;
		}
		if ( $score < 0 ) {
			$score = 0;
		}

		return $score;
	}

	/**
	 * Calculate show character score.
	 */
	public static function show_character_score( $post_id ) {

		// Base Score
		$score = array(
			'alive'   => 0,
			'cliches' => 0,
		);

		// Count characters
		$number_chars    = max( 0, self::count_queers( $post_id, 'count' ) );
		$number_dead     = max( 0, self::count_queers( $post_id, 'dead' ) );
		$number_queerirl = max( 0, self::count_queers( $post_id, 'queer-irl' ) );
		$number_none     = self::count_queers( $post_id, 'none' );

		// If there are no chars, the score will be zero, so bail early.
		if ( 0 !== $number_chars ) {
			$score['alive']   = ( ( ( $number_chars - $number_dead ) / $number_chars ) * 100 );
			$score['cliches'] = ( ( ( $number_queerirl + $number_none ) / $number_chars ) * 100 );
		}

		// Update post meta for counts
		// NOTE: This cannot be an array becuase of how it's used for Facet later on.
		// MIKA! SERIOUSLY! NO!
		if ( 'post_type_shows' === get_post_type( $post_id ) ) {
			update_post_meta( $post_id, 'lezshows_char_count', $number_chars );
			update_post_meta( $post_id, 'lezshows_dead_count', $number_dead );
		} else {
			delete_post_meta( $post_id, 'lezshows_char_count' );
			delete_post_meta( $post_id, 'lezshows_dead_count' );
		}

		return $score;
	}

	/**
	 * Calculate show character data.
	 */
	public static function show_character_data( $post_id ) {

		// If this isn't a show post, return nothing
		if ( get_post_type( $post_id ) !== 'post_type_shows' ) {
			return;
		}

		// Create a massive array of all the terms we care about...
		$valid_taxes = array(
			'gender'    => 'lez_gender',
			'sexuality' => 'lez_sexuality',
			'romantic'  => 'lez_romantic',
		);
		$tax_data    = array();

		foreach ( $valid_taxes as $title => $taxonomy ) {
			$terms = get_terms( $taxonomy );
			if ( ! empty( $terms ) && ! is_wp_error( $terms ) ) {
				$tax_data[ $title ] = array();
				foreach ( $terms as $term ) {
					$tax_data[ $title ][ $term->slug ] = 0;
				}
			}
		}

		// Loop to get the list of characters
		$charactersloop = LWTV_Loops::post_meta_query( 'post_type_characters', 'lezchars_show_group', $post_id, 'LIKE' );

		// Store as array to defeat some stupid with counting and prevent querying the database too many times
		if ( $charactersloop->have_posts() ) {
			while ( $charactersloop->have_posts() ) {

				$charactersloop->the_post();
				$char_id     = get_the_ID();
				$shows_array = get_post_meta( $char_id, 'lezchars_show_group', true );

				if ( '' !== $shows_array && 'publish' === get_post_status( $char_id ) ) {
					foreach ( $shows_array as $char_show ) {
						if ( $char_show['show'] === $post_id ) {
							foreach ( $valid_taxes as $title => $taxonomy ) {
								$this_term = get_the_terms( $char_id, $taxonomy, true );
								if ( $this_term && ! is_wp_error( $this_term ) ) {
									foreach ( $this_term as $term ) {
										$tax_data[ $title ][ $term->slug ]++;
									}
								}
							}
						}
					}
				}
			}
			wp_reset_query();
		}

		foreach ( $valid_taxes as $title => $taxonomy ) {
			update_post_meta( $post_id, 'lezshows_char_' . $title, $tax_data[ $title ] );
		}

	}

	/**
	 * do_the_math function.
	 *
	 * This will update the following metakeys on save:
	 *  - lezshows_char_count      Number of characters
	 *  - lezshows_dead_count      Number of dead characters
	 *  - lezshows_the_score       Score of show data
	 *
	 * @access public
	 * @param mixed $post_id
	 * @return void
	 */
	public static function do_the_math( $post_id ) {

		// Get the ratings
		$score_show_rating  = self::show_score( $post_id );
		$score_chars_total  = self::show_character_score( $post_id );
		$score_chars_alive  = $score_chars_total['alive'];
		$score_chars_cliche = $score_chars_total['cliches'];
		$score_show_tropes  = self::show_tropes_score( $post_id );

		// Generate character data
		self::show_character_data( $post_id );

		// Calculate the full score
		$calculate = ( $score_show_rating + $score_chars_alive + $score_chars_cliche + $score_show_tropes ) / 4;

		// Add Intersectionality Bonus
		// If you do good with intersectionality you can have more points up to 10
		$count_inters = 0;
		$intersection = get_the_terms( $post_id, 'lez_intersections' );

		if ( is_array( $intersection ) ) {
			$count_inters = count( $intersection );
		}

		if ( ( $count_inters * 3 ) >= 15 ) {
			$calculate += 15;
		} else {
			$calculate += ( $count_inters * 3 );
		}

		// Keep it between 0 and 100
		if ( $calculate > 100 ) {
			$calculate = 100;
		}
		if ( $calculate < 0 ) {
			$calculate = 0;
		}

		// Update the meta
		if ( 'post_type_shows' === get_post_type( $post_id ) ) {
			update_post_meta( $post_id, 'lezshows_the_score', $calculate );
		} else {
			delete_post_meta( $post_id, 'lezshows_the_score' );
		}
	}
}

new LWTV_Shows_Calculate();
