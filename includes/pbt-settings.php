<?php

/**
 * Options/Settings for PressBooks Textbook plugin
 * 
 * @package PressBooks_Textbook
 * @author Brad Payne <brad@bradpayne.ca>
 * @license   GPL-2.0+
 * 
 * @copyright 2014 Brad Payne
 */

namespace PBT\Settings;

/**
 * Provides a simple description
 * 
 * @since 1.0.1
 */
function latest_files_section_callback() {
	echo "<p>If they exist, one of each of the latest (epub, pdf, xhtml, hpub, mobi, wxr, icml) export files will be available for download on the homepage.</p>"
	. '<figure><img src="'.PBT_PLUGIN_URL .'admin/assets/img/latest-export-files.png" /><figcaption>The dowload links as they would appear on the homepage.</figcaption></figure>';
}

/**
 * Fields callback
 * 
 * @since 1.0.1
 * @param $args
 */
function latest_files_public_callback( $args ) {
	$files_public = get_option( 'latest_files_public' );

	$html = '<input type="radio" id="files-public" name="latest_files_public" value="1" ';
	if ( $files_public ) $html .= 'checked="checked" ';
	$html .= '/>';
	$html .= '<label for="files-public"> ' . __( 'Yes. I would like the latest export files to be available on the homepage for free, to everyone.', 'pressbooks-textbook' ) . '</label><br />';
	$html .= '<input type="radio" id="files-admin" name="latest_files_public" value="0" ';
	if ( ! $files_public ) $html .= 'checked="checked" ';
	$html .= '/>';
	$html .= '<label for="files-admin"> ' . __( 'No. I would like the latest export files to only be available to administrators. (PressBooks default)', 'pressbooks-textbook' ) . '</label>';
	echo $html;
}

/**
 * Sanitization callback
 * 
 * @since 1.0.1
 * @param $args
 * @return 
 */
function latest_files_public_sanitize( $args ) {
	return absint( $args );
}
