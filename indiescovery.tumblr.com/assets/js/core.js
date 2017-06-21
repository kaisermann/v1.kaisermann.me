var isMobile = (/Android|webOS|iPhone|iPad|iPod|BlackBerry|IEMobile|Opera Mini/i.test(navigator.userAgent));
var current_slide = 0;
var prev_slide = -1;
var loading_removed = false;
var fullscreen = false;
var ytloaded = false;
var players = [];
var autoplay = false;
var isloading = false
var post_count = 0;

var article_size = window.innerWidth;

function getContentHeight()
{
	return $(window).innerHeight() - $(".header-wrapper").innerHeight();
}

function totalSlides() {
	return Math.round($(".slide-gallery .gallery-item").length)
}
function isFinalPage() {
	return cur_page >= total_pages
}
function nextPost() {
	if (current_slide + 1 >= totalSlides())
		return;
	gotoPost((current_slide + 1))
}
function prevPost() {
	if (current_slide < 1)
		return;
	gotoPost((current_slide - 1))
}
function loadNextPage() {
	$.get(blogurl + "page/" + (cur_page + 1), function(b) {
		$(b).find(".slide-gallery .gallery-item").each(function() {
			var c = $("<div />").append($(this).clone()).html();
			addPost(c, totalSlides() - 1);
		});
		activateSlide(current_slide);
		cur_page++;

		if (cur_page === total_pages) {
			removePost(totalSlides() - 1);
			loading_removed = true
		}
		isloading = false;
		refreshSizes();
	});
}
function gotoPost(a) {
	var b = (current_slide !== a) ? (a > current_slide) ? 1 : -1 : 0;
	$(".gallery-item").css("left", -(a * article_size));
	current_slide = a;
	activateSlide(a);

	if (!isFinalPage() && b === 1 && loading_removed === false && current_slide === totalSlides() - 1)
	{
		isloading = true;
		loadNextPage();
	}
}
function activateSlide(a) {
	$(".slide-gallery .gallery-item").removeClass("post-active").eq(a).addClass("post-active");

	if ($(".post-active").has(".video").length > 0)
	{
		$('.fullscreen-btn').show();

		if (ytloaded)
		{
			console.log("activatin video at " + a);
			activateVideo();
		}
	}
	else
		$('.fullscreen-btn').hide();
}

function removePost(a) {
	$(".slide-gallery .gallery-item").eq(a).remove();
	$(".slide-container").css("width", "-=" + article_size);
}

function addPost(c, a) {
	var $elem = $(c);
	if ($elem.has('.video-wrapper').length)
	{
		if (fullscreen)
			$elem.addClass("fullscreen");

		updateYTSrc($elem);
	}
	$elem.find("article").css("max-height", getContentHeight());
	if (typeof a !== "undefined")
	{
		$elem.css({left: -(current_slide * article_size), width: article_size});
		$(".slide-container .gallery-item").eq(a).before($elem);
	}
	else
	{
		a = $(".slide-container .gallery-item").length;
		$elem.css({left: -(current_slide * article_size), width: article_size});
		$(".slide-container").append($elem).index();
	}
	$('.slide-container').css("width", "+=" + article_size);
	if (!$elem.find("article").hasClass('loading-slider'))
		post_count++;
}

function refreshSizes()
{
	article_size = $(window).innerWidth();
	$('.gallery-item').css('width', article_size);

	if (fullscreen)
		$('.video-wrapper').height(getContentHeight());
	else
		$('.video-wrapper').height(0);
	$('.slide-container').css({"width": (article_size * totalSlides()), "min-height": getContentHeight()});
	$('article').css("max-height", getContentHeight()); 
}

function initializejQuery()
{
	article_size = $(window).innerWidth();
	$(document).ready(function()
	{
		$('.gallery-item .video').each(function() {
			updateYTSrc(this);
			post_count++;
		});
		$(document).keydown(function(e) {
			if (e.keyCode === 37) {
				prevPost();
				return false
			} else if (e.keyCode === 39) {
				nextPost();
				return false
			}
		});

		$("#header .selector").click(function() {
			$(this).toggleClass("active");
			$(this).parent().find('.pop-menu').toggleClass("active")
		})

		$('body').
				on({"mousewheel": function(event)
					{
						clearTimeout($.data(this, "mousewheelEndCheck"));
						$.data(this, "mousewheelEndCheck", setTimeout(function()
						{
							if (event.originalEvent.wheelDelta >= 0)
								prevPost();
							else
								nextPost();
						}, 50));
						event.preventDefault();
					}}).
				on("mousewheel", "article", function(e, delta)
				{
					if ($(this).find(".post-wrapper").height() > parseInt($(this).css("max-height")))
					{
						$(this).scrollTop($(this).scrollTop() - delta * 10);
						e.preventDefault();
						e.stopPropagation();
					}
				}).
				on('click', '.ins-prev', prevPost).
				on('click', '.ins-next', nextPost).
				on('click', '.fullscreen-btn', function()
				{
					var $fbtn = $('.fullscreen-btn');
					fullscreen = ($fbtn.hasClass("on")) ? false : true;
					$('.gallery-item').has('.video').toggleClass('fullscreen');
					$(this).toggleClass('off').toggleClass('on');
					refreshSizes();
				});

		$(window).resize(function()
		{
			refreshSizes();
			clearTimeout($.data(this, "resizeEndCheck"));
			$.data(this, "resizeEndCheck", setTimeout(function()
			{
				gotoPost(current_slide);
			}, 250));
		});

		if (cur_page < total_pages)
			addPost($('.pre-loading').removeClass("pre-loading").detach());
		else
			$('.pre-loading').remove();
		
		refreshSizes();
		$('.slide-gallery').fadeIn();

		gotoPost(0);

		if (totalSlides() < 2)
			$(".ins-prev,.ins-next").hide();

		boot.loadScript("https://www.youtube.com/iframe_api");

	});
}
function updateYTSrc(elem)
{
	elem = $(elem).find(".video-wrapper iframe");
	var src = elem.attr("src");
	if (src.indexOf("youtube") > -1)
	{
		elem.attr("src", src + "&enablejsapi=1");
		elem.attr("id", "video_" + post_count);
	}
}
function onYouTubeIframeAPIReady() {
	ytloaded = true;
	activateVideo();
}
function activateVideo()
{
	if(isMobile)
		return;
	if (players[current_slide] === undefined)
		players[current_slide] = new YT.Player('video_' + current_slide, {
			events: {
				'onReady': onPlayerReady,
				'onStateChange': onPlayerStateChange
			}
		});
	else
		playCurrent();
}
function playCurrent()
{
	if (autoplay == true)
		players[current_slide].playVideo();
	autoplay = false;
}
function onPlayerReady()
{
	console.log("IM READY");
	playCurrent();
}
function onPlayerStateChange() {
	var player = players[current_slide];
	var active = $(".post-active");

	switch (player.getPlayerState())
	{
		case 1:
			for(var i=0; i < players.length; i++)
				if(i!==current_slide)
					players[i].pauseVideo();
			document.title = "♪ "+active.find('h3').text();
			break;
			
		case 2:
			document.title = "INDIESCOVERY";
			break;
			
		case 0:
			document.title = "INDIESCOVERY";
			autoplay = true;
			do
			{
				if (isFinalPage() && current_slide + 1 === totalSlides())
				{
					gotoPost(0);
					break;
				}
				nextPost();
			}
			while (active.has(".video").length === 0);
			break;
	}
}