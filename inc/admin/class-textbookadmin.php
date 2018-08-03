<?php

/**
 * Administrative functionality, settings/options
 *
 * @package   Pressbooks_Textbook
 * @author    Brad Payne
 * @license   GPL-2.0+
 * @copyright Brad Payne
 *
 */
namespace PBT\Admin;

use PBT;

class TextbookAdmin extends PBT\Textbook {

	/**
	 * Initialize the plugin by loading admin scripts & styles and adding a
	 * settings page and menu.
	 *
	 * @since     1.0.1
	 */
	function __construct() {

		parent::get_instance();

		// Add the options page and menu item.
		add_action( 'admin_menu', [ &$this, 'adminMenuAdjuster' ] );
		add_action( 'admin_init', [ &$this, 'adminSettings' ] );
		add_action( 'init', '\PBT\Modules\Search\ApiSearch::formSubmit', 50 );
		add_action( 'admin_enqueue_scripts', [ &$this, 'enqueueAdminStyles' ] );
		add_filter( 'tiny_mce_before_init', [ &$this, 'modForSchemaOrg' ] );
		// needs to be delayed to come after PB
		add_action( 'wp_dashboard_setup', [ &$this, 'addOtbNewsFeed' ], 11 );

		// Add an action link pointing to the options page.
		$plugin_basename = plugin_basename( plugin_dir_path( __DIR__ ) . $this->plugin_slug . '.php' );
		add_filter( 'plugin_action_links_' . $plugin_basename, [ $this, 'addActionLinks' ] );
	}

	/**
	 * Adds and Removes some admin buttons
	 *
	 * @since 1.0.1
	 */
	function adminMenuAdjuster() {
		if ( \Pressbooks\Book::isBook() ) {
			add_menu_page( __( 'Import', $this->plugin_slug ), __( 'Import', $this->plugin_slug ), 'edit_posts', 'pb_import', '\Pressbooks\Admin\Laf\display_import', 'dashicons-upload', 15 );
			add_options_page( __( 'Textbooks for Pressbooks Settings', $this->plugin_slug ), __( 'Textbooks for PB', $this->plugin_slug ), 'manage_options', $this->plugin_slug . '-settings', [ $this, 'displayPluginAdminPage' ] );
			add_menu_page( __( 'Textbooks for Pressbooks', $this->plugin_slug ), __( 'Textbooks for PB', $this->plugin_slug ), 'edit_posts', $this->plugin_slug, [ $this, 'displayPBTPage' ], 'dashicons-tablet', 64 );
			// check if the functionality we need is available
			if ( class_exists( '\Pressbooks\Modules\Api_v1\Api' ) ) {
				add_submenu_page( $this->plugin_slug, __( 'Search and Import', $this->plugin_slug ), __( 'Search and Import', $this->plugin_slug ), 'edit_posts', 'api_search_import', [ $this, 'displayApiSearchPage' ], '', 65 );
			}
			add_submenu_page( $this->plugin_slug, __( 'Download Textbooks', $this->plugin_slug ), __( 'Download Textbooks', $this->plugin_slug ), 'edit_posts', 'download_textbooks', [ $this, 'displayDownloadTextbooks' ], '', 66 );
			if ( version_compare( PB_PLUGIN_VERSION, '2.7' ) >= 0 ) {
				remove_menu_page( 'pb_publish' );
			} else {
				remove_menu_page( 'pb_sell' );
			}
		}
	}

	/**
	 * Initializes PBT Settings page options
	 *
	 * @since   1.0.1
	 */
	function adminSettings() {

		$this->remixSettings();
		$this->allowedPostTags();
	}

	/**
	 * Register and enqueue public-facing style sheet.
	 *
	 * @since    1.0.0
	 */
	function enqueueAdminStyles() {
		wp_register_style( 'pbt-import-button', PBT_PLUGIN_URL . 'admin/assets/css/menu.css', '', self::VERSION );
		wp_enqueue_style( 'pbt-import-button' );
	}

	/**
	 * TinyMCE will brilliantly strip out attributes like itemprop, itemscope, etc
	 * This reverses that brilliance
	 *
	 * @TODO - make this better.
	 * @since 1.1.5
	 * @param array $init
	 * @return array $init
	 */
	function modForSchemaOrg( $init ) {

		$ext = 'span[*],img[*],h3[*],div[*],a[*],meta[*]';

		$init['extended_valid_elements'] = $ext;

		return $init;
	}

