<?php
/**
 * LezWatch.TV Custom Queries
 *
 * Custom Query Variables that let us have special funky town pages.
 *
 * Version: 1.0
 *
 * @package LezWatch.TV Theme
 *
 */

// if this file is called directly abort
if ( ! defined( 'WPINC' ) ) {
	die;
}

class LWTV_Query_Vars {

	// Constant for the query arguments we allow
	public $lez_query_args   = array();
	public $lez_plural_types = array();

	/**
	 * Construct
	 * Runs the Code
	 *
	 * @since 1.0
	 */
	public function __construct() {
		add_action( 'init', array( $this, 'init' ) );

		// The custom queries that have special pages
		$this->lez_query_args = array(
			'statistics' => 'statistics',
			'this-year'  => 'thisyear',
		);

		// The custom queries that DO NOT have special pages
		$this->naked_query_args = array( 'format' );

		// Some Yoasty things
		add_action( 'wpseo_register_extra_replacements', array( $this, 'yoast_seo_register_extra_replacements' ) );
	}

	/**
	 * Main Plugin setup
	 *
	 * Adds actions, filters, etc. to WP
	 *
	 * @access public
	 * @return void
	 * @since 1.0
	 */
	public function init() {
		// Plugin requires permalink usage - Only setup handling if permalinks enabled
		if ( '' !== get_option( 'permalink_structure' ) ) {

			// tell WP not to override query vars
			add_action( 'query_vars', array( $this, 'query_vars' ) );

			// add filter for pages
			add_filter( 'page_template', array( $this, 'page_template' ) );

			// Query Vars for custom pages
			// Based on $this->lez_query_args
			foreach ( $this->lez_query_args as $slug => $query ) {
				add_rewrite_rule(
					'^' . $slug . '/([^/]+)/?$',
					'index.php?pagename=' . $slug . '&' . $query . '=$matches[1]',
					'top'
				);
				add_rewrite_rule(
					'^' . $slug . '/([^/]+)/page/([0-9]+)?/?$',
					'index.php?pagename=' . $slug . '&' . $query . '=$matches[1]&paged=$matches[2]',
					'top'
				);
			}
		} else {
			add_action( 'admin_notices', array( $this, 'admin_notice_permalinks' ) );
		}
	}

	/**
	 * No Permalinks Notice
	 *
	 * @since 1.0
	 */
	public function admin_notice_permalinks() {
		echo '<div class="error"><p><strong>LezWatch.TV Query Vars</strong> require you to use custom permalinks.</p></div>';
	}

	/**
	 * Add the query variables so WordPress won't override it
	 *
	 * @return $vars
	 */
	public function query_vars( $vars ) {
		foreach ( $this->lez_query_args as $argument ) {
			$vars[] = $argument;
		}
		return $vars;
	}

	/**
	 * Adds a custom template to the query queue.
	 *
	 * @return $templates
	 */
	public function page_template( $templates = '' ) {
		global $wp_query, $post;

		if ( array_key_exists( $post->post_name, $this->lez_query_args ) ) {
			$the_template = $this->lez_query_args[ $post->post_name ] . '.php';
		}

		foreach ( $this->lez_query_args as $argument ) {
			if ( isset( $wp_query->query[ $argument ] ) ) {
				$templates = get_stylesheet_directory() . '/page-templates/' . $the_template;
			}
		}

		return $templates;
	}

	/*
	 * Extra Replacement Functions for Yoast SEO
	 */
	public function yoast_seo_register_extra_replacements() {
		wpseo_register_var_replacement( '%%statistics%%', array( $this, 'yoast_retrieve_stats_replacement' ), 'basic', 'The type of stats page we\'re on.' );
		wpseo_register_var_replacement( '%%thisyear%%', array( $this, 'yoast_retrieve_year_replacement' ), 'basic', 'The year.' );
	}

	/*
	 * Extra Meta Variables for Yoast and Stats pages
	 *
	 * The type of stats page we're on
	 */
	public function yoast_retrieve_stats_replacement() {
		$statistics = get_query_var( 'statistics', 'none' );
		$return     = ( 'none' !== $statistics ) ? 'on ' . ucfirst( $statistics ) : '';
		return $return;
	}

	/*
	 * Extra Meta Variables for Yoast and Year pages
	 *
	 * The type of stats page we're on
	 */
	public function yoast_retrieve_year_replacement() {
		$this_year = get_query_var( 'thisyear', 'none' );
		$return    = ( 'none' !== $this_year ) ? ucfirst( $this_year ) : date( 'Y' );
		$return    = '(' . $return . ')';
		return $return;
	}

}

new LWTV_Query_Vars();
