var dev = false;

function Kaisermann()
{
	'use strict';

	var
	$body = document.body,
	$content = $('.content'),
	$laptop,
	$monitor_window,

	$song_video,
	$workspace;

	var code_interval = 20;

	var
	is_song_playing = false,
	current_song_index = 0;

	var animation_timers = {};
	
	var
	sounds_id = ["typing","click", "phone", "rotation", "safe", "camera", "inception"],
	sounds = {},
	songs_id = ['dQw4w9WgXcQ', 'qGyPuey-1Jw', '-jYrFN78E30'];

	var win = {width: window.innerWidth, height: window.innerHeight};

	var
	domain_url = dev ? "http://10.0.1.2" : "https://kaisermann.me",
	main_path = dev ? "/new/" : "/",
	main_url = domain_url + main_path,
	assets_url = main_url + "assets/";

	var 
	laptop_zoomed = false,
	isFirefox = (typeof InstallTrigger !== 'undefined'),
	bodyScale = (isFirefox || isMobile.any) ? false : 3,
	isloading = true,
	shouldload = true;

	var langs = ["br","en"],
	cur_lang = "br",
	lang_file;

	var cur_section;

	function getLang(id) { return lang_file[id][cur_lang]; }

	var sections = 
	{
		"eastereggs":
		{
			main: function()
			{
				if(!isSmallScreen())
				{
					// Inception
					var $iframe,
					$piao = $('.inception-spinning-top');

					var inception_handler = throttle(function(ev) {
						var scale = $monitor_window.outerWidth()/$iframe.outerWidth();
						$iframe.css("transform", "scale("+scale+")");
					}, 10);

					$piao.on('click', function()
					{
						if($piao.hasClass("spinning_top--spinning"))
						{
							window.removeEventListener("resize", inception_handler);
							$(".easteregg__inception-frame").fadeOut(function(){ $(this).remove()} );
							stopSound("inception");
						}
						else
						{
							console.log("POOOOOOOOOOM");
							playSound("inception");
							$iframe = $('<iframe class="easteregg__inception-frame" src="'+main_url+'?'+getRandomInt(0,500)+'"></iframe>');
							$monitor_window.append($iframe);

							$iframe.css("transform", "scale("+$monitor_window.outerWidth()/$iframe.outerWidth()+")");	

							window.addEventListener("resize", inception_handler);
						}
						$piao.toggleClass("spinning_top--spinning");
					});

					// Batpug

					$("body").append('<div class="easteregg__batpug"></div>');
					$(".monitor__cam").on("click", function(e)
					{
						e.preventDefault();
						var $batpug = $(".easteregg__batpug");

						if($batpug.hasClass("easteregg__batpug--active"))
							return;

						$batpug.addClass("easteregg__batpug--active");
						setTimeout(function()
						{
							$batpug.removeClass("easteregg__batpug--active");
						},2100);
					});
				}
			}

		},
		"general": 
		{
			main: function()
			{
				var self = this;

				var 
				$code_elem = $(".notepad__code"),
				$clock = $clock = $('.clock').find(".clock__container"),
				$camera = $('.camera'),
				$telephone = $('.telephone'),
				$safe = $('.portrait__safe'),
				$speakers = $(".speaker"),
				$flags = $(".flag");

				var cookielang = readCookie("lang");

				function setCurrentLanguage(lang)
				{
					var _b = $('body');
					_b.removeClass('lang--'+_b.attr("data-lang")).attr("data-lang",lang).addClass("lang--"+lang);
					cur_lang = lang;
					$('[data-lang-id]').each(function(e)
					{
						var _ = $(this);
						var _id = _.attr("data-lang-id");
						_.text(getLang(_id));
					});

					parseTooltips();
					createCookie("lang", lang, 365);
				}

				function parseTooltips()
				{
					$('.text--doodle').each(function()
					{
						if($(this).children().length)
							return;

						$(this).html(function (i, html) {
							var newhtml = '';
							html = html.split('');
							for(var i = 0; i < html.length; i++)
							{
								var emptystyle = (html[i]==' ') ? 'style="display:inline-block; width: 10px; "' :'';
								newhtml += '<span '+emptystyle+'>'+html[i]+'</span>';
							}
							return newhtml;
						});
					});
				}

				function playVideoAudio(id)
				{
					is_song_playing = true;
					$monitor_window.append('<iframe class="speaker-song-frame" src="https://youtube.com/embed/'+id+'?autoplay=1&autohide=1"></iframe>');
				}

				function stopVideoAudio()
				{
					is_song_playing = false;
					$(".speaker-song-frame").fadeOut(function(){ $(this).remove(); });
				}

				if(cookielang)
					setCurrentLanguage(cookielang);
				else
					setCurrentLanguage("br");

				this.parameters.lamp = $('.lamp');

				(function configureHighlight()
				{
					//if(isFirefox)
					//	$(".notepad__code").css("white-space","pre-wrap");

					hljs.configure(
					{
						languages: ["html"],
						useBR: true,
						classPrefix: 'code__'
					});
				})();

				(function parseHTMLCode()
				{
					var html = $code_elem[0].innerHTML.replace(/([^\r\n]+)/g, '<span class="code__line">$1</span><br>');

					$code_elem[0].innerHTML = html;

					hljs.highlightBlock($code_elem[0]);

					$(".code__line,.code__tag").contents().filter(function() {
						return (this.nodeType == 3 && !new RegExp(/^(\n|\r)/g).test(this.textContent));
					}).wrap('<span class="code__text"></span>');

					self.parameters.code_tags = $code_elem.find("*");
					self.parameters.code_tags.hide();
				})();

				$('.section-link').on('click', function(e)
				{
					e.preventDefault();
					changeUrl($(this).attr("href"));
					events.openSection(parseHash($(this).attr("href")));

					if(typeof window.ga == "function")
						ga('send', 'pageview', $(this).attr("href"));	
				});

				$flags.on('click', function()
				{
					var _ = $(this),
					target_lang = _.attr("data-lang");

					if(!langs.indexOf(target_lang)<0)
						return;

					setCurrentLanguage(target_lang);
				});

				if(!isMobile.any)
				{
					$speakers.on("click", function() 
					{
						if (is_song_playing && $(this).hasClass("speaker--playing"))
						{
							$clock.removeClass("clock--shaking");
							stopVideoAudio();

							if($laptop.hasClass("animating"))
								playSound("typing", true);
						}
						else
						{
							var songid, max = songs_id.length;

							playVideoAudio(songs_id[current_song_index]);

							if(++current_song_index==max)
								current_song_index = 0;

							if($laptop.hasClass("animating"))
								stopSound("typing");
						}

						$speakers.toggleClass("speaker--playing");
					});

					$telephone.find(".telephone__handle")
					.on("mouseover", function() { playSound("phone"); })
					.on("mouseleave", function() { stopSound("phone"); });

					$telephone.find(".telephone__base")
					.on("mouseover", function() { playSound("rotation"); })
					.on("mouseleave", function() { stopSound("rotation"); });

					$safe
					.on("mouseover", function() { playSound("safe"); })
					.on("mouseleave", function() { stopSound("safe"); });

					$camera.find(".camera__container")
					.on("mouseover", function() { playSound("camera"); })
					.on("mouseleave", function() { stopSound("camera"); });

					$clock.on("click", function()
					{
						stopVideoAudio();
						if($(this).hasClass("clock--shaking"))
							$speakers.removeClass("speaker--playing");
						else
						{
							playVideoAudio("JwYX52BP2Sk")
							$speakers.addClass("speaker--playing");
						}
						$clock.toggleClass("clock--shaking");
					});

					this.parameters.lamp.find(".lamp--clickable").on('click', function()
					{
						self.parameters.lamp.toggleClass("lamp--off");
						sounds["click"].currentTime = 0;
						playSound("click");
					});

					window.addEventListener('focus', function() {
						if($laptop.hasClass("animating") && !isMobile.any && !laptop_zoomed)
							playSound("typing", true);
					});

					window.addEventListener('blur', function() {
						if($laptop.hasClass("animating"))
							stopSound("typing");
					});
				}

				(function updateClock()
				{
					var angle = 360/60;
					var date = new Date();
					var hour = date.getHours();
					hour = (hour > 12) ? hour - 12 : hour;

					var minute = date.getMinutes();
					var second = date.getSeconds();
					var hourAngle = (360/12) * hour + (360/(12*60)) * minute;

					$clock.find(".clock__second").css('transform','rotate('+((angle*second)-90)+'deg)');
					$clock.find(".clock__minute").css('transform','rotate('+((angle*minute)-90)+'deg)');
					$clock.find(".clock__hour").css('transform','rotate('+((hourAngle)-90)+'deg)');

					setTimeout(updateClock, 1000);
				})();
			},
			"queue": 
			{
				50: function () { $workspace.addClass("workspace--slided"); },
				400: (isMobile.any)? null: function () 
				{
					this.parameters.lamp.removeClass("lamp--off").addClass("lamp--blink"); 
					if(!laptop_zoomed) 
						playSound("click");
				},
				500: function() {
					var self = this;

					$laptop.addClass("animating").removeClass("preanimation");

					if(!isMobile.any && !laptop_zoomed)
						playSound("typing", true);

					animation_timers["typing"] = setTimeout(
						(function()
						{
							var 
							code_counter = 0,
							code_length = self.parameters.code_tags.length,
							$notepad_content = $(".notepad__content")[0];	

							return function timer_typing_callback() 
							{
								animation_timers["typing"] && clearTimeout(animation_timers["typing"]);

								$(self.parameters.code_tags[code_counter++]).attr('style', function(i, style)
								{
									return style.replace(/display[^;]+;?/g, '');
								});
								$notepad_content.scrollTop = $notepad_content.scrollHeight;

								if (code_counter <= code_length)
									animation_timers["typing"] = setTimeout(timer_typing_callback, code_interval);
								else 
								{
									(function typeEnded () 
									{
										$laptop.addClass("animated").removeClass("animating");
										if(!isMobile.any)
											stopSound("typing");
									})();
								}
							};
						})()
						, code_interval);
				},
				700: function() { this.parameters.lamp.removeClass("lamp--blink"); },
				680: function() { $('.text--doodle').not(".tooltip--hoverable").addClass("object__tooltip--active"); }
			}
		},
		"about": {
			main: function()
			{	
				var self = this;

				var $rowfirst = $(".-exp-first"),
				$ratedlist = $('.language-list'),
				$skill_items = $rowfirst.find(".skill__item"),
				$rated_items = $ratedlist.find(".rated-list__rate"),
				$listitems = $(".list__item"),
				list_count = 0;

				var skill_animated = false, language_animated = false, list_animated = false;

				self.methods.scrollHandler = function(e)
				{
					if(skill_animated && language_animated && list_animated)
					{
						window.removeEventListener("scroll", self.methods.scrollHandler);
						return;
					}

					if($ratedlist.isOnScreen() && !language_animated)
					{
						$rated_items.each(function()
						{
							var _ = $(this),
							rate = parseInt($(this).attr("data-rate")),
							count = 0;

							setTimeloop(function()
							{
								_.append('<span class="rate__square"></span>');
							},
							100,
							function() { return ++count == rate; } )
						});
						language_animated = true;
					}

					if(list_count<=$listitems.length)
					{
						$listitems.filter(":not(.animated)").each(function()
						{
							var _ = $(this);
							if(_.isOnScreen())
							{
								_.addClass("animated fadeInDown");
								list_count++;
							}
						});
					}
					else
						list_animated = false;

					if($rowfirst.isOnScreen() && !skill_animated)
					{
						var skills_cur_item = 0;

						setTimeloop(function()
						{
							var _ = $skill_items.eq(skills_cur_item);
							_.children(".skill__bar").css("width", _.attr("data-width")+"%");
						},
						150,
						function() { return ++skills_cur_item == $skill_items.length; }
						);		
						skill_animated = true;			
					}
				};

				window.addEventListener("scroll", self.methods.scrollHandler);
				setTimeout(self.methods.scrollHandler, 200);

				animateItems(this, 3, function(i) { return (300*(i-1)); });
			},
			methods: {},
			onClose: function()
			{
				window.removeEventListener("scroll", this.methods.scrollHandler);
			}
		},
		"contact": {
			main: function()
			{
				var form_selector = ".contact-form";

				$(form_selector+" input,"+form_selector+" textarea").on("input",function(){$(this).parent().removeClass("form--error").removeAttr("data-error")});
				$(form_selector+" .form__submit").on("click",function(a){a.preventDefault();for(var b=$(form_selector).find(".form__submit"),c=$(form_selector),d=c.serializeArray(),e=[],f=/^[a-zA-Z0-9.!#$%&'*+\/=?^_`{|}~-]+@[a-zA-Z0-9](?:[a-zA-Z0-9-]{0,61}[a-zA-Z0-9])?(?:\.[a-zA-Z0-9](?:[a-zA-Z0-9-]{0,61}[a-zA-Z0-9])?)*$/,g=0;g<d.length;g++){var h=d[g];h.value.length<1?e.push({name:h.name,msg:getLang("empty_field")}):"email"!=h.name||f.test(h.value)||e.push({name:h.name,msg:getLang("invalid_email")})}return e.length?(e.forEach(function(a){c.find('[name="'+a.name+'"]').parent().addClass("form--error").attr("data-error",a.msg)}),void scrollToElem('[name="'+e[0].name+'"]',300,-50)):(b.addClass("form--sending"),void $.ajax({type:"POST",url:"functions/sendMail.php",data:c.serialize()+"&lang="+cur_lang,success:function(a){a=$.parseJSON(a),b.removeClass("form--sending");var c=document.createElement("div");c.className="form__feedback feedback--"+(1==a.state?"ok":"error"),c.innerHTML=a.msg,$(c).hide().insertAfter(form_selector).fadeIn(),scrollToElem(".form__feedback",300,50)}}))});

				animateItems(this, 5, 0, 100);
			}
		},
		"projects": {
			main: function(parameters) 
			{
				var pitem_selector = ".project__item";
				var pitem_link_selector = ".project__link";

				animateItems(this, $(pitem_selector).length, 0, 100);

				function toggleCompleteInfo(a,b){b?isSmallScreen()?a.show():a.slideDown():isSmallScreen()?a.hide():a.slideUp()}

				$(pitem_link_selector).on("click", function(b)
				{
					b.preventDefault();
					var _ = $(this);
					var _parent = $(this).parent();
					var _complete = _parent.find(".project__complete");

					if(_parent.hasClass("project--expanded"))
					{
						_parent.removeClass("project--expanded");
						toggleCompleteInfo(_complete, false);

						changeUrl(main_path + "#projects");
						return;
					}

					var current_complete = $(pitem_selector)
					.filter(".project--expanded")
					.removeClass("project--expanded")
					.find(".project__complete")

					toggleCompleteInfo(current_complete, false);

					_parent.addClass("project--expanded");
					toggleCompleteInfo(_complete, true);
					changeUrl(main_path + _.attr("href"));

					setTimeout(function() { scrollToElem(_complete,300,-50); },400);

					if(!_complete.data("slided"))
					{

						var imgs = _complete.find(".project__slide img[data-lazyload]");
						imgs.each(function() {
							$(this).attr("src", $(this).attr("data-lazyload")).removeAttr("data-lazyload");
						});

						var gal = _complete.data("slided", true).find('.project__slides-wrapper').flickity({
							cellAlign: 'left',
							wrapAround: true,
							imagesLoaded: true,
							cellSelector: '.project__slide',
							pageDots: false,
							setGallerySize: false,
							arrowShape: {
								x0: 10,
								x1: 40, y1: 50,
								x2: 80, y2: 50,
								x3: 50
							}
						});

						var flkty = gal.data('flickity');

						gal.on( 'cellSelect', function() {
							var cur_slide = gal.find(".project__slide.is-selected");
							gal.find(".flickity-viewport").css("max-height", cur_slide.outerHeight());
						});
					}

					if(typeof window.ga == "function")
						ga('send', 'pageview', _.attr("href"));	
				});

if(this.parameters.hash !== undefined)
{
	var project = this.parameters.hash[1];
	this.parameters["focused_project"] = $('.project__link[href$="#projects/'+project+'"]');
}

},
onQueueStep: function(step_selector)
{
	if(this.parameters["focused_project"] && this.parameters["focused_project"].parent().hasClass(step_selector.substring(1)))
		this.parameters["focused_project"].trigger("click");

}
}
};

var events = 
{
	runSection: function(id, queue_id)
	{
		var section = sections[id];
		queue_id = queue_id ? queue_id : "queue";

		if(!section.parameters)
			section.parameters = {};

		if(sections[id].hasOwnProperty("main"))
			section.main.call(section);

		if(section.hasOwnProperty(queue_id))
		{
			(function sectionQueueHelper(id, queue, limit, current, interval)
			{
				var page = sections[id];
				var step_ret = null;
				animation_timers[id] && clearTimeout(animation_timers[id]);

				if(page.queue.hasOwnProperty(current))
				{
					limit--;
					var current_queue_item = page.queue[current];

					if(typeof current_queue_item == "function")
						step_ret = current_queue_item.call(page);
					else
					{
						if(current_queue_item && typeof current_queue_item.method == "function")
							step_ret = current_queue_item.method.call(page, current_queue_item.params);
					}

					if(typeof page.onQueueStep == "function")
						page.onQueueStep.call(page, step_ret);
				}

				if(limit)
				{
					current += interval;
					animation_timers[id] = setTimeout(function(){sectionQueueHelper(id, queue, limit, current, interval);}, interval);
				}
				else
				{
					if(typeof page.onQueueEnd == "function")
						page.onQueueEnd.call(page, step_ret);
				}
			})(id, section.queue, Object.keys(section.queue).length, 0, 10);
		}
	},
	openLaptop: function()
	{
		if( laptop_zoomed ) 
			return;
		laptop_zoomed = true;

		if(!isSmallScreen())
		{
			$laptop.addClass("laptop--zoomed");
			applyTransforms($laptop);

			if(bodyScale) 
				$($body).css("transform", "scale3d("+bodyScale+","+bodyScale+",1)");			

			transitionEnd($laptop).bind(function() 
			{
				if( bodyScale )		
					$($body).addClass("body--notransition").css('transform','none');

				$content.addClass("content--active");
				$workspace.addClass("workspace--zoomed");

				$laptop.addClass('laptop--notransition').css('transform', 'translate3d(0,0,0) scale3d(1,1,1)');
				transitionEnd($laptop).unbind();
			});
		}
		else
		{
			$workspace.addClass("workspace--zoomed");
			$content.addClass("content--active");
		}
	},
	closeLaptop: function()
	{
		laptop_zoomed = false;
		$workspace.removeClass("workspace--zoomed");
		$content.removeClass("content--active content--loaded content--loading");

		if(!isSmallScreen())
		{
			var nobodyscale = true;

			applyTransforms($laptop);

			if( bodyScale )
				$($body).css("transform", "scale3d("+bodyScale+","+bodyScale+",1)");

			applyTransforms($laptop);

			setTimeout(function()
			{
				$laptop.removeClass('laptop--notransition laptop--zoomed').css('transform','translate3d(0,0,0) scale3d(1,1,1)');

				if( bodyScale )
					$($body).removeClass("body--notransition").css("transform", "scale(1)");
			}, 25);
		}
	},
	openSection: function(hash_array)
	{
		var section_name = hash_array[0];

		if(!sections.hasOwnProperty(section_name))
			return;

		events.openLaptop();
		$content.removeClass("content--loaded").addClass("content--loading");
		isloading = false;
		shouldload = true;

		scrollToPos(0, 300);

		cur_section = section_name;

		$.get(main_url + 'query.php',
			'page='+section_name+'&lang='+cur_lang,
			function( response )
			{
				isloading = false;
				if(!shouldload)
					return;

				$content
				.find(".content__container")
				.addClass("content--"+section_name)
				.empty()
				.html(response);

				setTimeout(function()
				{
					$content
					.removeClass("content--loading")
					.addClass("content--loaded");

					if(hash_array && hash_array.length>0)
						sections[section_name].parameters = {"hash": hash_array};

					events.runSection(section_name);
				},200)

			});
	},
	closeSection: function()
	{
		if(sections[cur_section].hasOwnProperty("onClose"))
			sections[cur_section].onClose.call(sections[cur_section]);

		changeUrl(main_path);
		events.closeLaptop();
		$content.find(".content__container").removeClass().addClass("content__container");

		if(isloading)
			shouldload = false;
		isloading = false;
		cur_section = null;
	}
};

function init() 
{
	initElements();
	initTriggers();

	if(!isMobile.any)
	{
		for(var i = 0; i<sounds_id.length;i++)
			sounds[sounds_id[i]] = new Audio(assets_url + "sound/" + sounds_id[i] + ".mp3");
	}

	events.runSection("general");
	events.runSection("eastereggs");

	if(window.location.hash !== undefined && window.location.hash.length>=1)
		events.openSection(parseHash(window.location.hash));
}

function initElements() 
{
	$laptop = $(".laptop");
	$workspace = $('.workspace');
	$monitor_window = $('.window__content');
};

function initTriggers() 
{
	window.addEventListener('resize', throttle(function(ev) {
		win = {width: window.innerWidth, height: window.innerHeight};
	}, 10));

	$(".button--close, .content__wrapper .window__top-dots.dot--red").on("click", events.closeSection);
}

function parseHash(hash)
{
	return hash.substring(1).split('/');
}

/* Helpers */

function changeUrl(url) { window.history.replaceState("", "", url); }

function animateItems(obj, max, calc_or_offset, inc)
{
	obj.queue = !obj.queue ? {} : obj.queue;

	if(typeof calc_or_offset == "function")
	{
		for(var i = 1; i <= max; i++)
			obj.queue[calc_or_offset(i)] = {params: {id:i}, method: function(params) { showStructure(".animation--"+params.id); return ".animation--"+params.id; }};
	}
	else
	{
		calc_or_offset = !calc_or_offset?0:calc_or_offset;
		inc = !inc?100:inc;

		for(var i = 1; i <= max; i++)
			obj.queue[i*inc] = {params: {id:i}, method: function(params) { showStructure(".animation--"+params.id); return ".animation--"+params.id; }};
	}
}

function showStructure(selector) { $(selector).removeClass("structure--invisible").addClass("structure--visible"); }

function isSmallScreen() { return isMobile.any || win.width < 992; }

function playSound(id, shouldLoop)
{	
	shouldLoop = !!shouldLoop;

	if(shouldLoop)
		sounds[id].addEventListener('ended', sound_looper_callback);
	sounds[id].play();
}

function stopSound(id)
{
	sounds[id].pause();
	sounds[id].currentTime = 0;
	sounds[id].removeEventListener("ended",sound_looper_callback)
}

function sound_looper_callback() { this.currentTime = 0; this.play(); }


function setTimeloop(callback, delay, condition_callback)
{
	if(condition_callback && typeof condition_callback !== "function")
	{
		console.error("Condition callback must be a callback.");
		return;
	}

	setTimeout((function()
	{
		return function inner_callback()
		{
			callback();

			if(condition_callback())
				return;

			setTimeout(inner_callback, delay);
		}
	})(),delay);
}

function throttle(fn, delay) 
{
	var allowSample = true; 

	return function(e) 
	{
		if (allowSample) 
		{ 
			allowSample = false;
			setTimeout(function() { allowSample = true; }, delay);
			fn(e);
		}
	};
}

function scrollToElem(selector, duration, offset)
{
	offset = !offset ? 0 : offset;

	$("html, body" ).animate({
		scrollTop: $(selector).offset().top + offset
	}, duration);
}

function scrollToPos(position, duration)
{
	$("html, body").animate({
		scrollTop: position
	}, duration);
}

function applyTransforms(el) 
{
	var laptopArea = el[0].querySelector('.monitor__screen'), 
	laptopAreaSize = {width: laptopArea.offsetWidth, height: laptopArea.offsetHeight},
	scaleVal = laptopAreaSize.width/laptopAreaSize.height < win.width/win.height ? win.width/laptopAreaSize.width : win.height/laptopAreaSize.height;

	$(el).css({ "transform": 'scale3d(' + scaleVal + ',' + scaleVal + ',1)'});
}
function createCookie(name,value,days) {
	if (days) 
	{
		var date = new Date();
		date.setTime(date.getTime()+(days*24*60*60*1000));
		var expires = "; expires="+date.toGMTString();
	}
	else var expires = "";
	document.cookie = name+"="+value+expires+"; path=/";
}

function readCookie(name) {
	var nameEQ = name + "=";
	var ca = document.cookie.split(';');
	for(var i=0;i < ca.length;i++) {
		var c = ca[i];
		while (c.charAt(0)==' ') c = c.substring(1,c.length);
		if (c.indexOf(nameEQ) == 0) return c.substring(nameEQ.length,c.length);
	}
	return null;
}

function eraseCookie(name) { createCookie(name,"",-1); }

$.getJSON(assets_url + "langs.json", function(data) {
	lang_file = data;
	init();
});
}

new Kaisermann();

function getRandomInt(min, max) { return Math.floor(Math.random() * (max - min + 1)) + min; }

