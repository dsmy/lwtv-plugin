<?php
/*
 * All CPTs Code
 *
 * Code that runs on all custom post types.
 *
 * @since 1.5
 * Authors: Mika Epstein
 */


/**
 * class LWTV_All_CPTs
 */
class LWTV_All_CPTs {

	/**
	 * Constructor
	 */
	public function __construct() {
		add_action( 'admin_init', array( $this, 'featured_images' ) );

		add_action( 'edit_form_after_title', array( $this, 'admin_notices' ) );
		add_action( 'wp_enqueue_scripts', array( $this, 'wp_enqueue_scripts') );
		add_action( 'get_header' , array( $this, 'admin_notices' ), 20 );
	}

	/**
	 * Rename Featured Images
	 */
	public function featured_images() {
		$post_type_args = array(
		   'public'   => true,
		   '_builtin' => false
		);
		$post_types = get_post_types( $post_type_args, 'objects' );
		foreach ( $post_types as $post_type ) {

			$type = $post_type->name;
			$name = $post_type->labels->singular_name;

			// change the default "Featured Image" metabox title
			add_action('do_meta_boxes', function() use ( $type, $name ) {
				remove_meta_box( 'postimagediv', $type, 'side' );
				add_meta_box('postimagediv', $name.' Image', 'post_thumbnail_meta_box', $type, 'side');
			});

			// change the default "Set Featured Image" text
			add_filter( 'admin_post_thumbnail_html', function( $content ) use ( $type, $name ) {
				global $current_screen;
				if( !is_null($current_screen) && $type == $current_screen->post_type ) {
				    // Get featured image size
				    global $_wp_additional_image_sizes;
				    $genesis_image_size = rtrim( str_replace( 'post_type_', '', $type ), 's' ).'-img';
				    if ( isset( $_wp_additional_image_sizes[ $genesis_image_size ] ) ) {
				        $content = '<p>Image Size: ' . $_wp_additional_image_sizes[$genesis_image_size]['width'] . 'x' . $_wp_additional_image_sizes[$genesis_image_size]['height'] . 'px</p>' . $content;
				    }
					$content = str_replace( __( 'featured' ), strtolower( $name ) , $content);
				}
				return $content;
			});
		}
	}

	/**
	 * Front End CSS Customizations
	 */
	public function wp_enqueue_scripts( ) {
		wp_register_style( 'cpt-shows-styles', plugins_url( 'shows.css', __FILE__ ) );

		if( is_single() && get_post_type() == 'post_type_shows' ){
			wp_enqueue_style( 'cpt-shows-styles' );
		}
	}

	/*
	 * Admin Notices
	 */
	function admin_notices() {

		// Bail if not a post
		if ( !get_post() ) return;

		$message    = '';
		$type       = 'updated';
		$post       = get_post();

		$content    = get_post_field( 'post_content', $post->ID );
		$word_count = str_word_count( strip_tags( $content ) );

		switch ( $post->post_type ) {
			case 'post_type_shows':
				$countqueers = get_post_meta( $post->ID, 'lezshows_char_count', true );

				$worth_desc   = get_post_meta( $post->ID, 'lezshows_worthit_details', true );

/*
	// Not using this ssection yet, but the idea is to better flag what posts need work
	// If they're missing data we should know but it makes the page load slower so ...
				$worth_rating = get_post_meta( $post->ID, 'lezshows_worthit_rating', true);
				$tropes       = get_the_terms( $post->ID, 'lez_tropes' );
				$plots        = get_post_meta( $post->ID, 'lezshows_plots', true);
				$episodes     = get_post_meta( $post->ID, 'lezshows_episodes', true);

				$real_rating  = (int) get_post_meta( $post->ID, 'lezshows_realness_rating', true);
				$show_quality = (int) get_post_meta( $post->ID, 'lezshows_quality_rating', true);
				$screen_time  = (int) get_post_meta( $post->ID, 'lezshows_screentime_rating', true);

				$stations     = get_the_terms( $post->ID, 'lez_stations' );
				$airdates     = get_post_meta( $post->ID, 'lezshows_airdates', true);
				$formats      = get_the_terms( $post->ID, 'lez_formats' );
*/

				// If there's no worth it data and some queers, we have some data so let's do this thing
				if ( $worth_desc < '1' && $countqueers !== '0' ) {
					$type     = 'notice-info';
					$message  = 'Is ' . $post->post_title . ' show worth watching? We don\'t know.';
					$dashicon = 'heart';

					if ( $word_count < '100' ) {
						$type     = 'notice-error';
						$message  = 'We clearly know nothing about ' . $post->post_title . '.';
						$dashicon = 'warning';
					} elseif ( $word_count < '200' ) {
						$type     = 'notice-warning';
						$message  = $post->post_title . ' is a stub. Please edit it and make it more awesome.';
						$dashicon = 'info';
					}
				}
				break;
		}

		if ( $message && is_user_logged_in() && ( is_single() || is_admin() ) ) {
			printf( '<div class="wrap"><div class="notice %1$s"><p><span class="dashicons dashicons-%2$s"></span> %3$s</p></div></div>', esc_attr( $type ), esc_attr( $dashicon ), esc_html( $message ) );
		}

	}

}
new LWTV_All_CPTs();