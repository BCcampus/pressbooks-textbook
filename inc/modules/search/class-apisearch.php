<?php

/**
 * Searches the API for resources, returns results to an import interface
 *
 * @package Pressbooks_Textbook
 * @author Brad Payne
 * @license   GPL-2.0+
 *
 * @copyright Brad Payne
 */

namespace PBT\Modules\Search;

use PBT\Modules\Import;
use Pressbooks\Book;
use Pressbooks\Redirect;

/**
 * Description of class-pb-apisearch
 *
 * @author bpayne
 */
class ApiSearch {

	/**
	 * API version number
	 *
	 * @var string
	 */
	private static $version = 'v1';

	/**
	 * User defined search terms
	 *
	 * @var string
	 */
	private static $search_terms = '';

	/**
	 *
	 */
	public function __construct() {

	}

	/**
	 *
	 */
	static function formSubmit() {

		// evaluate POST DATA
		if ( false === static::isFormSubmission() || false === current_user_can( 'edit_posts' ) ) {
			return;
		}

		$redirect_url   = get_bloginfo( 'url' ) . '/wp-admin/admin.php?page=api_search_import';
		$current_import = get_option( 'pbt_current_import' );

		// determine stage of import, revoke if necessary
		if ( isset( $_GET['revoke'] ) && 1 == $_GET['revoke'] && check_admin_referer( 'pbt-revoke-import' ) ) { //@codingStandardsIgnoreLine
			self::revokeCurrentImport();
			Redirect\location( $redirect_url );
		}

		// do chapter import if that's where we're at
		if ( isset( $_GET['import'] ) && isset( $_POST['chapters'] ) && is_array( $_POST['chapters'] ) && is_array( $current_import ) && check_admin_referer( 'pbt-import' ) ) {

			$keys  = array_keys( $_POST['chapters'] );
			$books = [];

			// Comes in as:
			/** Array (
			 *  [103] => Array(
			 *  [import] => 1
			 *  [book] => 6
			 *  [license] =>
			 *  [author] => bpayne
			 *  [type] => chapter
			 *    )
			 *  )
			 */
			foreach ( $keys as $id ) {
				if ( ! Import\PBImport::flaggedForImport( $id ) ) {
					continue;
				}

				// set the post_id and type
				$chapter[ $id ]['type']    = $_POST['chapters'][ $id ]['type'];
				$chapter[ $id ]['license'] = $_POST['chapters'][ $id ]['license'];
				$chapter[ $id ]['author']  = $_POST['chapters'][ $id ]['author'];
				$chapter[ $id ]['link']    = $_POST['chapters'][ $id ]['link'];

				// add it to the blog_id to which it belongs
				$books[ $_POST['chapters'][ $id ]['book'] ][ $id ] = $chapter[ $id ];
			}

			/**
			 * Modified as:
			 *
			 * Array(
			 *  [103] => Array (
			 *  [6] => Array(
			 *  [type] => chapter
			 *  [license] => cc-by
			 *  [author] => Brad Payne
			 *  [link] => http://opentextbc.ca/modernphilosophy/chapter/background-to-modern-philosophy/
			 *  )
			 * )
			 *
			 */

			// Decide which import local/remote, evaluate the domain
			$host  = wp_parse_url( network_site_url(), PHP_URL_HOST );
			$local = strcmp( $_POST['domain'], $host );

			// local import
			if ( 0 === $local ) {
				$importer = new Import\PBImport();
				$ok       = $importer->import( $books );
			} else { // do something remote
				/**
				 * take $books array, convert it into something that xhtml import can use
				 * must return something like this:
				 *
				 * Array
				 *  (
				 *  [file] => http://opentextbc.ca/modernphilosophy/chapter/background-to-modern-philosophy/
				 *  [file_type] => text/html
				 *  [type_of] => html
				 *  [chapters] => Array
				 *  (
				 *      [1] => Background to Modern Philosophy | Modern Philosophy
				 *   )
				 *  )
				 */
				foreach ( $books as $book => $chapters ) {
					// more than 1 chapter in a book?
					if ( count( $chapters ) > 1 ) {
						foreach ( $chapters as $key => $chapter ) {
							$id = $key;

							$remote_import['file']      = $chapter['link'];
							$remote_import['file_type'] = 'text/html';
							$remote_import['type_of']   = 'html';
							$remote_import['chapters']  = [
								$key => 'title_placeholder',
							];
							$all_chapters[]             = $remote_import;
						}
					} else {
						$id = array_keys( $chapters );

						$remote_import['file']      = $chapters[ $id[0] ]['link'];
						$remote_import['file_type'] = 'text/html';
						$remote_import['type_of']   = 'html';
						$remote_import['chapters']  = [
							$id[0] => 'title_placeholder',
						];
						$all_chapters[]             = $remote_import;
					}
				}

				$importer = new Import\RemoteImport();
				$ok       = $importer->import( $all_chapters );
			}

			$msg  = 'Tried to import a post from this Pressbooks instance and ';
			$msg .= ( $ok ) ? 'succeeded :)' : 'failed :(';

			if ( $ok ) {
				// Success! Redirect to organize page
				$success_url = get_bloginfo( 'url' ) . '/wp-admin/admin.php?page=pb_organize';
				self::log( $msg, $books );
				Redirect\location( $success_url );
			}
			// do book import
		} elseif ( isset( $_GET['import'] ) && isset( $_POST['book'] ) && is_array( $current_import ) && check_admin_referer( 'pbt-import' ) ) {

			// get the one book that we are importing
			$book_id  = $_POST['book'];
			$fqdn     = network_home_url();
			$endpoint = $fqdn . '/api/' . self::$version . '/books/' . $book_id . '/';

			// remote call to the API using book id
			$args     = [
				'timeout' => '20',
			];
			$response = wp_remote_get( $endpoint, $args );

			// response gets all chapters, types
			if ( is_wp_error( $response ) ) {
				error_log( '\PBT\Search\formSubmit error: ' . $response['response']['message'] ); //@codingStandardsIgnoreLine
				Redirect\location( get_bloginfo( 'url' ) . '/wp-admin/admin.php?page=api_search_import' );
			}

			$import_chapters = json_decode( $response['body'], true );

			// something goes wrong at the API level/response
			if ( 0 == $import_chapters['success'] ) { //@codingStandardsIgnoreLine
				return;
			}

			// format the chapters array
			$all_chapters = self::getAllChapters( $import_chapters, $book_id );

			$importer = new Import\RemoteImport();
			$ok       = $importer->import( $all_chapters );

			$msg  = 'Tried to import a post from this Pressbooks instance and ';
			$msg .= ( $ok ) ? 'succeeded :)' : 'failed :(';

			if ( $ok ) {
				// Success! Redirect to organize page
				$success_url = get_bloginfo( 'url' ) . '/wp-admin/admin.php?page=pb_organize';
				self::log( $msg, $import_chapters['data'][ $book_id ]['book_toc'] );
				Redirect\location( $success_url );
			}

			// return results from user's search
		} elseif ( isset( $_GET['import'] ) && $_POST['search_api'] && check_admin_referer( 'pbt-import' ) ) {

			// find out what domain we are handling
			$endpoint = $_POST['endpoint'] . 'api/' . self::$version . '/';
			$domain   = wp_parse_url( $_POST['endpoint'], PHP_URL_HOST );

			// filter post values
			$search = filter_input( INPUT_POST, 'search_api', FILTER_SANITIZE_STRING );

			// explode on space, using preg_split to deal with one or more spaces in between words
			$search = preg_split( '/[\s]+/', $search, 5 );

			// convert to csv
			self::$search_terms = implode( ',', $search );

			// discover if we are searching for books, or chapters
			// do books
			if ( 0 === strcmp( 'books', $_POST['collection'] ) ) {
				// no cache, assumes search term will be unique
				$books = self::getPublicBooks( $endpoint, self::$search_terms );

				if ( ! empty( $books ) && is_array( $books ) ) {

					update_option( 'pbt_current_import', $books );
					delete_option( 'pbt_terms_not_found' );
				} else {
					update_option( 'pbt_terms_not_found', self::$search_terms );
				}
				// do chapters
			} else {
				// check the cache
				$books = get_transient( 'pbt-public-books-' . $domain );

				// get the response
				if ( false === $books ) {
					$books = self::getPublicBooks( $endpoint );
				}

				if ( is_array( $books ) ) {
					$chapters = self::getPublicChapters( $books, $endpoint, self::$search_terms );
				}

				// set chapters in options table, only if there are results
				if ( ! empty( $chapters ) ) {
					update_option( 'pbt_current_import', $chapters );
					delete_option( 'pbt_terms_not_found' );
				} else {
					update_option( 'pbt_terms_not_found', self::$search_terms );
				}
			}
		}

		// redirect back to import page
		Redirect\location( $redirect_url );
	}

