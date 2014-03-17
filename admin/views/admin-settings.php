<?php
/**
 * Represents the view for PressBooks Textbook Options page.
 *
 * @package PressBooks_Textbook
 * @author Brad Payne <brad@bradpayne.ca>
 * @license   GPL-2.0+
 * 
 * @copyright 2014 Brad Payne
 */
?>

<div class="wrap">

	<h2><?php echo esc_html( get_admin_page_title() ); ?></h2>

	<div id="icon-options-general" class="icon32"></div>
	<!-- Create the form that will be used to modify display options -->
	<form method="post" action="options.php">
		<?php 
		settings_fields( 'open_file_settings' );
		do_settings_sections( 'open_file_settings' );
		submit_button(); 
		?>
	</form>

</div>
