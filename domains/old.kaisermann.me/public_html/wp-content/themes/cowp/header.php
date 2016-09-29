<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" 
	"http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
<!--[if IE 7]>
<html class="ie ie7" <?php language_attributes(); ?>>
<![endif]-->
<!--[if IE 8]>
<html class="ie ie8" <?php language_attributes(); ?>>
<![endif]-->
<!--[if !(IE 7) | !(IE 8)  ]><!-->
<html <?php language_attributes(); ?>>
<!--<![endif]-->
<head>
	<meta charset="utf-8"/>
	<meta http-equiv="X-UA-Compatible" content="IE=edge"/>
	<meta HTTP-EQUIV="Content-Type" CONTENT="text/html; charset=utf-8"/>
	<meta name="viewport" content="width=device-width, initial-scale=1, user-scalable=no" />
	
	<link rel="profile" href="http://gmpg.org/xfn/11" />
	<link rel="pingback" href="<?php bloginfo( 'pingback_url' ); ?>" />
	<link rel="shortcut icon" href="<?php echo get_template_directory_uri(); ?>/assets/img/fav.gif"/>
<link rel="stylesheet" media="screen" href="http://openfontlibrary.org/face/nemoy" rel="stylesheet" type="text/css"/>
<!--[if lt IE 9]>
<script src="<?php echo get_template_directory_uri(); ?>/assets/js/iefallback/html5.js" type="text/javascript"></script>
<![endif]-->
<?php wp_head(); ?>
<script>
	var ishome = <?php echo (is_front_page())?"true":"false"; ?>;
	var home_url = "<?php echo bloginfo('url'); ?>";
</script>
</head>

<body <?php body_class(); ?>>
	<div id="page-wrapper">
		<aside>
			<div class="content">
				<div class="top">
					<a class="logo" href="<?php echo bloginfo('url'); ?>">Kaisermann</a>
					<div class="mobile-menu-btn">
						<span class="bar-icon"></span>
						<span class="bar-icon"></span>
						<span class="bar-icon"></span>
					</div>
				</div>
				<nav>
					<?php wp_nav_menu( array('theme_location'  => 'principal' )); ?>
				</nav>
			</div>
		</aside>
		<div id="resolution-controls">
			<span data-width="320"><i class="icon-phone"></i></span><span data-width="991"><i class="icon-tablet"></i></span><span data-width="100%"><i class="icon-desktop"></i></span>
		</div>
		<main>
			<div id="page-content">
				<div id="projeto-info">
					<div class="desc">
					</div>
					<div class="close">
						<i class="icon-close"></i>
					</div>
				</div>