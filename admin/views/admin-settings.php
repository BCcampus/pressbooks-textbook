<?php
/**
 * Represents the view for Textbooks for Pressbooks Options page.
 *
 * @package Pressbooks_Textbook
 * @author Brad Payne
 * @license   GPL-2.0+
 *
 * @copyright Brad Payne
 */
?>

<div class="wrap">

	<h2><?php echo esc_html( get_admin_page_title() ); ?></h2>
	<?php
	get_settings_errors();

	// message about functionality being tied to theme
	if ( false === \PBT\Textbook::isTextbookTheme() ) {
		echo "<div class='updated'><p>To access many features of this plugin, first <a href='themes.php'>activate one of our themes</a>, such as the Open Textbook theme.</p></div>";
	}
	?>
	<?php $active_tab = isset( $_GET['tab'] ) ? $_GET['tab'] : 'remix'; //@codingStandardsIgnoreLine ?>

	<div id="icon-options-general" class="icon32"></div>
	<h2 class="nav-tab-wrapper">
		<a href="?page=pressbooks-textbook-settings&tab=remix" class="nav-tab <?php echo $active_tab === 'remix' ? 'nav-tab-active' : ''; ?>">Search and Import</a>
	</h2>
	<!-- Create the form that will be used to modify display options -->
	<form method="post" action="options.php">
		<?php
		$current_theme = wp_get_theme()->Name;
		$pbt_theme     = \PBT\Textbook::isTextbookTheme();

		switch ( $active_tab ) {

			case 'remix':
				echo '<h3>Search, Import</h3>';

				if ( class_exists( '\Pressbooks\Modules\Api_v1\Api' ) ) {
					echo "<p>Remixing starts with finding the right content. <a href='admin.php?page=api_search_import'>Search this instance of Pressbooks for relevant content and import it into your book</a>.</p>";
					settings_fields( 'pbt_remix_settings' );
					do_settings_sections( 'pbt_remix_settings' );

				} else {
					echo "<p>You will need to <a href='https://github.com/pressbooks/pressbooks/commit/78a68c9cbba1ce3f5783215194921224558e83a2'>upgrade to a more recent version of Pressbooks which contains the API</a>. The functionality of Search and Import depends on the API.";
				}

				break;

		}
		if ( ! in_array( $active_tab, [ 'revise' ], true ) ) {
			submit_button();
		}
		?>
	</form>

</div>

<script>

	function getRowNum() {
		num = jQuery('table.form-table tbody tr').filter(":last").find('td input').attr('id');
		return num;
	}

	function addRow() {
		rowNum = getRowNum();
		rowNum++;
		var row = '<tr class="endpoints-' + rowNum + '"><th>' + rowNum + '</th><td><input id="' + rowNum + '" class="regular-text highlight" type="url" name="pbt_remix_settings[pbt_api_endpoints][' + rowNum + ']" value="" />\n\
	<input type="button" value="Add URL" onclick="addRow();" /><input type="button" value="Remove URL" onclick="removeRow(' + rowNum + ');" /></td></tr>';

		jQuery('table.form-table tbody').append(row);
	}

	function removeRow(rnum) {
		jQuery('table.form-table tbody tr.endpoints-' + rnum).remove();
	}

</script>
