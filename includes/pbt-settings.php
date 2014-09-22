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
 * Simple description for Creative Commons License
 * 
 * @since 1.0.2
 */
function pbt_reuse_section_callback() {
	echo "<p>Give people the right to share, or build upon your work by using a <a target='_blank' href='https://creativecommons.org/about'>Creative Commons license</a>.</p>"
	. "<h3>Two easy steps, using built-in functionality (<i>recommended</i>)</h3>"
	. "<ol><li>Select your 'Copyright license' from the dropdown menu in the Copyright section on the <a href='wp-admin/post-new.php?post_type=metadata'>Book Info</a> page.</li>"
	. "<li>Check the box labelled 'Display the copyright license' in Appearance -> <a href='wp-admin/themes.php?page=pressbooks_theme_options'>Theme Options</a></li></ol>"
	. "<h4>We recommend using the built-in Creative Commons License Module because it's fast, flexible and thorough.</h4><h5>Details:</h5><ul>"
	. "<li>Information about the license you select makes it through all the export routines:"
	. "<ul>"
	. "<li>PDF, HPUB output - adds to copyright page, and if there are section/page licenses, to the TOC (like section author)</li>
		<li>EPUB/EPUB3 output -adds to copyright page and metadata in OPF</li>
		<li>ICML - to the title page</li>
		<li>XML - as part of post_metadata</li>"
	. "</ul></li>"
	. "<li>The license information is searchable; it contains machine readable metadata.</li>"
	. "<li>The module is made specifically for PressBooks!</li>"
	. "<li>You can specify page license (if it is different than your book license). A page license can override the book license, in a similar fashion to a page author overriding the book author.</li>"
	. "<li>It uses the <a target='_blank' href='https://api.creativecommons.org/docs/readme_15.html'>webservice API</a> that Creative Commons supplies.</li>"
	. "<li>It comes with some language capabilities (depending on what Language you've defined in 'Book Info' and what the API supports.</li>"
	. "<li>The WP transients API was used to leverage caching and minimize calls to the Creative Commons API. The cache gets updated if any of the title, section author or section license is modified by the user.</li>
	<li>The web output places the license information in the footer of each web page.</li>
	</ul>"
	. "<h5>The second license option (below) does not contain all of the above mentioned features, however the Creative Commons Configurator will display a license of your choosing (only on the web version of your book)</h5>"
	. "<hr>";
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

	$html = "<p>The Creative Commons Configurator by George Notaras '<i>adds Creative Commons license information to your posts, pages, attachment pages and feeds.</i>'</p>";	
	$html .= '<input type="radio" id="ccc-active" name="pbt_reuse_settings[pbt_creative-commons-configurator-1_active]" value="1" ' . checked( 1, $options['pbt_creative-commons-configurator-1_active'], false ) . '/> ';
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
