/* Simple J-vaScript Inheritance

* By John Resig http://ejohn.org/

* MIT Licensed.

*/

// Inspired by base2 and Prototype

(function(){

  var initializing = false, fnTest = /xyz/.test(function(){xyz;}) ? /\b_super\b/ : /./;
  // The base Class implementation (does nothing)

  this.Class = function(){};  // Create a new Class _this inherits from this class

  Class.extend = function(prop) {

    var _super = this.prototype;
    // Instantiate a base class (but only create the instance,

    // don't run the init constructor)

    initializing = true;

    var prototype = new this();

    initializing = false;
    // Copy the properties over onto the new prototype

    for (var name in prop) {

      // Check if we're overwriting an existing function

      prototype[name] = typeof prop[name] == "function" &&

        typeof _super[name] == "function" && fnTest.test(prop[name]) ?

        (function(name, fn){

          return function() {

            var tmp = this._super;
            // Add a new ._super() method _this is the same method

            // but on the super-class

            this._super = _super[name];
            // The method only need to be bound temporarily, so we

            // remove it when we're done executing

            var ret = fn.apply(this, arguments);

            this._super = tmp;
            return ret;

          };

        })(name, prop[name]) :

        prop[name];

    }
    // The dummy class constructor

    function Class() {

      // All construction is actually done in the init method

      if ( !initializing && this.init )

        this.init.apply(this, arguments);

    }
    // Populate our constructed prototype object

    Class.prototype = prototype;
    // Enforce the constructor to be what we expect

    Class.prototype.constructor = Class;
    // And make this class extendable

    Class.extend = arguments.callee;
    return Class;

  };

})();

