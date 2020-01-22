<?php
/*
Plugin Name:	Black Studio for OECD Auditors Alliance
Plugin URI:		https://auditorsalliance.org
Description:	Custom functions.
Version:		1.0.0
Author:			Black Studio
Author URI:		https://www.blackstudio.it
License:		GPL-2.0+
License URI:	http://www.gnu.org/licenses/gpl-2.0.txt

This plugin is free software: you can redistribute it and/or modify
it under the terms of the GNU General Public License as published by
the Free Software Foundation, either version 2 of the License, or
any later version.

This plugin is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
GNU General Public License for more details.

You should have received a copy of the GNU General Public License
along with This plugin. If not, see {URI to Plugin License}.
*/

if ( ! defined( 'WPINC' ) ) {
	die;
}

add_action( 'wp_enqueue_scripts', 'bs_enqueue_files' );
function bs_enqueue_files() {

	// loads a CSS file in the head.
	wp_enqueue_style( 'bs-style', plugin_dir_url( __FILE__ ) . 'assets/css/bs-style.css' );

	// loads JS files in the footer.
	wp_enqueue_script( 'bs-script', plugin_dir_url( __FILE__ ) . 'assets/js/bs-script.js', '', '1.0.0', true );

}

// Gallery<->forum automation
function gallery_forum_automation( $term_id, $tt_id, $taxonomy_slug ){

	// Check if it's a new Gallery taxonomy term
	if( 'gallery' != $taxonomy_slug )
		return;

	// Get new term data
	$new_term = get_term( $term_id, $taxonomy_slug );

	// Create a Forum with the name of the term and associate the new term as taxonomy
	$forum_data = array(
		'post_title'		=> $new_term->name,
		'tax_input'			=> array(
			'gallery' => array( $term_id ),
		),
	);
	$new_forum_id = bbp_insert_forum( $forum_data );

	// Create the first topic for Generic discussion
	$topic_data = array(
		'post_parent'		=> $new_forum_id,
		'post_title'		=> 'General discussion',
		'post_content'	=> 'Here you can discuss in general the subject of the gallery' . $new_term->name,
	)

	$first_topic_id = bbp_insert_topic( $topic_data );

}
add_action( 'create_term', 'gallery_forum_automation', 10, 3 );
