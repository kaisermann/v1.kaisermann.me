<article <?php post_class(); ?>>
	<header>
		<div class="title">
			<h3><a href="<?php the_permalink(); ?>"><?php the_title(); ?></a></h3>
		</div>
	</header>
	<div class="top clear-after">
		<div class="me-myself-and-i">
			<figure>
				<?php the_image(get_field("avatar"), 'sobre-thumb', array("class"=>"img-responsive")) ?>
			</figure>
		</div>
		<div class="about content-light">
			<div class="inner">
				<?php the_content(); ?> 
			</div>
		</div>
	</div>
	<div class="mid content-light">
		<?php 
		$anos = get_field("timeline");
		foreach ($anos as $ano) 
		{
			$jobs = $ano["jobs"];
			echo '<div class="card-timeline">';
			echo '<h3>'.$ano["ano"].'</h3>';
			foreach ($jobs as $job)
				echo '<div class="job-item"><span class="title">'.$job["titulo"]."</span><span class=\"periodo\">(".$job["periodo"].")</span><div class=\"desc\">".$job["desc"]."</div></div>";
			echo '</div>';	
		}
		?>	
	</div>
	<div class="mid content-light">
		<?php the_field("conhecimentos"); ?>	
	</div>
	<div class="mid content-light">
		<div class="portfolio-list">
			<h3>Projetos</h3>
			<?php 
			$categories = get_categories() ;
			foreach ($categories as $cat) 
			{
				$ps = get_posts( array('post_type' => 'projeto',"category_name" => $cat->name, "posts_per_page" => -1, "orderby" => "rand"));

				echo '<div class="card-portfolio-list">';
				echo '<span>'.$cat->name.'</span>';
				echo "<ul>";
				foreach ($ps as $p)
				{
					echo '<li><a href="'.get_permalink($p->ID).'" class="plink">'.$p->post_title."</a></li>";
				}
				echo '</ul></div>';	
			}
			?>	
		</div>
	</div>
</article>