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
	<!-- display settings errors -->
	<?php
	get_settings_errors();

	// message about functionality being tied to theme
	if ( false == \PBT\Textbook::isTextbookTheme() ) {
		echo "<div class='updated'><p>To access many features of this plugin, first <a href='themes.php'>activate one of our themes</a>, such as the Open Textbook theme.</p></div>";
	}
	?>
	<?php $active_tab = isset( $_GET['tab'] ) ? $_GET['tab'] : 'reuse'; ?>

	<div id="icon-options-general" class="icon32"></div>
	<h2 class="nav-tab-wrapper">
		<a href="?page=pressbooks-textbook-settings&tab=reuse" class="nav-tab <?php echo $active_tab == 'reuse' ? 'nav-tab-active' : ''; ?>">Reuse</a>
		<a href="?page=pressbooks-textbook-settings&tab=revise" class="nav-tab <?php echo $active_tab == 'revise' ? 'nav-tab-active' : ''; ?>">Revise</a>
		<a href="?page=pressbooks-textbook-settings&tab=remix" class="nav-tab <?php echo $active_tab == 'remix' ? 'nav-tab-active' : ''; ?>">Remix</a>
		<a href="?page=pressbooks-textbook-settings&tab=redistribute" class="nav-tab <?php echo $active_tab == 'redistribute' ? 'nav-tab-active' : ''; ?>">Redistribute</a>
		<a href="?page=pressbooks-textbook-settings&tab=retain" class="nav-tab <?php echo $active_tab == 'retain' ? 'nav-tab-active' : ''; ?>">Retain</a>
		<a href="?page=pressbooks-textbook-settings&tab=other" class="nav-tab <?php echo $active_tab == 'other' ? 'nav-tab-active' : ''; ?>">Other</a>
	</h2>
	<!-- Create the form that will be used to modify display options -->
	<form method="post" action="options.php" name="pbt_settings">
		<?php
		$current_theme = wp_get_theme()->Name;
		$pbt_theme = \PBT\Textbook::isTextbookTheme();

		switch ( $active_tab ) {

			case 'reuse':				
				settings_fields( 'pbt_reuse_settings' );
				do_settings_sections( 'pbt_reuse_settings' );
				break;

			case 'revise':
				echo '<h3>Adapt, Adjust, Modify</h3>'
				. "<p><b>Good News!</b> We've added some functionality to the TinyMCE editor</p>"
				. "<ol><li><b>MCE Textbook Buttons</b> by Brad Payne adds the following textbook specific buttons: Learning Objectives (LO), Key Takeaways (KT), Excercies (EX).</li>"
				. "<li><b>MCE Table Buttons</b> by jakemgold, 10up, thinkoomph adds table buttons to the editor.</li>"
				. "<li><b><a href='options-general.php?page=pb-latex'>PB LaTeX</a></b> by Brad Payne adds the ability to include math equations using LaTeX markup.</li>"
				. "<li>Anchor tags!</li></ol>";
				break;

			case 'remix':
				
				echo "<h3>Search, Import</h3>";
				
				if ( class_exists( '\PressBooks\Api_v1\Api') ){
					echo "<p>Remixing starts with finding the right content. <a href='admin.php?page=api_search_import'>Search this instance of PressBooks for relevant content and import it into your book</a>.</p>";
					settings_fields( 'pbt_remix_settings' );
					do_settings_sections( 'pbt_remix_settings' );
					
				} else {
					echo "<p>You will need to <a href='https://github.com/pressbooks/pressbooks/commit/78a68c9cbba1ce3f5783215194921224558e83a2'>upgrade to a more recent version of PressBooks which contains the API</a>. The functionality of Search and Import depends on the API.";
				}
				
				break;

			case 'redistribute':
				settings_fields( 'pbt_redistribute_settings' );
				do_settings_sections( 'pbt_redistribute_settings' );
				break;
			
			case 'retain':
				require( PBT_PLUGIN_DIR . 'includes/modules/catalogue/EquellaFetch.php');
				require( PBT_PLUGIN_DIR . 'includes/modules/catalogue/Filter.php');
				
				echo "<h3>Download openly licensed textbooks</h3>";

				// check if it's in the cache
				$textbooks = wp_cache_get('open-textbooks', 'pbt');

				// check if we need to regenerate cache
				if ( $textbooks ) {
					echo $textbooks;
				} else {
					$equellaFetch = new \PBT\Catalogue\EquellaFetch();
					$filter = new \PBT\Catalogue\Filter( $equellaFetch );
					$textbooks = $filter->displayBySubject();
					
					wp_cache_set( 'open-textbooks', $textbooks, 'pbt', 10800 );

					echo $textbooks;
				}
				break;

			case 'other':
				settings_fields( 'pbt_other_settings' );
				do_settings_sections( 'pbt_other_settings' );
				break;
		}
		if ( 'revise' != $active_tab ) {
			submit_button();
		}
		?>
	</form>

</div>

<script>

function getRowNum(){
	num = jQuery('table.form-table tbody tr').filter(":last").find('td input').attr('id');
	return num;
}

function addRow(){
	rowNum = getRowNum();
	rowNum++;
	var row = '<tr class="endpoints-'+rowNum+'"><th>'+rowNum+'</th><td><input id="'+rowNum+'" class="regular-text highlight" type="url" name="pbt_remix_settings[pbt_api_endpoints]['+rowNum+']" value="" />\n\
	<input type="button" value="Add URL" onclick="addRow();" /><input type="button" value="Remove URL" onclick="removeRow('+rowNum+');" /></td></tr>';
	
	jQuery('table.form-table tbody').append(row);
}

function removeRow(rnum){
	jQuery('table.form-table tbody tr.endpoints-'+rnum).remove();
}

</script>
	
	