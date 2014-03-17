<?php

/**
 * Rewrite rules for file downloads.
 * *
 * @package PressBooks_Textbook
 * @author Brad Payne <brad@bradpayne.ca>
 * @license   GPL-2.0+
 * 
 * @copyright 2014 Brad Payne
 */

namespace PBT\Rewrite;

function flusher() {
	$pull_the_lever = false;

	$set = get_option( 'pbt_flushed_open' );
	if ( ! $set ) {
		$pull_the_lever = true;
		update_option( 'pbt_flushed_open', true );
	}

	if ( $pull_the_lever ) {
		flush_rewrite_rules( false );
	}
}

/**
 * Display book in a custom format.
 */
function do_open() {

	if ( ! array_key_exists( 'open', $GLOBALS['wp_query']->query_vars ) ) {
		// Don't do anything and return
		return;
	}

	$action = get_query_var( 'open' );

	if ( 'download' == $action ) {
		// Download
		if ( ! empty( $_GET['filename'] ) && ! empty( $_GET['type'] ) ) {
			$filename = sanitize_file_name( $_GET['filename'] );

			switch ( $_GET['type'] ) {
				case 'xhtml':
					$ext = 'html';
					break;
				case 'wxr':
					$ext = 'xml';
					break;
				case 'epub3':
					$ext = '_3.epub';
					break;
				default:
					$ext = $_GET['type'];
					break;
			} 
			
			$filename = $filename . '.' . $ext;
			downloadOpenExportFile( $filename );
		}
	}

	wp_die( __( 'Error: Unknown export format.', 'pressbooks-textbook' ) );
}

function downloadOpenExportFile( $filename ) {

	$filepath = \PressBooks\Export\Export::getExportFolder() . $filename;
	if ( ! is_readable( $filepath ) ) {
		// Cannot read file
		wp_die( __( 'File not found', 'pressbooks-textbook' ) . ": $filename", '', array( 'response' => 404 ) );
	}

	// Force download
	set_time_limit( 0 );
	header( 'Content-Description: File Transfer' );
	header( 'Content-Type: ' . \Pressbooks\Export\Export::mimeType( $filepath ) );
	header( 'Content-Disposition: attachment; filename="' . $filename . '"' );
	header( 'Content-Transfer-Encoding: binary' );
	header( 'Expires: 0' );
	header( 'Cache-Control: must-revalidate, post-check=0, pre-check=0' );
	header( 'Pragma: public' );
	header( 'Content-Length: ' . filesize( $filepath ) );
	@ob_clean();
	flush();
	while ( @ob_end_flush() ); // Fix out-of-memory problem
	readfile( $filepath );

	exit;
}
