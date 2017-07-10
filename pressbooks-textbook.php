<?php

/**
 * Pressbooks Textbook
 *
 * @package   Pressbooks_Textbook
 * @author    Brad Payne <brad@bradpayne.ca>
 * @license   GPL-2.0+
 * @copyright 2014 Brad Payne
 *
 * @wordpress-plugin
 * Plugin Name:       Pressbooks Textbook
 * Description:       A plugin that extends Pressbooks for textbook authoring
 * Version:           3.1.0
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
	const VERSION = '3.1.0';

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
	 * If there is a dependency with a specific PB version
	 *
	 * @var string
	 */
	protected $min_pb_compatibility_version = '4.0.0';

	/**
	 * Initialize the plugin by setting localization and loading public scripts
	 * and styles.
	 *
	 * @since 1.0.1
	 */
	private function __construct() {
		// Define plugin constants
		if ( ! defined( 'PBT_PLUGIN_DIR' ) ) {
			define( 'PBT_PLUGIN_DIR', __DIR__ . '/' );
		}
		if ( ! defined( 'PBT_PLUGIN_URL' ) ) {
			define( 'PBT_PLUGIN_URL', plugin_dir_url( __FILE__ ) );
		}

		// Must have trailing slash!
		if ( ! defined( 'PB_PLUGIN_DIR' ) ) {
			define( 'PB_PLUGIN_DIR', WP_PLUGIN_DIR . '/pressbooks/' );
		}

		// Allow override in wp-config.php
		if ( ! defined( 'WP_DEFAULT_THEME' ) ) {
			define( 'WP_DEFAULT_THEME', 'opentextbook' );
		};

		// Hide PB cover promotion
		define( 'PB_HIDE_COVER_PROMO', true );

		// Load translations
		add_action( 'init', array( $this, 'loadPluginTextDomain' ) );
		// Check Compatibility with PB
		add_action( 'init', array( $this, 'pbCompatibility' ) );

		// Setup our activation and deactivation hooks
		register_activation_hook( __FILE__, array( $this, 'activate' ) );
		register_deactivation_hook( __FILE__, array( $this, 'deactivate' ) );

		// Hook in our pieces
		add_action( 'plugins_loaded', array( $this, 'includes' ) );
		add_action( 'pressbooks_register_theme_directory', array( $this, 'pbtInit' ) );
		add_action( 'wp_enqueue_style', array( $this, 'registerChildThemes' ) );
		add_action( 'wp_enqueue_scripts', array( $this, 'enqueueScriptsnStyles' ) );
		add_filter( 'allowed_themes', array( $this, 'filterChildThemes' ), 11 );
		add_action( 'pressbooks_new_blog', array( $this, 'newBook' ) );
		add_filter( 'pb_publisher_catalog_query_args', array( $this, 'rootThemeQuery' ) );

		$this->update();

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
			'mce-textbook-buttons/mce-textbook-buttons.php'       => 1,
			'creative-commons-configurator-1/cc-configurator.php' => 1,
			'hypothesis/hypothesis.php'                           => 1,
			'tinymce-spellcheck/tinymce-spellcheck.php'           => 1,
		);

		$pbt_plugin = $this->filterPlugins( $pbt_plugin );

		// include plugins
		if ( ! empty( $pbt_plugin ) ) {
			foreach ( $pbt_plugin as $key => $val ) {
				require_once( PBT_PLUGIN_DIR . 'symbionts/' . $key );
			}
		}
	}

	/**
	 * Filters out active plugins, to avoid collisions with plugins already installed
	 *
	 * @since 1.0.8
	 *
	 * @param array $pbt_plugin
	 *
	 * @return array
	 */
	private function filterPlugins( $pbt_plugin ) {
		$already_active         = get_option( 'active_plugins' );
		$network_already_active = get_site_option( 'active_sitewide_plugins' );

		// activate only if one of our themes is being used
		if ( false == self::isTextbookTheme() ) {
			unset( $pbt_plugin['mce-textbook-buttons/mce-textbook-buttons.php'] );
			unset( $pbt_plugin['hypothesis/hypothesis.php'] );
			unset( $pbt_plugin['creative-commons-configurator-1/cc-configurator.php'] );
			unset( $pbt_plugin['tinymce-spellcheck/tinymce-spellcheck.php'] );
		}

		// don't include plugins already active at the site level, network level
		if ( ! empty( $pbt_plugin ) ) {
			foreach ( $pbt_plugin as $key => $val ) {
				if ( in_array( $key, $already_active ) || array_key_exists( $key, $network_already_active ) ) {
					unset( $pbt_plugin[ $key ] );
				}

			}
		}

		// don't include plugins if the user doesn't want them
		if ( ! empty( $pbt_plugin ) ) {

			// get user options
			$user_options = $this->getUserOptions();

			if ( is_array( $user_options ) ) {
				foreach ( $pbt_plugin as $key => $val ) {

					$name       = strstr( $key, '/', true );
					$pbt_option = "pbt_" . $name . "_active";

					// either it doesn't exist, or the client doesn't want it
					if ( array_key_exists( $pbt_option, $user_options ) ) {
						// check the value
						if ( false == $user_options[ $pbt_option ] ) {
							unset( $pbt_plugin[ $key ] );
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
		load_plugin_textdomain( $domain, false, basename( plugin_dir_path( dirname( __FILE__ ) ) ) . '/languages/' );
	}

	/**
	 * Queue child theme
	 *
	 * @since 1.0.0
	 */
	function registerChildThemes() {
		wp_register_style( 'open-textbook', PBT_PLUGIN_URL . 'themes-book/opentextbook/style.css', array( 'pressbooks' ), self::VERSION, 'screen' );
	}

	/**
	 * Pressbooks filters allowed themes, this adds our themes to the list
	 *
	 * @since 1.0.7
	 *
	 * @param array $themes
	 *
	 * @return array
	 */
	function filterChildThemes( $themes ) {
		$pbt_themes = array();

		if ( \Pressbooks\Book::isBook() ) {
			$registered_themes = search_theme_directories();

			foreach ( $registered_themes as $key => $val ) {
				if ( $val['theme_root'] == PBT_PLUGIN_DIR . 'themes-book' ) {
					$pbt_themes[ $key ] = 1;
				}
			}
			// add our theme
			$themes = array_merge( $themes, $pbt_themes );

			return $themes;
		} else {
			return $themes;
		}
	}

	function enqueueScriptsnStyles() {
		wp_enqueue_style( 'jquery-ui', '//code.jquery.com/ui/1.12.0/themes/base/jquery-ui.css', '', self::VERSION, 'screen, print' );
		wp_enqueue_script( 'jquery-ui-tabs', '/wp-includes/js/jquery/ui/jquery.ui.tabs.min.js' );
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
			'pdf_page_size'  => 3,
			'pdf_blankpages' => 2,
		);

		$epub_compress_images = array(
			'ebook_compress_images' => 1
		);

		$redistribute_files = array(
			'latest_files_public' => 1,
		);

		$web_options = array(
			'part_title' => 1,
		);

		// Allow for override in wp-config.php
		if ( 0 === strcmp( 'opentextbook', WP_DEFAULT_THEME ) || ! defined( 'WP_DEFAULT_THEME' ) ) {

			// set the default theme to opentextbooks
			switch_theme( 'opentextbook' );

			// safety
			update_option( 'stylesheet_root', '/plugins/pressbooks-textbook/themes-book' );
			update_option( 'template', 'pressbooks-book' );
			update_option( 'stylesheet', 'opentextbook' );
		}

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

		// redistribute latest exports
		update_option( 'pbt_redistribute_settings', $redistribute_files );

		// web theme options
		update_option( 'pressbooks_theme_options_web', $web_options );
	}

	/**
	 * Triggers a recompile of sass to css required for a switch to sassified theme
	 * modified/borrowed from Pressbooks pressbooks_update_webbook_stylesheet()
	 *
	 */
	function updateWebbookStylesheet() {
		foreach ( glob( get_stylesheet_directory() . '/assets/styles/components/*.scss' ) as $import ) {
			$inputs[] = realpath( $import );
		}

		$output    = get_stylesheet_directory_uri() . '/style.css';
		$recompile = false;
		foreach ( $inputs as $input ) {
			if ( filemtime( $input ) > filemtime( $output ) ) {
				$recompile = true;
				break;
			}
		}
		if ( true == $recompile ) {
			error_log( 'Updating web book stylesheet.' );
			\Pressbooks\Container::get( 'Sass' )->updateWebBookStyleSheet();
		} else {
			error_log( 'No update needed.' );
		}

	}

	/**
	 * Pass additional arguments to Publisher Root theme catalogue page
	 * @return array
	 */
	function rootThemeQuery() {
		return array(
			'number'  => 150,
			'orderby' => 'last_updated',
			'order'   => 'DESC',
		);
	}

	/**
	 * Perform site and network option updates
	 * to keep up with a moving target
	 */
	private function update() {
		// Set once, check and update network settings
		$network_version = get_site_option( 'pbt_version', 0, false );
		$pb_version      = get_site_option( 'pbt_pb_version' );

		// triggers a network event with every new PBT Version
		if ( version_compare( $network_version, self::VERSION ) < 0 ) {
			update_site_option( 'pressbooks_sharingandprivacy_options', array( 'allow_redistribution' => 1 ) );
			update_site_option( 'pbt_version', self::VERSION );
		}

		// triggers a site event with every new PBT Version
		$site_version = get_option( 'pbt_version', 0, false );

		if ( version_compare( $site_version, self::VERSION ) < 0 ) {
			add_action( 'template_redirect', array( $this, 'updateWebbookStylesheet' ) );
			update_option( 'pbt_version', self::VERSION );
		}

		// triggers a site event once for version 3.0.1
		if ( version_compare( '3.0.1', self::VERSION ) == 0 ) {
			$part_title = array(
				'part_title' => 1,
			);
			update_option( 'pressbooks_theme_options_web', $part_title );
		}

		// triggers on version update to 4.0, deals with breaking change
		// runs once
		$once = get_site_option( 'pbt_update_template_root', 0 );
		if ( version_compare( $pb_version, '4.0.0' ) >= 0 && $once === 0 ) {
			$count = [ 'count' => true ];
			$limit = get_sites( $count );
			// avoid the default maximum of 100
			$number = [ 'number' => $limit ];
			$sites  = get_sites( $number );

			// update all sites
			foreach ( $sites as $site ) {
				switch_to_blog( $site->blog_id );
				$root  = get_option( 'template_root' );
				$theme = get_option( 'stylesheet' );
				if ( strcmp( $root, '/plugins/pressbooks/themes-book' ) == 0 && strcmp( $theme, 'opentextbook' ) == 0 ) {
					update_option( 'template_root', '/themes' );
				}
				restore_current_blog();
			}
			update_site_option( 'pbt_update_template_root', 1 );
		}

	}


	/**
	 * Must meet miniumum requirements before either PB or PBT objects are instantiated
	 *
	 */
	function pbCompatibility() {
		if ( ! @include_once( WP_PLUGIN_DIR . '/pressbooks/compatibility.php' ) ) {
			add_action( 'admin_notices', function () {
				echo '<div id="message" class="error fade"><p>' . __( 'PBT cannot find a Pressbooks install.', $this->plugin_slug ) . '</p></div>';
			} );

		} elseif ( ! pb_meets_minimum_requirements() ) { // This PB function checks for both multisite, PHP and WP minimum versions.
			add_action( 'admin_notices', function () {
				echo '<div id="message" class="error fade"><p>' . __( 'Your PHP version may not be supported by PressBooks.'
				                                                      . ' If you suspect this is the case, it can be overridden, so long as it is remains above PHP 5.4.0. Add a line to wp-config.php as follows: $pb_minimum_php = "5.4.0"; ', $this->plugin_slug ) . '</p></div>';
			} );

		} elseif ( ! version_compare( PB_PLUGIN_VERSION, $this->min_pb_compatibility_version, '>=' ) ) {
			add_action( 'admin_notices', function () {
				echo '<div id="message" class="error fade"><p>' . __( 'PB Textbook requires Pressbooks 4.0.0 or greater.', $this->plugin_slug ) . '</p></div>';
			} );
		}
		// need version number outside of init hook
		update_site_option( 'pbt_pb_version', PB_PLUGIN_VERSION );

	}

}


if ( get_site_option( 'pressbooks-activated' ) ) {
	if ( is_admin() ) {
		require( dirname( __FILE__ ) . '/admin/class-pbt-textbook-admin.php' );
		$pbt = new Admin\TextbookAdmin;
	} else {
		$pbt = \PBT\Textbook::get_instance();
	}
}