	/**
	 * Add blog feed from open.bccampus.ca
	 *
	 * @since 1.1.0
	 */
	function addOtbNewsFeed() {
		// remove PB news from their blog
		remove_meta_box( 'pb_dashboard_widget_metadata', 'dashboard', 'side' );
		// add our own
		add_meta_box( 'pbt_news_feed', __( 'Open Textbook News', $this->plugin_slug ), [ $this, 'displayOtbFeed' ], 'dashboard', 'side', 'high' );
	}

	/**
	 * Callback function that adds our feed
	 *
	 * @since 1.1.0
	 */
	function displayOtbFeed() {
		wp_widget_rss_output(
			[
				'url' => 'https://open.bccampus.ca/feed/',
				'title' => __( 'Open Textbook News', $this->plugin_slug ),
				'items' => 5,
				'show_summary' => 1,
				'show_author' => 0,
				'show_date' => 1,
			]
		);
	}

	/**
	 * Options for functionality that support remix
	 */
	private function remixSettings() {
		$page    = $option = 'pbt_remix_settings';
		$section = 'pbt_remix_section';

		// Remix
		$defaults = [
			'pbt_api_endpoints' => [ network_home_url() ],
		];

		if ( false == get_option( 'pbt_remix_settings' ) ) {
			add_option( 'pbt_remix_settings', $defaults );
		}

		// group of settings
		// $id, $title, $callback, $page(menu slug)
		add_settings_section(
			$section,
			'Manage Federated Network of Pressbooks sites',
			'\PBT\Settings\remix_section_callback',
			$page
		);

		// register a settings field to a settings page and section
		// $id, $title, $callback, $page, $section
		add_settings_field(
			'add_api_endpoint',
			__( 'Add an endpoint to your network', $this->plugin_slug ),
			'\PBT\Settings\api_endpoint_public_callback',
			$page,
			$section
		);

		// $option_group(group name), $option_name, $sanitize_callback
		register_setting(
			$option,
			$option,
			'\PBT\Settings\remix_url_sanitize'
		);

	}

	/**
	 * Modifies a global variable to prevent wp_kses from stripping it out
	 *
	 * @since 1.1.5
	 * @global array $allowedposttags
	 */
	function allowedPostTags() {
		global $allowedposttags;

		$microdata_atts = [
			'itemprop' => true,
			'itemscope' => true,
			'itemtype' => true,
		];

		$allowedposttags['iframe'] = [
			'src' => true,
			'height' => true,
			'width' => true,
			'allowfullscreen' => true,
			'name' => true,
		];

		$allowedposttags['div']  += $microdata_atts;
		$allowedposttags['a']    += $microdata_atts;
		$allowedposttags['img']  += $microdata_atts;
		$allowedposttags['h3']   += $microdata_atts;
		$allowedposttags['span'] += [
			'content' => true,
		] + $microdata_atts;
		$allowedposttags['meta']  = [
			'content' => true,
		] + $microdata_atts;
		$allowedposttags['time']  = [
			'datetime' => true,
		] + $microdata_atts;
	}

	/**
	 * Render the settings page for this plugin.
	 *
	 * @since    1.0.1
	 */
	function displayPluginAdminPage() {

		include_once( PBT_PLUGIN_DIR . 'admin/views/admin-settings.php' );
	}

	/**
	 * Render the menu page
	 */
	function displayPBTPage() {

		include_once( PBT_PLUGIN_DIR . 'admin/views/pbt-home.php' );
	}
	/**
	 * Render the downloand textbooks page for editors
	 *
	 * @since 1.1.8
	 */
	function displayDownloadTextbooks() {

		include_once( PBT_PLUGIN_DIR . 'admin/views/download-textbooks.php' );
	}

	/**
	 * Render the API search page
	 *
	 * @since 1.1.6
	 */
	function displayApiSearchPage() {

		include_once( PBT_PLUGIN_DIR . 'admin/views/api-search.php' );
	}

	/**
	 * Add settings action link to the plugins page.
	 *
	 * @since    1.0.1
	 *
	 * @param array $links
	 *
	 * @return array
	 */
	function addActionLinks( $links ) {

		return array_merge(
			[
				'settings' => '<a href="' . admin_url( 'options-general.php?page=' . $this->plugin_slug . '-settings' ) . '">' . __( 'Settings', $this->plugin_slug ) . '</a>',
			], $links
		);
	}

}
