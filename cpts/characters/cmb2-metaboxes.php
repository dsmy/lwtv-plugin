<?php
/**
 * Name: CMB2 Metaboxes
 */

if ( ! defined( 'WPINC' ) ) {
	die;
}

class LWTV_Characters_CMB2 {

	public $character_roles;

	/**
	 * Constructor
	 */
	public function __construct() {
		$this->character_roles = array(
			'regular'   => 'Regular/Main Character',
			'recurring' => 'Recurring Character',
			'guest'     => 'Guest Character',
		);

		add_action( 'init', array( $this, 'init' ) );
		add_action( 'cmb2_init', array( $this, 'cmb2_metaboxes' ) );
		add_action( 'admin_menu', array( $this, 'remove_metaboxes' ) );
	}

	/**
	 *  Init
	 */
	public function init() {
		// Force saving data to convert select2 saved data to a taxonomy
		$post_id = ( isset( $_GET['post'] ) ) ? intval( $_GET['post'] ) : 0; // WPCS: CSRF ok.

		if ( 0 !== $post_id && is_admin() ) {
			$post_type = ( isset( $_GET['post_type'] ) ) ? sanitize_text_field( $_GET['post_type'] ) : 0; // WPCS: CSRF ok.
			switch ( $post_type ) {
				case 'post_type_characters':
					// Force saving data to convert select2 saved data to a taxonomy
					LWTV_CMB2_Addons::select2_taxonomy_save( $post_id, 'lezchars_cliches', 'lez_cliches' );
					break;
			}
		}
	}

	/*
	 * Create a list of all shows
	 */
	public function cmb2_get_shows_options() {
		$return = LWTV_CMB2::get_post_options(
			array(
				'post_type'   => 'post_type_shows',
				'numberposts' => ( 50 + wp_count_posts( 'post_type_shows' )->publish ),
				'post_status' => array( 'publish', 'pending', 'draft', 'future' ),
			)
		);
		return $return;
	}

	/*
	 * Create a list of all actors
	 */
	public function cmb2_get_actors_options() {
		$return = LWTV_CMB2::get_post_options(
			array(
				'post_type'   => 'post_type_actors',
				'numberposts' => ( 50 + wp_count_posts( 'post_type_actors' )->publish ),
				'post_status' => array( 'publish', 'pending', 'draft', 'future' ),
			)
		);
		return $return;
	}

	/*
	 * CMB2 Metaboxes
	 */
	public function cmb2_metaboxes() {
		// prefix for all custom fields
		$prefix = 'lezchars_';

		// MetaBox Group: Character Details
		$cmb_characters = new_cmb2_box(
			array(
				'id'           => 'chars_metabox',
				'title'        => 'Character Details',
				'object_types' => array( 'post_type_characters' ),
				'context'      => 'normal',
				'priority'     => 'high',
				'show_in_rest' => true,
				'show_names'   => true, // Show field names on the left
			)
		);
		// Field: Character Gender Idenity
		$field_gender = $cmb_characters->add_field(
			array(
				'name'             => 'Gender',
				'desc'             => 'Gender identity',
				'id'               => $prefix . 'gender',
				'taxonomy'         => 'lez_gender',
				'type'             => 'taxonomy_select',
				'default'          => 'cisgender',
				'show_option_none' => false,
				'remove_default'   => 'true',
			)
		);
		// Field: Character Sexual Orientation
		$field_sexuality = $cmb_characters->add_field(
			array(
				'name'             => 'Sexuality',
				'desc'             => 'Sexual orientation',
				'id'               => $prefix . 'sexuality',
				'taxonomy'         => 'lez_sexuality',
				'type'             => 'taxonomy_select',
				'default'          => 'homosexual',
				'show_option_none' => false,
				'remove_default'   => 'true',
			)
		);
		// Field: Character Romantic Orientation
		$field_romantic = $cmb_characters->add_field(
			array(
				'name'             => 'Romantic',
				'desc'             => 'Romantic orientation',
				'id'               => $prefix . 'romantic',
				'taxonomy'         => 'lez_romantic',
				'type'             => 'taxonomy_select',
				'default'          => 'none',
				'show_option_none' => true,
				'remove_default'   => 'true',
			)
		);
		// Character Sidebar Grid
		if ( ! is_admin() ) {
			return;
		} else {
			$grid_char = new \Cmb2Grid\Grid\Cmb2Grid( $cmb_characters );
			$row1      = $grid_char->addRow();
			$row2      = $grid_char->addRow();
			$row1->addColumns( array( $field_gender, $field_sexuality ) );
			$row2->addColumns( array( $field_romantic ) );
		}
		// Field: Character Clichés
		$field_cliches = $cmb_characters->add_field(
			array(
				'name'              => 'Character Clichés',
				'id'                => $prefix . 'cliches',
				'taxonomy'          => 'lez_cliches',
				'type'              => 'pw_multiselect',
				'select_all_button' => false,
				'remove_default'    => 'true',
				'options'           => LWTV_CMB2_Addons::select2_get_options_array_tax( 'lez_cliches' ),
				'attributes'        => array(
					'placeholder' => 'Common clichés ...',
				),
			)
		);
		// Field: Year of Death (if applicable)
		$field_death = $cmb_characters->add_field(
			array(
				'name'        => 'Date of Death',
				'desc'        => 'If the character is dead, select when they died.',
				'id'          => $prefix . 'death_year',
				'type'        => 'text_date',
				'date_format' => 'Y-m-d',
				'repeatable'  => true,
			)
		);
		// Field: Actor Name(s)
		$field_actors = $cmb_characters->add_field(
			array(
				'name'             => 'Actor Name',
				'desc'             => 'Add the actor as a CPT first.',
				'id'               => $prefix . 'actor',
				'type'             => 'select',
				'show_option_none' => true,
				'default'          => 'custom',
				'options_cb'       => array( $this, 'cmb2_get_actors_options' ),
				'repeatable'       => true,
			)
		);

		// Field Group: Character Show information
		// Made repeatable since each show might have a separate role. Yikes...
		$group_shows = $cmb_characters->add_field(
			array(
				'id'         => $prefix . 'show_group',
				'type'       => 'group',
				'repeatable' => true,
				'options'    => array(
					'group_title'   => 'Show #{#}',
					'add_button'    => 'Add Another Show',
					'remove_button' => 'Remove Show',
					'sortable'      => true,
				),
			)
		);
		// Field: Show Name
		$field_shows = $cmb_characters->add_group_field(
			$group_shows,
			array(
				'name'             => 'TV Show',
				'id'               => 'show',
				'type'             => 'select',
				'show_option_none' => true,
				'default'          => 'custom',
				'options_cb'       => array( $this, 'cmb2_get_shows_options' ),
			)
		);
		// Field: Character Type
		$field_chartype = $cmb_characters->add_group_field(
			$group_shows,
			array(
				'name'             => 'Character Type',
				'desc'             => 'Mains are in credits. Recurring have their own plots. Guests show up once or twice.',
				'id'               => 'type',
				'type'             => 'select',
				'show_option_none' => true,
				'default'          => 'custom',
				'options'          => $this->character_roles,
			)
		);

	}

	/*
	 * Remove Metaboxes we use elsewhere
	 */
	public function remove_metaboxes() {
		remove_meta_box( 'authordiv', 'post_type_characters', 'normal' );
		remove_meta_box( 'postexcerpt', 'post_type_characters', 'normal' );
	}

}

new LWTV_Characters_CMB2();
