(function($){
	$.fn.extend({
		mmovieList:function(options){
			var defaults = {
				offsetPos:4 //이동해야 할 리스트 갯수
			};
			var options = $.extend(defaults,options);
			/* 리스트가 4개 이하일 경우, next버튼 disabled 설정 */
			$('.mmovieListWrap').each(function(){
				if($(this).find('li').length <= 4){
					$(this).parent().nextAll('.next_img').addClass('disabled');
				}
			});
			/* 텍스트 data 설정 */
			$('.mmovieListWrap li').each(function(){

				$(this).find('.filter').on('mouseover',function(){
					var idx = $(this).parent('li').index();
					$(this).text(data[idx].title);
					$(this).parent('li').addClass('on');
				}).on('mouseout',function(){
					$(this).text('');
					if($(this).parent().siblings().hasClass('on')){
						$(this).parent('li').removeClass('on');
					}
				});
				$(this).on('click',function(){
					$(this).addClass('on').siblings().removeClass('on');
					var idx = $(this).index();
					$(this).parents('.wrapper').nextAll('.moviePlayer').find('#movieView').attr('src',data[idx].youtube_src)
						.end().end()
						.nextAll('.movieStory')
						.find('dl dt').text(data[idx].title)
						.end()
						.find('dl dd').text(data[idx].txt);
				});
			});
			/* 리스트 이동 버튼 설정 */
			$('.mmovieList_btn').on('click',function(){
				var imgWrap = $(this).prevAll('.wrapper').find('.mmovieListWrap');
				var imgElements = $(imgWrap).find('li');
				var pos = parseInt($(imgWrap).css('left'));
				var liElements = imgElements.width();
				var maxLeftPos = -parseInt(liElements);
				var o = options;
				if($(this).hasClass('prev_img')){
					if(pos<0){
						if((parseInt($(imgWrap).css('left'))%o.offsetPos*liElements) == 0){
							$(imgWrap).stop(true,true).animate({'left':'+='+o.offsetPos*liElements},500);
						}
						$(this).nextAll('.next_img').removeClass('disabled');
						pos = pos +  parseInt(o.offsetPos*liElements);
						if(pos >= 0){
							$(this).addClass('disabled');
						}
					}else{
						$(imgWrap).stop();
					}
				}else if($(this).hasClass('next_img')){
					if(pos > maxLeftPos){
						if((parseInt($(imgWrap).css('left'))%o.offsetPos*liElements) == 0){
							$(imgWrap).stop(true,true).animate({'left':'-='+o.offsetPos*liElements},500);
						}
						$(this).prevAll('.prev_img').removeClass('disabled');
						pos = pos - o.offsetPos*liElements;
						if(pos <= maxLeftPos){
							$(this).addClass('disabled');
						}
					}
				}
			});
		}
	});
})(jQuery);