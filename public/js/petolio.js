/**
 * Petolio
 * Main website class
 *
 * @author Seth
 * @version 0.3
 */
var Petolio = (function($) {
	return function() {
		var	translate = {},
			locale = {},
			js_cache = {},
			loading_stopped = false;

		function loadJs(js, ignoreCache) {
			var base = SITE_URL + 'js/';

			if(js_cache[js] == true && !ignoreCache) {
				return true; // if this line is commented then the js cache is temporary disabled
			}

			$('body').append('<script type="text/javascript" src="'+ base + js +'" />');
			js_cache[js] = true;
		};

		function showMessage(a, f) {
			$('<div id="dialog-info" title="'+ translate.dialog[0] +'" class="ui-state-highlight">'+
				'<div style="margin: 8px 0px 2px 0px; line-height: 18px;">'+
					'<table cellpadding="0" cellspacing="0" border="0"><tr><td valign="top" style="padding: 1px 5px 0px 0px;"><span class="ui-icon ui-icon-info"></span></td>'+
					'<td>' + a + '</td></tr></table>'+
				'</div>'+
			'</div>').dialog({
				resizable: false,
				modal: true,
				width: 350,
				minHeight: 50,
				buttons: [{
					text: translate.dialog[2],
					click: function() {
						$(this).dialog("close");
					}
				}],
				close: function() {
					if(f)
						f.apply(this, Array.prototype.slice.call(arguments, 1));

					$("#dialog-info").remove();
				}
			});
		};

		/**
		 * Displays a confirmation box for various actions
		 */
		function showConfirm(a, f) {
			$('<div id="dialog-confirm" title="'+ translate.dialog[1] +'" class="ui-state-highlight">'+
				'<div style="margin: 8px 0px 2px 0px; line-height: 18px;">'+
					'<table cellpadding="0" cellspacing="0" border="0"><tr><td valign="top" style="padding: 1px 5px 0px 0px;"><span class="ui-icon ui-icon-alert"></span></td>'+
					'<td>' + a + '</td></tr></table>'+
				'</div>'+
			'</div>').dialog({
				resizable: false,
				modal: true,
				width: 350,
				minHeight: 50,
				buttons: [
					{
						text: translate.dialog[2],
						click: function() {
							if(f) // callback
								f.apply(this, Array.prototype.slice.call(arguments, 1));

							$(this).dialog("close");
						}
					}, {
						text: translate.dialog[3],
						click: function() {
							$(this).dialog("close");
						}
					}
				],
				close: function() {
					$("#dialog-confirm").remove();
				}
			});
		};

		/**
		 * Universal confirm function for links
		 */
		function confirmLinkAction() {
			$('.reqconf').click(function(e) {
				Petolio.showConfirm($(e.currentTarget).attr('title'), function() {
					Petolio.go($(e.currentTarget).attr('href'));
				});

				return false;
			});
		}

		/**
		 * Flag as Inappropriate window
		 */
		var flagInappropriate = function() {
			$('.openflag').click(function(e) {
				$(".dialog-flag").dialog("open");
			});
		};

		function showLoading(a, bt) {
			loading_stopped = false;
			$('<div id="dialog-loading" title="'+ translate.dialog[4] +'" style="padding: 10px 0px 7px 0px; text-align: center;" class="ui-state-info">'+ (a ? a : translate.dialog[5]) +'</div>').dialog({
				resizable: false,
				modal: true,
				zIndex: 400000,
				minHeight: 50,
				width: bt ? 350 : 300,
				buttons: bt ? bt : null,
				close: function() {
					loading_stopped = true;
					$("#dialog-loading").remove();
				}, open: function() {
					if(bt != undefined) {
						var c = $(this),
							b = c.next('div.ui-dialog-buttonpane');

						b.append('<div style="float:left; margin-top: 12px; margin-left: 5px;">'+
							$("#showLoading_img").html() +
						'</div>');
					}
				}
			});
		};

		function hideLoading() {
			$("#dialog-loading").remove();
		};

		function showError() {
			hideLoading();
			/*
			var error = $('<div id="dialog-error" title="'+ translate.dialog[6] +'" style="padding: 10px 0px 7px 0px; text-align: center; color: red;" class="ui-state-error">'+ translate.dialog[7] +'</div>').dialog({
				resizable: false,
				modal: true,
				minHeight: 50,
				close: function() {
					$("#dialog-error").remove();
				}
			});
			*/
	//		window.setTimeout(function() {
	//			error.dialog('close');
	//		}, 5000);
		};

		function ajax(o) {
			var overwrite = {
				url: SITE_URL + o.url,
				success: function(x) {
					if(x.success) o.success(x);
					else (o.error ? o.error(x) : Petolio.showError());
				},
				error: (o.error ? o.error : Petolio.showError)
			};

			$.ajax($.extend({}, o, overwrite));
		};

		function handleG() {
/**
			if ($.browser.msie && parseInt($.browser.version) == 7) {
				$('a[name="google_plus"]').remove();
				return true;
			}

			if(!$('a[name="google_plus"]').length > 0)
				return false;

			var p = document.createElement('script'),
				s = document.getElementsByTagName('script')[0];

			p.async = true;
			p.type = 'text/javascript';
			p.src = 'https://apis.google.com/js/plusone.js';
			s.parentNode.insertBefore(p, s);

			$('a[name="google_plus"]').css({width: '38px'});
**/
		};

		function handleLang() {
/**
			$('div[class="lang"]').click(function(e) {
				var i = $(e.currentTarget);

				i.find('ul').toggle();
				i.find('i').toggleClass('l_up');
			});

			$('div[class="lang"]').find('li').click(function(e) {
				var i = $(e.currentTarget);

				$('div[class="lang"]').find('div').html(i.html());
				Petolio.go(SITE_URL + 'site/setlocale/locale/' + i.attr('lang'));
			});

			// lol ie7 fix.
			if ($.browser.msie && parseInt($.browser.version) == 7) {
				$('div[class="lang"]').css({position: 'relative'});
			}
**/
		};

		function handleMessages() {
			if($('div[class="po_messages"]').length)
				window.setTimeout(function() { $('div[class="po_messages"]').slideUp(300); }, 5000);

			if($('div[class="po_messages po_error"]').length)
				window.setTimeout(function() { $('div[class="po_messages po_error"]').slideUp(300); }, 20000);
		};

		function handleSelects() {
			$("div.rightbox select.chzn-select:not(select.chzn-custom)").each(function(s, i) {
				var x = $(i);

				if(x.attr('title'))
					x.data('placeholder', x.attr('title'));

				x.chosen({translate: translate.chosen});
				x.next().css({ float: 'left', marginLeft: '5px'});

				// reverse psychology on IE7
				if ($.browser.msie && parseInt($.browser.version) == 7) {
					var zIndexNumber = 1000;
					$('div').each(function() {
						$(this).css('zIndex', zIndexNumber);
						zIndexNumber -= 1;
					});
				}
			});
		};

		function handlePictures() {
			var stop = {attr: function(){ return false; }};

			// event for hover
			$("div.picture").mouseenter(function() {
				stop = $(this);
				$(this).find("span.links").fadeIn('fast');
			}).mouseleave(function() {
				$(this).find("span.links").fadeOut();
			});

			// fade out all links after 2 seconds (except the ones we're hovering)
			window.setTimeout(function() {
				$("div.picture > span.links").each(function(s, i){
					var t = $(i).parent();

					if(stop.attr('rel') !== t.attr('rel'))
						$(i).fadeOut('slow');
				});
			}, 2000);
		};

		function handleHideCustom() {
			var l = $('h3', 'div.sidebar');

			// go through each
			l.each(function(k, v) {
				var v = $(v);
				// hide blocks
				if(v.data('hide') === true) {
					var t = v.parent();
					t.hide();

					// remove up as well from next if above it was just the hidden div
					if(!t.hasClass('up'))
						t.next().removeClass('up');
				}
			});
		};

		function handleContextMenu() {
			// get page context menu
			var c = $('#context_menu');
			if(!c.length > 0)
				return;

			// get all submenus
			var l = $('ul.leftsubmenu', 'div.sidebar'),
				template = '<ul id="context_ul"><dfn></dfn></ul>',
				t = [],
				d = [];

			// go through each
			l.each(function(k, v) {
				var v = $(v);
				// skip menus without context integration
				if(v.data('context') !== true)
					return false;

				// push titles
				t.push(v.prev('h3').html());

				// push data
				d.push(v.html());
			});

			// hide context menu if nothing is in there
			if(!d.length > 0)
				c.hide();

			// assign title
			c.find('span').html(t[0]);

			// insert template
			c.after(template);
			var u = $('#context_ul'),
				o = false;

			// append links
			$(d).each(function(k, v) {
				u.append(v);
				u.append('<li class="sep"></li>');
			});

			// remove last separator
			u.find('li.sep').last().remove();

			// close function
			var _close = function() {
				// only if open
				if(o) {
					// make the dom changes to close
					o.removeClass('the_up').addClass('the_down');
					o.next('ul').find('dfn').hide();
					o = false;

					// hide menu
					u.hide();
				}

			// bind function
			}, _bind = function() {
				// close on body click anywhere
				$('body').click(_close);

				// on link click
				c.click(function(e){
					e.stopPropagation();

					// if open close
					if(o) _close();

					// if closed open
					else {
						o = $(this);
						o.removeClass('the_down').addClass('the_up');
						o.next('ul').find('dfn').css({width: o.outerWidth() - 2}).show();
						u.show();
					}
				});

			// load function
			}, _load = function() {
				var l = u.find('li'),
					a = l.find('a');

				// make active
				a.each(function(k, v) {
					var v = $(v),
						h = v.attr('href');

					if(h == location.href)
						v.closest('li').addClass('active');
				});

				// disable default href
				a.click(function(e) {
					e.preventDefault();
				});

				// on menu link click
				l.click(function(e) {
					var a = $(this).find('a');

					// handle javascript link
					if(/javascript\:/g.test(a.attr('href'))) {
						eval(a.attr('href').replace('javascript:', ''));
						return true;
					}

					// handle require confirmation links
					if(a.hasClass('reqconf')) {
						Petolio.showConfirm(a.attr('title'), function() {
							Petolio.go(a.attr('href'));
						});

					// flag as link
					} else if(a.hasClass('openflag')) {
						$(".dialog-flag").dialog("open");

					// normal link
					} else {
						Petolio.go(a.attr('href'));
					}
				});

			// hack function
			}, _hack = function() {
				var i = c.parent().find('a.icon_link').length,
					l = c.outerWidth() > u.outerWidth();

				// link bigger than menu? fix
				if(l) u.outerWidth(c.outerWidth());

				// got more links? align left
				if(i > 1) {
					u.css({left: 2});
					u.find('dfn').css({left: 0});

				// just context link? align right
				} else {
					u.css({right: 0});
					u.find('dfn').css({right: 0});
				}
			};

			// engine start ;)
			_bind(); _load(); _hack();
		};

		function handleContextMenuSpecials() {

			var menus = $('ul.leftsubmenu, ul#context_ul');
			if (menus.length == 0) {
				return;
			}

			$('li > a.deactivate-account-button', menus).unbind('click').on('click', function() {
				handleDeactivateAccount();
			});

		};

		function handleDeactivateAccount() {

			$.ajax({
				url: SITE_URL + 'accounts/deactivate',
				async: false,
				cache: false,
				success: function (r) {
					if(r.success) {
						showConfirm(r.content, function() {
							document.location.href = r.redirect_url;
						});
					};
				}
			});

		};

		function handleMovableVirtualDir() {
			$("#mVd").children().appendTo("div.sidebar");
		};

		function confirmSale(txt, el) {
			if($(el).val() == 1)
				Petolio.showMessage(txt);
		};

		function __translate(t) {
			translate = t;
		};

		function runClock(spy) {
			var currentTime = new Date(),
				currentHours = currentTime.getHours(),
				currentMinutes = currentTime.getMinutes(),
				currentSeconds = currentTime.getSeconds();

			// make request only if we reach 12 am or with the help of spy
			if ((currentHours == 0 && currentMinutes == 0 && currentSeconds == 0) || spy)
				Petolio.ajax({
					url: 'site/get-date',
					type: 'post',
					data: {},
					cache: false,
					success: function(x) {
						if(x.success)
							$("#topdate").html(x.date);
					},
					error: function() {}
				});

			// Update the time display
			$("#toptime").html(currentHours + ":" + ((currentMinutes < 10 ? "0" : "" ) + currentMinutes));
		};

		function setLocale() {
			// choose date strings
			if(LOCALE == 'en') {
				locale.date_short = "mm/dd/yy";
				locale.date_long = "DD, MM d, yy";
				locale.date_options = {};
			} else {
				locale.date_short = "dd.mm.yy";
				locale.date_long = "DD, d MM, yy";
				locale.date_options = {
					dayNamesShort: $.datepicker.regional[LOCALE].dayNamesShort,
					dayNames: $.datepicker.regional[LOCALE].dayNames,
					monthNamesShort: $.datepicker.regional[LOCALE].monthNamesShort,
					monthNAmes: $.datepicker.regional[LOCALE].monthNames
				};
			}

			// set datepicker locale
			$.datepicker.setDefaults($.datepicker.regional[LOCALE]);
			$.datepicker.setDefaults({dateFormat: locale.date_short});
		};

		function showServiceTypeData(select) {
			var service = {};
			service.service_type_id = select.options[select.selectedIndex].value;

			$.ajax({
				url: SITE_URL + 'services/get-service-type-data',
				type: 'post',
				data: service,
				cache: false,
				success: function (x) {
					if(x.success) {
						$("#service-type-data").html(x.type + '<br/>' + x.description);
						$("#service-type-data").attr('class', 'service-type-desc');
					};
				}
			});
		};

		function go(href) {
			window.location.href = href;
		};

		function logout(href) {
			Online.Data.ape.core.quit();
			window.setTimeout(function() {
				window.location.href = href;
			}, 100);
		};

		function __construct(t) {
			setLocale();
			confirmLinkAction();
			flagInappropriate();

			handleG();
			handleLang();
			handleMessages();
			handleSelects();
			handlePictures();
			handleHideCustom();
			handleContextMenu();
			handleContextMenuSpecials();
			handleMovableVirtualDir();

			// trigger petolo event loaded
			$("body").trigger({
				type: "Petolio",
				load: true
			});
		};

		return {
			init: __construct,
			translate: __translate,
			translateChosen: function(){
				return translate.chosen;
			},
			confirmSale: confirmSale,
			showMessage: showMessage,
			showConfirm: showConfirm,
			showLoading: showLoading,
			hideLoading: hideLoading,
			stopLoading: function() {
				return loading_stopped;
			},
			showError: showError,
			ajax: ajax,
			handleSelects: handleSelects,
			runClock: runClock,
			getLocale: locale,
			loadJs: loadJs,
			showServiceTypeData: showServiceTypeData,
			logout: logout,
			go: go
		};
	}();
})(jQuery);

