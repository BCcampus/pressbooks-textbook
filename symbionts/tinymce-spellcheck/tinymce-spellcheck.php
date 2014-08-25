<?php
/*
Plugin Name: TinyMCE Spellcheck
Description: Adds a contextual spell, style, and grammar checker to WordPress 3.6+
Author: Matthew Muro
Author URI: http://matthewmuro.com
Version: 1.3
*/

/*
This program is free software; you can redistribute it and/or modify
it under the terms of the GNU General Public License as published by
the Free Software Foundation; version 2 of the License.

This program is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
GNU General Public License for more details.

You should have received a copy of the GNU General Public License
along with this program; if not, write to the Free Software
Foundation, Inc., 51 Franklin St, Fifth Floor, Boston, MA  02110-1301  USA
*/


function TSpell_configuration_load() {
	wp_safe_redirect( get_edit_profile_url( get_current_user_id() ) . '#atd' );
	exit;
}

/*
 *  Load necessary include files
 */
include( 'includes/config-options.php' );
include( 'includes/config-unignore.php' );
include( 'includes/proxy.php' );

define('TSPELL_VERSION', '20140801');

/**
 * Update a user's After the Deadline Setting
 */
function TSpell_update_setting( $user_id, $name, $value ) {
	update_user_meta( $user_id, $name, $value );
}

/**
 * Retrieve a user's After the Deadline Setting
 */
function TSpell_get_setting( $user_id, $name, $single = true ) {
	return get_user_meta( $user_id, $name, $single );
}

/*
 * Display the AtD configuration options
 */
function TSpell_config() {
	TSpell_display_options_form();
	TSpell_display_unignore_form();
}

/*
 *  Code to update the toolbar with the AtD Button and Install the AtD TinyMCE Plugin
 */
function TSpell_addbuttons() {
	/* Don't bother doing this stuff if the current user lacks permissions */
	if ( ! TSpell_is_allowed() )
		return;

	if ( ! defined( 'TSPELL_TINYMCE_4' ) ) {
		define( 'TSPELL_TINYMCE_4', ( ! empty( $GLOBALS['tinymce_version'] ) && substr( $GLOBALS['tinymce_version'], 0, 1 ) >= 4 ) );
	}

	/* Add only in Rich Editor mode */
	if ( get_user_option( 'rich_editing' ) == 'true' ) {
		add_filter( 'mce_external_plugins', 'add_TSpell_tinymce_plugin' );
		add_filter( 'mce_buttons', 'register_TSpell_button' );
	}

	add_action( 'personal_options_update', 'TSpell_process_options_update' );
	add_action( 'personal_options_update', 'TSpell_process_unignore_update' );
	add_action( 'profile_personal_options', 'TSpell_config' );
}

/*
 * Hook into the TinyMCE buttons and replace the current spellchecker
 */
function register_TSpell_button( $buttons ) {
	if ( TSPELL_TINYMCE_4 ) {
		// Use the default icon in TinyMCE 4.0 (replaced by dashicons in editor.css)
		if ( ! in_array( 'spellchecker', $buttons, true ) ) {
			$buttons[] = 'spellchecker';
		}

		return $buttons;
	}

	/* kill the spellchecker.. don't need no steenkin PHP spell checker */
	foreach ( $buttons as $key => $button ) {
		if ( $button == 'spellchecker' ) {
			$buttons[$key] = 'AtD';
			return $buttons;
		}
	}

	/* hrm... ok add us last plz */
	array_push( $buttons, '|', 'AtD' );
	return $buttons;
}

/*
 * Load the TinyMCE plugin : editor_plugin.js (wp2.5)
 */
function add_TSpell_tinymce_plugin( $plugin_array ) {
	$plugin = TSPELL_TINYMCE_4 ? 'plugin' : 'editor_plugin';

	$plugin_array['AtD'] = plugins_url( '/tinymce/' . $plugin . '.js?v=' . TSPELL_VERSION, __FILE__ );
	return $plugin_array;
}

/*
 * Update the TinyMCE init block with AtD specific settings
 */
function TSpell_change_mce_settings( $init_array ) {
	if ( ! TSpell_is_allowed() )
		return $init_array;

	$user = wp_get_current_user();

	$init_array['atd_rpc_url']        = admin_url( 'admin-ajax.php?action=proxy_atd&_wpnonce=' . wp_create_nonce( 'proxy_atd' ) . '&url=' );
	$init_array['atd_ignore_rpc_url'] = admin_url( 'admin-ajax.php?action=atd_ignore&_wpnonce=' . wp_create_nonce( 'tspell_ignore' ) . '&phrase=' );
	$init_array['atd_rpc_id']         = 'WPORG-' . md5(get_bloginfo('wpurl'));
	$init_array['atd_theme']          = 'wordpress';
	$init_array['atd_ignore_enable']  = 'true';
	$init_array['atd_strip_on_get']   = 'true';
	$init_array['atd_ignore_strings'] = json_encode( explode( ',',  TSpell_get_setting( $user->ID, 'TSpell_ignored_phrases' ) ) );
	$init_array['atd_show_types']     = TSpell_get_setting( $user->ID, 'TSpell_options' );
	$init_array['gecko_spellcheck']   = 'false';

	return $init_array;
}

/*
 * Sanitizes AtD AJAX data to acceptable chars, caller needs to make sure ' is escaped
 */
function TSpell_sanitize( $untrusted ) {
        return preg_replace( '/[^a-zA-Z0-9\-\',_ ]/i', "", $untrusted );
}

/*
 * AtD HTML Editor Stuff
 */
