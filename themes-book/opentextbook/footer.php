<?php if( !is_single() ){?>
	
	</div><!-- #content -->

<?php } ?>
<?php if( !is_front_page() ){?>

	<?php get_sidebar(); ?>

	</div><!-- #wrap -->
	<div class="push"></div>
	
	</div><!-- .wrapper for sitting footer at the bottom of the page -->
<?php } ?>


<div class="footer">
	<div class="inner">
		<?php if (get_option('blog_public') == '1' || is_user_logged_in()): ?>
			<?php if (is_page() || is_home( ) ): ?>
			
			<table>
				<tr>
					<td><?php _e('Book Name', 'pressbooks'); ?>:</td>
					<td itemprop="name"><?php bloginfo('name'); ?></td>
				</tr>
				<?php global $metakeys; ?>
       			 <?php $metadata = pb_get_book_information();

			 ?>
				<?php foreach ($metadata as $key => $val): ?>
				<?php if ( isset( $metakeys[$key] ) && ! empty( $val ) ): ?>
				<tr>
					<td><?php _e($metakeys[$key], 'pressbooks'); ?>:</td>
					<td><?php 
					switch ( $key ) {
						case 'pb_publication_date':
							$val = '<span class="date updated" itemprop="datePublished">' . date_i18n( 'F j, Y', absint( $val ) ) . '</span>';
							break;
						case 'pb_author':
							$val = '<span itemprop="author">' . $val . '</span>';
							break;
						case 'pb_publisher':
							$val = '<span itemprop="publisher">' . $val . '</span>';
							break;
						case 'pb_keywords_tags':
							$val = '<span itemprop="keywords">' . $val . '</span>';
							break;
						default:
							break;
					}
				echo $val; 
					?>
					</td>
				<?php endif; ?>
				<?php endforeach; ?>
				</tr>
				<?php
				// Copyright
				echo '<tr><td>' . __( 'Copyright', 'pressbooks' ) . '</td><td>';
				echo ( ! empty( $metadata['pb_copyright_year'] ) ) ? '<span itemprop="copyrightYear">' . $metadata['pb_copyright_year'] . '</span>' : '<span itemprop="copyrightYear">' . date( 'Y' ) . '</span>';
		if ( ! empty( $metadata['pb_copyright_holder'] ) ) echo ' ' . __( 'by', 'pressbooks' ) . '<span itemprop="copyrightHolder">' . $metadata['pb_copyright_holder'] . '</span>. ';
				echo "</td></tr>\n";
				?>

				</table>
				<?php endif; ?>
				<?php endif; ?>
		<p class="cie-name">
			<?php
			if ( 'opentextbc.ca' == $_SERVER['SERVER_NAME'] ) {
				_e( '<a href="http://open.bccampus.ca/find-open-textbooks/">This textbook is available for free at open.bccampus.ca</a>', 'pressbooks' );
			} else {
				_e('PressBooks.com: Simple Book Production', 'pressbooks');
			}
			?>
		</p>
	</div><!-- #inner -->
</div><!-- #footer -->
</span><!-- schema.org CreativeWork -->
<?php wp_footer(); ?>
</body>
</html>
