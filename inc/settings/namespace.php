<?php

/**
 * Options/Settings for Textbooks for Pressbooks plugin
 *
 * @package Pressbooks_Textbook
 * @author Brad Payne
 * @license   GPL-2.0+
 *
 * @copyright Brad Payne
 */

namespace PBT\Settings;

/**
 *
 */
function remix_section_callback() {
	echo "<p>If you know of another Pressbooks instance, and you know they also have Creative Commons licensed materials, here is where you add their domain. 
Having a list of domains will enable <a href='admin.php?page=api_search_import'>searching and importing</a> against their collection, the same way that you can search and import against your own collection.</p>";
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
			$html .= '<input id="' . $key . '" disabled="true" class="regular-text highlight" type="url" name="pbt_remix_settings[pbt_api_endpoints][' . $key . ']" value="' . $endpoint . '" />
			<input onclick="addRow(this.form);" type="button" value="Add URL" />';

			// hidden value, because disabled inputs don't make it to $_POST
			$html .= '<input type="hidden" name="pbt_remix_settings[pbt_api_endpoints][0]" value="' . network_home_url() . '"/>';
		} else {
			$html .= '<tr class="endpoints-' . $key . '">
			<th>' . $key . '</th>
			<td><input id="' . $key . '" class="regular-text highlight" type="url" name="pbt_remix_settings[pbt_api_endpoints][' . $key . ']" value="' . $endpoint . '" />
			<input type="button" value="Add URL" onclick="addRow();" /><input type="button" value="Remove URL" onclick="removeRow(' . $key . ')" /></td></tr>';
		}
	}

	echo $html;
}

/**
 * @param $input
 *
 * @return array
 */
function remix_url_sanitize( $input ) {
	$protocols   = [ 'http', 'https' ];
	$i           = 0;
	$valid       = [];
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
	if ( network_home_url() !== $valid['pbt_api_endpoints'][0] ) {
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
