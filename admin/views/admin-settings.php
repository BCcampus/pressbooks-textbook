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
	settings_errors();

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
		<a href="?page=pressbooks-textbook-settings&tab=other" class="nav-tab <?php echo $active_tab == 'other' ? 'nav-tab-active' : ''; ?>">Other</a>
	</h2>
	<!-- Create the form that will be used to modify display options -->
	<form method="post" action="options.php">
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
				. "<li><b>PB LaTeX</b> by Brad Payne adds the ability to include math equations using LaTeX markup.</li></ol>";
				break;

			case 'remix':
				require( PBT_PLUGIN_DIR . 'includes/modules/catalogue/EquellaFetch.php');
				require( PBT_PLUGIN_DIR . 'includes/modules/catalogue/Filter.php');
				
				echo "<hgroup>"
				. "<h2>Combine content with other open content</h2>"
				. "<h3>Import documents</h3>"
				. "</hgroup>"
				. "<p><b>Good News!</b> The <a href='?page=pb_import'>import feature</a> has been incorporated into PressBooks. Our code contributions to PB core now makes it possible to import from EPUB, DOCX, ODT or XML files.</p>"
				. "<h3>Download openly licensed textbooks</h3>";

				$equellaFetch = new \PBT\Catalogue\EquellaFetch();
				echo $equellaFetch->displayContent( 0 );
				
				break;

			case 'redistribute':
				settings_fields( 'pbt_redistribute_settings' );
				do_settings_sections( 'pbt_redistribute_settings' );
				break;

			case 'other':
				settings_fields( 'pbt_other_settings' );
				do_settings_sections( 'pbt_other_settings' );
				break;
		}
		if ( 'remix' != $active_tab && 'revise' != $active_tab ) {
			submit_button();
		}
		?>
	</form>

</div>
