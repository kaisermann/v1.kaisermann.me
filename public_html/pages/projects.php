<?php 

$project_list_by_cat = [];
$count = 1;
$cat_count = 1;

srand((double) microtime() * 1000000);

function random_color($min,$max)
{
	static $last = -1;
	while(($ret = mt_rand($min,$max))==$last);
	return($last = $ret);
}
function readDirectory($dir,$recursive = true)
{
	if(!is_dir($dir))
		return false;
	try
	{
		$Resource = opendir($dir);
		$Found = array();

		while(false !== ($item = readdir($Resource)))
		{
			if($item[0] == ".")
				continue;

			if($recursive === true && is_dir($item))
				$Found[] = readDirectory($dir . $item);
			else
				$Found[] = $dir ."/". $item;
		}
	}catch(Exception $e)
	{
		return false;
	}

	return $Found;
}

$project_dirs = readDirectory('projects', false);
foreach($project_dirs as $project_dir)
{
	if(strlen($project_dir)<3)
		continue;
	$project_info = json_decode(file_get_contents($project_dir."/meta.json"), true);

	$project_info["dir"] = $project_dir;
	$project_list_by_cat[$project_info["category"]][] = $project_info;
}

foreach($project_list_by_cat as $cat => $projeto)
	shuffle($project_list_by_cat[$cat]);

$main_url = 'http://'.$_SERVER['HTTP_HOST'].dirname($_SERVER['PHP_SELF']);
?>
<div class="project-wrapper">
	<h2 class="page-title title--padded-mobile"><?php lang("projects"); ?></h2>
	<?php $cats = ['cat_pessoal', 'cat_pessoal_blogs', 'cat_zonainternet']; ?>

	<?php foreach($cats as $catid): ?>
		<?php $projects = $project_list_by_cat[$catid]; ?>
		<div class="project__list">
			<h3 class="structure--animatable animation--1"><?php lang($catid); ?></h3>
			<div class="project__grid clear--after">
				<?php foreach($projects as $info): ?>
					<?php 
					if(isset($info['hidden']) && $info['hidden']) 
						continue;
					?>
					<?php $id = $info["id"]; ?>
					<?php $color = random_color(1,5); ?>
					<div class="project__item project--color-<?php echo $color; ?> animation--<?php echo $count++;  ?>">
						<a href="#projects/<?php echo $id; ?>" class="project__link">
							<div class="project__link-wrapper">
								<img src="<?php echo $main_url ?>/projects/<?php echo $id ?>/<?php echo $id; ?>.jpg" alt="<?php echo $info["name"]; ?>" />
								<div class="project__base project__base--color-<?php echo $color; ?> "><h4 class="project__base-title"><?php echo $info["name"]; ?></h4></div>
							</div>
						</a>
						<div class="project__complete">
							<div class="project__complete-wrapper clear--after">
								<div class="project__slides">
									<div class="project__slides-wrapper">
										<?php 
										$imgs = readDirectory($info["dir"]."/imgs", false);
										sort($imgs, SORT_STRING);
										forEach($imgs as $img)
										echo '<div class="project__slide"><img data-lazyload="'.$main_url."/".$img.'" /></div>';
										?>
									</div>
								</div>
								<div class="project__infos">
									<h4 class="project__title"><?php echo $info["name"] ?></h4>
									<div class="project__desc">
										<?php foreach($info["desc"][$lang] as $paragraph): ?>
											<p><?php echo $paragraph; ?></p>
										<?php endforeach; ?>
										<p><strong><?php lang("technologies"); ?></strong>: <?php 
											$tech_list = @$info["technologies"];
											$nulltest = is_null($tech_list);

											if($nulltest || $tech_list[0]=="+")
												$tech_list = "HTML5, CSS3, jQuery, Wordpress (PHP)".($nulltest?'':substr($tech_list,1));

											echo $tech_list;
											?></p>
											<p><?php echo "<strong>"; lang("project_area"); echo "</strong>: ".$info["area"]; ?></p>
										</div>
										<?php if(strlen($info["url"])>4): ?>
											<div class="project__address">
												<a href="<?php echo $info["url"]; ?>" target="_blank">
													<span><?php lang("project_link"); ?></span>
												</a>
											</div>
										<?php endif; ?>
									</div>
								</div>
							</div>
						</div>
					<?php endforeach; ?>
				</div>
			</div>
		<?php endforeach; ?>
	</div>
