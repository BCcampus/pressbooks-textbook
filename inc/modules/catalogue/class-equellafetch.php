<?php

/**
 * The class makes various queries to the Equella's REST API's.
 * Equella comes with an overview of the REST API http://solr.bccampus.ca:8001/bcc/apidocs.do
 * October 2012
 *
 * @package   Pressbooks_Textbook
 * @author    Brad Payne
 * @license   GPL-2.0+
 */

namespace PBT\Modules\Catalogue;

class EquellaFetch {

	private $apiBaseUri         = 'http://solr.bccampus.ca:8001/bcc/api/';
	private $subjectPath1       = '/xml/item/subject_class_level1';
	private $subjectPath2       = '/xml/item/subject_class_level2';
	private $contributorPath    = '/xml/contributordetails/institution';
	private $keywordPath        = '/xml/item/keywords';
	private $whereClause        = '';
	private $url                = '';
	private $justTheResultsMaam = [];
	private $availableResults   = 0;
	private $searchTerm         = '';
	private $keywordFlag        = false;
	private $byContributorFlag  = false;
	private $uuid               = '';
	private $collectionUuid     = '7567d816-90cc-4547-af7a-3dbd43277639';

	const OPR_IS      = ' is ';
	const OPR_OR      = ' OR ';
	const ALL_RECORDS = '_ALL';

	/**
	 * EquellaFetch constructor.
	 * @throws \Exception
	 */
	public function __construct() {
		$this->searchBySubject( $this->searchTerm );
	}

	public function getUuid() {
		return $this->uuid;
	}

	public function getKeywordFlag() {
		return $this->keywordFlag;
	}

	public function getContributorFlag() {
		return $this->byContributorFlag;
	}

	public function getResults() {
		return $this->justTheResultsMaam;
	}

	public function getWhereClause() {
		return $this->whereClause;
	}

	/**
	 * Private helper function that url encodes input (with + signs as spaces)
	 *
	 * @param $any_string
	 *
	 * @return bool|string
	 */
	private function urlEncode( $any_string ) {
		if ( ! empty( $any_string ) ) {
			$result = urlencode( $any_string ); //@codingStandardsIgnoreLine
			return $result;
		} else {
			return false;
		}
	}

	/**
	 * Private helper function that rawURL encodes (with %20 as spaces)
	 *
	 * @param $any_string
	 *
	 * @return bool|string
	 */
	private function rawUrlEncode( $any_string ) {
		if ( ! empty( $any_string ) ) {
			return rawurlencode( $any_string );
		} else {
			return false;
		}
	}

