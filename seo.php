<?php
/*
Plugin Name: SEO Customizations
Description: Some tweaks I have for SEO that don't work
Version: 1.0
*/

/*
 * Setting OpenGraph image for taxonomies
 * This uses the default image set for the taxonomy via that symbolicons stuff
 *
 * @since 1.0
 */

add_action('wp_head', 'lez_opengraph_image', 5);
function lez_opengraph_image( ) {

	// If it's not a taxonomy, die.
	if ( !is_tax() ) {
		return;
	}

	$term_id = get_queried_object_id();
	$icon = get_term_meta( $term_id, 'lez_termsmeta_icon', true );
	$iconpath = get_stylesheet_directory().'/images/symbolicons/png/'.$icon.'.png';
	if ( empty($icon) || !file_exists( $iconpath ) ) {
		$icon = 'square';
	}

	$image = get_stylesheet_directory_uri().'/images/symbolicons/png/'.$icon.'.png';

	echo '<meta property="og:image" content="'.$image.'" /><meta name="twitter:image" content="'.$image.'" />';
}