<?php

/**
 * Administrative functionality, settings/options
 *
 * @package   PressBooks_Textbook
 * @author    Brad Payne <brad@bradpayne.ca>
 * @license   GPL-2.0+
 * @copyright 2014 Brad Payne
 * 
 */

namespace PBT\Admin;

class TextbookAdmin extends \PBT\Textbook {

	/**
	 * Initialize the plugin by loading admin scripts & styles and adding a
	 * settings page and menu.
	 *
	 * @since     1.0.1
	 */
	function __construct() {

		parent::get_instance();

		// Add the options page and menu item.
		add_action( 'admin_menu', array( &$this, 'adminMenuAdjuster' ) );
		add_action( 'admin_init', array( &$this, 'adminSettings' ) );
		add_action( 'admin_enqueue_scripts', array( &$this, 'enqueueAdminStyles' ) );

		// Add an action link pointing to the options page.
		$plugin_basename = plugin_basename( plugin_dir_path( __DIR__ ) . $this->plugin_slug . '.php' );
		add_filter( 'plugin_action_links_' . $plugin_basename, array( $this, 'add_action_links' ) );

		// include other functions
		require( PBT_PLUGIN_DIR . 'includes/pbt-settings.php' );

	}
	
	/**
	 * Adds and Removes some admin buttons
	 * 
	 * @since 1.0.1
	 */
	function adminMenuAdjuster() {
		if ( \Pressbooks\Book::isBook() ) {
			add_menu_page( __( 'Import', $this->plugin_slug ), __( 'Import', $this->plugin_slug ), 'edit_posts', 'pb_import', '\PressBooks\Admin\Laf\display_import', '', 15 );
			add_menu_page( __( 'PressBooks Textbook Settings', $this->plugin_slug ), __( 'PB Textbook', $this->plugin_slug ), 'manage_options', $this->plugin_slug . '-settings', array( $this, 'displayPluginAdminPage' ), '', 64);
			add_menu_page( 'Plugins', 'Plugins', 'manage_network_plugins', 'plugins.php', '', 'dashicons-admin-plugins', 65 );
			remove_menu_page( 'pb_sell' );
		}
	}
	
	/**
	 * Register and enqueue public-facing style sheet.
	 *
	 * @since    1.0.0
	 */
	function enqueueAdminStyles() {
		wp_enqueue_style( 'pbt-import-button' );
	}
	
	/**
	 * Initializes PBT Settings page options
	 * 
	 * @since	1.0.1
	 */
	function adminSettings() {

		// group of settings
		// $id, $title, $callback, $page(menu slug)
		add_settings_section(
			'latest_files_section', 
			'Redistribute your latest export files', 
			'\PBT\Settings\latest_files_section_callback', 
			'open_file_settings'
		);

		// register a settings field to a settings page and section
		// $id, $title, $callback, $page, $section
		add_settings_field(
			'latest_files_public', 
			__( 'Share Latest Export Files', $this->plugin_slug ), 
			'\PBT\Settings\latest_files_public_callback', 
			'open_file_settings', 
			'latest_files_section'
		);

		// $option_group(group name), $option_name, $sanitize_callback
		register_setting(
			'open_file_settings', 
			'latest_files_public', 
			'\PBT\Settings\latest_files_public_sanitize'
		);
	}

	/**
	 * Render the settings page for this plugin.
	 *
	 * @since    1.0.1
	 */
	function displayPluginAdminPage() {

		include_once( 'views/admin-settings.php' );
	}

	/**
	 * Add settings action link to the plugins page.
	 *
	 * @since    1.0.1
	 */
	function addActionLinks( $links ) {

		return array_merge(
			array(
		    'settings' => '<a href="' . admin_url( 'options-general.php?page=' . $this->plugin_slug . '-settings' ) . '">' . __( 'Settings', $this->plugin_slug ) . '</a>'
			), $links
		);
	}

}
