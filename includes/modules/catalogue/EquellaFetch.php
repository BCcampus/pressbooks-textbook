<?php

/**
 * The class makes various queries to the Equella's REST API's.
 * Equella comes with an overview of the REST API http://solr.bccampus.ca:8001/bcc/apidocs.do
 * October 2012
 * 
 * @package   PressBooks_Textbook
 * @author    Brad Payne <brad@bradpayne.ca>
 * @license   GPL-2.0+
 */

namespace PBT\Catalogue;

class EquellaFetch {

	private $apiBaseUrl = 'http://solr.bccampus.ca:8001/bcc/api/';
	private $subjectPath1 = '/xml/item/subject_class_level1';
	private $subjectPath2 = '/xml/item/subject_class_level2';
	private $contributorPath = '/xml/contributordetails/institution';
	private $keywordPath = '/xml/item/keywords';
	private $whereClause = '';
	private $url = '';
	private $justTheResultsMaam = array();
	private $availableResults = 0;
	private $searchTerm = '';
	private $keywordFlag = false;
	private $byContributorFlag = false;
	private $uuid = '';
	private $collectionUuid = '7567d816-90cc-4547-af7a-3dbd43277639';

	const OPR_IS = ' is ';
	const OPR_OR = ' OR ';
	const ALL_RECORDS = '_ALL';

	/**
	 * 
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
	 * @param item that needs encoding
	 *
	 * @return the encoded item 
	 */
	private function urlEncode( $anyString ) {
		$result = '';
		if ( ! empty( $anyString ) ) {
			$result = urlencode( $anyString );
			return $result;
		} else {
			return false;
		}
	}

	/**
	 * Private helper function that rawURL encodes (with %20 as spaces)
	 * @param type $anyString
	 * @return string - the encoded item, or false if it's empty 
	 */
	private function rawUrlEncode( $anyString ) {
		if ( ! empty( $anyString ) ) {
			return rawurlencode( $anyString );
		} else {
			return false;
		}
	}

