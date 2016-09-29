<?php get_header(); ?>
<div class="page <?php echo $post->post_name; ?> content-wrapper">
	<?php
	while (have_posts()):
		the_post();
		$slug = $post->post_name;
		$template = "content-".$slug.".php";
		if(locate_template($template))
			get_template_part("content", $slug); 
		else
			get_template_part("content");
	endwhile; 
	?>
</div>
<?php get_footer(); ?>