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
	$pbt_import_url = wp_nonce_url( get_bloginfo( 'url' ) . '/wp-admin/options-general.php?page=api_search_import&import=1', 'pbt-import' );
	$pbt_revoke_url = wp_nonce_url( get_bloginfo( 'url' ) . '/wp-admin/options-general.php?page=api_search_import&revoke=1', 'pbt-revoke-import' );
	$pbt_current_import = get_option( 'pbt_current_import' );
	$not_found = get_option( 'pbt_terms_not_found' );
	$remix = get_option( 'pbt_remix_settings' );

// IMPORT show only if there is an import in progress
	if ( is_array( $pbt_current_import ) ) {
		?>
		<!-- Import in progress -->

		<p> Import in Progress...</p>
		<p><i>Reminder: You are responsible for adhering to all licensing and copyright restrictions.<br>For information on how to properly attribute material offered under a <a href="https://creativecommons.org/licenses/">Creative Commons</a> license, please refer to the information on the 
				<a href="https://wiki.creativecommons.org/FAQ#How_do_I_properly_attribute_a_Creative_Commons_licensed_work.3F">Creative Commons website</a>.</i></p>

		<script type="text/javascript">
			// <![CDATA[
			jQuery(function ($) {
				// Power hover
				$('tr').not(':first').hover(
					function () {
						$(this).css('background', '#ffff99');
					},
					function () {
						$(this).css('background', '');
					}
				);
				// Power select
				$("#checkall").click(function () {
					$(':checkbox').prop('checked', this.checked);
				});
				// Abort import
				$('#abort_button').bind('click', function () {
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
		<?php
		?>
		<form id='pbt_import_form' action='<?= $pbt_import_url; ?>' method='post'>
			<table class="wp-list-table widefat">
				<thead>
					<tr>
						<th style="width:10%;"><?php _e( 'Import', 'pressbooks-textbook' ); ?></th>
						<th><?php _e( 'Chapter Title', 'pressbooks-textbook' ); ?></th>
						<th><?php _e( 'Book Title', 'pressbooks-textbook' ); ?></th>
						<th><?php _e( 'License', 'pressbooks-textbook' ); ?></th>
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

					foreach ( $pbt_current_import as $book_id => $book ) {
						// set book title, author, license
						$book_title = $book['title'];
						$book_license = $book['license'];
						$book_author = $book['author'];
						$book_domain = $book['domain'];
						
						// do chapters
						if ( isset( $book['chapters'] ) ) {
							foreach ( $book['chapters'] as $key => $chapter ) {

								// book author/license sets chapter author/license if not set 
								$license = ( empty( $chapter['post_license'] ) ) ? $book_license : $chapter['post_license'];
								// don't allow importing of chapters marked as all-rights-reserved
								if ( 0 === strcmp( $license, 'all-rights-reserved' ) ) continue;
								$author = ( empty( $chapter['post_authors'] ) ) ? $book_author : $chapter['post_authors'];
								?>

								<tr <?php if ( $i % 2 ) echo 'class="alt"'; ?> >

									<td><input type='checkbox' id='selective_import_<?= $i; ?>' name='chapters[<?= $key; ?>][import]' value='1'></td>
							<input type='hidden' name='chapters[<?= $key; ?>][book]' value='<?= $book_id; ?>'>
							<input type='hidden' name='domain' value='<?= $book_domain; ?>'>
							<input type='hidden' name='chapters[<?= $key; ?>][link]' value='<?= $chapter['post_link']; ?>'>
							<td><label for="selective_import_<?php echo $i; ?>"><a href='<?= $chapter['post_link']; ?>' target='_blank'><?= $chapter['post_title']; ?></a></label></td>
							<td><label><?= $book_title; ?></label></td>
							<td><label><?= $license; ?></label></td>
							<input type='hidden' name='chapters[<?= $key; ?>][license]' value='<?= $license; ?>'>
							<input type='hidden' name='chapters[<?= $key; ?>][author]' value='<?= $author; ?>'>
							<td><input type='radio' name='chapters[<?= $key; ?>][type]' value='front-matter'></td>
							<td><input type='radio' name='chapters[<?= $key; ?>][type]' value='chapter' checked='checked'></td>
							<td><input type='radio' name='chapters[<?= $key; ?>][type]' value='back-matter'></td>
							</tr>
							<?php
							++ $i;
						}
					// do books	
					} else { ?>
						<tr <?php if ( $i % 2 ) echo 'class="alt"'; ?> >
							
							<td><input type="radio" id="selective_import_<?= $i; ?>" name="book" value="<?= $book_id ;?>" /></td>
							<input type='hidden' name='domain' value='<?= $book_domain; ?>'>
							<td><label>-----</label></td>
							<td><label for="selective_import_<?= $i ;?>"><?= $book_title . ", by " . $book_author ;?></label>
							<td><label><?= $book_license;?></label></td>
							<td><label>-----</label></td>
							<td><label>-----</label></td>
							<td><label>-----</label></td>
						</tr>
						
					<?php
					$i++;
					}
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

		<?php
	} else {

		if ( false != $not_found ) {
			?>

			<div class="error">
				<p>Sorry, the search term(s) <b><?= $not_found; ?></b> did not return any results, try again</p>
			</div>
			<?php
			// clear it
			delete_option( 'pbt_terms_not_found' );
		}
		?>
		<p><i>Search for the following terms:</i></p>

		<form method="post" id="search_api_form" action="<?= $pbt_import_url ?>">
			<p><label for="search_api">Search terms</label>
				<input type="text" name="search_api" id="search_api" /></p>

			<hr>
			<div>
				<p><i>Search against the following domains. <br>(You can <a href="options-general.php?page=pressbooks-textbook-settings&tab=remix">manage the list of domains</a> to search against at any time:</i>)</p>
				<?php
				foreach ( $remix['pbt_api_endpoints'] as $key => $endpoint ) {
					if ( 0 === $key ) {
						$html .= '<p><input type="radio" checked="true" value="' . $endpoint . '" name="endpoint" />' . $endpoint . '</p>';
					} else {
						$html .= '<p><input type="radio" value="' . $endpoint . '" name="endpoint" />' . $endpoint . '</p>';
					}
				}
				echo $html;
				?>
			</div>
			<hr>
			<div>
				<p><i>Search chapter titles, or book titles</i></p>
				<p><input type="radio" checked="checked" value="chapters" name="collection" /> Chapters </p>
				<p><input type="radio" value="books" name="collection" /> Books </p>
			</div>

			<?php submit_button( __( 'Search the collection', 'pressbooks-textbook' ) ); ?>

		</form>	

	<?php } ?>

</div>