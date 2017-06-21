<!DOCTYPE html>
<html lang="en">
<head>
	<meta charset="UTF-8">
	<meta name="viewport" content="width=device-width, initial-scale=1, user-scalable=no" />
	<title>Kaisermann | Web Design &amp; Development</title>
	<meta name="description" content="Hey, faço sites práticos, elegantes e simples :)"/>
	<meta name="web_author" content="Christian Kaisermann <christian@kaisermann.it>"/>
	<meta property="og:locale" content="pt_BR" />
	<meta property="og:type" content="website" />
	<meta property="og:title" content="Kaisermann | Web Design &amp; Development" />
	<meta property="og:description" content="Hey, faço sites práticos, elegantes e simples :)" />
	<meta property="og:url" content="<?php echo $main_url; ?>" />
	<meta property="og:site_name" content="Kaisermann | Web Design &amp; Development" />
	<meta property="og:image" content="<?php echo $main_url; ?>/assets/img/site_thumb.png" />
	<link rel="stylesheet" href="<?php echo $main_url; ?>/assets/css/main.min.css?<?php echo $asset_version; ?>">
	<link rel="shortcut icon" href="<?php echo $main_url; ?>/assets/img/favicon.png"/>

	<?php if(!$dev): ?>
		<script>
			(function(i,s,o,g,r,a,m){i['GoogleAnalyticsObject']=r;i[r]=i[r]||function(){
				(i[r].q=i[r].q||[]).push(arguments)},i[r].l=1*new Date();a=s.createElement(o),
				m=s.getElementsByTagName(o)[0];a.async=1;a.src=g;m.parentNode.insertBefore(a,m)
			})(window,document,'script','//www.google-analytics.com/analytics.js','ga');

			ga('create', 'UA-75837777-1', 'auto');
			ga('require', 'displayfeatures');
			ga('send', 'pageview');
		</script>
	<?php endif; ?>

