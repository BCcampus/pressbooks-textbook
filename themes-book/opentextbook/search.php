<?php get_header(); ?>
			
			
				<div role="main">
				
					<div><h1><span><?php _e("Search Results for","pressbooks"); ?>:</span> <?php echo esc_attr(get_search_query()); ?></h1></div>

					<?php if (have_posts()) : while (have_posts()) : the_post(); ?>
					
					<article id="post-<?php the_ID(); ?>" <?php post_class('clearfix'); ?> role="article">
						
						<header>
							
							<h3><a href="<?php the_permalink() ?>" rel="bookmark" title="<?php the_title_attribute(); ?>"><?php the_title(); ?></a></h3>
							
						
						</header> <!-- end article header -->
					
						<section class="post_content">
							<?php the_excerpt('<span class="read-more">' . __("Read more on","pressbooks") . ' "'.the_title('', '', false).'" &raquo;</span>'); ?>
					
						</section> <!-- end article section -->
						
					
					</article>
					<hr>
					<!-- end article -->
					
					<?php endwhile; ?>	
					
						
					
					<?php else : ?>
					
					<!-- this area shows up if there are no results -->
					
					<article id="post-not-found">
					    <header>
					    	<h1><?php _e("Not Found", "pressbooks"); ?></h1>
					    </header>
					    <section class="post_content">
					    	<p><?php _e("Sorry, but the requested resource was not found on this site.", "pressbooks"); ?></p>
					    </section>

					</article>
					
					<?php endif; ?>
			
				</div> <!-- end #main -->
    			
    

<?php get_footer(); ?>