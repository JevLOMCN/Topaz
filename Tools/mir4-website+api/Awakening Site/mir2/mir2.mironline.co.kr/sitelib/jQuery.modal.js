(function($) {

	var methods={
		$modal:null, $modal_width:0, $modal_height:0,
		$modal_top:0, $modal_left:0, $ie6:null,that:null,
		init : function( options,callback ){
			var settings = $.extend({
				modalOpen:'.modal',
				modalClose:'#modal-close',
				modalWrap:'.modalWrapper',
				opacity:0.5,
				animationTime:500
			},options);

			var $this=jQuery(this);
			
			if(settings.modalOpen.length < 0) {
				// isn't exist tooltip, exit function
				return;
			}

			// IE6(boolean)
			//$ie6 = typeof document.addEventListener !== 'function' && !window.XMLHttpRequest;
			
			if(settings.modalOpen!='empty') {
				jQuery(settings.modalOpen).click(function(e) {
					methods.showModal(settings,callback,this);
					e.stopPropagation();
				});
			} else {
				methods.showModal(settings,callback,this);
			}

			jQuery(settings.modalClose).click(function(e) {
				methods.hideModal(settings);
				methods.hideDimmed(settings);
				e.stopPropagation();
			});

			jQuery('body').click(function(e){
					if(jQuery('#dimmed').size()>0 && jQuery(e.target).attr('id')!='modal-wrapper' && jQuery(e.target).parents('#modal-wrapper').size() < 1 && jQuery('#modal-wrapper').css('display')=='block') {
						methods.hideModal(settings);
						methods.hideDimmed(settings);
					}
					if(jQuery('#dimmed').size()>0 && jQuery(e.target).attr('class')!=settings.modalWrap.split('.')[0] && jQuery(e.target).parents(settings.modalWrap).size() < 1 && jQuery(settings.modalWrap).css('display')=='block') {
						methods.hideModal(settings);
						methods.hideDimmed(settings);
					}
				e.stopPropagation();
			});

			jQuery(window).resize(function(e) {
				if(jQuery('#dimmed').size()>1) {
					jQuery('#dimmed')
						.css({
							'width':parseInt(jQuery(window).width()) > 960 ? parseInt(jQuery(window).width()) : 968+'px',
							'height':parseInt(jQuery(document).height())+'px'
						});
					}
			});
		},
		dimmed: function(dimmed_width,dimmed_height,settings) {
			if(jQuery('#dimmed').size()==0) {
				jQuery('<div id="dimmed"></div>').appendTo('body')
					.css({
						'position':'absolute',
						'top':0,
						'left':0,
						'width':dimmed_width > 960 ? dimmed_width-1 : 968+'px',
						'height':dimmed_height+'px',
						'background-color':'#333',
						'opacity':0.1,
						'z-index':3
					}).fadeTo(settings.animationTime,settings.opacity);
			} else {
				jQuery('#dimmed')
					.css({
						'width':dimmed_width > 960 ? dimmed_width : 968+'px',
						'height':dimmed_height+'px',
						'z-index':3
					}).fadeTo(settings.animationTime,settings.opacity);
			}
		},
		/* only ie6 */
		posisitionModal: function() {
			jQuery('#modal-wrapper').css('top', jQuery(document).scrollTop() + 'px');
		},
		showModal: function(settings,callback,_this) {
			//if($ie6) {
				//positionModal();
			//}
			this.that=_this;
			jQuery('body').click(function(e){
				if(jQuery(_this).attr('class')=='modal') {
					if(jQuery('#dimmed').size()>0 && jQuery(e.target).attr('id')!='modal-wrapper' && jQuery(e.target).parents('#modal-wrapper').size() < 1 && jQuery('#modal-wrapper').css('display')=='block') {
						methods.hideModal(settings);
						methods.hideDimmed(settings);
					}
				} else {
					if(jQuery('#dimmed').size()>0 && jQuery(e.target).attr('class')!=settings.modalWrap.split('.')[0] && jQuery(e.target).parents(settings.modalWrap).size() < 1 && jQuery(settings.modalWrap).css('display')=='block') {
						methods.hideModal(settings);
						methods.hideDimmed(settings);
					}
				}
				e.stopPropagation();
			});


			methods.dimmed(parseInt(jQuery(window).width()),parseInt(jQuery(document).height()),settings);
			if(jQuery(_this).attr('class')=='modal') {
				jQuery('#modal-wrapper')
					.css({
						'zoom':1,
						'position':'absolute',
						'top':'50%',
						'left':'50%',
						'margin-top':-(jQuery('#modal-wrapper').height()/2)+$(window).scrollTop()+'px',
						'margin-left':-(jQuery('#modal-wrapper').width()/2)+'px',
						'z-index':4
					}).fadeIn(settings.animationTime);
			} else {
				jQuery(settings.modalWrap)
					.css({
						'zoom':1,
						'position':'absolute', 
						'top':'50%',
						'left':'50%',
						'margin-top':-(jQuery(settings.modalWrap).height()/2)+$(window).scrollTop()+'px',
						'margin-left':-(jQuery(settings.modalWrap).width()/2)+'px',
						'z-index':4
					}).fadeIn(settings.animationTime);
			}
			if(typeof callback == 'function') {
				callback.call(this);
			}
		},
		hideModal: function(settings) {
			jQuery('body').unbind('click');
			//jQuery(settings.modalClose).unbind('click');

			if(jQuery(settings.modalWrap).size()<1) {
				jQuery('#modal-wrapper').fadeOut(settings.animationTime);
			} else {
				jQuery(settings.modalWrap).fadeOut(settings.animationTime);
			}
		},
		hideDimmed: function(settings){
			jQuery('#dimmed').fadeOut(settings.animationTime);
		}
	};

	$.fn.modal = function( method ){
		// Method calling logic
		if ( methods[method] ) {
			return methods[ method ].apply( this, Array.prototype.slice.call( arguments, 1 ));
		} else if ( typeof method === 'object' && method != null ) {
			return methods.init.apply( this, arguments );
		} else {
			$.error( 'Method ' +  method + ' does not exist on jQuery.ncwUI.modal' );
		}    
	};
})(jQuery);