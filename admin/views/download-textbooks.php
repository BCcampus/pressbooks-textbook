<?php
/**
 * This admin page allows editors access to functionality that downloads textbooks. 
 *
 * @package PressBooks_Textbook
 * @author Brad Payne <brad@bradpayne.ca>
 * @license   GPL-2.0+
 * 
 * @copyright 2014 Brad Payne
 */
if ( ! defined( 'ABSPATH' ) ) exit;

?>

<div class="wrap">
	<h2><?php echo esc_html( get_admin_page_title() ); ?></h2>
	
	<?php
	require( PBT_PLUGIN_DIR . 'includes/modules/catalogue/EquellaFetch.php');
	require( PBT_PLUGIN_DIR . 'includes/modules/catalogue/Filter.php');

	echo "<h3>Download openly licensed textbooks</h3>";

	// check if it's in the cache
	$textbooks = wp_cache_get( 'open-textbooks', 'pbt' );

	// check if we need to regenerate cache
	if ( $textbooks ) {
		echo $textbooks;
	} else {
		try {
			$equellaFetch = new \PBT\Catalogue\EquellaFetch();
			$filter = new \PBT\Catalogue\Filter( $equellaFetch );
			$textbooks = $filter->displayBySubject();
			
		} catch ( Exception $exc ) {
			echo $exc->getMessage();
		}

		wp_cache_set( 'open-textbooks', $textbooks, 'pbt', 10800 );

		echo $textbooks;
	}
	?>
</div>
