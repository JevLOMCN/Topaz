/* MIR4 */
var mir = {
  fullIndex : 0,
  aniCheck : null,
  getSection: function() {
    return ['story', 'class', 'growth', 'signature', 'media'];
  },
  passToSection: function() {
    if(!location.search.length) {
      return;
    }

    var search = location.search;
    var delay = 10;
    var section = search.replace('?s=', '').toLowerCase();
    var sectionArray = mir.getSection();
    var matchIndex;
    for(var i=0; i<sectionArray.length; i++) {
      if(sectionArray[i] === section) {
        matchIndex = i;
        continue;
      }
    }
    if(typeof(matchIndex) !== 'number') {
      return;
    }

    setTimeout(function() {
      $('.sectionWrap > div').eq(0).find('.inVertical').addClass('noneTransform');
    }, delay);
    setTimeout(function(){
      $('.sectionWrap > div').eq(0).find('.hiddenNav li').eq(matchIndex).trigger('click');
    }, delay + 10);
    setTimeout(function(){
      $('.inVertical').removeClass('noneTransform');
    }, delay + 50);
  },
  fullPage: function () { //fullPage
	
	$('.sectionWrap').fullpage({
		anchors: ['story'],
		menu: '.gnb',
		//responsiveHeight: 650,
		onLeave : function(origin, destination, direction){
			if(direction == 'up'){
				var pageIdx = $('.sectionWrap >div').eq(destination-1).find('.hiddenNav li.active').index();
				var subVerticalName = $('.sectionWrap >div').eq(destination-1).find('.inVertical >div').eq(pageIdx).attr('data-vercal-name');

				setTimeout(function(){
					$('body').removeClass().addClass('fp-viewing-'+subVerticalName);
				},1)
			}
		},
		afterLoad: function(origin, destination, direction){
      mir.fullIndex = destination;
			mir.aniCheck = true;

			$('.sectionWrap >div').eq(mir.fullIndex).find('.hiddenNav ul li:first-child').addClass('active').siblings().removeClass('active');
			$('.sectionWrap >div').eq(mir.fullIndex).attr('data-vertical','index0');
			$('.sectionWrap >div').eq(mir.fullIndex).find('.gameGnb ul li:first-child').addClass('active').siblings().removeClass('active');

			mir.format();
			mir.aniCheck = false;

			//영상 재생
			setTimeout(function(){
				//전설의 시작 영상
				if(origin == 'story' && $('.gameGnb ul li.active').index() == 0){

					if(!$('#bg_story video').attr('src')){
						player_story.src([
								{
										"src": "",
										"type": ""
								}
						]);
					}else{
						player_story.play();
					}
				}
			},500)

		}
	});

  },
  gnb: function () { //GNB Toggle

	$('.navList ul li p').on('mouseenter',function(){
		$('.head').addClass('gnbOpen');
	})
	$('.head').on('mouseleave',function(){
		$('.head').removeClass('gnbOpen');
	})

	$('.navList ul li').on('click',function(){
		$('.head').removeClass('gnbOpen');
	})

  },
  agreeAll: function () { //약관 전체동의하기

	$("#agreeAll").click(function(){
		$(".checkList").find( "input:checkbox" ).prop("checked" ,$(this).prop("checked") );
	})

	$(".checkList .check input:checkbox").click(function(){

		if(!$(this).prop("checked")){
			$('#agreeAll').prop("checked",false);
		}

		var allCheck = 0;
		$(".checkList .check").find( "input:checkbox" ).each(function(){
			if($(this).prop("checked")){
				allCheck++;
			}
		})

		if(allCheck == $(".checkList .check").length){
			$('#agreeAll').prop("checked",true);
		}
	});

  },
  btnUrl: function () { // URL 복사
	/*
	var urlCopy = new ClipboardJS('.btnUrl', {
	  text: function (trigger) {
		return $('.copyUrl').val();
	  }
	});
	urlCopy.on('success', function (e) {
	  mir.alertOpen('URL 복사가 완료되었습니다.');
	});

	//사이트url 공유
	var btnShareUrl = new ClipboardJS('.btnShareUrl', {
	  text: function (trigger) {
		return window.location.href;
	  }
	});
	btnShareUrl.on('success', function (e) {
	  mir.alertOpen('URL 복사가 완료되었습니다.');
	});
	*/
  },
  contentOpen: function (el) { //컨텐츠팝업-열기

	this.scrollOff();
	
	var parent = $('.contentPop')
	parent.show();
	parent.find(el).show().siblings().hide();
	
	var pop = parent.find('.popIn');
	
	setTimeout(function(){
		pop.css({ 'visibility' : 'visible',  'marginTop' : - (pop.outerHeight()/2)});
	},10)

  },
  contentClose: function () { //컨텐츠팝업-닫기

	this.scrollOn()
	$('.contentPop').hide(); 
	$('.contentPop .popIn').css({ 'visibility' : 'hidden', 'marginTop' : 0})

  },
  alertOpen: function (el,popClass,width,height) { //경고팝업-열기

	this.scrollOff();
	$('.alertText').html(el);
	$('.alertText').css({
		'width':width,
		'height':height
	})
	$('#popupAlert').addClass(popClass).show()
	
	var pop = $('#popupAlert .popIn');
	
	setTimeout(function(){
		pop.css({ 
			'visibility' : 'visible', 
			'marginTop' : - (pop.outerHeight()/2)
		});
	},10)

  },
  alertClose: function () { //경고팝업-닫기

	this.scrollOn()
	$('#popupAlert').hide().removeClass();
	$('.alertText').css({'width':'auto', 'height':'auto'})
	$('#popupAlert .popIn').css({ 'visibility' : 'hidden', 'marginTop' : 0})

  },
  youtubeOpen: function (id){ //유투브팝업-열기

	this.scrollOff();
	var src = 'https://www.youtube.com/embed/'+id+'?rel=0&showinfo=0&autoplay=1';
	$('#youtubeMovie iframe').attr('src',src);
	$('.youtubePop').show();

  },
  youtubeClose: function () { // 유투브팝업-닫기

	this.scrollOn()
	$('.youtubePop').hide();
	$('#youtubeMovie iframe').attr('src','');

  },
  rewardOpen: function (off, video){ //보상받기팝업-열기

	var randomSrc = ['88siCoM7ku0','XjHN2M7H_EY','AwEvakykKyk','YyHOiGkkRt4'] // 랜덤영상 4개
	var randomNum = Math.floor(Math.random() * 5);

	var src = 'https://www.youtube.com/embed/'+video+'?rel=0&showinfo=0&autoplay=1';
	$('#rewardYoutube iframe').attr('src',src);

	//비활성 보상받기 표시
	if(off == 'off'){
		$('.rewardPop').addClass('off');
	}

	$('.rewardPop').show();

	this.scrollOff();

  },
  rewardClose: function () { // 보상받기팝업-닫기

	this.scrollOn()
	//clearTimeout(rewardCheck);
	$('.rewardPop').hide().addClass('off');
	$('#rewardYoutube iframe').attr('src','');

  },
  scrollOff: function () { //팝업오픈 스크롤방지

	$.fn.fullpage.setAllowScrolling(false);

  },
  scrollOn: function () { //팝업오픈 스크롤해제

	$.fn.fullpage.setAllowScrolling(true);

  },
  fullVideo: function (el) { //Full Video

		var width = $(window).width(),
		pWidth,
		height = $(window).height(),
		pHeight;
		
		$video = $(el).find('video');
		
		if (width / (16/9) < height) { 
			pWidth = Math.ceil(height * (16/9));
			$video.width(pWidth).height(height).css({left: (width - pWidth) / 2, top: 0});
		} else { 
			pHeight = Math.ceil(width / (16/9));
			$video.width(width).height(pHeight).css({left: 0, top: (height - pHeight) / 2});
		}
  },
  pageChange: function (el) { //Full Video
	$('.pageChange').on('click',function(){
		mir.aniCheck = true;
		var $parent = $(this).parents('.page');
		if($parent.hasClass('subOpen')){
			$parent.removeClass('subOpen');
			$('html').removeClass('black');
		}else{
			$parent.addClass('subOpen');
			$('html').addClass('black');
		}
		setTimeout(function(){
			mir.aniCheck = false;
		},700)
		$('.video .youtube').empty();
	})

	//성장 슬라이드 영상 재생
	if($('#growthSlide1 video').attr('src')){
		player_growthSlide1.play()
	}else{
		player_growthSlide1.src([
				{
						"src": "",
						"type": ""
				}
		]);
	}

  },
  chaChange: function (el) { //Class Change

	var ani = true;

	$('.btnCha a').on('click',function(e){
	
		if(ani){
			var idx = $(this).attr('data-cha');
			
			$('#classList > ul > li').eq(idx).addClass('active').siblings().removeClass('active')
			$('.skillText,.skillIcon').each(function(){
				$(this).find('li').eq(0).addClass('active').siblings().removeClass('active');
			})

			$('.controlWrap').each(function(){
				$(this).find('.control a').eq(0).addClass('active').siblings().removeClass('active');
				$(this).find('.infoCha > div').eq(0).show().siblings().hide();
			});

			setTimeout(function(){
				ani = true;
			},100)
		}
		ani = false;

	})
  },
  growthSlide: function (el) { //Growth Slide
	$slickElement = $('.growthSlide ul').slick({
		dots: false,
		slidesToShow: 3,
		slidesToScroll: 1,
		centerMode: true,
		arrows: false,
		fade: false,
		infinite: false,
		autoplay: false,
		//autoplaySpeed: 4000,
		speed: 500,
		pauseOnHover:false,
		pauseOnDotsHover:false,
		pauseOnFocus:false,
		useCSS:false
	});

		

	$slickElement.on('afterChange', function(event, slick, currentSlide, nextSlide){

		if(currentSlide == 0){
			if(!$('#growthSlide1 video').attr('src')){
				player_growthSlide1.src([
						{
								"src": "",
								"type": ""
						}
				]);
			}else{
				player_growthSlide1.play()
			}

			player_growthSlide2.pause()
			player_growthSlide3.pause()
			player_growthSlide4.pause()

		}else if(currentSlide == 1){
			if(!$('#growthSlide2 video').attr('src')){
				player_growthSlide2.src([
						{
								"src": "",
								"type": ""
						}
				]);
			}else{
				player_growthSlide2.play()
			}
			player_growthSlide1.pause()
			player_growthSlide3.pause()
			player_growthSlide4.pause()

		}else if(currentSlide == 2){
			if(!$('#growthSlide3 video').attr('src')){
				player_growthSlide3.src([
						{
								"src": "",
								"type": ""
						}
				]);
			}else{
				player_growthSlide3.play()
			}
			player_growthSlide1.pause()
			player_growthSlide2.pause()
			player_growthSlide4.pause()

		}else if(currentSlide == 3){
			if(!$('#growthSlide4 video').attr('src')){
				player_growthSlide4.src([
						{
								"src": "",
								"type": ""
						}
				]);
			}else{
				player_growthSlide4.play()
			}
			player_growthSlide1.pause()
			player_growthSlide2.pause()
			player_growthSlide3.pause()
		}
		


		$('.growthSlide .slick-slide').not('.slick-current').each(function(){
			//$(this).find('.growthVideo video').get(0).pause()
		})


		$('.infoSwipe').removeClass('show');
	});


  },
 noticeSlide: function (el) { //Growth Slide
	var noticeSlide = $('.noticeSlide ul').slick({
		dots: false,
		slidesToShow: 1,
		slidesToScroll: 1,
		arrows: false,
		fade: false,
		infinite: true,
		autoplay: true,
		vertical: true,
		//autoplaySpeed: 4000,
		speed: 500,
		pauseOnHover:false,
		pauseOnDotsHover:false,
		pauseOnFocus:false,
	});
  },
  tabChange: function (el) { //Tab Change
	$('.tabList ul li').on('click',function(e){
		var idx = $(this).index();
		$(this).addClass('active').siblings().removeClass('active');
		$(this).parents('.tabList').parent().find('.tabCont ul li').eq(idx).show().siblings().hide();
		$('.video .youtube').empty();
	})
  },
  videoSkillChange: function (el) { //Video Skill Change
	$('.controlWrap .control a').on('click',function(e){
		var idx = $(this).index();
		$(this).parents('.controlWrap').find('.infoCha > div').eq(idx).show().siblings().hide();
		$(this).addClass('active').siblings().removeClass('active');
		$('.controlWrap .youtube').empty();

	})
  },
  video: function (el) { //video
	$('.video').on('click',function(e){
		var idx = $(this).index();
		var iframe = '<iframe width="100" height="120" src="https://www.youtube.com/embed/'+$(this).attr('data-mp4')+'?rel=0&showinfo=0&autoplay=1" allow="accelerometer; autoplay; encrypted-media; gyroscope; picture-in-picture"  frameborder="0" allowfullscreen="0"></iframe>'
		$(this).find('.youtube').append(iframe);
	})
  },
  mp4Video: function (el) { //mp4Video
	$('.mp4Wrap').on('click',function(e){
		//$(this).find('.mp4Video').show().find('video').get(0).play();
	})
  },
  wheelChange: function () { //Wheel Change
	$('#viewport').bind('wheel mousewheel DOMMouseScroll', function(e){

		if(mir.fullIndex == 1){ //게임소개 // 수정1119 
			e.preventDefault()

			if(mir.aniCheck){ return false; };

			if (e.originalEvent.deltaY < 0) { //위로
				
				if($('.fp-completely .hiddenNav li.active').prev().length > 0 ){
					$('.fp-completely .hiddenNav li.active').prev().trigger('click')
						mir.aniCheck = true
				}else{
					$.fn.fullpage.moveSectionUp();
				}

			} else { //아래로
				if($('.fp-completely .hiddenNav li.active').index() == 1){
					
					if(!$('.classSection .page').hasClass('subOpen')){
						$('.classSection .pageChange.sub').trigger('click');
						mir.aniCheck = true;

				}else{
						$('.fp-completely .hiddenNav li.active').next().trigger('click')

					}

				}else if($('.fp-completely .hiddenNav li.active').index() == 2){
					if(!$('.growthSection .page').hasClass('subOpen')){

						$('.growthSection .pageChange.sub').trigger('click');

						//성장 슬라이드 첫번째 영상 재생
						if($('#growthSlide1 video').attr('src')){
							player_growthSlide1.play()
						}else{
							player_growthSlide1.src([
									{
											"src": "",
											"type": ""
									}
							]);
						}

						mir.aniCheck = true
					}else{
						$('.fp-completely .hiddenNav li.active').next().trigger('click')
					}
				}else{
					if($('.fp-completely .hiddenNav li.active').next().length > 0 ){
						$('.fp-completely .hiddenNav li.active').next().trigger('click')
							mir.aniCheck = true
					}else{
						$.fn.fullpage.moveSectionDown();
					}
				}
				
			}

			return false;
		}

	});
  },
  format: function (el) { //format
	$('.video .youtube').empty();
	$('html').removeClass('black');
	$('.page').removeClass('subOpen');
	
	$('#classList > ul > li').eq(0).addClass('active').siblings().removeClass('active');
	$('.skillText,.skillIcon').each(function(){
		$(this).find('li').eq(0).addClass('active').siblings().removeClass('active');
	})


	$('.controlWrap').each(function(){
		$(this).find('.control a').eq(0).addClass('active').siblings().removeClass('active');
		$(this).find('.infoCha > div').eq(0).show().siblings().hide();
	})

	$('.tabCont ul li').eq(0).show().siblings().hide();

	setTimeout(function(){
		if($('.growthSection .growthSub .growthSlide .slick-center').index() != 0){
			$slickElement.slick('slickGoTo', 0);
		}

		/* @start 수정1015 */
		if($('.mediaSlide .slick-active').index() != 0){
			$slickMedia.slick('slickGoTo', 0);
		}
		/* @end 수정1015  */

		
	},100)

	setTimeout(function(){
		$('.infoSwipe').addClass('show');
		//$('.mediaMain .infoSwipe').addClass('show'); // 수정1015
	},1000)

	/* @start 수정1121 */
	/*
	$('.showcase').removeClass('enter videoComplted');
	$('.playShow').removeClass('active');
	document.getElementById('introVideo').pause();
    document.getElementById('introVideo').currentTime = 0;
	*/
	/* @end 수정1121  */

	
  },
  randomStone: function (el) { //Random Stone

	var stone = [4,5,6] // 랜덤마석 3종류
	var randomNum = Math.floor(Math.random() * 4);

	$('.cont10-random .ran').text(stone[randomNum]);
	mir.contentOpen('.cont10-random')
  },
  verticalMove: function (el) { //Vertical Move
	$('.hiddenNav ul li').on('click',function(){

		mir.aniCheck = true;

		var idx = $(this).index();

		//영상 재생
		setTimeout(function(){
     if(mir.fullIndex == 1){ //게임소개 //수정1119 
				if(idx == 0){
					if(!$('#bg_story video').attr('src')){
						player_story.src([  //전설의 시작 영상
								{
										"src": "",
										"type": ""
								}
						]);
					}else{
						player_story.play();
					}

				}else if(idx == 1){ 
					if(!$('#bg_class video').attr('src')){
						player_class.src([ //직업배경영상 로드
								{
										"src": "",
										"type": ""
								}
						]);
					}else{
						player_class.play();
					}

				}else if(idx == 2){ 
					if(!$('#bg_growth video').attr('src')){
						player_growth.src([ //성장 배경영상 로드
								{
									"src": "",
									"type": ""
								}
						]);
					}else{
						player_growth.play();
					}

				}
			}

		},500)



		if(mir.fullIndex == 1){ // 수정1119 
			$('.gameGnb ul li').eq(idx).addClass('active').siblings().removeClass('active');

      // set URL param
      mir.setUrl(idx);

			// if(idx == 0 || idx == 1 || idx == 2){
			if(idx !== 3){
				$('.gameGnb').addClass('white');
			}else{
				$('.gameGnb').removeClass('white');
			}
		}

	
		var $parent = $(this).parents('.verticalSection');
		$(this).addClass('active').siblings().removeClass('active');

		$parent.attr('data-vertical','index'+idx);
		var subVerticalName = $parent.find('.inVertical >div').eq(idx).attr('data-vercal-name');
		$('body').removeClass().addClass('fp-viewing-'+subVerticalName);
		
		setTimeout(function(){
			mir.aniCheck = false;
			mir.format();
		},700)
	})

	//게임소개 서브메뉴
	$('.gameGnb ul li').on('click',function(){
      mir.aniCheck = true;
      var idx = $(this).index();
      var $parent = $(this).parents('.verticalSection');
      $(this).addClass('active').siblings().removeClass('active');
      $parent.find('.hiddenNav ul li').eq(idx).addClass('active').siblings().removeClass('active');

      $parent.attr('data-vertical','index'+idx);
      var subVerticalName = $parent.find('.inVertical >div').eq(idx).attr('data-vercal-name');
      $('body').removeClass().addClass('fp-viewing-'+subVerticalName);
      
      console.log(idx);
      if(idx == 3) {
        $('.gameGnb').removeClass('white');
      } else {
        $('.gameGnb').addClass('white');
      }

    // set URL param
    mir.setUrl(idx);

		setTimeout(function(){
			mir.aniCheck = false;
		},700)
	})

	//메인 사전예약 이동
	$('.mouse,.mainCont .btn a').on('click',function(){
		$(this).parents('.verticalSection').find('.hiddenNav li').eq(1).trigger('click');
	})

  },
  silentFake: function (idx,subIdx) { //Silent Fake
    $.fn.fullpage.silentMoveTo(idx);
    $('.sectionWrap > div').eq(idx-1).find('.inVertical').addClass('noneTransform');

    // set URL param
    mir.setUrl(subIdx);

    setTimeout(function(){
      $('.sectionWrap > div').eq(idx-1).find('.hiddenNav li').eq(subIdx).trigger('click');
    },10)
    setTimeout(function(){
      $('.inVertical').removeClass('noneTransform');
    },30)
  },

  setUrl: function(index) {
    if(typeof(index) !== 'number') {
      return;
    }
    var sectionArray = mir.getSection();
    var sectionUrl = location.origin + location.pathname + '?s=' + sectionArray[index];
    history.pushState('', '', sectionUrl);
  },

  resizeFullpage: function () { //Resize Fullpage
	var pageIdx = 1;
	if(mir.fullIndex == 0){
		pageIdx = 1;
	}else{
		pageIdx = mir.fullIndex;
	}
	var navIdx = $('.fp-completely .hiddenNav li.active').index();
	var subVerticalName = $('.sectionWrap>div').eq(pageIdx-1).find('.inVertical > div').eq(navIdx).attr('data-vercal-name')
	setTimeout(function(){
		$('body').removeClass().addClass('fp-viewing-'+subVerticalName);
	},400)
  },
  chaSkillList: function () { //Cha Skill List
	$('.skillIcon ul li').mouseenter(function(){
		var idx = $(this).index();
		$(this).parents('.skillInfo').find('.skillText ul li').eq(idx).addClass('active').siblings().removeClass('active');
		$(this).addClass('active').siblings().removeClass('active');
	})
  },

  init: function () {

    this.fullPage(); //fullPage
    this.gnb(); //GNB Toggle
    this.agreeAll(); //약관 전체동의하기
    this.btnUrl(); //URL 복사
	this.fullVideo('.bgVideo'); //Full Video	 
	this.pageChange(); //Page Change	 
	this.chaChange(); //Class Change 
	this.growthSlide(); //Growth Slide
	this.tabChange(); //Tab Change
	this.videoSkillChange(); //Video Skill Change
	this.video(); //Video
	this.mp4Video(); //mp4Video
	this.wheelChange(); //Wheel Change
	this.noticeSlide(); //Notice Slide
	this.verticalMove(); //Vertical Move
	this.chaSkillList(); //Cha Skill List
  this.passToSection(); // URL파라미터로 특정 세션 바로가기
  },
}


