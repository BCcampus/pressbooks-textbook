<?php

/**
 * Searches the API for resources, returns results to an import interface
 *
 * @package PressBooks_Textbook
 * @author Brad Payne <brad@bradpayne.ca>
 * @license   GPL-2.0+
 * 
 * @copyright 2014 Brad Payne
 */

namespace PBT\Search;
/**
 * Description of class-pb-apisearch
 *
 * @author bpayne
 */
class ApiSearch {
	
	public function __construct() {
		
	}
	
	static function formSubmit(){
		// evaluate POST DATA
		
		if (false == static::isFormSubmission() || false == current_user_can('edit_posts')){
			return;
		}
		
		
		echo "<pre>";
		print_r( $_POST );
		echo "</pre>";
		
	}
	
	static function isFormSubmission(){
		
		if ( 'api_search_import' != @$_REQUEST['page'] ) {
			return false;
		}

		if ( ! empty ( $_POST ) ) {
			return true;
		}

//		if ( count( $_GET ) > 1 ) {
//			return true;
//		}

		return false;
	}
}
