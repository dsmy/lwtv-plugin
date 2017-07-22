<?php
/*
 Plugin Name: LezWatch TV
 Plugin URI:  https://lezwatchtv.com
 Description: All the base code for LezWatch TV - If this isn't active, the site dies. An ugly death.
 Version: 2.0
 Author: Mika Epstein
*/

define( 'LWTV_PLUGIN_PATH', plugin_dir_path( __FILE__ ) );

/**
 * class LWTV_Functions
 *
 * The background functions for the site, independant of the theme.
 */
class LWTV_Functions {

	/**
	 * Constructor
	 */
	public function __construct() {
		add_action( 'admin_init', array( $this, 'admin_init') );
		add_action( 'init', array( $this, 'init') );
	}

	/**
	 * Init
	 */
	public function init() {
		// Placeholder
	}

	/**
	 * Admin Init
	 */
	public function admin_init() {
		// If Yoast SEO is active, call customizations
		if ( is_plugin_active( 'wordpress-seo/wp-seo.php' ) || is_plugin_active( 'wordpress-seo-premium/wp-seo-premium.php' ) || defined( 'WPSEO_VERSION' ) ) {
			require_once( 'plugins/yoast-seo.php' );
		}
	}

}
new LWTV_Functions();

// Call CMB2 - it doesn't error if it's not there
require_once( 'plugins/cmb2.php' );

// If Facet WP is active, call customizations
if ( class_exists( 'FacetWP' ) ) {
	require_once( 'plugins/facetwp.php' );
}

// Include CPTs
include_once( 'cpts/characters.php' );
include_once( 'cpts/shows.php' );
include_once( 'cpts/all-cpts.php' );

// JSON API
include_once( 'rest-api/bury-your-queers.php' );
include_once( 'rest-api/stats.php' );
include_once( 'rest-api/alexa-skills.php' );

// Include Others
include_once( 'search.php' );
include_once( 'seo.php' );
include_once( 'custom-loops.php' );
include_once( 'statistics.php' );
include_once( 'query_vars.php' );