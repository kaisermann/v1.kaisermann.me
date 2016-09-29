<?php get_header(); ?>

	<?php while ( have_posts() ) : the_post(); ?>
		<?php get_template_part('content'); ?>
	<?php endwhile; ?>

	<?php if(function_exists('wp_simple_pagination')) wp_simple_pagination(); ?> 

<?php get_footer(); ?>