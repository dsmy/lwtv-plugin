<?php
/*
 * Custom Post Type for characters on LWTV
 *
 * @since 1.0
 */

/**
 * class LWTV_CPT_Characters
 */
class LWTV_CPT_Characters {

	protected static $all_taxonomies;

	/**
	 * Constructor
	 */
	public function __construct() {

		self::$all_taxonomies = array(
			'cliché'               => 'cliches',
			'gender'               => 'gender',
			'sexual orientation'   => 'sexuality',
			'romantic orientation' => 'romantic',
		);

		add_action( 'admin_init', array( $this, 'admin_init' ) );
		add_action( 'init', array( $this, 'create_post_type' ), 0 );
		add_action( 'init', array( $this, 'create_taxonomies' ), 0 );
		add_action( 'amp_init', array( $this, 'amp_init' ) );
		add_action( 'wpseo_register_extra_replacements', array( $this, 'yoast_seo_register_extra_replacements' ) );
	}

	/**
	 * Admin Init
	 */
	public function admin_init() {

		if ( class_exists( 'VarnishPurger' ) ) {
			$this->varnish_purge = new VarnishPurger();
		}

		add_action( 'admin_head', array( $this, 'admin_css' ) );
		add_action( 'dashboard_glance_items', array( $this, 'dashboard_glance_items' ) );
		add_action( 'save_post_post_type_characters', array( $this, 'update_meta' ), 10, 3 );
		add_filter( 'enter_title_here', array( $this, 'custom_enter_title' ) );
	}

	/*
	 * CPT Settings
	 *
	 */
	public function create_post_type() {

		$char_taxonomies = array();
		foreach ( self::$all_taxonomies as $pretty => $slug ) {
			$char_taxonomies[] = 'lez_' . $slug;
		}

		$labels = array(
			'name'                  => 'Characters',
			'singular_name'         => 'Character',
			'menu_name'             => 'Characters',
			'parent_item_colon'     => 'Parent Character:',
			'all_items'             => 'All Characters',
			'view_item'             => 'View Character',
			'add_new_item'          => 'Add New Character',
			'add_new'               => 'Add New',
			'edit_item'             => 'Edit Character',
			'update_item'           => 'Update Character',
			'search_items'          => 'Search Characters',
			'not_found'             => 'No characters found',
			'not_found_in_trash'    => 'No characters in the Trash',
			'featured_image'        => 'Character Photo',
			'set_featured_image'    => 'Set Character Photo',
			'remove_featured_image' => 'Remove Character Photo',
			'use_featured_image'    => 'Use as Character Photo',
		);
		$args   = array(
			'label'               => 'post_type_characters',
			'description'         => 'Characters',
			'labels'              => $labels,
			'public'              => true,
			'show_in_rest'        => true,
			'rest_base'           => 'character',
			'menu_position'       => 7,
			'menu_icon'           => 'dashicons-nametag',
			'supports'            => array( 'title', 'editor', 'thumbnail', 'revisions' ),
			'has_archive'         => 'characters',
			'rewrite'             => array( 'slug' => 'character' ),
			'taxonomies'          => $char_taxonomies,
			'delete_with_user'    => false,
			'exclude_from_search' => false,
		);
		register_post_type( 'post_type_characters', $args );
	}

	/*
	 * Custom Taxonomies
	 *
	 */
	public function create_taxonomies() {

		foreach ( self::$all_taxonomies as $pretty => $slug ) {
			// Labels for taxonomy
			$labels = array(
				'name'                       => ucwords( $pretty ) . 's',
				'singular_name'              => ucwords( $pretty ),
				'search_items'               => 'Search ' . ucwords( $pretty ) . 's',
				'popular_items'              => 'Popular ' . ucwords( $pretty ) . 's',
				'all_items'                  => 'All' . ucwords( $pretty ) . 's',
				'edit_item'                  => 'Edit ' . ucwords( $pretty ),
				'update_item'                => 'Update ' . ucwords( $pretty ),
				'add_new_item'               => 'Add New ' . ucwords( $pretty ),
				'new_item_name'              => 'New' . ucwords( $pretty ) . 'Name',
				'separate_items_with_commas' => 'Separate ' . $pretty . 's with commas',
				'add_or_remove_items'        => 'Add or remove' . $pretty . 's',
				'choose_from_most_used'      => 'Choose from the most used ' . $pretty . 's',
				'not_found'                  => 'No ' . ucwords( $pretty ) . 's found.',
				'menu_name'                  => ucwords( $pretty ) . 's',
			);
			//parameters for the new taxonomy
			$arguments = array(
				'hierarchical'          => false,
				'labels'                => $labels,
				'show_ui'               => true,
				'show_in_rest'          => false,
				'show_admin_column'     => true,
				'update_count_callback' => '_update_post_term_count',
				'query_var'             => true,
				'show_in_nav_menus'     => true,
				'rewrite'               => array( 'slug' => rtrim( $slug, 's' ) ),
			);
			// Taxonomy name
			$taxonomyname = 'lez_' . $slug;

			// Register taxonomy
			register_taxonomy( $taxonomyname, 'post_type_characters', $arguments );
		}
	}

