<?php

/**
 * Options/Settings for Pressbooks Textbook plugin
 *
 * @package Pressbooks_Textbook
 * @author Brad Payne
 * @license   GPL-2.0+
 *
 * @copyright 2014 Brad Payne
 */

namespace PBT\Settings;

/**
 * Simple description for Other plugins that support textbooks
 *
 * @since 1.0.2
 */
function pbt_other_section_callback() {
	echo '<p>The Hypothesis plugin by timmmmyboy adds annotation functionality to your book. </p>';
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
 *
 * @return array
 */
function other_absint_sanitize( $input ) {
	$options = get_option( 'pbt_other_settings' );

	// radio buttons
	foreach ( array( 'pbt_hypothesis_active' ) as $val ) {
		$options[ $val ] = absint( $input[ $val ] );
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
		 . '<h3>Two easy steps, using built-in functionality (<i>recommended</i>)</h3>'
		 . "<ol><li>Select your 'Copyright license' from the dropdown menu in the Copyright section on the <b>Book Info</b> page. (menu on the left)</li>"
		 . "<li>Check the box labelled 'Display the copyright license' in Appearance -> <a href='wp-admin/themes.php?page=pressbooks_theme_options'>Theme Options</a></li></ol>";
}

/**
 *
 */
function remix_section_callback() {
	echo '<p>If you know of another Pressbooks instance, and you know they also have Creative Commons licensed materials, here is where you add their domain.'
		 . " Having a list of domains will enable <a href='admin.php?page=api_search_import'>searching and importing</a> against their collection, the same way that you can search and import against your own collection.</p>";

}

/**
 *
 */
function api_endpoint_public_callback() {
	$options = get_option( 'pbt_remix_settings' );

	// add default if not set
	if ( ! isset( $options['pbt_api_endpoints'] ) ) {
		$options['pbt_api_endpoints'][0] = network_home_url();
	}

	$html = '';

	foreach ( $options['pbt_api_endpoints'] as $key => $endpoint ) {
		if ( 0 === $key ) {
			$html .= '<input id="' . $key . '" disabled="true" class="regular-text highlight" type="url" name="pbt_remix_settings[pbt_api_endpoints][' . $key . ']" value="' . $endpoint . '" />'
					 . '<input onclick="addRow(this.form);" type="button" value="Add URL" />';

			// hidden value, because disabled inputs don't make it to $_POST
			$html .= '<input type="hidden" name="pbt_remix_settings[pbt_api_endpoints][0]" value="' . network_home_url() . '"/>';
		} else {
			$html .= '<tr class="endpoints-' . $key . '">'
					 . '<th>' . $key . '</th>'
					 . '<td><input id="' . $key . '" class="regular-text highlight" type="url" name="pbt_remix_settings[pbt_api_endpoints][' . $key . ']" value="' . $endpoint . '" />'
					 . '<input type="button" value="Add URL" onclick="addRow();" /><input type="button" value="Remove URL" onclick="removeRow(' . $key . ');" /></td></tr>';
		}
	}

	echo $html;
}

/**
 *
 * @param type $input
 *
 * @return type
 */
function remix_url_sanitize( $input ) {
	$protocols   = array( 'http', 'https' );
	$i           = 0;
	$valid       = array();
	$success_msg = 'Settings saved. <a href="admin.php?page=api_search_import">Ready to search and import?</a>';

	// get rid of blank input, sanitize url
	foreach ( $input['pbt_api_endpoints'] as $key => $url ) {
		if ( empty( $url ) ) {
			continue;
		}
		// sanitize, reset the key to maintain sequential numbering, to account for blank entries
		$valid['pbt_api_endpoints'][ $i ] = trailingslashit( esc_url( $url, $protocols ) );

		// check if they are API enabled Pressbooks instances:
		$api_endpoint = $valid['pbt_api_endpoints'][ $i ] . 'api/v1/books/';

		$check_response = wp_remote_head( $api_endpoint );
		$code           = wp_remote_retrieve_response_code( $check_response );

		if ( $code > 400 ) {
			$msg = $code . ' returned for ' . $api_endpoint . ' — the domain you tried to add did not return a response code we can not work with.';
			add_settings_error(
				'pbt_remix_settings',
				'settings_updated',
				$msg,
				'error'
			);
			// jankified, so discard
			unset( $valid['pbt_api_endpoints'][ $i ] );
		}

		$i ++;
	}

	// before returning, force this PB instance to be preserved
	if ( network_home_url() != $valid['pbt_api_endpoints'][0] ) {
		$valid['pbt_api_endpoints'][0] = network_home_url();
	}

	add_settings_error(
		'pbt_remix_settings',
		'settings_updated',
		$success_msg,
		'updated'
	);

	return $valid;
}
