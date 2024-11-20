function mirResize(max,min) {
	if(jQuery(window).width() < max && jQuery(window).width() > min) {
		jQuery('#sub').css('width',jQuery(window).width());
		jQuery('.container_inner').css('width',jQuery(window).width());
	} else if(jQuery(window).width() < min){
		jQuery('#sub').css('width',min);
		jQuery('.container_inner').css('width',min);
	} else {
		jQuery('#sub').css('width','100%');
		jQuery('.container_inner').css('width',max);
	}
}

function navigation(firstMenu,secondMenu) {
	jQuery('.navigation dl').eq(firstMenu).find('dt').addClass('on')
	.end().find('dd').eq(secondMenu).find('a').addClass('on');

	//top navigation 
	jQuery('.navigation').on('mouseleave',function(e) {
		navigation(firstMenu,secondMenu);
	});

}

function sidebarMenu(firstMenu) {
	$('.submenu li').eq(firstMenu).find('img').attr('src', $('.submenu li').eq(firstMenu).find('img').attr('src').replace('off.png','on.png'));

	//top navigation 
	jQuery('.submenu').on('mouseleave',function(e) {
		sidebarMenu(firstMenu);
	});

}

$(document).ready(function(){
	//20120329 남동훈 등급 표시 관련
	setTimeout(function() {
		jQuery('.grade').hide();
	},3000);

	//resize browser 
	mirResize(1232,1000);
	jQuery(window).resize(function(){
		mirResize(1232,1000);
	});

	/*skip navigation 키보드 접근*/
	jQuery('#skipnav a').on('click',function() {
		var target=jQuery('#'+jQuery(this).attr('href').split('#')[1]),
		oldTabIndex=target.attr('tabindex');

		target.attr('tabindex',0).focus();

		if(oldTabIndex) {
			target.attr('tabindex',oldTabIndex);
		} else {
			target.removeAttr('tabindex');
		}
	});

	var mover = function(){
		$('.submenu li img').each(function() {
			jQuery(this).attr('src', jQuery(this).attr('src').replace('on.png','off.png'));
		});

		$(this).find('img').attr('src', $(this).find('img').attr('src').replace('off.png','on.png'));
	}
	var mout = function(){
		$(this).find('img').attr('src', $(this).find('img').attr('src').replace('on.png','off.png'));
	}

	$('.submenu li a').mouseover(mover).mouseout(mout);

	jQuery('.btn_pcspec').click(function(e) {
		$(this).parent().find('dl').toggle();
	});

	if($('#gallery_modal').size()>0) {
		$('#gallery_modal').modal({
			modalOpen:'.gallery_modal',
			modalClose:'#modal-close',
			modalWrap:'#gallery_modal',
			opacity:0.8,
			animationTime:500
		},function() {
			$('.big_image img').attr('src', jQuery(this.that).find('.thumb img').attr('src').substring(0, jQuery(this.that).find('.thumb img').attr('src').lastIndexOf("."))+'_L.jpg');
			$('.msg').text(jQuery(this.that).find('.gallery_title a').text());
		});
	}

	jQuery('.main_update li').on('mouseover',function(e) {
		jQuery(this).addClass('on');
	}).on('mouseleave',function(e) {
		jQuery(this).removeClass('on');
	});
	jQuery('.main_update a').on('focus',function(e) {
		jQuery(this).parents('li').addClass('on');
	}).on('focusout',function(e) {
		jQuery(this).parents('li').removeClass('on');
	});

	jQuery('.link_table .title').on('mouseover',function(e) {
		jQuery(this).addClass('on');
		jQuery(this).find('.char_pop').show();
	}).on('mouseleave',function(e) {
		jQuery(this).removeClass('on');
		jQuery(this).find('.char_pop').hide();
	});
	jQuery('.link_table .title a').on('focus',function(e) {
		jQuery(this).parents('td').addClass('on');
		jQuery(this).find('.char_pop').show();
	}).on('focusout',function(e) {
		jQuery(this).parents('td').removeClass('on');
		jQuery(this).find('.char_pop').hide();
	});

	jQuery('.recommend_item li').on('mouseover',function(e) {
		jQuery(this).addClass('on');
	}).on('mouseleave',function(e) {
		jQuery(this).removeClass('on');
	});
	jQuery('.recommend_item a').on('focus',function(e) {
		jQuery(this).parents('li').addClass('on');
	}).on('focusout',function(e) {
		jQuery(this).parents('li').removeClass('on');
	});

	jQuery('.screen_listwrap li').on('mouseover',function(e) {
		jQuery(this).addClass('on');
	}).on('mouseleave',function(e) {
		jQuery(this).removeClass('on');
	});
	jQuery('.screen_listwrap a').on('focus',function(e) {
		jQuery(this).parents('li').addClass('on');
	}).on('focusout',function(e) {
		jQuery(this).parents('li').removeClass('on');
	});

	
	jQuery('.navigation dl').on('mouseover',function(e) {
		jQuery('.navigation dt').removeClass('on');
		jQuery('.navigation dd a').removeClass('on');
		jQuery(this).find('dt').addClass('on');
	}).on('mouseleave',function(e) {
		jQuery(this).find('dt').removeClass('on');
	});

	jQuery('.navigation dd a').on('mouseenter',function(e) {
		jQuery('.navigation dd a').removeClass('on');
		jQuery(this).addClass('on');
		e.stopPropagation();
	}).on('mouseout',function(e) {
		jQuery(this).removeClass('on');
	});

	//control keyboard top navigation 
	jQuery('.navigation dt a').on('focus',function(e) {
		jQuery('.navigation dt').removeClass('on');
		jQuery('.navigation dd a').removeClass('on');
		jQuery(this).addClass('on').parents('dl').find('dt').addClass('on');
	});

	jQuery('.navigation dd a').on('focus',function(e) {
		jQuery('.navigation dt').removeClass('on');
		jQuery('.navigation dd a').removeClass('on');
		jQuery(this).addClass('on').parents('dl').find('dt').addClass('on');
	});

	jQuery('.navigation dl:last dd:last a').on('focusout',function(e) {
		jQuery('.navigation dt a').parent().removeClass('on');
		jQuery('.navigation dd a').removeClass('on');
	});
	
	var $news=jQuery('.important_news .news');
	if($news.size() >0 ){
		$news.vTicker({ 
			speed: 500,
			pause: 5000,
			mousePause: true,
			showItems: 4,
			previous : '.up',
			next : '.down'
		});
	}
});