	/**
	 * Given a response from the API, it returns an array that can be handed off
	 * @see \PBT\Modules\Import\RemoteImport($current_import)
	 *
	 * @param array $import_chapters
	 * @return array $all_chapters
	 */
	static function getAllChapters( $import_chapters, $book_id ) {

		$all_chapters = [];
		$fm           = $import_chapters['data'][ $book_id ]['book_toc']['front-matter'];
		$chap         = $import_chapters['data'][ $book_id ]['book_toc']['part'];
		$bm           = $import_chapters['data'][ $book_id ]['book_toc']['back-matter'];
		$parts_count  = count( $chap );

		// front-matter
		foreach ( $fm as $chapters ) {

			$remote_import['file']      = $chapters['post_link'];
			$remote_import['file_type'] = 'text/html';
			$remote_import['type_of']   = 'html';
			$remote_import['type']      = 'front-matter';
			$remote_import['chapters']  = [
				$chapters['post_id'] => $chapters['post_title'],
			];
			$all_chapters[]             = $remote_import;
		}

		// parts, chapters
		for ( $i = 0; $i < $parts_count; $i ++ ) {
			// parts

			$part_import['file']      = $chap[ $i ]['post_link'];
			$part_import['file_type'] = 'text/html';
			$part_import['type_of']   = 'html';
			$part_import['type']      = 'part';
			$part_import['chapters']  = [
				// @TODO - this misses chapters nested inside the part
				// loop in to grab 'post_id'
				$chap[ $i ]['post_id'] => $chap['post_title'],
			];
			$all_chapters[]           = $part_import;

			// chapters
			foreach ( $chap[ $i ]['chapters'] as $chapters ) {

				$remote_import['file']      = $chapters['post_link'];
				$remote_import['file_type'] = 'text/html';
				$remote_import['type_of']   = 'html';
				$remote_import['type']      = 'chapter';
				$remote_import['chapters']  = [
					$chapters['post_id'] => $chapters['post_title'],
				];
				$all_chapters[]             = $remote_import;
			}
		}

		// back-matter
		foreach ( $bm as $chapters ) {

			$remote_import['file']      = $chapters['post_link'];
			$remote_import['file_type'] = 'text/html';
			$remote_import['type_of']   = 'html';
			$remote_import['type']      = 'back-matter';
			$remote_import['chapters']  = [
				$chapters['post_id'] => $chapters['post_title'],
			];
			$all_chapters[]             = $remote_import;
		}

		return $all_chapters;
	}