$(document).ready(function(){
	$('html').addClass('load');

	 mir.init(); // mir init
	 $(this).find('.reserveForm').cmmValidator(); //유효성 체크 실행

	//파라미터로 서브 페이지 이동
	if(params.subIdx){
		setTimeout(function(){
			$('.'+window.location.hash.substr(1)).parents('.verticalSection').find('.hiddenNav li').eq(params.subIdx).trigger('click');
		},1000)
	}
})


$(window).on({
	resize:function() {
		mir.fullVideo('.bgVideo');
		mir.resizeFullpage();
	},
	load : function(){
		mir.fullVideo('.bgVideo');
	}
})


//params
function getSearchParameters() {
      var prmstr = window.location.search.substr(1);
      return prmstr != null && prmstr != "" ? transformToAssocArray(prmstr) : {};
}

function transformToAssocArray( prmstr ) {
    var params = {};
    var prmarr = prmstr.split("&");
    for ( var i = 0; i < prmarr.length; i++) {
        var tmparr = prmarr[i].split("=");
        params[tmparr[0]] = tmparr[1];
    }
    return params;
}

var params = getSearchParameters();



/* @start 수정1012 */

$(document).ready(function(){

	//쿠키 체크
	if (getCookie("todayShow") != "done") {  //수정1107
		$(".floatPop").show();
	}else{
		$('.floatBanner').show();
	}

	//쿠키 굽기
	$('.floatPop .popIn .closeFloat').on('click',function(){

		if($('#todayNot').prop('checked')){
			setCookie("todayShow", "done", 1); //수정1107
		}

		$(".floatPop").hide();
		$('.floatBanner').show();
	})
})


