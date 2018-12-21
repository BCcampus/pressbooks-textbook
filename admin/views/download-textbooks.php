<?php
/**
 * This admin page allows editors access to functionality that downloads textbooks.
 *
 * @package Pressbooks_Textbook
 * @author Brad Payne
 * @license   GPL-2.0+
 *
 * @copyright Brad Payne
 */
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

?>

<div class="wrap">
	<h2><?php echo esc_html( get_admin_page_title() ); ?></h2>
	<?php
	require( PBT_PLUGIN_DIR . 'inc/modules/catalogue/class-equellafetch.php' );
	require( PBT_PLUGIN_DIR . 'inc/modules/catalogue/class-filter.php' );

	echo '<h3>Download openly licensed textbooks</h3>';

	// check if it's in the cache
	$textbooks = wp_cache_get( 'open-textbooks', 'pbt' );

	// check if we need to regenerate cache
	if ( $textbooks ) {
		echo $textbooks;
	} else {
		try {
			$equella_fetch = new \PBT\Modules\Catalogue\EquellaFetch();
			$filter        = new \PBT\Modules\Catalogue\Filter( $equella_fetch );
			$textbooks     = $filter->displayBySubject();

		} catch ( Exception $exc ) {
			echo $exc->getMessage();
		}

		wp_cache_set( 'open-textbooks', $textbooks, 'pbt', 10800 );

		echo $textbooks;
	}
	?>
</div>
