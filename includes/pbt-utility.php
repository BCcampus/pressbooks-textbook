<?php
/**
 * Utility functions particular to PBT
 *
 * @package PressBooks_Textbook
 * @author Brad Payne <brad@bradpayne.ca>
 * @license   GPL-2.0+
 * 
 * @copyright 2014 Brad Payne
 */

namespace PBT\Utility;

/**
 * Scan the export directory, return latest of each file type
 * 
 * @return array 
 */
function latest_exports() {
	$suffix = array(
	    '._3.epub',
	    '.epub',
	    '.pdf',
	    '.mobi',
	    '.hpub',
	    '.icml',
	    '.html',
	    '.xml',
	    '._vanilla.xml',
	    '._oss.pdf',
	);

	$dir = \PressBooks\Export\Export::getExportFolder();

	$files = array();

	// group by extension, sort by date newest first 
	foreach ( \PressBooks\Utility\scandir_by_date( $dir ) as $file ) {
		// only interested in the part of filename starting with the timestamp
		preg_match( '/-\d{10,11}(.*)/', $file, $matches );
		// grab the first captured parenthisized subpattern
		$ext = $matches[1];
		$files[$ext][] = $file;
	}

	// get only one of the latest of each type
	$latest = array();

	foreach ( $suffix as $value ) {
		if ( array_key_exists( $value, $files ) ) {
			$latest[$value] = $files[$value][0];
		}
	}
	// @TODO filter these results against user prefs

	return $latest;
}
