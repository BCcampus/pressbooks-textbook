<?php
/**
 * Project: pressbooks
 * Project Sponsor: BCcampus <https://bccampus.ca>
 * Copyright 2012-2017 Brad Payne <https://github.com/bdolor>
 * Date: 2017-09-01
 * Licensed under GPLv3, or any later version
 *
 * @author Brad Payne
 * @package OPENTEXTBOOKS
 * @license https://www.gnu.org/licenses/gpl-3.0.txt
 * @copyright (c) 2012-2017, Brad Payne
 */

namespace PBT;

use Pressbooks\Book;

class Textbook {

	/**
	 * Plugin version, used for cache-busting of style and script file references.
	 *
	 * @since 1.0.0
	 * @var string
	 */
	const VERSION = '4.3.1';

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

		// Load translations
		add_action( 'init', [ $this, 'loadPluginTextDomain' ] );

		// Set up our activation/deactivation hooks
		register_activation_hook( __FILE__, [ $this, 'activate' ] );
		register_deactivation_hook( __FILE__, [ $this, 'deactivate' ] );

		// Hook in our pieces
		add_action( 'plugins_loaded', [ $this, 'includes' ] );
		add_action( 'pressbooks_register_theme_directory', [ $this, 'pbtInit' ] );
		add_action( 'wp_enqueue_style', [ $this, 'registerChildThemes' ] );
		add_filter( 'allowed_themes', [ $this, 'filterChildThemes' ], 11 );
		add_action( 'pressbooks_new_blog', [ $this, 'newBook' ] );

		$this->update();

		wp_cache_add_global_groups( [ 'pbt' ] );

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
		if ( null === self::$instance ) {
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
		$pbt_plugin = [
			'mce-textbook-buttons/class-textbookbuttons.php' => 1,
			'tinymce-spellcheck/tinymce-spellcheck.php'      => 1,
		];

		$pbt_plugin = $this->filterPlugins( $pbt_plugin );

		// include plugins
		if ( ! empty( $pbt_plugin ) ) {
			foreach ( $pbt_plugin as $key => $val ) {
				if ( file_exists( PBT_PLUGIN_DIR . 'symbionts/' . $key ) ) {
					require_once( PBT_PLUGIN_DIR . 'symbionts/' . $key );
				}
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
		if ( false === self::isTextbookTheme() ) {
			unset( $pbt_plugin['mce-textbook-buttons/class-textbookbuttons.php'] );
			unset( $pbt_plugin['tinymce-spellcheck/tinymce-spellcheck.php'] );
		}

		// don't include plugins already active at the site level, network level
		if ( ! empty( $pbt_plugin ) ) {
			foreach ( $pbt_plugin as $key => $val ) {
				if ( in_array( $key, $already_active, true ) || array_key_exists( $key, $network_already_active ) ) {
					unset( $pbt_plugin[ $key ] );
				}
			}
		}

		return $pbt_plugin;
	}

	/**
	 * Checks to see if a PBT compatible theme is active
	 *
	 * @param \WP_Theme|null $obj
	 *
	 * @return bool
	 */
	static function isTextbookTheme( \WP_Theme $obj = null ) {
		if ( is_object( $obj ) ) {
			$style = $obj->get_stylesheet();
		}
		$t = ( null === $obj ) ? wp_get_theme()->Tags : wp_get_theme( $style )->Tags;
		if ( is_array( $t ) && in_array( 'Textbooks for Pressbooks', $t, true ) ) {
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
	 * @return   string slug variable.
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
		wp_register_style( 'open-textbook', PBT_PLUGIN_URL . 'themes-book/opentextbook/style.css', [ 'pressbooks' ], self::VERSION, 'screen' );
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
		$pbt_themes = [];

		if ( Book::isBook() ) {
			$registered_themes = search_theme_directories();

			foreach ( $registered_themes as $key => $val ) {
				if ( $val['theme_root'] === PBT_PLUGIN_DIR . 'themes-book' ) {
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

	/**
	 * This function is added to the PB hook 'pressbooks_new_blog' to add some time
	 * saving customizations
	 *
	 * @since 1.2.1
	 * @see pressbooks/includes/class-pb-activation.php
	 *
	 */
	function newBook() {

		$display_copyright = [
			'copyright_license' => 1,
		];

		$pdf_options = [
			'pdf_page_size'  => 3,
			'pdf_blankpages' => 2,
		];

		$epub_compress_images = [
			'ebook_compress_images' => 1,
		];

		$redistribute_files = [
			'latest_files_public' => 1,
		];

		$web_options = [
			'part_title' => 1,
		];

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
		update_option( 'blogdescription', __( 'Open Textbook', 'pressbooks-textbook' ) );

		// redistribute latest exports
		update_option( 'pbt_redistribute_settings', $redistribute_files );

		// web theme options
		update_option( 'pressbooks_theme_options_web', $web_options );
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
			// network and sharing options
			update_site_option(
				'pressbooks_sharingandprivacy_options', [
					'allow_redistribution' => 1,
					'enable_network_api'   => 1,
					'enable_cloning'       => 1,
				]
			);

			update_site_option( 'pbt_version', self::VERSION );
		}

		// triggers a site event with every new PBT Version
		$site_version = get_option( 'pbt_version', 0, false );

		if ( version_compare( $site_version, self::VERSION ) < 0 ) {
			update_option( 'pbt_version', self::VERSION );
		}

		// triggers a site event once for version 3.0.1
		if ( version_compare( '3.0.1', self::VERSION ) === 0 ) {
			$part_title = [
				'part_title' => 1,
			];
			update_option( 'pressbooks_theme_options_web', $part_title );
		}

		// triggers on version update to 4.0, deals with breaking change
		// runs once
		$once = get_site_option( 'pbt_update_template_root', 0 );
		if ( version_compare( $pb_version, '4.0.0' ) >= 0 && $once === 0 ) {
			$count = [
				'count' => true,
			];
			$limit = get_sites( $count );
			// avoid the default maximum of 100
			$number = [
				'number' => $limit,
			];
			$sites  = get_sites( $number );

			// update all sites
			foreach ( $sites as $site ) {
				switch_to_blog( $site->blog_id );
				$root  = get_option( 'template_root' );
				$theme = get_option( 'stylesheet' );
				if ( strcmp( $root, '/plugins/pressbooks/themes-book' ) === 0 && strcmp( $theme, 'opentextbook' ) === 0 ) {
					update_option( 'template_root', '/themes' );
				}
				restore_current_blog();
			}
			update_site_option( 'pbt_update_template_root', 1 );
		}

	}

}
