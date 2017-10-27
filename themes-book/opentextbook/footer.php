<?php if ( ! is_single() ) {?>

	</div><!-- #content -->

<?php } ?>
<?php if ( ! is_front_page() ) {?>

	<?php get_sidebar(); ?>
	<?php get_template_part( 'tabs', 'content' ); ?>

	</div><!-- #wrap -->
	<div class="push"></div>

	</div><!-- .wrapper for sitting footer at the bottom of the page -->
<?php } ?>


<div class="footer">
	<div class="inner">
		<?php if ( pb_is_public() ) : ?>
			<?php if ( is_page() || is_home( ) ) : ?>

			<dl>
				<dt><?php _e( 'Book Name', 'pressbooks-book' ); ?>:</dt>
				<dd><?php bloginfo( 'name' ); ?></dd>
				<?php global $metakeys;
				$metadata = pb_get_book_information();
				foreach ( $metadata as $key => $val ) :
					if ( isset( $metakeys[ $key ] ) && ! empty( $val ) ) : ?>
						<dt><?php echo $metakeys[ $key ]; ?>:</dt>
						<dd><?php if ( 'pb_publication_date' === $key ) {
							$val = date_i18n( 'F j, Y', absint( $val ) );
}
						echo $val; ?></dd>
				<?php endif;
				endforeach; ?>
				<?php
				// Copyright
				echo '<dt>' . __( 'Copyright', 'pressbooks-book' ) . ':</dt><dd>';
				echo ( ! empty( $metadata['pb_copyright_year'] ) ) ? $metadata['pb_copyright_year'] : date( 'Y' );
				if ( ! empty( $metadata['pb_copyright_holder'] ) ) {
					echo ' ' . __( 'by', 'pressbooks-book' ) . ' ' . $metadata['pb_copyright_holder'];
				}
				echo "</dd>\n"; ?>

			</dl>
			<?php endif; ?>

			<?php echo pressbooks_copyright_license(); ?>

		<?php endif; ?>
		<p class="cie-name">
			<?php

			if ( 'opentextbc.ca' == $_SERVER['SERVER_NAME'] ) {
				_e( '<a href="//open.bccampus.ca/find-open-textbooks/">This textbook is available for free at open.bccampus.ca</a>', 'pressbooks-textbook' );
			} else {
				_e( 'Pressbooks.com: Simple Book Production', 'pressbooks' );
			}
			?>
		</p>
	</div><!-- #inner -->
</div><!-- #footer -->
<?php wp_footer(); ?>
</body>
</html>