function TSpell_settings() {
    $user = wp_get_current_user();

    header( 'Content-Type: text/javascript' );

	/* set the RPC URL for AtD */
	echo "AtD.rpc = " . json_encode( esc_url_raw( admin_url( 'admin-ajax.php?action=proxy_atd&_wpnonce=' . wp_create_nonce( 'proxy_atd' ) . '&url=' ) ) ) . ";\n";

	/* set the API key for AtD */
	echo "AtD.api_key = " . json_encode( 'WPORG-' . md5( get_bloginfo( 'wpurl' ) ) ) . ";\n";

    /* set the ignored phrases for AtD */
	echo "AtD.setIgnoreStrings(" . json_encode( TSpell_get_setting( $user->ID, 'TSpell_ignored_phrases' ) ) . ");\n";

    /* honor the types we want to show */
    echo "AtD.showTypes(" . json_encode( TSpell_get_setting( $user->ID, 'TSpell_options' ) ) .");\n";

	/* this is not an AtD/jQuery setting but I'm putting it in AtD to make it easy for the non-viz plugin to find it */
	$admin_ajax_url = admin_url( 'admin-ajax.php?action=atd_ignore&_wpnonce=' . wp_create_nonce( 'atd_ignore' ) . '&phrase=' );
	echo "AtD.rpc_ignore = " . json_encode( esc_url_raw( $admin_ajax_url ) ) . ";\n";

    die;
}

function TSpell_load_javascripts() {
	if ( !TSpell_should_load_on_page() )
		return;

	wp_enqueue_script( 'TSpell_core', plugins_url( '/js/atd.core.js', __FILE__ ), array(), TSPELL_VERSION );
	wp_enqueue_script( 'TSpell_quicktags', plugins_url( '/js/atd-nonvis-editor-plugin.js', __FILE__ ), array('quicktags'), TSPELL_VERSION );
	wp_enqueue_script( 'TSpell_jquery', plugins_url( '/js/jquery.atd.js', __FILE__ ), array('jquery'), TSPELL_VERSION );
	wp_enqueue_script( 'TSpell_settings', admin_url() . 'admin-ajax.php?action=atd_settings', array('TSpell_jquery'), TSPELL_VERSION );
	wp_enqueue_script( 'TSpell_autoproofread', plugins_url( '/js/atd-autoproofread.js', __FILE__ ), array('TSpell_jquery'), TSPELL_VERSION );
}

/* Spits out user options for auto-proofreading on publish/update */
function TSpell_load_submit_check_javascripts() {
	global $pagenow;

	$user = wp_get_current_user();
	if ( ! $user || $user->ID == 0 )
		return;

	if ( TSpell_should_load_on_page() ) {
		$atd_check_when = TSpell_get_setting( $user->ID, 'TSpell_check_when' );

		if ( !empty( $atd_check_when ) ) {
			$check_when = array();
			/* Set up the options in json */
			foreach( explode( ',', $atd_check_when ) as $option ) {
				$check_when[$option] = true;
			}
			echo '<script type="text/javascript">' . "\n";
			echo 'TSpell_check_when = ' . json_encode( (object) $check_when ) . ";\n";
			echo '</script>' . "\n";
		}
	}
}

/*
 * Check if a user is allowed to use AtD
 */
function TSpell_is_allowed() {
        $user = wp_get_current_user();
        if ( ! $user || $user->ID == 0 )
                return;

        if ( ! current_user_can( 'edit_posts' ) && ! current_user_can( 'edit_pages' ) )
                return;

        return 1;
}

function TSpell_load_css() {
	if ( TSpell_should_load_on_page() )
	        wp_enqueue_style( 'TSpell_style', plugins_url( '/css/atd.css', __FILE__ ), null, TSPELL_VERSION, 'screen' );
}

/* Helper used to check if javascript should be added to page. Helps avoid bloat in admin */
function TSpell_should_load_on_page() {
	global $pagenow, $current_screen;

	$pages = array( 'post.php', 'post-new.php', 'page.php', 'page-new.php', 'admin.php', 'profile.php' );

	if ( in_array( $pagenow, $pages ) ) {
		if ( isset( $current_screen->post_type ) && $current_screen->post_type ) {
			return post_type_supports( $current_screen->post_type, 'editor' );
		}
		return true;
	}

	return apply_filters( 'atd_load_scripts', false );
}

// add button to DFW
add_filter( 'wp_fullscreen_buttons', 'TSpell_fullscreen' );
function TSpell_fullscreen($buttons) {
	$buttons['spellchecker'] = array( 'title' => __( 'Proofread Writing', 'tinymce-spellcheck' ), 'onclick' => "tinyMCE.execCommand('mceWritingImprovementTool');", 'both' => false );
	return $buttons;
}

/* add some vars into the AtD plugin */
add_filter( 'tiny_mce_before_init', 'TSpell_change_mce_settings' );

/* load some stuff for non-visual editor */
add_action( 'admin_print_scripts', 'TSpell_load_javascripts' );
add_action( 'admin_print_scripts', 'TSpell_load_submit_check_javascripts' );
add_action( 'admin_print_styles', 'TSpell_load_css' );

/* init process for button control */
add_action( 'init', 'TSpell_addbuttons' );

/* setup hooks for our PHP functions we want to make available via an AJAX call */
add_action( 'wp_ajax_proxy_atd', 'TSpell_redirect_call' );
add_action( 'wp_ajax_atd_ignore', 'TSpell_ignore_call' );
add_action( 'wp_ajax_atd_settings', 'TSpell_settings' );

/* load and install the localization stuff */
include( 'includes/atd-l10n.php' );

// Load i18n
add_action( 'plugins_loaded', 'TSpell_languages' );
function TSpell_languages() {
	load_plugin_textdomain( 'tinymce-spellcheck', false , dirname( plugin_basename( __FILE__ ) ) . '/languages/' );
}
