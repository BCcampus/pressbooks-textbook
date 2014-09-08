<?php
/**
 * This admin page will take a search term and return results from 
 * the PB API. The user has the option of importing any relevant chapters 
 * returned by the search.
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
	$pbt_import_url = wp_nonce_url( get_bloginfo( 'url' ) . '/wp-admin/options-general.php?page=api_search_import&import=1' );
	$pbt_revoke_url = wp_nonce_url( get_bloginfo( 'url' ) . '/wp-admin/options-general.php?page=api_search_import&revoke=1' );
	$pbt_current_import = get_option( 'pbt_current_import' );

	
// IMPORT show only if there is an import in progress
	if ( is_array( $pbt_current_import ) ) {
		?>
		<!-- Import in progress -->

		<p> Import in Progress...</p>

		<script type="text/javascript">
			// <![CDATA[
			jQuery(function($) {
				// Power hover
				$('tr').not(':first').hover(
					function() {
						$(this).css('background', '#ffff99');
					},
					function() {
						$(this).css('background', '');
					}
				);
				// Power select
				$("#checkall").click(function() {
					$(':checkbox').prop('checked', this.checked);
				});
				// Abort import
				$('#abort_button').bind('click', function() {
					if (!confirm('<?php esc_attr_e( 'Are you sure you want to abort the import?', 'pressbooks-textbook' ); ?>')) {
						return false;
					}
					else {
						window.location.href = "<?php echo htmlspecialchars_decode( $pbt_revoke_url ); ?>";
						return false;
					}
				});
			});
			// ]]>
		</script>			
		?>
		<form id="pbt_import_form" action="<?php echo $import_form_url ?>" method="post">

			<table class="wp-list-table widefat">
				<thead>
					<tr>
						<th style="width:10%;"><?php _e( 'Import', 'pressbooks-textbook' ); ?></th>
						<th><?php _e( 'Title', 'pressbooks-textbook' ); ?></th>
						<th style="width:10%;"><?php _e( 'Front Matter', 'pressbooks-textbook' ); ?></th>
						<th style="width:10%;"><?php _e( 'Chapter', 'pressbooks-textbook' ); ?></th>
						<th style="width:10%;"><?php _e( 'Back Matter', 'pressbooks-textbook' ); ?></th>
					</tr>
				</thead>
				<tbody>
					<tr>
						<td><input type="checkbox" id="checkall" /></td>
						<td colspan="4" style="color:darkred;"><label for="checkall">Select all</label></td>
					</tr>
					<?php
					$i = 1;
					foreach ( $current_import['chapters'] as $key => $chapter ) {
						?>
						<tr <?php if ( $i % 2 ) echo 'class="alt"'; ?> >
							<td><input type='checkbox' id='selective_import_<?php echo $i; ?>' name='chapters[<?php echo $key; ?>][import]' value='1'></td>
							<td><label for="selective_import_<?php echo $i; ?>"><?php echo $chapter; ?></label></td>
							<td><input type='radio' name='chapters[<?php echo $key; ?>][type]' value='front-matter'></td>
							<td><input type='radio' name='chapters[<?php echo $key; ?>][type]' value='chapter' checked='checked'></td>
							<td><input type='radio' name='chapters[<?php echo $key; ?>][type]' value='back-matter'></td>
						</tr>
						<?php
						++ $i;
					}
					?>
				</tbody>
			</table>

			<p><?php
				submit_button( __( 'Start', 'pressbooks-textbook' ), 'primary', 'submit', false );
				echo " &nbsp; "; // Space
				submit_button( __( 'Cancel', 'pressbooks-textbook' ), 'delete', 'abort_button', false );
				?></p>

		</form>
<?php } else { ?>
	<form method="post" id="search_api" action="<?php $pbt_import_url; ?>">
		<input type="text" name="search_api" id="search_api" />
		<input type="hidden" name="pbt_import" value="1" />

		<?php submit_button( __( 'Search the collection', 'pressbooks-textbook' ) ); ?>

	</form>	
	
<?php } ?>

</div>