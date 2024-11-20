var csDomain = "";
var onestopDomain = "";
function init(){
	var commonDomain = document.domain;
	if(commonDomain.indexOf("test")>-1){
		csDomain = "cstest.icarusonline.co.kr";
		//onestopDomain = "onestoptest.mironline.co.kr";
	}else{
		csDomain = "cs.icarusonline.co.kr";
		//onestopDomain = "onestop.mironline.co.kr";
	}
	// add.202402 원스탑리뉴얼 도메인
	onestopDomain = "mir2-onestop.mironline.co.kr"; // 운영
	if(document.location.hostname.indexOf('mir2-stage.mironline.co.kr') == 0){
		onestopDomain = "mir2-stage-onestop.mironline.co.kr"; // 스테이지
	}else if(document.location.hostname.indexOf('mir2test.mironline.co.kr') == 0){
		onestopDomain = "mir2-dev-onestop.mironline.co.kr"; // dev
	}
}
function onopen()
{
var url =
"http://www.ftc.go.kr/info/bizinfo/communicationViewPopup.jsp?wrkr_no=2118623306";
window.open(url, "communicationViewPopup", "width=750, height=700;");
}
function onopen2()
{
var url =
"http://www.ftc.go.kr/info/bizinfo/communicationViewPopup.jsp?wrkr_no=8868600733";
window.open(url, "communicationViewPopup", "width=750, height=700;");
}
function makingFooter(a) {
	switch (a) {
		case "icarus":
			markup(a);
			setting(a, '<div class="logo_h2"><h2><a href="" target="_blank"><img src="" alt=""></a></h2></div>', '<div class="gradeImg"><img src=""></div>');
			gradeSet();
			break;
		case "icarus_obt":
			markup(a);
			setting(a, '<div class="logo_h2"><h2><a href="" target="_blank"><img src="" alt=""></a></h2></div>', '<div class="gradeImg"><img src=""></div>');
			gradeSet();
			break;
		case "icarus_obt_CH_nhn":
			markup(a);
			b = '<div class="logo_h2"><h2 style="padding:0 0 18px 0;"><a href="http://www.navercorp.com/" target="_blank"><img src="//web-cdn.mironline.co.kr/wemade/common/footer/logo_icarus_obt_naver.png" alt=""></a></h2><h2><a href="http://www.ydonline.co.kr" target="_blank"><img src="//web-cdn.mironline.co.kr/wemade/common/footer/logo_yd.png" alt=""></a></h2></div>';
			c = '<div class="gradeImg"><img src="//web-cdn.mironline.co.kr/wemade/common/footer/grade_icarus_obt.png"></div>';
			d = '<p class="ch_info">Naver Corporation is a communications sales intermediary, and the obligations and responsibilities for game transactions are with each provider.<br>We are not legally responsible for any problems that may arise in relation to this..</p>';
			jQuery(".footer_wrap form").after(d);
			setting(a, b, c);
			gradeSet();
			break;
		case "mir2":
			markup(a);
			setting(a, '<div class="logo_h2"><h2><a href="" target="_blank"><img src="" alt=""></a></h2></div>', '<div class="gradeImg"><img src="//web-cdn.mironline.co.kr/wemade/common/footer/grade_mir2.png?ver=20240709"></div>');
			gradeSet();
			break;
		case "mir3":
			markup(a);
			setting(a, '<div class="logo_h2"><h2><a href="" target="_blank"><img src="" alt=""></a></h2></div>', '<div class="gradeImg"><img src="//web-cdn.mironline.co.kr/wemade/common/footer/grade_mir3.png"></div>');
			gradeSet();
			break;
		case "wemade_online":
			markup(a);
			setting(a, '<div class="logo_h2"><h2><a href="http://www.ydonline.co.kr" target="_blank"><img src="//web-cdn.mironline.co.kr/wemade/common/footer/logo_wemade_online.png" alt=""></a></h2></div>');
			break;
		case "wemade_freebilling":
			markup(a);
			setting(a, '<div class="logo_h2"><h2><a href="http://www.ydonline.co.kr" target="_blank"><img src="//web-cdn.mironline.co.kr/wemade/common/footer/logo_yd.png" alt=""></a></h2></div>');
			break;
		case "wemade_error":
			markup(a);
			setting(a, '<div class="logo_h2"><h2><a href="http://www.ydonline.co.kr" target="_blank"><img src="//web-cdn.mironline.co.kr/wemade/common/footer/logo_yd.png" alt=""></a></h2></div>');
			break;
		case "wemade_ad":
			markup(a);
			jQuery("#benefitList").css("overflow", "hidden");
			jQuery("#benefitList .list").css("overflow", "hidden");
			setting(a, '<div class="logo_h2"><h2><a href="http://www.ydonline.co.kr" target="_blank"><img src="//web-cdn.mironline.co.kr/wemade/common/footer/logo_yd.png" alt=""></a></h2></div>');
			break;
		case "wemade_funpc":
			markup(a);
			setting(a, '<div class="logo_h2"><h2><a href="http://www.ydonline.co.kr" target="_blank"><img src="//web-cdn.mironline.co.kr/wemade/common/footer/logo_yd.png" alt=""></a></h2></div>');
			break;
		case "wemade_onestop":
			markup(a);
			setting(a, '<div class="logo_h2"><h2><a href="" target="_blank"><img src="" alt=""></a></h2></div>');
			break;
		case "wemade_cs":
			markup(a);
			setting(a, '<div class="logo_h2"><h2><a href="http://www.ydonline.co.kr" target="_blank"><img src="//web-cdn.mironline.co.kr/wemade/common/footer/logo_yd.png" alt=""></a></h2></div>');
			break;
		case "wemade_bill":
			markup(a);
			setting(a, '<div class="logo_h2"><h2><a href="" target="_blank"><img src="" alt=""></a></h2></div>');
			break;
		case "weme":
			markup(a);
			setting(a);
			break;
		case "weme_404":
			markup(a);
			setting(a);
			break;
		case "weme_mobile":
			markup(a), setting(a)
	}
}
function markup(a) {
	var  footerHtml = jQuery('<link rel="stylesheet" type="text/css" href="/sitelib/onestop/common_footer.css" />' +
		'<div class="footer_wrap clearfix"><div class="information">' +
		'<ul class="util_area clearfix" style="font-weight:bold; text-align: center; padding-bottom: 10px;">' +
		'<li style="display:inline-block; float:none;"><a href="http://chuanqico.com/" target="_blank">About Us</a><span>&nbsp;*&nbsp;</span></li>' +
		'<li style="display:inline-block; float:none;"><a href="https://'+onestopDomain+'/support/terms?termsCategory=service" target="_blank">Terms of Use</a><span>&nbsp;*&nbsp;</span></li>' +
		'<li style="display:inline-block; float:none;"><a href="https://'+onestopDomain+'/support/terms?termsCategory=privacy" target="_blank" ><strong>Privacy Policy</strong></a><span>&nbsp;*&nbsp;</span></li>' +
		'<li style="display:inline-block; float:none;"><a href="https://'+onestopDomain+'/support/terms?termsCategory=youth" target="_blank" >Youth Protection Policy</a><span>&nbsp;*&nbsp;</span></li>' +
		'<li style="display:inline-block; float:none;"><a href="https://'+onestopDomain+'" target="_blank" >Customer Service Center</a><span>&nbsp;*&nbsp;</span></li>' +
		'<li style="display:inline-block; float:none;" class="gradeArea"><a href="javaScript:void(0);">Game usage rating</a></li>' +
		'</ul>' +
		'<form name="frm" style="text-align:center;">' +
		'<input name="wrkr_no2" type="hidden" value="8868600733" />' +
		'<p class="address_area">Company Name: Electric IP Co., Ltd. | CEO: Park Gwan-ho | Business Registration Number: 886-86-00733 | Mail Order Business Report: No. 2018-Seongnam Bundang-0253 | <a href=\"javascript:onopen2();\">Business Information Verification</a>' +
		'<br>Gyeonggi-do Seongnam-si Bundang-gu Daewangpangyo-ro 644beon-gil 49 Wemix Tower | Phone: 1670-6310 E-mail: <a href="mailto:mirhelp@wemade.com">mirhelp@wemade.com</a><br>' +
		'</p>' +
		'<p class="copyright_area">© ChuanQi IP Co.,Ltd. All Rights Reserved.</p>' +
		'</form></div></div>');
	footerHtml.prependTo("#footer");
	/*
	"silkroad_CH" == a ? jQuery('<link rel="stylesheet" type="text/css" href="/sitelib/onestop/common_footer.css" /><div class="footer_wrap clearfix"><div class="information"><ul class="util_area clearfix"><li><a href="http://www.joymax.co.kr/" target="_blank">회사소개</a><span>|</span></li><li><a href="http://kr.joymax.com/portal/Joymax_Front.jmx?workURL=http://krportalcp.joymax.com/notarget/privacy_policy/privacy_policy.jmx">이용약관</a><span>|</span></li><li><a href="http://kr.joymax.com/portal/Joymax_Front.jmx?workURL=http://krportalcp.joymax.com/support/operation_policy.jmx">운영정책</a><span>|</span></li><li><a href="http://kr.joymax.com/portal/joymax_front.jmx?workURL=http://krportalcp.joymax.com//support/private_policy.jmx"><strong>개인정보처리방침/청소년보호정책</strong></a><span>|</span></li><li class="gradeArea"><a href="javaScript:void(0);">게임이용등급</a></li></ul><form name="frm"><input name="wrkr_no" type="hidden" value="2098124419" /><p class="address_area">㈜조이맥스 대표이사 : 이길형 사업자등록번호 : 214-86-15315 통신판매업 신고번호 제 송파-727호 l <a href="javaScript:joymax_licensee();">사업자정보확인</a><br />경기도 성남시 분당구 대왕판교로 644번길 DTC타워 10층 고객센터 : 1661-3195 팩스 : 1661-3194 이메일 : <a href="mailto:contact@joymax.com">contact@joymax.com</a></p><p class="copyright_area">ⓒ JOYMAX Co., Ltd. All Rights Reserved.</p></form></div></div>').prependTo("#footer") : "wemade_online" == a || "wemade_freebilling" == a || "wemade_error" == a || "wemade_ad" == a || "wemade_funpc" == a || "wemade_onestop" == a || "wemade_cs" == a || "wemade_bill" == a ? jQuery('<link rel="stylesheet" type="text/css" href="/sitelib/onestop/common_footer.css" /><div class="footer_wrap clearfix"><div class="information"><ul class="util_area clearfix"><li><a href="http://corp.mironline.co.kr" target="_blank">회사소개</a><span>|</span></li><li><a href="https://jobs.mironline.co.kr" target="_blank">채용안내</a><span>|</span></li><li><a href="http://'+csDomain+'/Policy/PolicyMember.aspx" target="_blank">이용약관</a><span>|</span></li><li><a href="http://'+csDomain+'/Policy/PolicyPersonal.aspx" target="_blank" ><strong>개인정보처리방침</strong></a><span>|</span></li><li><a href="http://'+csDomain+'/Policy/PolicyYoung.aspx" target="_blank" >청소년보호정책</a><span>|</span></li></ul><form name="frm"><input name="wrkr_no2" type="hidden" value="8868600733" /><p class="address_area">㈜ 전기아이피 대표이사 : 장현국 경기도 성남시 분당구 대왕판교로 644번길 49 위메이드타워<br>전화: 1670-6310 E-mail: <a href="mailto:mirhelp@wemade.com">mirhelp@wemade.com</a><br>사업자번호 : 886-86-00733 통신판매업신고 : 제2018-성남분당-0253호  <a href=\"javascript:onopen2();\">사업자정보확인</a></p><p class="copyright_area">© ChuanQi IP Co.,Ltd. All Rights Reserved.</p></form></div></div>').prependTo("#footer") : "icarus_obt_CH_nhn" == a ? jQuery('<link rel="stylesheet" type="text/css" href="/sitelib/onestop/common_footer.css" /><div class="footer_wrap clearfix"><div class="information"><ul class="util_area clearfix"><li><a href="http://www.ydonline.co.kr" target="_blank">회사소개</a><span>|</span></li><li><a href="http://'+csDomain+'/Policy/PolicyMember.aspx" target="_blank">이용약관</a><span>|</span></li><li><a href="http://www.naver.com/rules/privacy.html" target="_blank" ><strong>개인정보처리방침</strong></a><span>|</span></li><li><a href="http://pcbang.hangame.com/" target="_blank" >한게임PC방</a><span>|</span></li><li class="gradeArea"><a href="javaScript:void(0);">게임이용등급</a><div class="gradeImg"><img src="//web-cdn.mironline.co.kr/wemade/common/footer/grade_icarus_obt.png"></div></li></ul><form name="frm"></form></div></div>').prependTo("#footer") : "weme" == a || "weme_404" == a ? jQuery('<link rel="stylesheet" type="text/css" href="/sitelib/onestop/common_footer.css" /><div class="footer_wrap clearfix"><div class="policy"><ul><li><a href="http://corp.mironline.co.kr/" target="_blank">회사소개</a><em>|</em></li><li><a href="http://jobs.mironline.co.kr/recruit/main.asp" target="_blank">인재채용</a><em>|</em></li><li><a href="http://corp.mironline.co.kr/company/partnership.asp" target="_blank">제휴제안</a><em>|</em></li><li><a href="http://weme.mironline.co.kr/policy/policy.asp?tab=1">이용약관</a><em>|</em></li><li><a href="http://weme.mironline.co.kr/policy/policy.asp?tab=2">서비스정책</a><em>|</em></li><li><a href="http://weme.mironline.co.kr/policy/policy.asp?tab=3"><strong>개인정보처리방침</strong></a></li></ul></div><div class="address">㈜ 와이디온라인 대표이사 : 변종섭 서울특별시 강남구 학동로 97길 20, 3층<br>전화: 1670-6310 E-mail: <a href="mailto:mirhelp@wemade.com">mirhelp@wemade.com</a><br>사업자번호 : 211-86-23306 통신판매업신고 : 제2010-서울강남-03304호  <a href=\"javascript:onopen();\">사업자정보확인</a></div><p class="copyright">Copyright ⓒ <strong>WeMade Entertainment</strong> Co.,Ltd. All Rights Reserved.</p></div>').prependTo("#footer") : "mir2" == a || "mir3" == a ? jQuery('<link rel="stylesheet" type="text/css" href="/sitelib/onestop/common_footer.css" /><div class="footer_wrap clearfix"><div class="information"><ul class="util_area clearfix"><li><a href="http://chuanqico.com/" target="_blank">회사소개</a><span>|</span></li><li><a href="https://'+onestopDomain+'/account/main_membership12.asp" target="_blank">이용약관</a><span>|</span></li><li><a href="https://'+onestopDomain+'/account/main_membership12.asp?v=3cfada52" target="_blank" ><strong>개인정보처리방침</strong></a><span>|</span></li><li><a href="https://'+onestopDomain+'/account/main_membership12.asp?v=3d08ef74" target="_blank" >청소년보호정책</a><span>|</span></li><li><a href="https://'+onestopDomain+'" target="_blank" >고객센터</a><span>|</span></li><li class="gradeArea"><a href="javaScript:void(0);">게임이용등급</a></li></ul><form name="frm"><input name="wrkr_no2" type="hidden" value="8868600733" /><p class="address_area">㈜ 전기아이피 대표이사 : 박관호 경기도 성남시 분당구 대왕판교로 644번길 49 위메이드타워<br>전화: 1670-6310 E-mail: <a href="mailto:mirhelp@wemade.com">mirhelp@wemade.com</a><br>사업자번호 : 886-86-00733 통신판매업신고 : 제2018-성남분당-0253호  <a href=\"javascript:onopen2();\">사업자정보확인</a></p><p class="copyright_area">© ChuanQi IP Co.,Ltd. All Rights Reserved.</p></form></div></div>')
	.prependTo("#footer") : "icarus_obt" == a ? jQuery('<link rel="stylesheet" type="text/css" href="/sitelib/onestop/common_footer.css" /><div class="footer_wrap clearfix" style="width:970px !important;"><div class="information" style="width:800px !important;"><ul class="util_area clearfix"><li><a href="http://www.ydonline.co.kr/Home/HomeMain.aspx" target="_blank">회사소개</a><span>|</span></li><li><a href="http://'+csDomain+'/Policy/PolicyMember.aspx" target="_blank">이용약관</a><span>|</span></li><li><a href="http://'+csDomain+'/Policy/PolicyPersonal.aspx" target="_blank" ><strong>개인정보처리방침</strong></a><span>|</span></li><li><a href="http://'+csDomain+'/Policy/PolicyYoung.aspx" target="_blank" >청소년보호정책</a><span>|</span></li><li><a href="https://'+csDomain+'" target="_blank" >고객센터</a><span>|</span></li><li class="gradeArea"><a href="javaScript:void(0);">게임이용등급</a></li></ul><form name="frm"><input name="wrkr_no2" type="hidden" value="8868600733" /><p class="address_area">㈜ 전기아이피 대표이사 : 장현국 경기도 성남시 분당구 대왕판교로 644번길 49 위메이드타워<br>전화: 1670-6310 E-mail: <a href="mailto:mirhelp@wemade.com">mirhelp@wemade.com</a><br>사업자번호 : 886-86-00733 통신판매업신고 : 제2018-성남분당-0253호  <a href=\"javascript:onopen2();\">사업자정보확인</a></p>	<p class="copyright_area"></p></form></div></div>')
	.prependTo("#footer") : jQuery('<link rel="stylesheet" type="text/css" href="/sitelib/onestop/common_footer.css" /><div class="footer_wrap clearfix"><div class="information"><ul class="util_area clearfix"><li><a href="http://www.ydonline.co.kr/Home/HomeMain.aspx" target="_blank">회사소개</a><span>|</span></li><li><a href="http://'+csDomain+'/Policy/PolicyMember.aspx" target="_blank">이용약관</a><span>|</span></li><li><a href="http://'+csDomain+'/Policy/PolicyPersonal.aspx" target="_blank" ><strong>개인정보처리방침</strong></a><span>|</span></li><li><a href="http://'+csDomain+'/Policy/PolicyYoung.aspx" target="_blank" >청소년보호정책</a><span>|</span></li><li><a href="https://'+csDomain+'" target="_blank" >고객센터</a><span>|</span></li><li class="gradeArea"><a href="javaScript:void(0);">게임이용등급</a></li></ul><form name="frm">><input name="wrkr_no2" type="hidden" value="8868600733" /><p class="address_area">㈜ 전기아이피 대표이사 : 장현국 경기도 성남시 분당구 대왕판교로 644번길 49 위메이드타워<br>전화: 1670-6310 E-mail: <a href="mailto:mirhelp@wemade.com">mirhelp@wemade.com</a><br>사업자번호 : 886-86-00733 통신판매업신고 : 제2018-성남분당-0253호  <a href=\"javascript:onopen2();\">사업자정보확인</a></p>	<p class="copyright_area"></p></form></div></div>').prependTo("#footer")

	 */
}
function setting(a, b, c) {
	jQuery(".footer_wrap").parent().addClass(a + "_footer");
	jQuery(".footer_wrap .information").before(b);
	jQuery(".footer_wrap .information .util_area .gradeArea a").after(c)
}
function licensee() {
	window.open("http://www.ftc.go.kr/info/bizinfo/communicationViewPopup.jsp?wrkr_no=" + frm.wrkr_no.value, "communicationViewPopup", "width=750,height=700;")
}
function nhnPop(a, b, c, d) {
	window.open(a, b, "width=" + c + ",height=" + d + ",status=no, resizeable=no;")
}
function gradeSet() {
	jQuery(".footer_wrap .information .util_area .gradeArea a").hover(function() {
		jQuery(this).next(".gradeImg").fadeIn(200)
	}, function() {
		jQuery(this).next(".gradeImg").fadeOut(200)
	})
};