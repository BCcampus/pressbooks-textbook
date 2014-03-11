<?php

/**
 * PressBooks Textbook
 *
 * @package   PressBooks_Textbook
 * @author    Brad Payne <brad@bradpayne.ca>
 * @license   GPL-2.0+
 * @copyright 2014 Brad Payne
 *
 * @wordpress-plugin
 * Plugin Name:       PressBooks Textbook
 * Description:       A plugin that extends PressBooks for textbook authoring
 * Version:           1.0.0
 * Author:            Brad Payne
 * Text Domain:       pressbooks-textbook
 * License:           GPL-2.0+
 * License URI:       http://www.gnu.org/licenses/gpl-2.0.txt
 * Domain Path:       /languages
 * GitHub Plugin URI: https://github.com/BCcampus/pressbooks-textbook
 */

namespace PBT;
use PBT\Admin;

// If file is called directly, abort.
if ( ! defined( 'WPINC' ) ) {
	die();
}

require_once( ABSPATH . '/wp-admin/includes/plugin.php' );

class Textbook {

	/**
	 * Plugin version, used for cache-busting of style and script file references.
	 *
	 * @since 1.0.0
	 * @var string
	 */
	const VERSION = '1.0.0';

	/**
	 * Unique identifier for plugin.
	 *
	 * @since 1.0.0
	 * @var string
	 */
	protected $plugin_slug = 'pressbooks-textbook';

	/**
	 * Instance of this class.
	 *
	 * @since 1.0.0
	 * @var object
	 */
	protected static $instance = null;

	/**
	 * Initialize the plugin by setting localization and loading public scripts
	 * and styles.
	 *
	 * @since 1.0.0
	 */
	private function __construct() {
		// Define plugin constants
		if ( ! defined( 'PBT_PLUGIN_DIR' ) )
				define( 'PBT_PLUGIN_DIR', __DIR__ . '/' );

		if ( ! defined( 'PBT_PLUGIN_URL' ) )
				define( 'PBT_PLUGIN_URL', plugin_dir_url( __FILE__ ) );

		// Load translations
		add_action( 'init', array( $this, 'loadPluginTextDomain' ) );

		// Setup our activation and deactivation hooks
		register_activation_hook( __FILE__, array( $this, 'activate' ) );
		register_deactivation_hook( __FILE__, array( $this, 'deactivate' ) );

		// Hook in our pieces
		add_action( 'plugins_loaded', array( &$this, 'includes' ) );
		add_action( 'init', array( &$this, 'registerScriptsAndStyles' ) );
		add_action( 'admin_menu', array( &$this, 'adminMenuAdjuster' ) );
		add_action( 'admin_enqueue_scripts', array( &$this, 'enqueueAdminStyles' ) );
		add_action( 'wp_enqueue_style', array( &$this, 'enqueueChildThemes' ) );
		add_filter( 'allowed_themes', array( &$this, 'filterChildThemes' ), 11 );
	}

	/**
	 * Return an instance of this class.
	 *
	 * @since     1.0.0
	 *
	 * @return    object    A single instance of this class.
	 */
	public static function get_instance() {

		// If the single instance hasn't been set, set it now.
		if ( null == self::$instance ) {
			self::$instance = new self;
		}

		return self::$instance;
	}

	/**
	 * Include our plugins
	 */
	function includes() {
		$pbt_plugin = array(
		    'mce-table-buttons/mce_table_buttons.php' => 1,
		    'mce-textbook-buttons/mce-textbook-buttons.php' => 1,
		    'creative-commons-configurator-1/cc-configurator.php' => 1,
//		    'relevanssi/relevanssi.php' => 1,
		);

		$pbt_plugin = $this->filterActivePlugins( $pbt_plugin );

		// include plugins
		foreach ( $pbt_plugin as $key => $val ) {
			require_once( PBT_PLUGIN_DIR . 'symbionts/' . $key);
		}
	}

	/**
	 * Filters out active plugins
	 * 
	 * @param array $pbt_plugin
	 * @return array
	 */
	private function filterActivePlugins( $pbt_plugin ) {
		// don't include plugins already active
		foreach ( $pbt_plugin as $key => $val ) {
			if ( 1 == is_plugin_active( $key ) || 1 == is_plugin_active_for_network( $key ) ) {
				unset( $pbt_plugin[$key] );
			}
		}

		return $pbt_plugin;
	}