	/*
	 * Extra Meta Variables for Yoast and Actors
	 *
	 * List of actors who played a character, for use on character pages
	 */
	public function yoast_retrieve_actors_replacement() {
		global $post;
		$actors     = array();
		$actors_ids = get_post_meta( $post->ID, 'lezchars_actor', true );
		if ( ! is_array( $actors_ids ) ) {
			$actors_ids = array( get_post_meta( $post->ID, 'lezchars_actor', true ) );
		}
		if ( '' !== $actors_ids && ! is_null( $actors_ids ) ) {
			foreach ( $actors_ids as $each_actor ) {
				array_push( $actors, get_the_title( $each_actor ) );
			}
		}
		return implode( ', ', $actors );
	}

	/*
	 * Extra Meta Variables for Yoast and Characters
	 *
	 * List of shows featuring a character, for use on character pages
	 */
	public function yoast_retrieve_shows_replacement() {
		global $post;
		$shows_ids    = get_post_meta( $post->ID, 'lezchars_show_group', true );
		$shows_titles = array();
		if ( '' !== $shows_ids && ! is_null( $shows_ids ) ) {
			foreach ( $shows_ids as $each_show ) {
				array_push( $shows_titles, get_the_title( $each_show['show'] ) );
			}
		}
		return implode( ', ', $shows_titles );
	}

	/*
	 * Extra Replacement Functions for Yoast SEO
	 */
	public function yoast_seo_register_extra_replacements() {
		wpseo_register_var_replacement( '%%actors%%', array( $this, 'yoast_retrieve_actors_replacement' ), 'basic', 'A list of actors who played the character, separated by commas.' );
		wpseo_register_var_replacement( '%%shows%%', array( $this, 'yoast_retrieve_shows_replacement' ), 'basic', 'A list of shows the character was on, separated by commas.' );
	}

	/*
	 * AMP
	 */
	public function amp_init() {
		add_post_type_support( 'post_type_characters', AMP_QUERY_VAR );
	}

	/*
	 * Add to 'Right Now'
	 */
	public function dashboard_glance_items() {
		foreach ( array( 'post_type_characters' ) as $post_type ) {
			$num_posts = wp_count_posts( $post_type );
			if ( $num_posts && $num_posts->publish ) {
				if ( 'post_type_characters' === $post_type ) {
					// translators: %s is the number of characters
					$text = _n( '%s Character', '%s Characters', $num_posts->publish );
				}
				$text = sprintf( $text, number_format_i18n( $num_posts->publish ) );
				printf( '<li class="%1$s-count"><a href="edit.php?post_type=%1$s">%2$s</a></li>', esc_attr( $post_type ), esc_html( $text ) );
			}
		}
	}