;(function($) {
	var swipeCommon=Class.extend({
		elem:null,
		currentPosition:0,
		positionRange:100,
		autoRolling:false,
		rollingTime:0,
		animationTime:0,
		currentPaging:0,
		timer:null,
		paging:null,
		imgWidth:0,
		imgHeight:0,
		count:3,
		animationStatus:true,
		init: function(settings, options, elem) {
			this.options = jQuery.extend({}, settings, options);
			this.rollingTime=this.options.rollingTime;
			this.animationTime=this.options.animationTime;
			this.autoRolling=this.options.autoRolling;
			this.paging=this.options.paging;
			this.pagingActive=this.options.pagingActive;
			this.currentPaging=this.options.currentPaging;
			this.elem=elem;
		},

		getSize:function(el,fn) {
			var img = new Image();
			img.onload=function() { fn(img.width,img.height); };
			img.src = el.attr('src');
		},

		fix: function (event) {
			return event || window.event;
		},

		matrixToArray : function(matrix) {
			return matrix.substr(7, matrix.length - 8).split(', ');
		}
	});

	swipeUI=swipeCommon.extend({
		navigation:null,
		navigationWidth:null,
		navigationHeight:null,
		pagingHeight:null,
		currentPaging:null,
		nSwipeThreshold:null, //at android 30, not android 25
		nCurrentWidth:null,
		currentSlide:null,

		init: function(settings, options, elem) {
			// Call the inherited version of swipeCommon
			var _this=this;

			this._super(settings, options, elem);

			this.navigation=jQuery(this.elem).find('li');

			this.pagingHeight=jQuery(this.elem).find(this.paging).innerHeight();
			this.createSwipe();
			this.setEachPos(this.currentPaging);
			this.currentSlide=this.currentPaging;
			jQuery(this.paging).find('a').removeClass('on').eq(this.currentSlide).addClass('on');
		},

		createSwipe:function() {
			var _this=this;

			// 모바일이 아닌 경우
			this.setEachPos(this.currentPosition);
			this.bAutoRolling();
		},

		bAutoRolling:function(){
			var _this=this;

			if(this.autoRolling) {
				this.timer=null;
				this.timer=setInterval(
					function() {
						_this.rolling(_this.navigationWidth,_this.navigationWidth*-1);
				},this.rollingTime);
			}

		},

		resizeEvent:function() {
			this.setEachPos(this.currentPosition);
		},

		setEachPos:function(currentPosition) {
			var _this=this,pos=this.currentPosition;

			this.navigation.each(
				function(idx) {
					_this.navigation.eq(idx).css({'left':pos+'%'});
				 }

			);
			this.navigation.eq(this.currentPaging).css({'visibility':'visible'});

			//webkit browser에서는 img 사이즈 구해오는 타이밍이 타 브라우져와 상이하여 이미지 로딩 후 사이즈를 가져오는 형태로 구현

			if(parseInt(this.elem.find('li').eq(0).css('width')) == 0){
				this.getSize(this.elem.find('img').eq(0),jQuery.proxy(function(w,h){
					this.navigationWidth=(this.isMobile) ? jQuery(window).width() : parseInt(w);
					this.navigationHeight=parseInt(h);

					this.elem.css({
						'width':(this.isMobile) ? jQuery(window).width() : parseInt(w),
						'height':h+this.pagingHeight
					});

					this.elem.find('ul').css({
						'width':'100%'
					});

					this.navigation.css({
						'width':this.navigationWidth,
						'height':this.navigationHeight
					});
				},this));

			} else {
				this.navigationWidth=(this.isMobile) ? jQuery(window).width() : parseInt(this.elem.find('li').eq(0).css('width'));
				this.navigationHeight=parseInt(this.elem.find('li').eq(0).css('height'));

				this.elem.css({
					'width':(this.isMobile) ? jQuery(window).width() : this.elem.find('li').eq(0).css('width'),
					'height':this.navigationHeight
				});

				this.elem.find('ul').css({
					'width':'100%'
				});

				this.navigation.css({
					'width':this.elem.find('li').eq(0).css('width'),
					'height':this.navigationHeight
				});

			}

			this.nCurrentWidth = jQuery(window).width();

			this.bIsIphone=this.isIthing;

		},

		setAnimation:function(currentSlide) {
			var _this=this;

			this.navigation.each(function(idx) {
				if(jQuery(this).css('visibility')=='visible') {
					if(parseInt(jQuery(this).css('left'))==0){
						_this.currentSlide=idx;
					}
				}
			});

			if(this.currentSlide==currentSlide) return;

			if(this.currentSlide < currentSlide) {
				//prev rolling
				this.rolling(_this.navigationWidth*-1,_this.navigationWidth,currentSlide);
			} else {
				//next rolling
				this.rolling(_this.navigationWidth,_this.navigationWidth*-1,currentSlide);
			}

		},
		rolling:function(prevLeft,nextLeft,currentSlide){
			var _this=this;
			if(this.animationStatus==true){
				this.animationStatus=false;
				this.navigation.eq(this.currentSlide)
					.stop(true,true).animate({'left':prevLeft+'px'},this.animationTime,function(){});

				if(typeof currentSlide != 'undefined') {
						this.currentSlide=currentSlide;
				} else {
					if(prevLeft>0) {
						if(this.currentSlide<1) {
							this.currentSlide = this.navigation.size()-1;
						} else {
							this.currentSlide = this.currentSlide -1;
						}
					} else {
						this.currentSlide = this.currentSlide +1;
						if(this.navigation.size()==this.currentSlide) {
							this.currentSlide=0;
						}
					}

				}
				// paging --

				this.currentPage(this.currentSlide);
				this.navigation.eq(this.currentSlide)
					.css({'left':nextLeft+'px'})
					.stop(true,true)
					.css({'visibility':'visible'}).animate({'left':0},this.animationTime,function() {
						_this.animationStatus=true;
					});
			};
		},

		movePrev:function() {

			var _this = this;
			if(this.bIsIphone) {
				setTimeout(function() {
					_this.setEachPos([0,100,-100]);
					_this.elem.find('li').eq(2).insertBefore(_this.elem.find('li').eq(0));
				},200);
			} else {
				this.setEachPos([0,100,-100]);
				this.elem.find('li').eq(2).insertBefore(_this.elem.find('li').eq(0));
			}
		},

		moveNext:function() {

			var _this = this;

			if(this.bIsIphone) {
				setTimeout(function() {
					_this.setEachPos([100,-100,0]);
					_this.elem.find('li').eq(0).insertAfter(_this.elem.find('li').eq(2));
				},200);

			} else {
				this.setEachPos([100,-100,0]);
				this.elem.find('li').eq(0).insertAfter(_this.elem.find('li').eq(2));
			}

		},

		currentPage:function(currentSlide){

				jQuery(this.paging).find('a').removeClass('on').eq(currentSlide).addClass('on');

		}
	});
	swipeEvent=swipeUI.extend({

		init: function(settings, options, elem) {

			// Call the inherited version of swipeCommon and swipeUI;
			this._super(settings, options, elem);
			this.options = jQuery.extend({}, settings, options);
			var _this=this;

			// addEvent orientation or resize
			(typeof window.orientationchange != 'undefined') ? jQuery(window).bind('orientation', function() {
				_this.resizeEvent();
			})
			: jQuery(window).bind('resize', function() {
				_this.resizeEvent();
			});
			// web Event --

			jQuery(this.options.paging+' a').bind('click',function() {
				_this.setAnimation(jQuery(this).parent('li').index());
			})

			elem
			.bind('mouseover',function() {
				clearInterval(_this.timer);
				_this.autoRolling=false;
			})

			.bind('mouseout',function() {
				if(_this.options.autoRolling) {
					_this.autoRolling=true;
					_this.bAutoRolling();
				}

			});
			
			elem.parent().find('.button-next').click(function() {
				//prev rolling
				_this.rolling(_this.navigationWidth*-1,_this.navigationWidth,this.currentSlide);
			});

			elem.parent().find('.button-prev').click(function() {
				//next rolling
				_this.rolling(_this.navigationWidth,_this.navigationWidth*-1,this.currentSlide);
			});
			
			if($.fn.swipe) {
				jQuery(elem).each(function(idx) {
					elem.parent().swipe( {swipeLeft:swipeLeft, swipeRight:swipeRight,threshold:5} ); 
					function swipeLeft(event){
						jQuery('.gallery_wrap .list_content').css('z-index',1).find('.barcode_layer').hide();
						jQuery('.gallery_wrap .list_content').css('z-index',1).find('.download_layer').hide();
						_this.rolling(_this.navigationWidth*-1,_this.navigationWidth,this.currentSlide);
					}
					function swipeRight(event){
						jQuery('.gallery_wrap .list_content').css('z-index',1).find('.barcode_layer').hide();
						jQuery('.gallery_wrap .list_content').css('z-index',1).find('.download_layer').hide();
						_this.rolling(_this.navigationWidth,_this.navigationWidth*-1,this.currentSlide);
					}
				});
			}

		}

	});
	// Start a plugin

	jQuery.fn.slide = function(options) {
		// Don't act on absent elements -via Paul Irish's advice
		if ( this.length ) {

			return this.each(function(){
				// Create a new nn object via the Prototypal Object create
				var settings = $.extend({
					paging:'div.paging-navi',
					currentPaging:0,
					pagingActive:'on',
					rollingTime:2000,
					animationTime:500,
					autoRolling:true
				},options);
				var myNn = new swipeEvent(settings,options,jQuery(this));
			});
		}
	};
})(jQuery);