<?php get_header(); ?>



<div class="content-wrapper">

	<?php $q = new WP_Query("post_type=projeto&posts_per_page=-1&orderby=rand"); ?>

	<div class="mosaic-wrapper clear-after">

		<?php $i = 0; ?>

		<?php while($q->have_posts()): $q->the_post(); ?>

		<a class="card projeto plink" href="<?php the_permalink();?>">

			<div class="overlay"></div>

			<figure>

				<?php the_image(null, "front-thumb", array("class" => "img-responsive")); ?>

			</figure>

			<div class="desc">

				<div>

					<div>

						<p class="title"><?php echo str_replace(' ', '<br/>', get_the_title()); ?></p>

					</div>

				</div>

			</div>

		</a>			

		<?php $i++; ?>

	<?php endwhile; ?>

	<?php wp_reset_query(); ?>

	</div>

</div> 

<?php get_footer(); ?>