	/**
	 * Register all scripts and styles
	 * 
	 * @since 1.0.0
	 */
	function registerScriptsAndStyles() {
		// Register scripts
		// Register styles
		register_theme_directory( PBT_PLUGIN_DIR . 'themes-book' );
		wp_register_style( 'pbt-import-button', PBT_PLUGIN_URL . 'admin/assets/css/import-button.css', '', self::VERSION );
		wp_register_style( 'pbt-open-textbooks', PBT_PLUGIN_URL . 'themes-book/opentextbook/style.css', array( 'pressbooks' ), self::VERSION, 'screen' );
	}

	/**
	 * Fired when the plugin is activated.
	 *
	 * @since    1.0.0
	 */
	function activate() {
		if ( ! current_user_can( 'activate_plugins' ) ) {
			return;
		}

		add_site_option( 'pressbooks-textbook-activated', true );
	}

	/**
	 * Fired when the plugin is deactivated.
	 *
	 * @since    1.0.0
	 */
	function deactivate() {
		if ( ! current_user_can( 'activate_plugins' ) ) {
			return;
		}

		delete_site_option( 'pressbooks-textbook-activated' );
	}

	/**
	 * Return the plugin slug.
	 *
	 * @since    1.0.0
	 * @return    Plugin slug variable.
	 */
	function getPluginSlug() {
		return $this->plugin_slug;
	}

	/**
	 * Load the plugin text domain for translation.
	 *
	 * @since    1.0.0
	 */
	function loadPluginTextDomain() {

		$domain = $this->plugin_slug;
		$locale = apply_filters( 'plugin_locale', get_locale(), $domain );

		load_textdomain( $domain, trailingslashit( WP_LANG_DIR ) . $domain . '/' . $domain . '-' . $locale . '.mo' );
		load_plugin_textdomain( $domain, FALSE, basename( plugin_dir_path( dirname( __FILE__ ) ) ) . '/languages/' );
	}

	/**
	 * Register and enqueue public-facing style sheet.
	 *
	 * @since    1.0.0
	 */
	function enqueueAdminStyles() {
		wp_enqueue_style( 'pbt-import-button' );
	}

	function enqueueChildThemes() {
		wp_enqueue_style( 'pbt-open-textbooks' );
	}

	/**
	 * Register and enqueues public-facing JavaScript files.
	 *
	 * @since    1.0.0
	 */
	function enqueueScripts() {
//		wp_enqueue_script( $this->plugin_slug . '-plugin-script', plugins_url( 'assets/js/public.js', __FILE__ ), array( 'jquery' ), self::VERSION );
	}

	/**
	 * Adds and Removes some admin buttons
	 */
	function adminMenuAdjuster() {
		if ( \Pressbooks\Book::isBook() ) {
			add_menu_page( __( 'Import', 'pressbooks-textbook' ), __( 'Import', 'pressbooks-textbook' ), 'edit_posts', 'pb_import', '\PressBooks\Admin\Laf\display_import', '', 15 );
			add_menu_page( 'Plugins', 'Plugins', 'manage_network_plugins', 'plugins.php', '', 'dashicons-admin-plugins', 65 );
			remove_menu_page( 'pb_sell' );
		}
	}

	/**
	 * Pressbooks filters allowed themes, this adds our themes to the list
	 * 
	 * @param array $themes
	 * @return array
	 */
	function filterChildThemes( $themes ) {
		$pbt_themes = array();

		if ( \Pressbooks\Book::isBook() ) {
			$registered_themes = search_theme_directories();

			foreach ( $registered_themes as $key => $val ) {
				if ( $val['theme_root'] == PBT_PLUGIN_DIR . 'themes-book' ) {
					$pbt_themes[$key] = 1;
				}
			}
			// add our theme
			$themes = array_merge( $themes, $pbt_themes );

			return $themes;
		} else {
			return $pbt_themes;
		}
	}

}

// Prohibit installation on the main blog, or if PB is not installed
if ( is_main_site() || is_multisite() || get_site_option( 'pressbooks-activated' ) ) {
	if ( is_admin() ) {
		require (dirname( __FILE__ ) . '/admin/class-pb-textbook-admin.php');
		$pbt = new Admin\TextbookAdmin;
	} else {
		$pbt = \PBT\Textbook::get_instance();
	}
}