//쿠키 설정
function setCookie(cName, cValue, cDay) {
  var expire = new Date();
  expire.setDate(expire.getDate() + cDay);
  cookies = cName + '=' + escape(cValue) + '; path=/;'; 
  if (typeof cDay != 'undefined') cookies += ';expires=' + expire.toGMTString() + ';';
  document.cookie = cookies;
}


//쿠키 가져오기
function getCookie(cName) {
  cName = cName + '=';

  var cookieData = document.cookie;
  var start = cookieData.indexOf(cName);
  var cValue = '';

  if (start != -1) {
    start += cName.length;
    var end = cookieData.indexOf(';', start);
    if (end == -1) end = cookieData.length;
    cValue = cookieData.substring(start, end);
  }
  return unescape(cValue);
}

//편성표 열기
function tableOpen(){
	$('.tablePop').show();
	$.fn.fullpage.setAllowScrolling(false);
}

//편성표 닫기
function tableClose(){
	$('.tablePop').hide();
	$.fn.fullpage.setAllowScrolling(true);
}

/* @end 수정1012  */



/* @start 수정1015 */
	$slickMedia = $('.mediaSlide').slick({
		dots: false,
		slidesToShow:3,
		slidesToScroll: 1,
		arrows: false,
		fade: false,
		infinite: false,
		autoplay: false,
		//autoplaySpeed: 4000,
		speed: 300,
		draggable:false,
		pauseOnHover:false,
		pauseOnDotsHover:false,
		pauseOnFocus:false,
		useCSS:false
	});
	
	var mediaLength = $('.mediaSlide li').length;

	$slickMedia.on('afterChange', function(event, slick, currentSlide, nextSlide){
		$('.infoSwipe').removeClass('show');

		if(currentSlide == 0){
			$('.mediaInner').addClass('first').removeClass('last middle');
		}else if(currentSlide == mediaLength -3) {
			$('.mediaInner').addClass('last').removeClass('first middle');
		}else if(mediaLength > 4 && currentSlide > 0) {
			$('.mediaInner').addClass('middle').removeClass('last first');
		}
	});

	$('.mediaInner .arrow div.prev,.fakeArea div.prev').on('click',function(){
		$slickMedia.slick('slickPrev');
	})

	$('.mediaInner .arrow div.next,.fakeArea div.next').on('click',function(){
		$slickMedia.slick('slickNext');
	})


