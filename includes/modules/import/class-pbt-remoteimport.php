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
use PressBooks\Book;

if ( ! isset( $GLOBALS['pressbooks'] ) ) {
	require_once \WP_PLUGIN_DIR . '/pressbooks/pressbooks.php';
}

class RemoteImport extends Html\Xhtml {

	/**
	 * 
	 * @param array $current_import
	 */
	function import( array $current_import ) {
		$parent = 0;
		foreach ( $current_import as $import ) {
			
			// fetch the remote content
			$html = wp_remote_get( $import['file'] );
			
			if( is_wp_error( $html ) ){
				$err = $html->get_error_message();
				error_log( '\PBT\Import\RemoteImport\import() error with wp_remote_get(): ' . $err );
				unset( $html );
				$html['body'] = $err;
			}
			
			$url = parse_url( $import['file'] );
			// get parent directory (with forward slash e.g. /parent)
			$path = dirname( $url['path'] );

			$domain = $url['scheme'] . '://' . $url['host'] . $path;

			// get id (there will be only one)
			$id = array_keys( $import['chapters'] );

			// front-matter, part, chapter, or back-matter
			$post_type = ( isset( $import['type'] ) ) ? $import['type'] : $this->determinePostType( $id[0] );

			// chapter is the exception, needs a post_parent other than 0
			// front-matter, back-matter, parts all have post parent = 0;
			if ( 'chapter' == $post_type ){
				$chapter_parent = $this->getChapterParent();
			} else {
				$chapter_parent = $parent;
			}

			$pid = $this->kneadandInsert( $html['body'], $post_type, $chapter_parent, $domain );
			
			// set variable with Post ID of the last Part
			if ( 'part' == $post_type ){
				$parent = $pid;
			}
			
		}
		// Done
		return Search\ApiSearch::revokeCurrentImport();
	}

	/**
	 * Pummel then insert HTML into our database, separating it from parent class 
	 * to deal with Parts, as well as chapters.
	 *
	 * @param string $href
	 * @param string $post_type
	 * @param int $chapter_parent
	 * @param string $domain domain name of the webpage
	 */
	function kneadandInsert( $html, $post_type, $chapter_parent, $domain ) {
		$matches = array();
		$meta = $this->getLicenseAttribution( $html );
		$author = ( isset( $meta['authors'] )) ? $meta['authors'] : $this->getAuthors( $html );
		$license = ( isset( $meta['license'] )) ? $this->extractCCLicense( $meta['license'] ) : '';

		// get the title, preference to title set by PB
		preg_match( '/<h2 class="entry-title">(.*)<\/h2>/', $html, $matches );
		if ( ! empty( $matches[1] ) ) {
			$title = wp_strip_all_tags( $matches[1] );
		} else {
			preg_match( '/<title>(.+)<\/title>/', $html, $matches );
			$title = ( ! empty( $matches[1] ) ? wp_strip_all_tags( $matches[1] ) : '__UNKNOWN__' );
		}

		// just get the body
		preg_match( '/(?:<body[^>]*>)(.*)<\/body>/isU', $html, $matches );

		// get rid of stuff we don't need
		$body = $this->regexSearchReplace( $matches[1] );

		// clean it up
		$xhtml = $this->tidy( $body );

		$body = $this->kneadHtml( $xhtml, $post_type, $domain );

		$new_post = array(
		    'post_title' => $title,
		    'post_type' => $post_type,
		    'post_status' => ( 'part' == $post_type ) ? 'publish' : 'draft',
		);
		
		// parts are exceptional, content upload needs to be handled by update_post_meta
		if ( 'part' != $post_type ) {
			$new_post['post_content'] = $body;
		}
		// chapters are exceptional, need a chapter_parent
		if ( 'chapter' == $post_type ) {
			$new_post['post_parent'] = $chapter_parent;
		}

		$pid = wp_insert_post( add_magic_quotes( $new_post ) );

		// give parts content if it has some
		if ( 'part' == $post_type && !empty( $body ) ) {
			update_post_meta( $pid, 'pb_part_content', $body );
		}
		
		if( ! empty( $author )){
			update_post_meta( $pid, 'pb_section_author', $author );
		}
		
		if( ! empty( $license ) ){
			update_post_meta( $pid, 'pb_section_license', $license );
		}
		
		update_post_meta( $pid, 'pb_show_title', 'on' );
		update_post_meta( $pid, 'pb_export', 'on' );

		Book::consolidatePost( $pid, get_post( $pid ) ); // Reorder
		
		return $pid;
		
	}
	