	/**
	 * Uses v1/api to get an array of public books from a PB instance
	 *
	 * @param string $endpoint API url
	 * @return array of books
	 * [2] => Array(
	 *  [title] => Brad can has book
	 *  [author] => Brad Payne
	 *  [license] => cc-by-sa
	 *  )
	 *  [5] => Array(
	 *  [title] => Help, I'm a Book!
	 *  [author] => Frank Zappa
	 *  [license] => cc-by-nc-sa
	 *  )
	 */
	static function getPublicBooks( $endpoint, $search = '' ) {
		$books        = [];
		$current_book = get_current_blog_id();
		$domain       = wp_parse_url( $endpoint, PHP_URL_HOST );
		$titles       = ( ! empty( $search ) ) ? '?titles=' . $search : '';

		// build the url, get list of public books
		$args         = [
			'timeout' => '25',
		];
		$public_books = wp_remote_get( $endpoint . 'books' . $titles, $args );

		if ( is_wp_error( $public_books ) ) {
			error_log( '\PBT\Search\getPublicBooks error: ' . $public_books->get_error_message() ); //@codingStandardsIgnoreLine
			Redirect\location( get_bloginfo( 'url' ) . '/wp-admin/admin.php?page=api_search_import' );
		}

		$public_books_array = json_decode( $public_books['body'], true );

		// something goes wrong at the API level/response
		if ( 0 == $public_books_array['success'] ) { //@codingStandardsIgnoreLine
			return;
		}

		// a valid response
		if ( false !== ( $public_books_array ) ) {
			foreach ( $public_books_array['data'] as $id => $val ) {
				$books[ $id ] = [
					'title' => $public_books_array['data'][ $id ]['book_meta']['pb_title'],
					'author' => $public_books_array['data'][ $id ]['book_meta']['pb_authors'],
					'license' => $public_books_array['data'][ $id ]['book_meta']['pb_book_license'],
					'domain' => $domain,
				];
				if ( 0 === strcmp( 'all-rights-reserved', $books[ $id ]['license'] ) ) {
					unset( $books[ $id ] );
				}
			}
		}

		// don't return results from the book where the search is happening, only if searching this instance of PB
		if ( isset( $books[ $current_book ] ) && $endpoint === network_home_url() ) {
			unset( $books[ $current_book ] );
		}

		if ( ! empty( $books ) ) {
			// cache public books for 12 hours
			set_transient( 'pbt-public-books-' . $domain, $books, 43200 );
		}

		return $books;
	}