	/*
	 * Style for dashboard
	 */
	public function admin_css() {
		echo "<style type='text/css'>
			#adminmenu #menu-posts-post_type_characters div.wp-menu-image:before, #dashboard_right_now li.post_type_characters-count a:before {
				content: '\\f484';
				margin-left: -1px;
			}
		</style>";
	}

	/**
	 * list_characters function.
	 *
	 * @access public
	 * @static
	 * @param mixed $show_id
	 * @param string $output (default: 'query')
	 * @return void
	 */
	public static function list_characters( $show_id, $output = 'query' ) {
		$charactersloop = LWTV_Loops::post_meta_query( 'post_type_characters', 'lezchars_show_group', $show_id, 'LIKE' );
		$characters     = array();
		$char_counts    = array(
			'total' => 0,
			'dead'  => 0,
			'none'  => 0,
			'quirl' => 0,
			'trans' => 0,
			'txirl' => 0,
		);

		// Store as array to defeat some stupid with counting and prevent querying the database too many times
		if ( $charactersloop->have_posts() ) {
			while ( $charactersloop->have_posts() ) {
				$charactersloop->the_post();
				$char_id     = get_the_ID();
				$shows_array = get_post_meta( $char_id, 'lezchars_show_group', true );

				// If the character is in this show, AND a published character
				// we will pass the following data to the character template
				// to determine what to display
				if ( '' !== $shows_array && ! empty( $shows_array ) && 'publish' === get_post_status( $char_id ) ) {
					foreach ( $shows_array as $char_show ) {
						if ( (int) $char_show['show'] === $show_id ) {
							$characters[ $char_id ] = array(
								'id'        => $char_id,
								'title'     => get_the_title( $char_id ),
								'url'       => get_the_permalink( $char_id ),
								'content'   => get_the_content( $char_id ),
								'shows'     => $shows_array,
								'show_from' => $show_id,
							);

							$char_counts['total']++;

							// Dead?
							if ( has_term( 'dead', 'lez_cliches', $char_id ) ) {
								$char_counts['dead']++;
							}
							// No cliches?
							if ( has_term( 'none', 'lez_cliches', $char_id ) ) {
								$char_counts['none']++;
							}
							// Queer IRL?
							if ( has_term( 'queer-irl', 'lez_cliches', $char_id ) ) {
								$char_counts['quirl']++;
							}
							// Is Trans?
							$valid_trans_char = array( 'trans-man', 'trans-woman' );
							if ( has_term( $valid_trans_char, 'lez_gender', $char_id ) ) {
								$char_counts['trans']++;
							}

							// Now to see if we have trans IRL...
							$actors_ids = get_post_meta( $char_id, 'lezchars_actor', true );
							if ( ! is_array( $actors_ids ) ) {
								$actors_ids = array( get_post_meta( $char_id, 'lezchars_actor', true ) );
							}
							foreach ( $actors_ids as $actor ) {
								$valid_trans_actor = array( 'trans-man', 'trans-woman', 'transgender' );
								$gender_terms      = get_the_terms( $actor, 'lez_actor_gender', true );
								if ( $gender_terms && ! is_wp_error( $gender_terms ) && has_term( $valid_trans_actor, 'lez_actor_gender', $actor ) ) {
									$char_counts['txirl']++;
									// It's possible to have MORE trans actors than characters.
								}
							}
						}
					}
				}
			}
			wp_reset_query();
		}

		switch ( $output ) {
			case 'dead':
				$return = $char_counts['dead'];
				break;
			case 'none':
				$return = $char_counts['none'];
				break;
			case 'queer-irl':
				$return = $char_counts['quirl'];
				break;
			case 'trans':
				$return = $char_counts['trans'];
				break;
			case 'trans-irl':
				$return = $char_counts['txirl'];
				break;
			case 'query':
				$return = $charactersloop;
				break;
			case 'count':
				$return = $char_counts['total'];
				break;
		}

		return $return;
	}

	/**
	 * Get Characters For Show
	 *
	 * Get all the characters for a show, based on role type.
	 *
	 * @access public
	 * @param mixed $show_id: Extracted from page the function is called on
	 * @param mixed $role: regular (default), recurring, guest
	 * @return array of characters
	 */
	public static function get_chars_for_show( $show_id, $havecharcount, $role = 'regular' ) {

		/* The Shane Clause & The Clone Club Correlary
		 *
		 * Calculate the max number of characters to list, based on the
		 * previous count. Default/Minimum is 100 characters.
		 * Amusing note: Without this, Sarah Manning took the place of
		 * every single other character played by Tatiana Maslany.
		 */
		$count = ( isset( $havecharcount ) && $havecharcount >= '100' ) ? $havecharcount : '100';

		// Valid Roles:
		$valid_roles = array( 'regular', 'recurring', 'guest' );

		// If this isn't a show page, or there are no valid roles, bail.
		if ( ! isset( $show_id ) || 'post_type_shows' !== get_post_type( $show_id ) || ! in_array( $role, $valid_roles, true ) ) {
			return;
		}

		// Prepare the ARRAY
		$characters = array();

		$charactersloop = new WP_Query(
			array(
				'post_type'              => 'post_type_characters',
				'post_status'            => array( 'publish' ),
				'orderby'                => 'title',
				'order'                  => 'ASC',
				'posts_per_page'         => $count,
				'no_found_rows'          => true,
				'update_post_term_cache' => true,
				'meta_query'             => array(
					'relation' => 'AND',
					array(
						'key'     => 'lezchars_show_group',
						'value'   => $role,
						'compare' => 'LIKE',
					),
					array(
						'key'     => 'lezchars_show_group',
						'value'   => $show_id,
						'compare' => 'LIKE',
					),
				),
			)
		);

		if ( $charactersloop->have_posts() ) {
			while ( $charactersloop->have_posts() ) {
				$charactersloop->the_post();
				$char_id     = get_the_ID();
				$shows_array = get_post_meta( $char_id, 'lezchars_show_group', true );

				// The Sara Lance Complexity:
				// If the character is in this show, AND a published character,
				// AND has this role ON THIS SHOW we will pass the following
				// data to the character template to determine what to display.

				if ( 'publish' === get_post_status( $char_id ) && isset( $shows_array ) && ! empty( $shows_array ) ) {
					foreach ( $shows_array as $char_show ) {
						if ( $char_show['show'] == $show_id && $char_show['type'] === $role ) { // WPCS: loose comparison ok.
							$characters[ $char_id ] = array(
								'id'        => $char_id,
								'title'     => get_the_title( $char_id ),
								'url'       => get_the_permalink( $char_id ),
								'content'   => get_the_content( $char_id ),
								'shows'     => $shows_array,
								'show_from' => $show_id,
								'role_from' => $role,
							);
						}
					}
				}
			}
			wp_reset_query();
		}
		return $characters;
	}

	/*
	 * Save post meta for characters
	 *
	 * @param int $post_id The post ID.
	 * @param post $post The post object.
	 * @param bool $update Whether this is an existing post being updated or not.
	 */
	public function update_meta( $post_id ) {

		$screen    = get_current_screen();
		$purgeurls = array();

		// unhook this function so it doesn't loop infinitely
		remove_action( 'save_post_post_type_characters', array( $this, 'update_meta' ) );

		// get the most recent death and save it as a new meta
		$character_death = get_post_meta( $post_id, 'lezchars_death_year', true );
		$newest_death    = '0000-00-00';
		if ( '' !== $character_death ) {
			foreach ( $character_death as $death ) {
				if ( $death > $newest_death ) {
					$newest_death = $death;
				}
			}
			if ( '0000-00-00' !== $newest_death ) {
				update_post_meta( $post_id, 'lezchars_last_death', $newest_death );
			}
		}

		// Update show data
		$show_ids = get_post_meta( $post_id, 'lezchars_show_group', true );
		if ( '' !== $show_ids ) {
			foreach ( $show_ids as $each_show ) {
				if ( isset( $each_show['show'] ) && 'publish' === get_post_status( $each_show['show'] ) ) {
					$request     = wp_remote_get( get_permalink( $each_show['show'] ) . '/?nocache' );
					$purgeurls[] = get_permalink( $each_show['show'] );
				}
			}
		}

		// Update actor data
		$actor_ids = lwtv_yikes_chardata( get_the_ID(), 'actors' );
		if ( '' !== $actor_ids ) {
			foreach ( $actor_ids as $each_actor ) {
				if ( isset( $each_actor['show'] ) && 'publish' === get_post_status( $each_actor['show'] ) ) {
					$request     = wp_remote_get( get_permalink( $each_actor ) . '/?nocache' );
					$purgeurls[] = get_permalink( $each_actor['show'] );
				}
			}
		}

		// Flush Varnish
		if ( class_exists( 'VarnishPurger' ) && 'add' !== $screen->action && 'publish' === get_post_status( $post_id ) ) {
			// Generate list of URLs based on the show ID:
			$generate_urls = $this->varnish_purge->generate_urls( $post_id );
			$urls_to_purge = array_merge( $purgeurls, $generate_urls );

			// Purge 'em all
			foreach ( $urls_to_purge as $url ) {
				$this->varnish_purge->purge_url( $url );
			}
		}

		// re-hook this function
		add_action( 'save_post_post_type_characters', array( $this, 'update_meta' ) );
	}

	/*
	 * Customize title
	 */
	public function custom_enter_title( $input ) {
		if ( 'post_type_characters' === get_post_type() ) {
			$input = 'Add character';
		}
		return $input;
	}
}

// Include Sub Files
require_once 'cmb2-metaboxes.php';
require_once 'custom-columns.php';

new LWTV_CPT_Characters();