	/**
	 * Makes a request to the API for resources by subject/or keyword. This method builds the
	 * REST url and sets the response (json to an associative array) and size in instance variables
	 *
	 * @param string $any_query
	 * @param string $order
	 * @param int $start
	 * @param array $info
	 * @param int $limit
	 *
	 * @throws \Exception
	 */
	private function searchBySubject( $any_query = '', $order = 'modified', $start = 0, $info = [ 'basic', 'metadata', 'detail', 'attachment', 'drm' ], $limit = 0 ) {

		//the limit for the API is 50 items, so we need 50 or less. 0 is 'limitless' so we need to set
		//it to the max and loop until we reach all available results, 50 at a time.
		$limit = ( $limit === 0 || $limit > 50 ) ? $limit = 50 : $limit;

		$first_subject_path  = '';
		$second_subject_path = '';
		$is                  = $this->rawUrlEncode( self::OPR_IS );
		$or                  = $this->rawUrlEncode( self::OPR_OR );
		$optional_param      = '&info=' . $this->arrayToCSV( $info ) . '';

		// if there's a specified user query, deal with it, change the order
		// to relevance as opposed to 'modified' (default)
		if ( $any_query !== '' ) {
			$order     = 'relevance';
			$any_query = $this->rawUrlEncode( $any_query );
			$any_query = 'q=' . $any_query . '&';
		}

		// start building the URL
		$search_where = 'search?' . $any_query . '&collections=' . $this->collectionUuid . '&start=' . $start . '&length=' . $limit . '&order=' . $order . '&where=';   //limit 50 is the max results allowed by the API
		//switch the API url, depending on whether you are searching for a keyword or a subject.
		// SCENARIOS, require three distinct request urls depending...
		if ( empty( $this->whereClause ) ) {
			$this->url = $this->apiBaseUri . $search_where . $optional_param;
		} elseif ( $this->keywordFlag === true ) {
			$first_subject_path = $this->urlEncode( $this->keywordPath );
			//oh, the API is case sensitive so this broadens our results, which we want
			$second_where = strtolower( $this->whereClause );
			$first_where  = ucwords( $this->whereClause );
			$this->url    = $this->apiBaseUri . $search_where . $first_subject_path . $is . "'" . $first_where . "'" . $or . $first_subject_path . $is . "'" . $second_where . "'" . $optional_param;  //add the base url, put it all together
		} elseif ( $this->byContributorFlag === true ) {
			$first_subject_path = $this->urlEncode( $this->contributorPath );
			$this->url          = $this->apiBaseUri . $search_where . $first_subject_path . $is . "'" . $this->whereClause . "'" . $optional_param;
		} else {
			$first_subject_path  = $this->urlEncode( $this->subjectPath1 );
			$second_subject_path = $this->urlEncode( $this->subjectPath2 );
			$this->url           = $this->apiBaseUri . $search_where . $first_subject_path . $is . "'" . $this->whereClause . "'" . $or . $second_subject_path . $is . "'" . $this->whereClause . "'" . $optional_param;  //add the base url, put it all together
		}

		// go and get it
		$ok = wp_remote_get( $this->url, [ 'timeout' => 10 ] );

		if ( is_wp_error( $ok ) ) {
			throw new \Exception( 'Sorry, something went wrong with the API call to SOLR. <p>Visit <b>https://open.bccampus.ca/find-open-textbooks/</b> to discover and download free textbooks.</p>' . $ok->get_error_message() );
		}

		//get the array back from the API call
		$result = json_decode( $ok['body'], true );

		//if the # of results we get back is less than the max we asked for
		if ( $result['length'] !== 50 ) {

			$this->availableResults   = $result['available'];
			$this->justTheResultsMaam = $result['results'];
		} else {

			// is the available amount greater than the what was returned? Get more!
			$available_results = $result['available'];
			$start             = $result['start'];
			$limit             = $result['length'];

			if ( $available_results > $limit ) {
				$loop = intval( $available_results / $limit );

				for ( $i = 0; $i < $loop; $i ++ ) {
					$start        = $start + 50;
					$search_where = 'search?' . $any_query . '&collections=' . $this->collectionUuid . '&start=' . $start . '&length=' . $limit . '&order=' . $order . '&where=';   //length 50 is the max results allowed by the API
					//Three different scenarios here, depending..
					if ( ! empty( $this->whereClause ) && $this->byContributorFlag === true ) {
						$this->url = $this->apiBaseUri . $search_where . $first_subject_path . $is . "'" . $this->whereClause . "'" . $optional_param;
					} elseif ( ! empty( $this->whereClause ) ) {
						$this->url = $this->apiBaseUri . $search_where . $first_subject_path . $is . "'" . $this->whereClause . "'" . $or . $second_subject_path . $is . "'" . $this->whereClause . "'" . $optional_param;  //add the base url, put it all together
					} else {
						$this->url = $this->apiBaseUri . $search_where . $optional_param;
					}

					$ok2 = wp_remote_get( $this->url, [ 'timeout' => 10 ] );

					if ( is_wp_error( $ok2 ) ) {
						throw new \Exception( 'Sorry, something went wrong with the API call to SOLR. <p>Visit <b>https://open.bccampus.ca/find-open-textbooks/</b> to discover and download free textbooks.</p>' . $ok2->get_error_message() );
					}

					$next_result = json_decode( $ok2['body'], true );

					// push each new result onto the existing array
					$part_of_next_result = $next_result['results'];
					foreach ( $part_of_next_result as $val ) {
						array_push( $result['results'], $val );
					}
				}
			} /* end of if */
		} /* end of else */

		$this->availableResults   = $result['available'];
		$this->justTheResultsMaam = $result['results'];
	}

	/**
	 * Helper function to turn an array into a comma separated value. If it's passed
	 * a key (mostly an author's name) it will strip out the equella user name
	 *
	 * @param array $any_array
	 * @param String $key - the key of the associative array you want returned
	 *
	 * @return String of comma separated values
	 */
	public static function arrayToCSV( $any_array = [], $key = '' ) {
		$result = '';

		if ( is_array( $any_array ) ) {
			//if it's not being passed a key from an associative array
			//NOTE adding a space to either side of the comma below will break the
			//integrity of the url given to get_file_contents above.
			if ( $key === '' ) {
				foreach ( $any_array as $value ) {
					$result .= $value . ',';
				}
				//return the value at the key in the associative array
			} else {
				foreach ( $any_array as $value ) {
					//names in db sometimes contain usernames [inbrackets], strip 'em out!
					$tmp     = ( ! strpos( $value[ $key ], '[' ) ) ? $value[ $key ] : rtrim( strstr( $value[ $key ], '[', true ) );
					$result .= $tmp . ', ';
				}
			}

			$result = rtrim( $result, ', ' );
		} else {
			return false;
		}

		return $result;
	}

}


