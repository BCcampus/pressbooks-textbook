<?php
/**
 * Project: pressbooks-textbook
 * Project Sponsor: BCcampus <https://bccampus.ca>
 * Copyright 2012-2017 Brad Payne <https://github.com/bdolor>
 * Date: 2017-09-05
 * Licensed under GPLv3, or any later version
 *
 * @author Brad Payne
 * @package OPENTEXTBOOKS
 * @license https://www.gnu.org/licenses/gpl-3.0.txt
 * @copyright (c) 2012-2017, Brad Payne
 */


/**
 * enable footer tabs
 */
function pbt_enqueue_scripts() {

	// scripts only required if on a single page and user has configured theme options
	if ( is_single() && ! empty( pbt_get_web_options_tab() ) ) {
		wp_enqueue_script( 'pb-tabs', get_stylesheet_directory_uri() . '/assets/js/tabs.js', array( 'jquery' ), null, false );
		wp_enqueue_script( 'jquery-ui-tabs' );
		wp_enqueue_style( 'revisions', ABSPATH . '/wp-admin/css/revisions.css' );
	}
}

add_action( 'wp_enqueue_scripts', 'pbt_enqueue_scripts' );

/*
|--------------------------------------------------------------------------
| Tabbed Content
|--------------------------------------------------------------------------
|
| naming convention for functions that produce content is pbt_tab_
|
|
*/
/**
 * @param $post
 *
 * @return string
 */
function pbt_tab_revision_history( $post ) {
	$html    = '';
	$args    = array(
		'order'         => 'DESC',
		'orderby'       => 'date ID',
		'check_enabled' => false,
	);
	$enabled = wp_revisions_enabled( $post );
	$limit   = 3;
	$i       = 0;
	if ( false === $enabled ) {
		$html .= '<p>' . __( 'Revisions are not enabled', 'pressbooks' ) . '</p>';

		// these are not the revisions you're looking for
		return $html;
	}

	// wp_get_post_revisions returns an empty array if there are no revisions
	$revisions = wp_get_post_revisions( $post->ID, $args );

	// could be empty
	if ( empty( $revisions && true === $enabled ) ) {
		$html .= '<p>' . __( 'There are currently no revisions', 'pressbooks' ) . '</p>';

		return $html;
	}

	foreach ( $revisions as $revision ) {

		// skip autosave revisions
		if ( true === wp_is_post_autosave( $revision->ID ) ) {
			continue;
		}
		// save revision id
		$ids[] = $revision->ID;

		// special if it's the first loop
		if ( 0 === $i ) {
			$prev = 0;
			$new  = $post->post_content;
		} else {
			$prev = $i - 1;
			$new  = $revision->post_content;
		}

		// get previous revision
		$old_rev = wp_get_post_revision( $ids[ $prev ] );

		$diff = wp_text_diff( $new, $old_rev->post_content );

		if ( ! empty( $diff ) ) {
			$human_readable_date = date( 'M j, Y', strtotime( $revision->post_date_gmt ) );
			$html                .= "<b>{$human_readable_date}</b>{$diff}";
		}

		$i ++;
		if ( $limit === $i ) {
			break;
		}
	}

	return $html;
}

/**
 * Displays some book information
 *
 * @return string
 */
function pbt_tab_book_info() {
	$html      = '';
	$book_meta = \Pressbooks\Book::getBookInformation();
	$expected  = array(
		'pb_title',
		'pb_authors',
		'pb_contributors',
		'pb_editors',
		'pb_short_title',
		'pb_subtitle',
		'pb_publisher',
		'pb_publisher_city',
		'pb_copyright_year',
		'pb_copyright_holder',
		'pb_book_licence',
		'pb_keywords_tags',
		'pb_bisac_subject',
	);
	$html      .= '<dl class="dl-horizontal">';
	foreach ( $book_meta as $key => $val ) {
		// skip stuff we don't want
		if ( ! in_array( $key, $expected ) ) {
			continue;
		}
		$title = pbt_explode_on_underscores( $key, 'first' );
		$html  .= "<dt>{$title}</dt>";
		$html  .= "<dd>{$val}</dd>";
	}
	$html .= '</dl>';

	return $html;
}

/**
 *
 * @return string
 */
