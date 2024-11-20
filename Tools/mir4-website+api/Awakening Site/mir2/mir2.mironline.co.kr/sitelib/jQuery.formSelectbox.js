$config=null;

(function($) {

	var methods={
		$selectList:false,
		init : function( options,callback ){
			var settings = $.extend({
				wrapSelect:'.formenable',
				parent:'.category',
				parents:'.category',
				opacity:0.5,
				className:'.select',
				listPosition:'dropdown',
				count:9
			},options);
			$config=methods;

			var $this=jQuery(this);

			if(jQuery(settings.className)) {
				
				jQuery(settings.className).each(function(i) {
					methods.clickControl(jQuery(this),jQuery(this).attr('id'));
					
					// li 에 selected 속성이 설정되어 있다면 생성한 select option의 해당 값을 choose ! 
					var selectedObj = jQuery(this).find("li[selected=selected]");
					
					if(selectedObj.length > 0){
						// 중복된 값이 있을 경우 첫번째 값을 선택
						selectedObj = $(selectedObj[0]);
						methods.chooseList(selectedObj, selectedObj.parents('.select').attr('id'));
					}
					
					
				});
				
				var event=(jQuery.browser.msie) ? 'focus' : 'click focus';
				jQuery(settings.className).find('a:first').on(event,function(e) {
					methods.subMenu(jQuery(this),settings.listPosition,callback,settings);
					e.preventDefault();
					e.stopImmediatePropagation();
				}).on('mouseout',function(e) {
					methods.clickControl(jQuery(settings.className));
					e.stopImmediatePropagation();
				});

				jQuery(settings.className+' .list-wrap li').on('click',function(e) {
					methods.chooseList(jQuery(this),jQuery(this).parents('.select').attr('id'));
					e.preventDefault();
					
				}).on('mouseenter',function(e) {
					jQuery(this).attr('class','hover');
					e.stopImmediatePropagation();
				}).on('mouseleave',function(e) {
					jQuery(this).attr('class','');
					e.stopImmediatePropagation();
				});

				jQuery(settings.className+' .list-wrap li:last-child').on('focusout',function() {
					methods.hideSubMenu();
				});



			}

		},
		// 리스트 내용 중 선택된 내용으로 변경되도록
		setTextContent : function(element, text) {
			element.text(text);
		},
		// selectEnable
		selectboxEnable : function(element,settings,callback,event) {
			var opacity=0;

			element.parents(settings.wrapSelect).find('.select').css({
				'opacity':opacity
			});

		},
		// selectDisable
		selectboxDisable : function(element,settings) {
			jQuery(settings.className).css({
				'opacity':settings.opacity
			});
		},
		// initialize document.click and dropdown menu 
		clickControl : function(obj,type) {
			methods.createElement(obj,type);				
			jQuery(document).click(function(e) {
				methods.hideSubMenu();
				e.stopImmediatePropagation();
			});
		},
		// initialize dropdown
		hideSubMenu : function() {
			jQuery('.list-wrap').each(function() {
				//jQuery(this).eq(0).hide('fast');
				jQuery(this)
					.eq(0).css('display','none');

				if(methods.$selectList==false&&jQuery(this).parents('.select').find('a:first').attr('rel')=='default') {
					jQuery(this).parents('.select').find('a:first').attr('class','sel');
				} else {
					jQuery(this).parents('.select').find('a:first[class="sel-on"]').attr('class','sel-done');
				}
				
			});
		},
		// active dropdown menu
		subMenu : function(obj,position,callback,settings) {
			var count=0;
			var selectedObj=null;

			if(obj.parents(settings.parent).find(settings.parents)) {
				obj.parents(settings.parent).find(settings.parents).each(function(i){
					jQuery(this).css({
						'position':'relative',
						'z-index':'1'
					});
					jQuery(this).find('div'+settings.className).css('z-index','1').css('position','relative');

				});
			}
			if(jQuery('#sub').find(settings.parent)) {
				jQuery('#sub').find(settings.parent).each(function() {
					jQuery(this).css({
						'position':'relative',
						'z-index':'1'
					});
				});
			}

			count = (obj.parents('.select').find('.list-wrap li').size() > settings.count) ? settings.count : obj.parents('.select').find('.list-wrap li').size();
			if(obj.attr('class')) {
				if(!obj.attr('class').match('sel-on')){
					$(document).unbind('click');

					obj.parents('.select').find('.list-wrap').eq(0)
						.css('display','block')
						.css({
							'height':(obj.parents('.select').find('.list-wrap li:first').outerHeight())*count+'px'
						});

					if(position=='dropup') {
						obj.parents('.select').find('.list-wrap').css(
						{
							'position':'absolute',
							'top':(obj.parents('.select').find('.list-wrap').height()+obj.parents('.select').find('a:first').height()-Math.ceil(obj.parents('.select').find('a:first').height()/2))*-1+'px',
							'left':0,
							'width':obj.parents('.select').width()-2
						});
					} else {
						obj.parents('.select').find('.list-wrap').css(
						{
							'top':obj.parents('.select').find('a:first').height()+'px',
							'width':obj.parents('.select').width()-2
						});
					}

					obj.first().attr('class','sel-on').end().parents('.select').css('z-index','99999');

					if(obj.parents(settings.parent)) {
						obj.parents(settings.parent).css({
							'position':'relative',
							'z-index':'10'
						});
						obj.parents(settings.parents).css({
							'position':'relative',
							'z-index':'10'
						});
					}
				
					var listCount=0;
					var selectedObj=null;

					if(typeof callback == 'function') {
						callback.call(this);
					}
				} else {
					methods.hideSubMenu();
				}
			}
			methods.$selectList=false;

		},
		// select dropdown/up
		chooseList : function(obj,type,sl) {
			var swap=null,swapRel=null;
			//var liClassName = obj.parent().attr('class');
			
			if(typeof obj != 'object') {
				if(type.find('li[rel='+obj+']').size()>0) {
					obj=type.find('li[rel='+obj+']');
					type=type.parents('.select').attr('id');
				} else {
					var link=null;
					if(type.find('li a').size()>0) {
						link=type.find('li a');
					} else {
						link=type.find('li');
					}

					link.each(function() {
						if(jQuery(this).text()==obj) {
							obj = jQuery(this);
							type=type.parents('.select').attr('id');
							return;
						}
					});
				}
			
			}

			if(typeof obj == 'object') {
				var selectAlink = obj;
				var selectText = obj.parents('.select').find('strong').eq(0);
				jQuery(this).find('.list-wrap li').each(function(i) {
					if(jQuery(this).text()==selectAlink.text()) {
						selectAlink = jQuery(this).eq(0);
						selectText = jQuery(this).find('strong').eq(0);
					}
				});
				swap = selectAlink.text();
				swapRel = selectAlink.rel;
				var btn_search=selectAlink.parents('td').find('.btn_search');

				methods.setTextContent(selectText,obj.text());
				obj.parents('.select').find('a:first').attr('rel',obj.attr('rel'));
				if('default'!=obj.parents('.select').find('a:first').attr('rel')) {
					obj.parents('.select').find('a:first').attr('class','sel-done');
				}

				methods.$selectList=true;

				methods.selectboxSelect(obj,type);
			}
		},

		// 디자인 드롭다운메뉴와 동기화 되도록 selectbox list 선택
		selectboxSelect : function(obj,type) {
			/* selectbox selected 초기화 */
			var oSelect=jQuery('select[name='+type+'] option');
			var liTag=obj.parent().find('li');
			var count=0;

			liTag.each(function(i) {
				if(jQuery(this).attr('rel')==obj.attr('rel')) {
					count=i;
				}
				oSelect.attr('selected',false);
			});

			oSelect.eq(liTag.size()).attr('selected',false);
			oSelect.eq(count).attr('selected',true);

			liTag.attr('selected',false);
			liTag.eq(count).attr('selected','selected');
			
			if(typeof callback == 'function') {
				callback.call(this);
			}
			
			// select 값이 변경될 때 event를 생성하여 validation이
			// 적절히 수행되도록 하기 위한 추가
			$('select[name='+type+']').trigger('keyup');
		},
		createElement : function(obj,type) {
//			var liTag=obj.find('.list-wrap li');

			obj.each(function(i) {
				if(jQuery(this).attr('class').split(' ')[0]=='select'&&1>jQuery(this).find('select').size()) {
					
					jQuery('<select style="display:none;" id=h'+jQuery(this).attr('id')+' name='+jQuery(this).attr('id')+'>').appendTo(jQuery(this));
					
					var liTag=obj.find('li');
					liTag.each(function(i) {
						if(jQuery(this).parents('.select').attr('id')==type) {
							jQuery('<option value="'+jQuery(this).attr('rel')+'">'+jQuery(this).text()+'</option>').appendTo(obj.find('select'));
						}
					});
					jQuery('<option selected="selected" value="default">default</option>').appendTo(obj.find('select'));
				}
			});
		}
	};

	$.fn.formSelectbox = function( method ){
		// Method calling logic
		if ( methods[method] ) {
			return methods[ method ].apply( this, Array.prototype.slice.call( arguments, 1 ));
		} else if ( typeof method === 'object' || ! method ) {
			return methods.init.apply( this, arguments );
		} else {
		//	$.error( 'Method ' +  method + ' does not exist on jQuery.ncwUI.formSelectbox' );
		}    
	};
})(jQuery);