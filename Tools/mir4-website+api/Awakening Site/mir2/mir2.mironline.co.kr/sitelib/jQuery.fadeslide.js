(function($) {

	var methods={
		init : function( options,callback ){
			var settings = $.extend({
				className:'.slide_wrap',
				delayTime:1000,
				nDelayTime:1500,
				autoRolling:true,
				pagingTag:'.paging',
				nav:'.slide_navi',
				slideWrap:'.slide_wrap'
			},options);

			var $this=jQuery(this);
			var slideCnt=jQuery(settings.className+' .slide').size();
			var timer=null;
			var current=0;
			
			//자동 롤링 여부
			if(settings.autoRolling) {
				timer=setInterval(function() {
					methods.rolling('next',settings.className,settings.delayTime,settings.nDelayTime,settings.pagingTag);
				},5000);
			}
			
			if(settings.slideWrap) {
				/* mouseover시 롤링 멈춤 */
				jQuery(settings.slideWrap).on('focusin mouseover',function() {
					clearInterval(timer);
				}).on('focusout mouseout',function() {
					clearInterval(timer);

					//자동 롤링 여부
					if(settings.autoRolling) {
						timer=setInterval(function() {
							methods.rolling('next',settings.className,settings.delayTime,settings.nDelayTime,settings.pagingTag);
						},5000);
					}
				});
			}

			/* 이전 버튼 클릭 시 */
			jQuery(settings.className+' .prev').click(function() {
				methods.rolling('prev',settings.className,settings.delayTime,settings.nDelayTime,settings.pagingTag,jQuery(this));
			});

			/* 다음 버튼 클릭 시 */
			jQuery(settings.className+' .next').click(function() {
				methods.rolling('next',settings.className,settings.delayTime,settings.nDelayTime,settings.pagingTag,jQuery(this));
			});

			jQuery('.slide_control .paging a').click(function() {
				methods.rolling('select',settings.className,settings.delayTime,settings.nDelayTime,settings.pagingTag,jQuery(this));
			});
			
			if($.fn.swipe&&!$.browser.mozilla) {
				jQuery('#slide_wrap').swipe( {swipeLeft:swipeLeft, swipeRight:swipeRight,threshold:150} ); 
				function swipeLeft(event){
					methods.rolling('prev',settings.className,settings.delayTime,settings.nDelayTime,settings.pagingTag,jQuery(this));
				}
				function swipeRight(event){
					methods.rolling('next',settings.className,settings.delayTime,settings.nDelayTime,settings.pagingTag,jQuery(this));
				}
			}
		},
		rolling:function(dir,cn,dt,ndt,pt,that) {
			var count=0;

			jQuery(cn+' .slide').each(function(i) {
				if(1==jQuery(this).css('opacity')) {
					methods.current=i;
				}
			});
			
			//방향에 따라 count 계산
			if(dir=='next') {
				count=methods.current+1;
				if( count > jQuery(cn+' .slide').size()-1 ) {
					count = 0;
				}
			} else if (dir=='prev') {
				count=methods.current-1;
				if( count < 0 ) {
					count = jQuery(cn+' .slide').size()-1;
				}
			} else {
				count=jQuery('.slide_control .paging a').index(that);
			}

			if(!that||jQuery('.slide_control .paging a').index(that)!=methods.current) {
				if(!jQuery(cn+' .slide').is(':animated')) {
					jQuery(pt+' a.on').removeClass('on');
					jQuery(pt+' a').eq(count).addClass('on');
					jQuery('.slide_link a').removeClass('on').eq(count).addClass('on');
					jQuery(cn+' .slide').eq(methods.current).animate({
						'opacity':0
					},dt,function() {
						//complete animation
						//paging
						

					});

					jQuery(cn+' .slide').eq(count).animate({
						'opacity':1
					},ndt);
				}
			}

			if(typeof callback == 'function') {
				callback.call(this);
			}
		}
		
	};

	$.fn.fadeslide = function( method ){
		// Method calling logic
		if ( methods[method] ) {
			return methods[ method ].apply( this, Array.prototype.slice.call( arguments, 1 ));
		} else if ( typeof method === 'object' || ! method ) {
			return methods.init.apply( this, arguments );
		} else {
			$.error( 'Method ' +  method + ' does not exist on jQuery.slide' );
		}    
	};
})(jQuery);