</head>
<body>
	<div class="container">
		<section class="workspace">
			<div class="workspace__container">
				<div class="workspace__above-table">
					<div class="flags">
						<div class="flag flag--br" data-lang="br">
							<div class="flag__container">
								<div class="flag__diamond">
									<div class="flag__circle"></div>
								</div>
							</div>
						</div>
						<div class="flag flag--en" data-lang="en">
							<div class="flag__container">
								<div class="flag__stripes"></div>
								<div class="flag__stars"></div>
							</div>
						</div>
						<div class="shelf__base shelf__base--mobile"></div>
					</div>
					<div class="laptop preanimation">
						<div class="object__tooltip tooltip--top text--doodle">Hey :)</div>
						<div class="laptop__container">
							<div class="laptop__monitor">
								<span class="monitor__cam"></span>
								<span class="monitor__mic"></span>
								<div class="monitor__screen">
									<div class="screen__window">
										<div class="window__top">
											<span class="window__top-dots dot--red"></span>
										</div>
										<div class="window__content">
											<div class="notepad">
												<div class="notepad__content">
													<div class="notepad__code html"><?php include("notepad_code.html"); ?></div>
												</div>
											</div>
										</div>
									</div>
								</div>
							</div>
							<div class="laptop__base"></div>
						</div>
						<div class="shelf__base shelf__base--mobile"></div>
					</div>
					<a href="#projects" class="section-link">
						<div class="bookshelf">
							<div class="object__tooltip tooltip--bottom text--doodle" data-lang-id="projects">Projetos</div>
							<div class="bookshelf__container">
								<div class="bookshelf__book book--1"></div>
								<div class="bookshelf__book book--2"></div>
								<div class="bookshelf__book book--3"></div>
								<div class="bookshelf__book book--4"></div>
								<div class="bookshelf__book book--5"></div>
								<a href="http://programadeindie.com" target="_blank">
									<div class="camera">
										<div class="camera__container">
											<div class="camera__btns"></div>
											<div class="camera__eyepiece"></div>
											<div class="camera__shutter"></div>
											<div class="camera__flash"></div>
										</div>
									</div>
								</a>
							</div>
							<div class="shelf__base"></div>
						</div>
					</a>
					<div class="clock">
						<div class="clock__container">
							<div class="clock__markers"></div>
							<div class="clock__second"></div>
							<div class="clock__minute"></div>
							<div class="clock__hour"></div>
						</div>
					</div>
					<a href="#about" class="section-link">
						<div class="portrait">
							<div class="object__tooltip tooltip--bottom text--doodle" data-lang-id="about_me">Sobre Mim</div>
							<div class="portrait__container">
								<div class="portrait__nail"></div>
								<div class="portrait__inside">
									<div class="portrait__photo"></div>
								</div>
							</div>
							<div class="portrait__safe">
								<div class="safe__handle"></div>
								<div class="safe__lock"></div>
							</div>
						</div>
					</a>
					<div class="lamp lamp--off">
						<div class="lamp--clickable">
							<div class="lamp__top"></div>
							<div class="lamp__top-btn"></div>
							<div class="lamp__top-lamp"></div>
						</div>
						<div class="lamp--ignore">
							<div class="lamp__hinge"></div>
							<div class="lamp__pole"></div>
							<div class="lamp__top-light"></div>
							<div class="lamp__base"></div>
						</div>
					</div>
					<div class="speaker speaker--left">
						<div class="woofer-wrapper">
							<div class="woofer woofer--top"></div>
						</div>
						<div class="woofer-wrapper">
							<div class="woofer woofer--bottom"></div>
						</div>
					</div>
					<div class="speaker speaker--right">
						<div class="woofer-wrapper">
							<div class="woofer woofer--top"></div>
						</div>
						<div class="woofer-wrapper">
							<div class="woofer woofer--bottom"></div>
						</div>
					</div>
					<div class="inception-spinning-top">
						<div class="spinning-top__base"></div>
					</div>
					<a href="#contact" class="section-link">
						<div class="telephone">
							<div class="object__tooltip tooltip--top text--doodle" data-lang-id="contact">Contato</div>
							<div class="telephone__container">
								<div class="telephone__handle">
									<div class="telephone__cap cap--left"></div>
									<div class="telephone__cap cap--right"></div>
								</div>
								<div class="telephone__hook"></div>
								<div class="telephone__base">
									<div class="telephone__base-container">
										<div class="telephone__ringer"></div>
										<div class="ringer__blocker"></div>
									</div>
								</div>

							</div>
							<div class="shelf__base shelf__base--mobile"></div>
						</div>
					</a>
				</div>
				<div class="table">
					<div class="table__top"></div>
					<div class="table__bottom">
						<div class="table__base"></div>
						<div class="table__leg leg--left"></div>
						<div class="table__leg leg--right"></div>
					</div>
				</div>
			</div>
			<div class="wallpaper__base"></div>
		</section>
		<section class="content">
			<div class="loading__spinner"><div class="loading__rect rect--1"></div><div class="loading__rect rect--2"></div><div class="loading__rect rect--3"></div><div class="loading__rect rect--4"></div><div class="loading__rect rect--5"></div></div>
			<div class="content__wrapper">
				<div class="window__top window__top--content">
					<span class="window__top-dots dot--red"></span>
				</div>
				<div class="content__container"></div>
			</div>
			<div class="button button--close"></div>
		</section>
	</div>
	<div id="awwwards" class="nominee black left">
		<a href="http://www.awwwards.com/best-websites/kaisermann-web-design-development/" target="_blank">Awwwards</a>
	</div>
</body>
<script type="text/javascript" src='https://cdnjs.cloudflare.com/ajax/libs/jquery/2.1.3/jquery.min.js'></script>
<script type="text/javascript" src='<?php echo $main_url; ?>/assets/js/main.min.js?<?php echo $asset_version; ?>'></script>
<script type="text/javascript" src="https://signature.kaisermann.me/#state=fixed&responsive=true" async></script>
</html>