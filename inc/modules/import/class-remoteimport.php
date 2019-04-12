<?php

/**
 * Uses the v1/API to search titles on a remote system based on a user defined
 * search term Extends the existing Xthml import class used in Pressbooks, the
 * only differences being that we are sending that class more than one url/page
 * to scrape at a time and we need to revoke the PBT import rather than the PB
 * import.
 *
 * @package Pressbooks_Textbook
 * @author Brad Payne
 * @license GPL-2.0+
 *
 * @copyright 2015 Brad Payne
 */

namespace PBT\Modules\Import;

use PBT\Modules\Search;
use Pressbooks\Book;
use Pressbooks\Modules\Import\Html;

class RemoteImport extends Html\Xhtml {

	/**
	 * @param array $current_import
	 *
	 * @return bool
	 */
	function import( array $current_import ) {
		if ( ! isset( $parent ) ) {
			$parent = 0;
		};

		foreach ( $current_import as $import ) {
			// get id (there will be only one)
			$id    = array_keys( $import['chapters'] );
			$title = $import['chapters'][ $id[0] ];

			// fetch the remote content
			$html = wp_remote_get( $import['file'] );

			$ok = wp_remote_retrieve_response_code( $html );

			if ( absint( $ok ) === 200 && is_wp_error( $html ) === false ) {
				$html = $html['body'];
			} else {
				// Something went wrong, try to log it
				if ( is_wp_error( $html ) ) {
					$error_message = $html->get_error_message();
				} elseif ( is_array( $html ) && ! empty( $html['body'] ) ) {
					$error_message = wp_strip_all_tags( $html['body'] );
				} else {
					$error_message = 'An unknown error occurred';
				}
				error_log( '\PBT\Modules\Import\RemoteImport\import() error with wp_remote_get(): ' . $error_message ); //@codingStandardsIgnoreLine
				continue;
			}

			$url = wp_parse_url( $import['file'] );
			// get parent directory (with forward slash e.g. /parent)
			$path = dirname( $url['path'] );

			$domain = $url['scheme'] . '://' . $url['host'] . $path;

			// front-matter, part, chapter, or back-matter
			$post_type = ( isset( $import['type'] ) ) ? $import['type'] : $this->determinePostType( $id[0] );

			// chapter is the exception, needs a post_parent other than 0
			// front-matter, back-matter, parts all have post parent = 0;
			if ( 'chapter' === $post_type ) {
				$chapter_parent = $this->getChapterParent();
			} else {
				$chapter_parent = $parent;
			}

			$pid = $this->kneadandInsert( $html, $post_type, $chapter_parent, $domain, 'web-only', $title );

			// set variable with Post ID of the last Part
			if ( 'part' === $post_type ) {
				$parent = $pid;
			}
		}

		// Done
		return Search\ApiSearch::revokeCurrentImport();
	}

	/**
	 * Pummel then insert HTML into our database, separating it from parent
	 * class to deal with Parts, as well as chapters.
	 *
	 * @param string $html
	 * @param string $post_type
	 * @param int $chapter_parent
	 * @param string $domain domain name of the webpage
	 * @param string $post_status draft, publish, etc
	 * @param string $title of the post
	 *
	 * @return int|void|\WP_Error
	 */
	function kneadandInsert( $html, $post_type, $chapter_parent, $domain, $post_status = 'web-only', $title = '' ) {
		$matches = [];
		$meta    = $this->getLicenseAttribution( $html );
		$author  = ( isset( $meta['authors'] ) ) ? $meta['authors'] : $this->getAuthors( $html );
		$license = ( isset( $meta['license'] ) ) ? $this->extractCCLicense( $meta['license'] ) : '';

		// get the title, back up
		if ( empty( $title ) ) {
			preg_match( '/<h2 class="entry-title">(.*)<\/h2>/', $html, $matches );
			if ( ! empty( $matches[1] ) ) {
				$title = wp_strip_all_tags( $matches[1] );
			} else {
				preg_match( '/<title>(.+)<\/title>/', $html, $matches );
				$title = ( ! empty( $matches[1] ) ? wp_strip_all_tags( $matches[1] ) : '__UNKNOWN__' );
			}
		}

		// just get the body
		preg_match( '/(?:<body[^>]*>)(.*)<\/body>/isU', $html, $matches );

		// get rid of stuff we don't need
		$body = $this->regexSearchReplace( $matches[1] );

		// clean it up
		$xhtml = $this->tidy( $body );

		$body = $this->kneadHtml( $xhtml, $post_type, $domain );

		$new_post = [
			'post_title'  => $title,
			'post_type'   => $post_type,
			'post_status' => $post_status,
		];

		// parts are exceptional, content upload needs to be handled by update_post_meta
		$new_post['post_content'] = $body;

		// chapters are exceptional, need a chapter_parent
		if ( 'chapter' === $post_type ) {
			$new_post['post_parent'] = $chapter_parent;
		}

		$pid = wp_insert_post( add_magic_quotes( $new_post ) );

		if ( ! empty( $author ) ) {
			update_post_meta( $pid, 'pb_authors', $author );
		}

		if ( ! empty( $license ) ) {
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
	 *
	 * @return string $html
	 */
	protected function regexSearchReplace( $html ) {

		/* cherry pick likely content areas */
		// HTML5, non-greedy
		preg_match_all( '/(?:<main[^>]*>)(.*)<\/main>/isU', $html, $matches, PREG_PATTERN_ORDER );
		$html = ( ! empty( $matches[1][0] ) ) ? $matches[1][0] : $html;

		// general content area, non-greedy
		preg_match_all( '/(?:<div class="ugc"[^>]*>)(.*)<\/div>/isU', $html, $matches, PREG_PATTERN_ORDER );
		$html = ( ! empty( $matches[1][0] ) ) ? $matches[1][0] : $html;

		// general content area, non-greedy
		preg_match_all( '/(?:<div class="ugc front-matter-ugc|chapter-ugc|back-matter-ugc"[^>]*>)(.*)<\/div>/isU', $html, $matches, PREG_PATTERN_ORDER );
		$html = ( ! empty( $matches[1][0] ) ) ? $matches[1][0] : $html;

		// general content area, non-greedy
		preg_match_all( '/(?:<div class="part"[^>]*>)(.*)<\/div>/isU', $html, $matches, PREG_PATTERN_ORDER );
		$html = ( ! empty( $matches[1][0] ) ) ? $matches[1][0] : $html;

		/* cull */
		// get rid of headers
		$result = preg_replace( '/(?:<header[^>]*>)(.*)<\/header>/isU', '', $html );
		// get rid of script tags, ungreedy
		$result = preg_replace( '/(?:<script[^>]*>)(.*)<\/script>/isU', '', $result );
		// get rid of forms, ungreedy
		$result = preg_replace( '/(?:<form[^>]*>)(.*)<\/form>/isU', '', $result );
		// get rid of html5 nav content, ungreedy
		$result = preg_replace( '/(?:<nav[^>]*>)(.*)<\/nav>/isU', '', $result );
		$result = preg_replace( '/(?:<div class="block-reading-meta"[^>]*>)(.*)<\/div>/isU', '', $result );
		$result = preg_replace( '/(?:<div class="section-comments"[^>]*>)(.*)<\/div>/isU', '', $result );

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

		$config = [
			'comment'            => 1,
			'safe'               => 1,
			'valid_xhtml'        => 1,
			'no_deprecated_attr' => 2,
			'hook'               => '\Pressbooks\Sanitize\html5_to_xhtml11',
		];

		return \Pressbooks\HtmLawed::filter( $html, $config );
	}

}
