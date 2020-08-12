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
		'post_content'	=> 'Here you can discuss the subject of the gallery',
		'post_author' 	=> 14,
	);
	$first_topic_id = bbp_insert_topic( $topic_data );

}
add_action( 'create_term', 'gallery_forum_automation', 10, 3 );

// Content<->Topic automation
function content_topic_automation( $new_status, $old_status, $post ) {

	if( 'publish' == $new_status && 'publish' != $old_status && 'content' == $post->post_type ) :

		$ID = $post->ID;

		// Check if exists already a content-topic relationship
		$related = toolset_get_related_post( $post, 'content-topic', 'child');
		if( $related > 0 )
			return;

		// Get the belonging gallery
		$terms = get_the_terms( $ID, 'gallery' );
		$gallery = $terms[0];
		// If gallery exists, get the forum related to the gallery
		if( $gallery ) {
			$query = new WP_Query( array(
		    'post_type' => 'forum',
		    'tax_query' => array(
	        array (
	          'taxonomy' => 'gallery',
	          'field' => 'term_id',
	          'terms' => $gallery->term_id,
	        )
		    ),
			) );
			$forum = $query->posts[0];
		}

		// If forum exists, create a topic with the name of the content
		if( $forum ) {

			$topic_data = array(
				'post_parent'		=> $forum->ID,
				'post_title'		=> $post->post_title,
				'post_content'	=> 'Join the discussion and share your comments on this piece',
				'post_author' 	=> 14,
			);
			$topic_id = bbp_insert_topic( $topic_data );

		}

		// Set the relationship between the content and the topic
		if( $topic_id ) {
			toolset_connect_posts( 'content-topic', $ID, $topic_id );
		}

	endif;

}
add_action( 'transition_post_status', 'content_topic_automation', 10, 3);

//* Forum banner
function bs_display_forum_banner() {

	if ( !is_user_logged_in() )
		return;

	// get closed banner ids
	$closed_banner_comma_list = '';
	if( isset( $_COOKIE['closedBanner'] ) ) {

		$closed_banner_comma_list = str_replace( array( '[', '\"', ']' ), '', $_COOKIE['closedBanner'] );

	}

	// create target array
	$target = array( 'everyone' );

		// check if user is new (registered less than 15 days ago)
		$now = time();
		$current_user_data = get_userdata( get_current_user_id() );
		$date_diff = $now - strtotime( $current_user_data->user_registered );
		$date_diff_day = round( $date_diff / ( 60 * 60 * 24 ) );
		if ( $date_diff_day <= 15 ) {
			$target[] = 'new';
		}

		// check if user is very active on forum (more than 50 topic and reply)
		$topic_reply_sum = bbp_get_user_reply_count_raw( get_current_user_id() ) + bbp_get_user_topic_count_raw( get_current_user_id() );
		if ( $topic_reply_sum > 50 ) {
			$target[] = 'top';
		}

	$target_comma_list = rtrim(implode(',', $target), ',');

	$args = array(
    'id' 						=> 220,
		'closedbanner'	=> $closed_banner_comma_list,
		'target'				=> $target_comma_list,
	);
	echo render_view( $args );

}
add_action( 'buddyboss_inside_wrapper', 'bs_display_forum_banner', 40 );

// Enable visual editor in bbPress
function bbp_enable_visual_editor( $args = array() ) {
  if ( !is_page( 'register' )) {
    $args['tinymce'] = true;
    $args['quicktags'] = false;
  }
  return $args;
}
add_filter( 'bbp_after_get_the_content_parse_args', 'bbp_enable_visual_editor' );

// Add reply button after post in forum
add_filter( 'bbp_get_reply_content', 'bs_reply_button', 91 );
function bs_reply_button( $content ) {
  echo $content . '<div class="bs-reply-btn-wrapper"><a href="#new-post" class="button">Reply</a></div>';
}

// Enable mention autocomplete in bbPress
function buddydev_enable_mention_autosuggestions_on_compose( $load, $mentions_enabled ) {
	if ( ! $mentions_enabled ) {
		return $load; //activity mention is  not enabled, so no need to bother
	}
	//modify this condition to suit yours
	if( is_user_logged_in() ) {
		$load = true;
	}

	return $load;
}
add_filter( 'bp_activity_maybe_load_mentions_scripts', 'buddydev_enable_mention_autosuggestions_on_compose', 10, 2 );