	/**
	 * Makes a request to the API for resources by subject/or keyword. This method builds the 
	 * REST url and sets the response (json to an associative array) and size in instance variables.
	 * 
	 * @param string $query - query string
	 * @param string $order - Allowed values are 'relevance', 'modified', 'name' or 'rating'.
	 * @param int $start - the first record of the search results to return (zero based)
	 * @param string $info - How much information to return for each result. 
	 * Allowed values are 'basic', 'metadata', 'detail', 'attachment', 'navigation', 
	 * 'drm' and 'all'. Multiple values can be specified via comma separation. 
	 * Specifying an info parameter with no value will return resource 
	 * representations containing only uuid, version and links fields only.
	 * @param int $limit - the number of results to return, default is 0, or no limit,
	 * which as far as the API is concerned is 50 at a time; the max allowed. 
	 *  
	 */
	private function searchBySubject( $anyQuery = '', $order = 'modified', $start = 0, $info = array( 'basic', 'metadata', 'detail', 'attachment', 'drm' ), $limit = 0 ) {
		$availableResults = 0;
		$loop = 0;
		$result = array();

		//the limit for the API is 50 items, so we need 50 or less. 0 is 'limitless' so we need to set 
		//it to the max and loop until we reach all available results, 50 at a time.
		$limit = ($limit == 0 || $limit > 50 ? $limit = 50 : $limit = $limit);

		$firstSubjectPath = '';
		$secondSubjectPath = '';
		$is = $this->rawUrlEncode( self::OPR_IS );
		$or = $this->rawUrlEncode( self::OPR_OR );
		$optionalParam = "&info=" . $this->arrayToCSV( $info ) . "";

		// if there's a specified user query, deal with it, change the order 
		// to relevance as opposed to 'modified' (default)
		if ( $anyQuery != '' ) {
			$order = 'relevance';
			$anyQuery = $this->rawUrlEncode( $anyQuery );
			$anyQuery = "q=" . $anyQuery . "&";
		}

		// start building the URL 
		$searchWhere = "search?" . $anyQuery . "&collections=" . $this->collectionUuid . "&start=" . $start . "&length=" . $limit . "&order=" . $order . "&where=";   //limit 50 is the max results allowed by the API
		//switch the API url, depending on whether you are searching for a keyword or a subject.
		if ( empty( $this->whereClause ) ) {
			$this->url = $this->apiBaseUrl . $searchWhere . $optionalParam;
		}

		// SCENARIOS, require three distinct request urls depending... 
		// 1 
		elseif ( $this->keywordFlag == true ) {
			$firstSubjectPath = $this->urlEncode( $this->keywordPath );
			//oh, the API is case sensitive so this broadens our results, which we want
			$secondWhere = strtolower( $this->whereClause );
			$firstWhere = ucwords( $this->whereClause );
			$this->url = $this->apiBaseUrl . $searchWhere . $firstSubjectPath . $is . "'" . $firstWhere . "'" . $or . $firstSubjectPath . $is . "'" . $secondWhere . "'" . $optionalParam;  //add the base url, put it all together
		}
		// 2
		elseif ( $this->byContributorFlag == true ) {
			$firstSubjectPath = $this->urlEncode( $this->contributorPath );
			$this->url = $this->apiBaseUrl . $searchWhere . $firstSubjectPath . $is . "'" . $this->whereClause . "'" . $optionalParam;
		}
		// 3
		else {
			$firstSubjectPath = $this->urlEncode( $this->subjectPath1 );
			$secondSubjectPath = $this->urlEncode( $this->subjectPath2 );
			$this->url = $this->apiBaseUrl . $searchWhere . $firstSubjectPath . $is . "'" . $this->whereClause . "'" . $or . $secondSubjectPath . $is . "'" . $this->whereClause . "'" . $optionalParam;  //add the base url, put it all together
		}
		
		// go and get it
		$ch = curl_init();
		curl_setopt( $ch, CURLOPT_URL, $this->url );
		curl_setopt( $ch, CURLOPT_RETURNTRANSFER, 1 );
		curl_setopt( $ch, CURLOPT_TIMEOUT, 10 );
		$ok = curl_exec( $ch );
		
		if ( false == $ok ){
			throw new \Exception( "Sorry, something went wrong with the API call to SOLR. <p>Visit <b>http://open.bccampus.ca/find-open-textbooks/</b> to discover and download free textbooks.</p>" );
		}
		
		//get the array back from the API call
		$result = json_decode( $ok, true );

		//if the # of results we get back is less than the max we asked for 
		if ( $result['length'] != 50 ) {

			$this->availableResults = $result['available'];
			$this->justTheResultsMaam = $result['results'];
		} else {

			// is the available amount greater than the what was returned? Get more! 
			$availableResults = $result['available'];
			$start = $result['start'];
			$limit = $result['length'];

			if ( $availableResults > $limit ) {
				$loop = intval( $availableResults / $limit );
				
				for ( $i = 0; $i < $loop; $i ++  ) {
					$start = $start + 50;
					$searchWhere = "search?" . $anyQuery . "&collections=" . $this->collectionUuid . "&start=" . $start . "&length=" . $limit . "&order=" . $order . "&where=";   //length 50 is the max results allowed by the API
					//Three different scenarios here, depending..
					//1
					if ( ! empty( $this->whereClause ) && $this->byContributorFlag == true ) {
						$this->url = $this->apiBaseUrl . $searchWhere . $firstSubjectPath . $is . "'" . $this->whereClause . "'" . $optionalParam;
					}
					//2
					elseif ( ! empty( $this->whereClause ) ) {
						$this->url = $this->apiBaseUrl . $searchWhere . $firstSubjectPath . $is . "'" . $this->whereClause . "'" . $or . $secondSubjectPath . $is . "'" . $this->whereClause . "'" . $optionalParam;  //add the base url, put it all together
					}
					//3
					else {
						$this->url = $this->apiBaseUrl . $searchWhere . $optionalParam;
					}
					// modify the url
					curl_setopt( $ch, CURLOPT_URL, $this->url);
					$ok2 = curl_exec( $ch );

					if ( false == $ok ){
						throw new \Exception( "Something went wrong with the API call to SOLR" );
					}

					$nextResult = json_decode( $ok2, true );

					// push each new result onto the existing array 
					$partOfNextResult = $nextResult['results'];
					foreach ( $partOfNextResult as $val ) {
						array_push( $result['results'], $val );
					}
				}
				
				
			} /* end of if */
		} /* end of else */
		curl_close( $ch );
		
		$this->availableResults = $result['available'];
		$this->justTheResultsMaam = $result['results'];
	}

	/**
	 * Helper function to turn an array into a comma separated value. If it's passed
	 * a key (mostly an author's name) it will strip out the equella user name 
	 *
	 * @param Array - an array of values
	 * @param String $key - the key of the associative array you want returned
	 * @return String of comma separated values
	 */
	public static function arrayToCSV( $anyArray = array(), $key = '' ) {
		$result = '';

		if ( is_array( $anyArray ) ) {
			//if it's not being passed a key from an associative array
			//NOTE adding a space to either side of the comma below will break the 
			//integrity of the url given to get_file_contents above.
			if ( $key == '' ) {
				foreach ( $anyArray as $value ) {
					$result .= $value . ",";
				}
				//return the value at the key in the associative array  
			} else {
				foreach ( $anyArray as $value ) {
					//names in db sometimes contain usernames [inbrackets], strip 'em out!
					$tmp = ( ! strpos( $value[$key], '[' )) ? $value[$key] : rtrim( strstr( $value[$key], '[', true ) );
					$result .= $tmp . ", ";
				}
			}

			$result = rtrim( $result, ', ' );
		} else {
			return false;
		}

		return $result;
	}

}

?>