function pbt_tab_attributions() {
	global $post;
	$html = '';

	if ( class_exists( 'Candela\Citation' ) ) {
		if ( $citation = \Candela\Citation::renderCitation( $post->ID ) ) {
			$html .= '<section role="contentinfo"><div class="post-citations">' . $citation . '</div></section>';
		}
	}

	return $html;
}

/*
|--------------------------------------------------------------------------
| Tab Settings
|--------------------------------------------------------------------------
|
| Displays in Theme Options -> Web
|
|
*/
/**
 * Add our field to settings section
 *
 * @param $_page
 */
function pbt_theme_options_web_add_settings_fields( $_page ) {

	add_settings_field(
		'tabbed_content',
		__( 'Tabbed Content', 'open-textbooks' ),
		'pbt_tabbed_content_callback',
		$_page,
		'web_options_section'
	);

}

add_action( 'pb_theme_options_web_add_settings_fields', 'pbt_theme_options_web_add_settings_fields' );

/**
 * Displays tabbed content options in web options
 */
function pbt_tabbed_content_callback() {
	$options = get_option( 'pressbooks_theme_options_web' );

	// add default if not set
	if ( ! isset( $options['tab_revision_history'] ) ) {
		$options['tab_revision_history'] = 0;
	}
	if ( ! isset( $options['tab_book_info'] ) ) {
		$options['tab_book_info'] = 0;
	}
	if ( ! isset( $options['tab_attributions'] ) ) {
		$options['tab_attributions'] = 0;
	}

	// revision history
	$html = '<input type="checkbox" id="tab_revision_history" name="pressbooks_theme_options_web[tab_revision_history]" value="1" ' . checked( 1, $options['tab_revision_history'], false ) . '/>';
	$html .= '<label for="tab_revision_history"> ' . __( 'Display revision history for each chapter with everyone.', 'opentextbooks' ) . '</label><br/>';

	// book info
	$html .= '<input type="checkbox" id="tab_book_info" name="pressbooks_theme_options_web[tab_book_info]" value="1"  ' . checked( 1, $options['tab_book_info'], false ) . '/>';
	$html .= '<label for="tab_book_info"> ' . __( 'Display book information for each chapter with everyone.', 'opentextbooks' ) . '</label><br/>';

	// tab citations
	if ( class_exists( 'Candela\Citation' ) ) {
		$html .= '<input type="checkbox" id="tab_attributions" name="pressbooks_theme_options_web[tab_attributions]" value="1"  ' . checked( 1, $options['tab_attributions'], false ) . '/>';
		$html .= '<label for="tab_attributions"> ' . __( 'Display page attributions and licenses.', 'opentextbooks' ) . '</label>';
	}

	echo $html;
}

/**
 * Add our defaults to pb hook
 *
 * @param array $args
 *
 * @return mixed
 */
function pbt_web_defaults( $args ) {

	$args['tab_revision_history'] = 1;
	$args['tab_book_info']        = 1;
	$args['tab_attributions']     = 1;

	return $args;
}

add_filter( 'pb_theme_options_web_defaults', 'pbt_web_defaults' );

/**
 * Add our boolean options to pb hook
 *
 * @param $args
 *
 * @return mixed
 */
function pbt_boolean_options( $args ) {
	array_push( $args, 'tab_revision_history', 'tab_book_info', 'tab_attributions' );

	return $args;
}

add_filter( 'pb_theme_options_web_booleans', 'pbt_boolean_options' );

/*
|--------------------------------------------------------------------------
| Utility
|--------------------------------------------------------------------------
|
|
|
|
*/
/**
 * Return an array of web theme options related to tabbed content
 *
 * @return array
 */
function pbt_get_web_options_tab() {
	$options         = get_option( 'pressbooks_theme_options_web' );
	$web_option_keys = array_keys( $options );
	$prefix          = 'tab_';
	$length          = strlen( $prefix );
	$tabs            = array();

	// compare first four characters and check tab option is true
	foreach ( $web_option_keys as $key ) {
		if ( strncmp( $prefix, $key, $length ) === 0 && $options[ $key ] === 1 ) {
			$tabs[ $key ] = $options[ $key ];

		}
	}

	return $tabs;
}
