<?php

/**
 * Textbook Buttons
 *
 * @package   PressBooks_Textbook
 * @author    Brad Payne <brad@bradpayne.ca>
 * @license   GPL-2.0+
 * @copyright 2014 Brad Payne
 *
 * @wordpress-plugin
 * Plugin Name:       MCE Textbook Buttons for PressBooks
 * Description:       Adds buttons to TinyMCE for textbook specific sytles in PressBooks
 * Version:           1.0.0
 * Author:            Brad Payne
 * Text Domain:       mce-textbook-buttons
 * License:           GPL-2.0+
 * License URI:       http://www.gnu.org/licenses/gpl-2.0.txt

 */

namespace PBT\Plugins;

class TextbookButtons {

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
			add_filter( 'mce_external_plugins', array( $this, 'addTextbookButtons' ) );
			add_filter( 'mce_buttons_3', array( $this, 'registerTBButtons' ) );
			add_filter( 'mce_css', array( $this, 'textbookStyles' ) );
		}
	}

	/**
	 * Add the script to the mce array
	 * 
	 * @param array $plugin_array	
	 * @return array
	 */
	function addTextbookButtons( $plugin_array ) {

		$plugin_array['textbookbuttons'] = PBT_PLUGIN_URL . 'symbionts/mce-textbook-buttons/assets/js/textbook-buttons.js';
		$plugin_array['anchor'] = PBT_PLUGIN_URL . 'symbionts/mce-textbook-buttons/assets/js/anchor.js';
		return $plugin_array;
	}

	/**
	 * Push our buttons onto the buttons stack in the 3rd mce row
	 * 
	 * @param type $buttons
	 */
	function registerTBButtons( $buttons ) {

		array_push( $buttons, 'learningObjectives', 'keyTakeaway', 'exercises', 'anchor' );
		return $buttons;
	}

	/**
	 * Add editor styles
	 * 
	 * @param string $mce_css
	 * @return string
	 */
	function textbookStyles( $mce_css ) {

		if ( ! empty( $mce_css ) ) $mce_css .= ',';
		$mce_css .= PBT_PLUGIN_URL . 'symbionts/mce-textbook-buttons/assets/css/editor-style.css';

		return $mce_css;
	}
	
	}

$textbook_buttons = new \PBT\Plugins\TextbookButtons();

