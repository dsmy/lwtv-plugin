<?php
/*
Description: Customizations for CMB2
Version: 1.0
Author: Mika Epstein
*/

if ( ! defined('WPINC' ) ) die;

/**
 * class LWTV_CMB2
 *
 * Customize CMB2
 *
 * @since 1.0
 */
class LWTV_CMB2 {

	public $icon_taxonomies; // Taxonomies that have an icon

	/**
	 * Constructor
	 */
	public function __construct() {
		add_action( 'admin_init', array( $this, 'admin_init') );

		$this->icon_taxonomies = array( 'lez_cliches', 'lez_tropes', 'lez_gender', 'lez_sexuality', 'lez_formats', 'lez_genres' );

		// If we don't have symbolicons, there's not a reason to register the taxonomy box...
		if ( defined( 'LP_SYMBOLICONS_PATH' ) ) {
			add_action( 'cmb2_admin_init', array( $this, 'register_taxonomy_metabox' ) );
		}

		// Add all filters and actions to show icons on tax list page
		foreach ( $this->icon_taxonomies as $tax_name ) {
			add_filter( 'manage_edit-'.$tax_name. '_columns', array( $this, 'terms_column_header' ) );
			add_action( 'manage_'.$tax_name. '_custom_column', array( $this, 'terms_column_content' ), 10, 3 );
		}

	}

	/**
	 * Init
	 */
	public function admin_init() {
		add_action( 'admin_enqueue_scripts', array( $this, 'admin_enqueue_scripts'), 10 );
	}

	/**
	 * Extra Get post options.
	 */
	public static function get_post_options( $query_args ) {
	    $args = wp_parse_args( $query_args, array(
	        'post_type'   => 'post',
	        'numberposts' => wp_count_posts( 'post' )->publish,
	        'post_status' => array('publish'),
	    ) );

	    $posts = get_posts( $args );

	    $post_options = array();
	    if ( $posts ) {
	        foreach ( $posts as $post ) {
	          $post_options[ $post->ID ] = $post->post_title;
	        }
	    }

	    asort($post_options);
	    return $post_options;
	}

	/**
	 * CSS tweaks
	 */
	public function admin_enqueue_scripts( $hook ) {
		wp_register_style( 'cmb-styles', plugins_url( 'cmb2.css', __FILE__ ) );
		if ( $hook == 'post.php' || $hook == 'post-new.php' || $hook == 'edit-tags.php' || $hook == 'term.php' || $hook == 'page-new.php' || $hook == 'page.php' ) {
			wp_enqueue_style( 'cmb-styles' );
		}
	}

	/**
	 * Add metabox to custom taxonomies to show icon
	 *
	 * $this->icon_taxonomies   array of taxonomies to show icons on.
	 *
	 * register_taxonomy_metabox()  CMB2 mextabox code
	 * before_field_icon()          Show an icon if that exists
	 *
	 * @param  array              $field_args  Array of field parameters
	 * @param  CMB2_Field object  $field       Field object
	 */
	public function register_taxonomy_metabox() {
		$prefix = 'lez_termsmeta_';
		$icon_array = get_option( 'lp_symbolicons' );
		$symbolicon_url = admin_url( 'themes.php?page=symbolicons' );

		$cmb_term = new_cmb2_box( array(
			'id'				=> $prefix . 'edit',
			'title'				=> 'Category Metabox',
			'object_types'		=> array( 'term' ),
			'taxonomies'		=> $this->icon_taxonomies,
			'new_term_section'	=> true,
		) );

		$cmb_term->add_field( array(
			'name'				=> 'Icon',
			'desc'				=> 'Select the icon you want to use. Once saved, it will show on the left.<br />If you need help visualizing, check out the <a href='.$symbolicon_url.'>Symbolicons List</a>.',
			'id'				=> $prefix . 'icon',
		    'type'				=> 'select',
		    'show_option_none'	=> true,
		    'default'			=> 'custom',
		    'options'			=> $icon_array,
			'before_field'		=> array( $this, 'before_field_icon' ),
		) );
	}

	// Add before field icon display
	public function before_field_icon( $field_args, $field ) {
		$icon = $field->value;
		
		// Bail early if empty
		if ( empty( $icon ) ) return;
		
		$svg = wp_remote_get( LP_SYMBOLICONS_PATH . $icon .'.svg' );
		
		$iconpath = '';
		if ( wp_remote_retrieve_response_code( $svg ) == '200' ) {
			$iconpath = wp_remote_retrieve_body( $svg );
		}

		$content = 'N/A';
		if ( $iconpath !== '' ) {
			$content = '<span role="img" class="cmb2-icon">' . $iconpath . '</span>';
		}
		return $content;
	}

	// Tax list column header
	public function terms_column_header($columns){
	    $columns['icon'] = 'Icon';
	    return $columns;
	}

	// Tax list column content
	public function terms_column_content($value, $content, $term_id){
		$icon = get_term_meta( $term_id, 'lez_termsmeta_icon', true );
		$svg = wp_remote_get( LP_SYMBOLICONS_PATH . $icon .'.svg' );
		$iconpath = '';
		$content = 'N/A';

		if ( wp_remote_retrieve_response_code( $svg ) == '200' ) {
			$iconpath = wp_remote_retrieve_body( $svg );
		}

		if ( !empty( $icon ) && $iconpath !== '' ) {
			$content = '<span role="img" class="cmb2-icon">' . $iconpath . '</span>';
		}
	    return $content;
	}
}
new LWTV_CMB2();