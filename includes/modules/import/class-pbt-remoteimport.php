<?php
/**
 * Uses the v1/API to search titles on a remote system based on a user defined search term
 * Extends the existing Xthml import class used in PressBooks, the only differences being that we 
 * are sending that class more than one url/page to scrape at a time and we need to revoke 
 * the PBT import rather than the PB import.
 * 
 * @package PressBooks_Textbook
 * @author Brad Payne <brad@bradpayne.ca>
 * @license GPL-2.0+
 * 
 * @copyright 2015 Brad Payne
 */

namespace PBT\Import;

use PBT\Search;
use PressBooks\Import\Html;

require WP_PLUGIN_DIR . '/pressbooks/includes/modules/import/class-pb-import.php';
require WP_PLUGIN_DIR . '/pressbooks/includes/modules/import/html/class-pb-xhtml.php';


class RemoteImport extends Html\Xhtml{
	
	/**
	 * 
	 * @param array $current_import
	 */
	function import( array $current_import ) {
		foreach ( $current_import as $import ) {

			// fetch the remote content
			$html = wp_remote_get( $import['file'] );
			$url = parse_url( $import['file'] );
			// get parent directory (with forward slash e.g. /parent)
			$path = dirname( $url['path'] );

			$domain = $url['scheme'] . '://' . $url['host'] . $path;

			// get id (there will be only one)
			$id = array_keys( $import['chapters'] );

			// front-matter, chapter, or back-matter
			$post_type = $this->determinePostType( $id[0] );
			$chapter_parent = $this->getChapterParent();

			$body = $this->kneadandInsert( $html['body'], $post_type, $chapter_parent, $domain );
		}
		// Done
		return Search\ApiSearch::revokeCurrentImport();
	}
	
	
}