/* @end 수정1015  */





/* @start 수정1121 */
/*
$(function(){
	var dt =0;  // 서버시간과 오차
	var xmlHttp;
	var myrem;

	function srvTime(url){
	  if(url == undefined) { url = window.location.href.toString(); }
	  if(window.XMLHttpRequest){
	   xmlHttp = new XMLHttpRequest();
	  }else if(window.ActiveXObject){
	   xmlHttp = new ActiveXObject('Msxml2.XMLHTTP');
	  }else{
	   return null; 
	  }

	  xmlHttp.open('HEAD',url,false); 
	  xmlHttp.setRequestHeader("Content-Type", "text/html");
	  xmlHttp.send('');
	  return xmlHttp.getResponseHeader("Date"); 
	}

	// 카운트다운 
	//CountDownTimer('11/19/2020 04:00 PM', 'newcountdown');  //수정1113

	function CountDownTimer(dt, id){

		var end = new Date(dt);
		var _second = 1000;
		var _minute = _second * 60;
		var _hour = _minute * 60;
		var _day = _hour * 24;
		var timer;

		function showRemaining() {

			var now = new Date(srvTime());
			var distance = end - now;

			//카운트 다운 끝 체크
			if (distance < 0) {
				clearInterval(timer);
				$('.day').text('00')
				$('.hour').text('00')
				$('.minute').text('00')
				$('.second').text('00')
				 return;
			}

			var days = Math.floor(distance / _day);

			var hours = Math.floor((distance % _day) / _hour);

			var minutes = Math.floor((distance % _hour) / _minute);

			var seconds = Math.floor((distance % _minute) / _second);

			if(parseInt(days) < 10){ 
				days = 0 + "" + days; 
			} 

			if(parseInt(hours) < 10){ 
				hours = 0 + "" + hours; 
			}

			if(parseInt(minutes) < 10){ 
				minutes = 0 + "" + minutes; 
			} 

			if(parseInt(seconds) < 10){ 
				seconds = 0 + "" + seconds; 
			} 
			$('.day').text(days)
			$('.hour').text(hours)
			$('.minute').text(minutes)
			$('.second').text(seconds)
		}

		timer = setInterval(showRemaining, 1000);

	}
})

var myVid =  document.getElementById('introVideo');
myVid.addEventListener('ended',myHandler,false);

function myHandler(e) {
	$('.showcase').addClass('videoComplted');
	var src = $('.showEnter .videoArea .youtube').attr('video-src');
	var iframe = '<iframe width="100" height="120" src="'+src+'" allow="accelerometer; autoplay; encrypted-media; gyroscope; picture-in-picture"  frameborder="0" allowfullscreen="0"></iframe>'
	setTimeout(function(){
		$('.showEnter .videoArea .youtube').append(iframe);
	},200)
}

$('.playShow').not('.disabled').click(function(){
	$('.showcase').addClass('enter');
	mir.aniCheck = true;
	myVid.play();
})

$('.playShow').mouseenter(function(){
	$('.playShow').addClass('active');
})

$('.playShow').mouseleave(function(){
	$('.playShow').removeClass('active');
})


$('.showcase .closeEnter').click(function(){
	$('.showcase').removeClass('enter videoComplted');
	$('.showEnter .videoArea .youtube').empty();
	myVid.pause();
    myVid.currentTime = 0;
	$.fn.fullpage.setAllowScrolling(true);

	mir.aniCheck = false;
})
*/



/* @end 수정1121 */
/* @start 수정1210 */
$(function(){
	//메인 다운로드 버튼 토글
	$('.btnPlay .box .toggle').on('click',function(){
		$(this).siblings('ul').stop().slideToggle('fast');
	})
})
/* @end 수정1210 */
