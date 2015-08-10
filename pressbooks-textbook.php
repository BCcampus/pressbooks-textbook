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
 * Version:           1.2.14
 * Author:            Brad Payne
 * Author URI:        http://bradpayne.ca		
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

class Textbook {

	/**
	 * Plugin version, used for cache-busting of style and script file references.
	 *
	 * @since 1.0.0
	 * @var string
	 */
	const VERSION = '1.2.14';

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
	 * @since 1.0.1
	 */
	private function __construct() {
		// Define plugin constants
		if ( ! defined( 'PBT_PLUGIN_DIR' ) )
				define( 'PBT_PLUGIN_DIR', __DIR__ . '/' );

		if ( ! defined( 'PBT_PLUGIN_URL' ) )
				define( 'PBT_PLUGIN_URL', plugin_dir_url( __FILE__ ) );
		
		if ( ! defined( 'PB_PLUGIN_DIR' ) )	
				define ( 'PB_PLUGIN_DIR', WP_PLUGIN_DIR . '/pressbooks/' ); // Must have trailing slash!
		
		// Load translations
		add_action( 'init', array( $this, 'loadPluginTextDomain' ) );

		// Setup our activation and deactivation hooks
		register_activation_hook( __FILE__, array( $this, 'activate' ) );
		register_deactivation_hook( __FILE__, array( $this, 'deactivate' ) );

		// Hook in our pieces
		add_action( 'plugins_loaded', array( &$this, 'includes' ) );
		add_action( 'init', array( &$this, 'pbtInit' ) );
		add_action( 'template_redirect', '\PBT\Rewrite\do_open', 0 );
		add_action( 'wp_enqueue_scripts', array( &$this, 'enqueueChildThemes' ) );
		add_filter( 'allowed_themes', array( &$this, 'filterChildThemes' ), 11 );
		add_action( 'pressbooks_new_blog', array( $this, 'newBook' ) );

		// include other functions
		require( PBT_PLUGIN_DIR . 'includes/pbt-utility.php' );
		require( PBT_PLUGIN_DIR . 'includes/pbt-rewrite.php' );

		wp_cache_add_global_groups( array( 'pbt' ) );
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
	 * 
	 * @since 1.0.8
	 */
	function includes() {
		$pbt_plugin = array(
		    'mce-table-buttons/mce_table_buttons.php' => 1,
		    'mce-textbook-buttons/mce-textbook-buttons.php' => 1,
		    'mce-anchor-button/mce-anchor-button.php' => 1,
		    'mce-superscript-subscript-buttons/mce-superscript-subscript-buttons.php' => 1,
		    'creative-commons-configurator-1/cc-configurator.php' => 1,
		    'hypothesis/hypothesis.php' => 1,
		    'tinymce-spellcheck/tinymce-spellcheck.php' => 1,
		    'disable-comments/disable-comments.php' => 1,
		);

		$pbt_plugin = $this->filterPlugins( $pbt_plugin );

		// include plugins
		if ( ! empty( $pbt_plugin ) ) {
			foreach ( $pbt_plugin as $key => $val ) {
				require_once( PBT_PLUGIN_DIR . 'symbionts/' . $key);
			}
		}
	}

	/**
	 * Filters out active plugins, to avoid collisions with plugins already installed
	 * 
	 * @since 1.0.8
	 * @param array $pbt_plugin
	 * @return array
	 */
	private function filterPlugins( $pbt_plugin ) {
		$already_active = get_option('active_plugins');
		$network_already_active = get_site_option('active_sitewide_plugins');
		
		if ( defined( 'PB_PLUGIN_VERSION' ) ) {
			if ( version_compare( PB_PLUGIN_VERSION, '2.5.1' ) >= 0 ) {
				unset( $pbt_plugin['mce-table-buttons/mce_table_buttons.php'] );
			}
			if ( version_compare( PB_PLUGIN_VERSION, '2.5.2' ) >= 0 ) {
				unset( $pbt_plugin['mce-superscript-subscript-buttons/mce-superscript-subscript-buttons.php'] );
			}
			if ( version_compare( PB_PLUGIN_VERSION, '2.5.4' ) >= 0 ) {
				unset( $pbt_plugin['disable-comments/disable-comments.php'] );	
			}
		}
		
		// activate only if one of our themes is being used
		if ( false == self::isTextbookTheme() ) {
			unset( $pbt_plugin['mce-table-buttons/mce_table_buttons.php'] );
			unset( $pbt_plugin['mce-textbook-buttons/mce-textbook-buttons.php'] );
			unset( $pbt_plugin['mce-anchor-button/mce-anchor-button.php'] );
			unset( $pbt_plugin['mce-superscript-subscript-buttons/mce-superscript-subscript-buttons.php'] );
			unset( $pbt_plugin['hypothesis/hypothesis.php'] );
			unset( $pbt_plugin['creative-commons-configurator-1/cc-configurator.php'] );
			unset( $pbt_plugin['mce-table-buttons/mce_table_buttons.php'] );
			unset( $pbt_plugin['tinymce-spellcheck/tinymce-spellcheck.php'] );
			unset( $pbt_plugin['disable-comments/disable-comments.php'] );	
		}
		
		// don't include plugins already active at the site level, network level
		if ( ! empty( $pbt_plugin ) ) {
			foreach ( $pbt_plugin as $key => $val ) {
				if ( in_array( $key, $already_active ) || array_key_exists( $key, $network_already_active )) {
					unset( $pbt_plugin[$key] );
				}
				
			}
		}

		// don't include plugins if the user doesn't want them
		if ( ! empty( $pbt_plugin ) ) {
			
			// get user options
			$user_options = $this->getUserOptions();
			
			if ( is_array( $user_options ) ) {
				foreach ( $pbt_plugin as $key => $val ) {

					$name = strstr( $key, '/', true );
					$pbt_option = "pbt_" . $name . "_active";

					// either it doesn't exist, or the client doesn't want it
					if ( array_key_exists( $pbt_option, $user_options ) ) {
						// check the value
						if ( false == $user_options[$pbt_option] ) {
							unset( $pbt_plugin[$key] );
						}
					}
				}
			}
		}

		return $pbt_plugin;
	}
	
	/**
	 * Returns merged array of all PBT user options
	 * 
	 * @since 1.0.2
	 * @return array
	 */
	private function getUserOptions() {
		$result = array();
		
		( array ) $other = get_option( 'pbt_other_settings' );
		( array ) $reuse = get_option( 'pbt_reuse_settings' );
		( array ) $redistribute = get_option( 'pbt_redistribute_settings' );

		$result = @array_merge( $other, $reuse, $redistribute );
		
		return $result;
	}

	/**
	 * Checks to see if one of our child themes is active
	 * 
	 * @return boolean
	 */
	static function isTextbookTheme() {
		$t = wp_get_theme()->Tags;
		if ( is_array( $t ) && in_array( 'Pressbooks Textbook', $t ) ) {
			return true;
		}
		return false;
	}

	/**
	 * Register all scripts and styles
	 * 
	 * @since 1.0.1
	 */
	function pbtInit() {
		// Register theme directory
		register_theme_directory( PBT_PLUGIN_DIR . 'themes-book' );
		// Add a rewrite rule for the keyword "open"
		add_rewrite_endpoint( 'open', EP_ROOT );
		// Flush, if we haven't already 
		\PBT\Rewrite\flusher();
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
		// @TODO - update timezone and tagline
		// update_option('blogdescription', 'The Open Textbook Project provides flexible and affordable access to higher education resources');

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
	 * Queue child theme
	 * 
	 * @since 1.0.0
	 */
	function enqueueChildThemes() {
		wp_register_style( 'open-textbook', PBT_PLUGIN_URL . 'themes-book/opentextbook/style.css', array( 'pressbooks' ), self::VERSION, 'screen' );
		wp_enqueue_style( 'pressbooks-book' );
		wp_enqueue_style( 'open-textbook' );
	}

	/**
	 * Pressbooks filters allowed themes, this adds our themes to the list
	 * 
	 * @since 1.0.7
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
			return $themes;
		}
	}
	
	/**
	 * This function is added to the PB hook 'pressbooks_new_blog' to add some time
	 * saving customizations
	 * 
	 * @since 1.2.1
	 * @see pressbooks/includes/class-pb-activation.php
	 * 
	 */
	function newBook() {

		$display_copyright = array(
		    'copyright_license' => 1,
		);

		$pdf_options = array(
		    'pdf_page_size' => 3,
		    'pdf_blankpages' => 2,
		);

		$epub_compress_images = array(
		    'ebook_compress_images' => 1
		);
		
//		// set the default theme to opentextbooks
//		switch_theme( 'opentextbook' );
//		check_theme_switched();
//		
//		// safety
//		if ( ( get_option( 'template' ) != 'pressbooks-book' ) || ( get_option( 'stylesheet' ) != 'opentextbook' ) ) {
//			update_option( 'template', 'pressbooks-book' );
//			update_option( 'stylesheet', 'opentextbook' );
//		};

		// send validation logs
		update_option( 'pressbooks_email_validation_logs', 1 );
		
		// set display copyright information to on
		update_option( 'pressbooks_theme_options_global', $display_copyright );

		// choose 'US Letter size' for PDF exports
		update_option( 'pressbooks_theme_options_pdf', $pdf_options );

		// EPUB export - reduce image size and quality 
		update_option( 'pressbooks_theme_options_ebook', $epub_compress_images );
		
		// modify the book description
		update_option( 'blogdescription', __( 'Open Textbook', $this->plugin_slug ) );
	}
	
}

// Prohibit installation if PB is not installed
if ( get_site_option( 'pressbooks-activated' ) ) {
	if ( is_admin() ) {		
		require (dirname( __FILE__ ) . '/admin/class-pbt-textbook-admin.php');
		$pbt = new Admin\TextbookAdmin;		
	} else {
		$pbt = \PBT\Textbook::get_instance();
	}
}

