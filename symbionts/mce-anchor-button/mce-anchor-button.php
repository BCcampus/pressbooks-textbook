<?php

/**
 * Anchor Button
 *
 * @package   PressBooks_Textbook
 * @author    Brad Payne <brad@bradpayne.ca>
 * @license   GPL-2.0+
 * @copyright 2014 Brad Payne
 *
 * @wordpress-plugin
 * Plugin Name:       MCE Anchor Button for PressBooks
 * Description:       Adds a button to TinyMCE for an anchor element in PressBooks
 * Version:           1.0.0
 * Author:            Brad Payne
 * Text Domain:       mce-anchor-button
 * License:           GPL-2.0+
 * License URI:       http://www.gnu.org/licenses/gpl-2.0.txt

 */

namespace PBT\Plugins;

class AnchorButton {

	function __construct() {
		// Define plugin constants
		
		// Load translations

		// Hook in our bits
		add_action( 'admin_init', array( $this, 'addFilters' ) );
	}

	function addFilters() {
		
		if ( ! current_user_can( 'edit_posts' ) && ! current_user_can( 'edit_pages' ) ) {
			return;
		}

		if ( get_user_option( 'rich_editing' ) == 'true' ) {
			add_filter( 'mce_external_plugins', array( $this, 'addAnchorButton' ) );
			add_filter( 'mce_buttons_3', array( $this, 'registerAnchorButton' ) );
		}
	}

	/**
	 * Add the script to the mce array
	 * 
	 * @param array $plugin_array	
	 * @return array
	 */
	function addAnchorButton( $plugin_array ) {

		$plugin_array['anchor'] = PBT_PLUGIN_URL . 'symbionts/mce-textbook-buttons/assets/js/anchor.js';
		return $plugin_array;
	}

	/**
	 * Push our button onto the button stack in the 3rd mce row
	 * 
	 * @param type $buttons
	 */
	function registerAnchorButton( $buttons ) {

		array_push( $buttons, 'anchor' );
		return $buttons;
	}
	
	}

$anchor_button = new \PBT\Plugins\AnchorButton();

