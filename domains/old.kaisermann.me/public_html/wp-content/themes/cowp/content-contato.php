	<article <?php post_class(); ?>>
		<header>
			<div class="title">
				<h3><a href="<?php the_permalink(); ?>"><?php the_title(); ?></a></h3>
			</div>
		</header>
		<div class="content">
			<?php the_content(); ?>
		</div>
		<?php
		if ( function_exists( 'wpcf7_enqueue_scripts' ) ) {
			wpcf7_enqueue_scripts();
		}
		
		if ( function_exists( 'wpcf7_enqueue_styles' ) ) {
			wpcf7_enqueue_styles();
		}
		?>
	</article>