	/**
	 * Cherry pick likely content areas, then cull known, unwanted content areas
	 * 
	 * @param string $html
	 * @return string $html
	 */
	protected function regexSearchReplace( $html ) {

		/* cherry pick likely content areas */
		// HTML5, ungreedy
		preg_match( '/(?:<main[^>]*>)(.*)<\/main>/isU', $html, $matches );
		$html = ( ! empty( $matches[1] )) ? $matches[1] : $html;

		// WP content area, greedy
		preg_match( '/(?:<div id="main"[^>]*>)(.*)<\/div>/is', $html, $matches );
		$html = ( ! empty( $matches[1] )) ? $matches[1] : $html;

		// general content area, greedy
		preg_match( '/(?:<div id="content"[^>]*>)(.*)<\/div>/is', $html, $matches );
		$html = ( ! empty( $matches[1] )) ? $matches[1] : $html;

		// specific PB content area, greedy
		preg_match( '/(?:<div class="entry-content"[^>]*>)(.*)<\/div>/is', $html, $matches );
		$html = ( ! empty( $matches[1] )) ? $matches[1] : $html;

		/* cull */
		// get rid of page authors, we replace them anyways
		$result = preg_replace( '/(?:<h2 class="chapter_author"[^>]*>)(.*)<\/h2>/isU', '', $html );
		// get rid of script tags, ungreedy
		$result = preg_replace( '/(?:<script[^>]*>)(.*)<\/script>/isU', '', $html );
		// get rid of forms, ungreedy
		$result = preg_replace( '/(?:<form[^>]*>)(.*)<\/form>/isU', '', $result );
		// get rid of html5 nav content, ungreedy
		$result = preg_replace( '/(?:<nav[^>]*>)(.*)<\/nav>/isU', '', $result );
		// get rid of PB nav, next/previous
		$result = preg_replace( '/(?:<div class="nav"[^>]*>)(.*)<\/div>/isU', '', $result );
		// get rid of PB share buttons
		$result = preg_replace( '/(?:<div class="share-wrap-single"[^>]*>)(.*)<\/div>/isU', '', $result );
		// get rid of html5 footer content, ungreedy
		$result = preg_replace( '/(?:<footer[^>]*>)(.*)<\/footer>/isU', '', $result );
		// get rid of sidebar content, greedy
		$result = preg_replace( '/(?:<div id="sidebar\d{0,}"[^>]*>)(.*)<\/div>/is', '', $result );
		// get rid of comments, greedy
		$result = preg_replace( '/(?:<div id="comments"[^>]*>)(.*)<\/div>/is', '', $result );

		return $result;
	}

	/**
	 * Compliance with XHTML standards, rid cruft generated by word processors
	 *
	 * @param string $html
	 *
	 * @return string $html
	 */
	protected function tidy( $html ) {

		// Reduce the vulnerability for scripting attacks
		// Make XHTML 1.1 strict using htmlLawed

		$config = array(
		    'comment' => 1,
		    'safe' => 1,
		    'valid_xhtml' => 1,
		    'no_deprecated_attr' => 2,
		    'hook' => '\PressBooks\Sanitize\html5_to_xhtml11',
		);

		return htmLawed( $html, $config );
	}

}
