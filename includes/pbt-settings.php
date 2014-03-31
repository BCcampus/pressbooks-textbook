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
 * Provides a simple description for plugins that support redistribution
 * 
 * @since 1.0.2
 */
function redistribute_section_callback() {
	echo "<p>If they exist, one of each of the latest export files (epub, pdf, xhtml, hpub, mobi, wxr, icml) will be available for download on the homepage.</p>"
	. '<figure><img src="' . PBT_PLUGIN_URL . 'admin/assets/img/latest-export-files.png" /><figcaption>The dowload links as they would appear on the homepage.</figcaption></figure>';
}

/**
 * Fields callback
 * 
 * @since 1.0.2
 * @param $args
 */
function latest_files_public_callback( $args ) {
	$options = get_option( 'pbt_redistribute_settings' );

	// add default if not set
	if ( ! isset( $options['latest_files_public'] ) ) {
		$options['latest_files_public'] = 0;
	}

	$html = '<input type="radio" id="files-public" name="pbt_redistribute_settings[latest_files_public]" value="1"' . checked( 1, $options['latest_files_public'], false ) . '/> ';
	$html .= '<label for="files-public"> ' . __( 'Yes. I would like the latest export files to be available on the homepage for free, to everyone.', 'pressbooks-textbook' ) . '</label><br />';
	$html .= '<input type="radio" id="files-admin" name="pbt_redistribute_settings[latest_files_public]" value="0" ' . checked( 0, $options['latest_files_public'], false ) . '/> ';
	$html .= '<label for="files-admin"> ' . __( 'No. I would like the latest export files to only be available to administrators. (PressBooks default)', 'pressbooks-textbook' ) . '</label>';
	echo $html;
}

/**
 * Redistribute Sanitization callback
 * 
 * @since 1.0.1
 * @param array $input
 * @return array
 */
function redistribute_absint_sanitize( $input ) {
	$options = get_option( 'pbt_redistribute_settings' );

	// radio buttons
	foreach ( array( 'latest_files_public' ) as $val ) {
		$options[$val] = absint( $input[$val] );
	}

	return $options;
}

/**
 * Simple description for Other plugins that support textbooks
 * 
 * @since 1.0.2
 */
function pbt_other_section_callback() {
	echo "<p>The Hypothesis plugin by timmmmyboy adds annotation functionality to your book. </p>";
}

/**
 * Fields callback for Hypothesis
 * 
 * @since 1.0.2
 */
function pbt_hypothesis_active_callback() {
	$options = get_option( 'pbt_other_settings' );

	// add default if not set
	if ( ! isset( $options['pbt_hypothesis_active'] ) ) {
		$options['pbt_hypothesis_active'] = 0;
	}

	$html = '<input type="radio" id="hyp-active" name="pbt_other_settings[pbt_hypothesis_active]" value="1" ' . checked( 1, $options['pbt_hypothesis_active'], false ) . '/> ';
	$html .= '<label for="hyp-active"> ' . __( 'Yes. I would like to add annotation functionality to my book pages.', 'pressbooks-textbook' ) . '</label><br />';
	$html .= '<input type="radio" id="hyp-not-active" name="pbt_other_settings[pbt_hypothesis_active]" value="0" ' . checked( 0, $options['pbt_hypothesis_active'], false ) . '/> ';
	$html .= '<label for="hyp-not-active"> ' . __( 'No. I would not like to add annotation functionality.', 'pressbooks-textbook' ) . '</label>';
	echo $html;
}

/**
 * Sanitization callback
 * 
 * @param array $input
 * @return array
 */
function other_absint_sanitize( $input ) {
	$options = get_option( 'pbt_other_settings' );

	// radio buttons
	foreach ( array( 'pbt_hypothesis_active' ) as $val ) {
		$options[$val] = absint( $input[$val] );
	}

	return $options;
}

/**
 * Simple description for Hypothesis
 * 
 * @since 1.0.2
 */
function pbt_reuse_section_callback() {
	echo "<p>The Creative Commons Configurator by George Notaras '<i>adds Creative Commons license information to your posts, pages, attachment pages and feeds.</i>'</p>";
}

/**
 * Fields callback for Creative Commons
 * 
 * @since 1.0.2
 */
function pbt_ccc_active_callback() {
	$options = get_option( 'pbt_reuse_settings' );

	// add default if not set
	if ( ! isset( $options['pbt_creative-commons-configurator-1_active'] ) ) {
		$options['pbt_creative-commons-configurator-1_active'] = 0;
	}

	$html = '<input type="radio" id="ccc-active" name="pbt_reuse_settings[pbt_creative-commons-configurator-1_active]" value="1" ' . checked( 1, $options['pbt_creative-commons-configurator-1_active'], false ) . '/> ';
	$reminder = (true == $options['pbt_creative-commons-configurator-1_active']) ? ' Make sure you <a href="options-general.php?page=cc-configurator-options">configure your license</a>.' : '';
	$html .= '<label for="ccc-active"> ' . __( 'Yes. I would like to add Creative Commons license information to my book pages.', 'pressbooks-textbook' ) . $reminder . '</label><br />';
	$html .= '<input type="radio" id="ccc-not-active" name="pbt_reuse_settings[pbt_creative-commons-configurator-1_active]" value="0" ' . checked( 0, $options['pbt_creative-commons-configurator-1_active'], false ) . '/> ';

	$html .= '<label for="ccc-not-active"> ' . __( 'No. I would not like to add Creative Commons license information to my book pages.', 'pressbooks-textbook' ) . '</label>';

	echo $html;
}

/**
 * Sanitization callback
 * 
 * @since 1.0.2
 * @param array $input
 * @return array
 */
function reuse_absint_sanitize( $input ) {
	$options = get_option( 'pbt_reuse_settings' );

	// radio buttons
	foreach ( array( 'pbt_creative-commons-configurator-1_active' ) as $val ) {
		$options[$val] = absint( $input[$val] );
	}

	return $options;
}