	/**
	 * Gets a list of books that are set to display publicly
	 *
	 *
	 * @param array $books
	 * @param string $endpoint
	 * @param string $search
	 * @return array $chapters from the search results
	 */
	static function getPublicChapters( $books, $endpoint, $search = '' ) {
		$chapters = [];
		$blog_ids = array_keys( $books );
		$titles   = ( ! empty( $search ) ) ? '?titles=' . $search : '';

		// iterate through books, search for string match in chapter titles
		foreach ( $blog_ids as $id ) {
			$request  = $endpoint . 'books/' . $id . '/' . $titles;
			$args     = [
				'timeout' => '20',
			];
			$response = wp_remote_get( $request, $args );
			$body     = json_decode( $response['body'], true );
			if ( ! empty( $body ) && 1 == $body['success'] ) { //@codingStandardsIgnoreLine
				$chapters[ $id ]             = $books[ $id ];
				$chapters[ $id ]['chapters'] = $body['data'];
			}
		}

		return $chapters;
	}

	/**
	 * Simple check to see if the form submission is valid
	 * nonce verification happens
	 * @return boolean
	 *@see formSubmit()
	 *
	 */
	static function isFormSubmission() {
		// phpcs:disable WordPress.CSRF.NonceVerification.NoNonceVerification

		if ( empty( $_REQUEST['page'] ) ) {
			return false;
		}

		if ( 'api_search_import' !== $_REQUEST['page'] ) {
			return false;
		}

		if ( $_SERVER['REQUEST_METHOD'] === 'POST' ) {
			return true;
		}

		if ( count( $_GET ) > 1 ) {
			return true;
		}

		// phpcs:enable WordPress.CSRF.NonceVerification.NoNonceVerification

		return false;
	}

	/**
	 * Simple revoke of an import (user hits the 'cancel' button)
	 *
	 * @return bool
	 */
	static function revokeCurrentImport() {

		Book::deleteBookObjectCache();
		return delete_option( 'pbt_current_import' );
	}

	/**
	 * Log for the import functionality, for tracking bugs
	 *
	 * @param string $message
	 * @param array $more_info
	 */
	static function log( $message, array $more_info ) {
		$subject = '[ PBT Search and Import Log ]';
		// send to superadmin
		$admin_email = get_site_option( 'admin_email' );
		$from        = 'From: no-reply@' . get_blog_details()->domain;
		$logs_email  = [
			$admin_email,
		];

		$time = strftime( '%c' );
		$info = [
			'time' => $time,
			'site_url' => site_url(),
		];

		$msg = print_r( array_merge( $info, $more_info ), true ) . $message; //@codingStandardsIgnoreLine

		// Write to error log
		error_log( $subject . "\n" . $msg ); //@codingStandardsIgnoreLine

		// Email logs
		foreach ( $logs_email as $email ) {
			error_log( $time . ' - ' . $msg, 1, $email, $from ); //@codingStandardsIgnoreLine
		}
	}

}