var ClueTip = (function($) {
	return function() {
		function __construct() {
			var opt = {
				showTitle: false,
				local: true,
				width: 220,
				attribute: 'rel',
				cluetipClass: 'rounded',
				arrows: false,
				dropShadow: true,
				hoverIntent: true,
				sticky: false,
				multiple: false,
				mouseOutClose: true,
				closeText: '<img src="/images/icons/delete.png" />',
				cluezIndex: 999999,
				fx: {
					open: 'fadeIn',
					openSpeed: '5'
				},
				hoverIntent: {
					sensitivity: 10,
					interval: 300,
					timeout: 0
				}
			};

			// form cluetip
			$('form.cluetip_form input, form.cluetip_form textarea, form.cluetip_form select').each(function(k, v){
				if($(v).parent().find('ul.cluetip_errors').length > 0)
					$(v).cluetip(opt);
			});

			// pet / galleries cluetip
			opt.arrows = true;
			opt.positionBy = 'auto';
			$('a.cluetip').cluetip(opt);

			// img question helper cluetip
			opt.positionBy = 'fixed';
			opt.topOffset = 3;
			opt.leftOffset = 13;
			opt.sticky = true;
			opt.mouseOutClose = false;
			opt.activation = 'click';
			$('img.cluetip').cluetip(opt);
		};

		return {
			init: __construct
		};
	}();
})(jQuery);

// easier to write
function dump(what) {
	return console.log(what);
};

// IE8 lacks this...
if (!Array.prototype.indexOf) {
    Array.prototype.indexOf = function (searchElement /*, fromIndex */ ) {
        "use strict";
        if (this === void 0 || this === null) {
            throw new TypeError();
        }
        var t = Object(this);
        var len = t.length >>> 0;
        if (len === 0) {
            return -1;
        }
        var n = 0;
        if (arguments.length > 0) {
            n = Number(arguments[1]);
            if (n !== n) { // shortcut for verifying if it's NaN
                n = 0;
            } else if (n !== 0 && n !== Infinity && n !== -Infinity) {
                n = (n > 0 || -1) * Math.floor(Math.abs(n));
            }
        }
        if (n >= len) {
            return -1;
        }
        var k = n >= 0 ? n : Math.max(len - Math.abs(n), 0);
        for (; k < len; k++) {
            if (k in t && t[k] === searchElement) {
                return k;
            }
        }
        return -1;
    }
}