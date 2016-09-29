var isLoading = false;
var lastActive = null;
var hashframe = null;
jQuery(document).ready(function( $ ) {
	fixMainHeight();
	$(window).resize(fixMainHeight);
	$(".mobile-menu-btn").click(toggleMobileMenu);
	
	$("nav li.menu-item-has-children > a").click(function(e)
	{
		e.preventDefault();
		$(this).parent().toggleClass("active").find("> ul").slideToggle();
	});
	$(".menu-item-object-projeto").click(function(e)
	{
		e.preventDefault();
		frameCall($(this));
		if($(window).width()<992)
			toggleMobileMenu();
	});
	$("body").on('click', ".plink", function(e)
	{
		e.preventDefault();
		findIframeMenuItem($(this).attr("href"));
	});
	$("body").on('click', '#projeto-info .close', function()
	{
		unframeit(true);
	});
	$("#resolution-controls span").click(function()
	{
		$("#resolution-controls span").removeClass("active");
		var w = $(this).data("width");
		$("#iframe-projeto").css("max-width", w);
		$(this).addClass("active");
	});
	if(window.location.hash) {
		hashframe = window.location.hash.substring(1);
		findIframeMenuItem(hashframe);
	}
	function findIframeMenuItem(url)
	{
		$(".menu-item-object-projeto").each(function()
		{
			var link = $(this).find("a").attr("href");
			if(link.indexOf(url)>=0)
			{
				frameCall($(this));
				$(this).parents("ul").slideDown();
				return false;
			}
		});
	}
	function toggleMobileMenu()
	{
		$(".mobile-menu-btn").toggleClass('m-active');
		$("nav").toggleClass('m-active');
	}
	function frameCall(l)
	{
		var cur_frame = l.hasClass("current-menu-item");
		if($(".menu > li.current-menu-item").length)
			lastActive = $(".menu > li.current-menu-item");
		if(unframeit(cur_frame))
			return;
		l.addClass("current-menu-item");
		var url = l.find("a").attr("href");
		iframeit(url);
	}
	function unframeit(cur_frame)
	{
		$(".current-menu-item").removeClass("current-menu-item");
		if(isLoading)
		{
			$("#iframe-projeto").remove();
			isLoading = false;
		}
		else
			$("#iframe-projeto").slideUp(600, function(){$(this).remove();});
		$("#projeto-info.iframed").css("top", -$("#projeto-info").height()).removeClass('iframed');
		$("aside,#page-content,#page-wrapper, .content-wrapper").removeClass('iframed');
		$("#resolution-controls").removeClass('iframed').find("span").removeClass("active");
		$(".spinner-wrapper").fadeOut();
		if(cur_frame)
		{
			if(lastActive!=null)
				lastActive.addClass('current-menu-item');	
			return true;
		}
		return false;
	}
	function iframeit(url)
	{
		isLoading = true;
		$(".spinner-wrapper").fadeIn(function()
		{
			$.get( ajax_request_url,
			{
				'action' : 'get_post_ajax',
				'url' : url
			}, function( response ){
				if(isLoading)
					if ( !response.error)
					{
						$("#projeto-info .desc")
						.html(response.content)
						.append('
							<div>
							<p>
							Área de atuação: '+response.acf.o_que_foi_feito+'
							</p>
							</div>
							<div>
							<a href="'+response.acf.projeto_url+'" target="_blank">
							Visualizar projeto (em link externo)
							</a>
							</div>')
						.parent()
						.addClass("iframed")
						.css("top", function(){return -$(this).height()+26});
						if($(window).width()<992)
							projectLoaded();
						else
						{
							ifr = $('<iframe/>',
							{
								id:'iframe-projeto',
								src:response.acf.projeto_url,
								style:'width: 100%; height: 100%;',
								load:function()
								{
									projectLoaded(response.acf.responsivo);
								}
							});
							$('#iframe-wrapper').append(ifr); 
						}
					}
					else 
						alert ('Ajax error: ' + response.error );
				},  "json" );
});
}
function projectLoaded(is_responsivo)
{
	if(is_responsivo === undefined)
		is_responsivo = false;
	$("aside").css("left", function(){$(this).width();}).addClass('iframed');
	$(".spinner-wrapper").fadeOut();
	$("#page-wrapper,#page-content, .content-wrapper").addClass("iframed");
	if(is_responsivo)
		$("#resolution-controls").addClass("iframed");
	markResControls($(window).innerWidth(), $("#iframe-projeto").css("max-width"));
	isLoading = false; 
	fixMainHeight();
	$('html,body').animate({
		scrollTop: $("#page-content").offset().top
	}, 1000); 
}
function fixMainHeight() 
{
	var wid =  $(window).outerWidth();
	var pw = $('#page-wrapper');
	var pc = $('#page-content');
	var ip = $("#iframe-projeto");
	var iw = $("#iframe-wrapper");
	var imw = ip.css("max-width");
	if(wid>991)
	{
		ip.css("height", "100%");
		pw.css("height", $(window).height() - $("#wpadminbar").height());
		pc.css("min-height", pw.height());
		$("#projeto-info.iframed").css("top", function(){return -$(this).height()+26;});
	}
	else
	{
		ip.css("min-height", $(window).innerHeight() - $('#projeto-info').innerHeight() - $('.top').outerHeight() - 20*2);
		pw.css("height", "auto");
		pc.css("min-height", 0);
	}
	markResControls(wid, imw);
}
function markResControls(wid, imw)
{
	$("#resolution-controls span").removeClass("active");
	if(wid<480 || imw=="320px")
		$("#resolution-controls span:eq(0)").addClass("active");
	else
	{
		if(wid<992 || imw=="991px")
			$("#resolution-controls span:eq(1)").addClass("active");
		else
			$("#resolution-controls span:eq(2)").addClass("active");
	}
}
function loadpage(url)
{
	nohttp = url.replace("http://","").replace("https://","");
	firstsla = nohttp.indexOf("/");
	pathpos = url.indexOf(nohttp);
	path = url.substring(pathpos + firstsla);
	if(home_url.indexOf(url)==0)
		path = "/";
	setURL(path);
	$(".spinner-wrapper").fadeIn(400);
	jQuery.ajax({
		type: "GET",
		url: url,
		cache: false,
		dataType: "html",
		success: function(data) {
			$("#page-content").hide();
			$(document).attr('title', $(data).filter('title').text());
			document.getElementById("page-content").innerHTML = $("#page-content", data).html();
			$(".spinner-wrapper").fadeOut();
			$("#page-content").fadeIn();
		},
		error: function(jqXHR, textStatus, errorThrown) {
			AAPL_isLoad = false;
			document.title = "Error loading requested page!";
			
			if (AAPL_warnings == true) {
				txt="ERROR: \nThere was an error with AJAX.\n";
				txt+="Error status: " + textStatus + "\n";
				txt+="Error: " + errorThrown;
				alert(txt);
			}
			document.getElementById("page-content").innerHTML = "AJAX ERROR";
		}
	});
}
$(document).ajaxSend(function(event, xhr, settings){ 
	if (typeof _gaq !== "undefined" && _gaq !== null) {
		_gaq.push(['_trackPageview', settings.url]);
	}
});
function setURL(path)
{
	if (typeof window.history.pushState == "function") 
	{
		var stateObj = { foo: 1000 + Math.random()*1001 };
		history.pushState(stateObj, "ajax page loaded...", path);
	}
}
});