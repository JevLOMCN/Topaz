/*
// set Wemade Global Navigation Bar
// version : 2.0.0.2						20120711	by smalldevil
// version : 2.0.1.2	add Joymax			20120911	by smalldevil
// version : 2.0.2.2	add lostsaga		20130313	by smalldevil
// version : 2.1.0.1	edit mir billing	20130801	by smalldevil
*/

// add.202402 원스탑리뉴얼 도메인
var onestopRenewalDomain = "https://mir2-onestop.mironline.co.kr"; // 운영
// add.202403 미르3 도메인
var mir1Domain = "https://thelegendofmir.uk/mir1/main.html";
if(document.location.hostname.indexOf('mir2-stage.mironline.co.kr') == 0){
    onestopRenewalDomain = "https://mir2-stage-onestop.mironline.co.kr"; // 스테이지
    mir1Domain = "https://thelegendofmir.uk/mir1/main.html";
}else if(document.location.hostname.indexOf('mir2test.mironline.co.kr') == 0
    || document.location.hostname.indexOf('localhost') == 0){
    onestopRenewalDomain = "https://mir2-dev-onestop.mironline.co.kr"; // dev
    mir1Domain = "https://thelegendofmir.uk/mir1/main.html";
}

/* IE 체크 */
function getInternetExplorerVersion() {
    var rv = -1;
    if (navigator.appName == 'Microsoft Internet Explorer') {
        var ua = navigator.userAgent;
        var re = new RegExp('MSIE ([0-9]{1,}[\.0-9]{0,})');
        if (re.exec(ua) != null)
            rv = parseFloat(RegExp.$1);
    } else if (navigator.appName == 'Netscape') {
        var ua = navigator.userAgent;
        var re = new RegExp('Trident/.*rv:([0-9]{1,}[\.0-9]{0,})');
        if (re.exec(ua) != null)
            rv = parseFloat(RegExp.$1);
    }
    return rv;
}
var IEVer = getInternetExplorerVersion();
//mir or wmg or joymax or lostsaga service type 20110809 by smalldevil
function fnSiteType() {
    var strURL = document.location.hostname;
    if (strURL.indexOf("mir2.co.kr") > 1 || strURL.indexOf("mir") == 0 || strURL.indexOf("mir3.co.kr") > 1 || strURL.indexOf("mirx.co.kr") > 1 || strURL.indexOf("onestop.wemade.com") > 1) {
        return "MIR"
    } else if (strURL.indexOf("joymax.com") > 1) {
        return "JOY"
    } else if (strURL.indexOf("lostsaga.com") > 1) {
        return "LTS"
    } else {
        return "WMG"
    }
}
//get domain 20110810 by smalldevil
function fnDomain() {
    var strURL = document.location.hostname;
    var arrUrl = strURL.split(".");
    if (fnSiteType() == "MIR") {
        if (strURL.indexOf("mir") == 0) { //미르2
            return arrUrl[0]
        } else {
            return arrUrl[1]
        }
    } else {
        return arrUrl[0]
    }
}
//set service type
function getServerLevel() {
    var strURL = document.location.hostname;
    var strServer = "";
    if (strURL.indexOf("dev.") >= 0) {
        strServer = "dev";
    } else if (strURL.indexOf("test.") >= 0) {
        strServer = "test";
    }

    //strServer = "dev";
    return strServer;
}
//set cookie
function setGnbCookie(name, value, cookieDomain) {
    var today = new Date();
    var exp = new Date(today.getTime() + 24 * 60 * 60 * 1000 * 7); //Expire Time : 7 Day
    var cookies = document.cookie.split(";");
    for (i = 0; i < cookies.length; i++) {
        if (cookies[i].indexOf(name + "=") >= 0) {
            break;
        }
    }
    document.cookie = name + "=" + value + ";path=/;Domain=" + cookieDomain + ";expires=" + exp.toGMTString();
}
//get cookie
function getGnbCookie(sName) {
    var aCookie = document.cookie.split("; ");
    for (var i = 0; i < aCookie.length; i++) {
        var aCrumb = aCookie[i].split("=");
        if (sName == aCrumb[0]) {
            return unescape(aCrumb[1]);
        }
    }
    return null;
}
//delete cookie
function delGnbCookie(sName) {
    var exp = new Date();
    exp.setDate(exp.getDate() - 1);
    document.cookie = sName + "=; expires=" + exp.toGMTString() + "; path=/;Domain=mironline.co.kr";
}
//trim
function replaceTrim(str) {
    return str.replace(/^\s*|\s*$/g, "");
}
// We Cash Billing(mir billing)
function openGnbBillingWinMir(obj) {
    // mir2
    var getMirBillUrl = (document.location.hostname.indexOf("mir2") == 0) ? "/common/getBillUrl" : "/sitelib/get_billingurl_v1002.asp";
    jQuery.ajax({
        url: getMirBillUrl,
        type: "POST",
        data: "t=" + obj,
        dataType: "text",
        timeout: 2000,
        error: function () {
            alert("Failed to retrieve Mir Billing recharge URL.\n\nPlease try again later.");
            return false;
        },
        success: function (data) {
            // 결과 페이지 확인
            if (data.toLowerCase().indexOf("Log in") == -1) {
                if (data.length > 10) {
					if(obj == 'P'){
						//var win = window.open('https://onestop.mironline.co.kr/pay/main_price07.asp', '_blank');
                        var win = window.open(onestopRenewalDomain + '/member/hwan/gift', '_blank');
						win.focus();
					}else{
						window.open(data, 'winWeCashBilling', 'width=550px,height=580px,status=no,toolbar=no,menubar=no,location=no,scrollbars=no')
					}
                } else {
                    alert('Currently, the Mir integrated payment system is under maintenance.\n\nPlease refer to the notice and try using it again..');
                    return false;
                }
            } else {
                goMemberLogin();
            }
        }
    });
}
//wmg billing
function openGnbBillingWinWmg() {
    var script = document.createElement("script");
    script.setAttribute("type", "text/javascript");
    script.setAttribute("src", "//common.icarusonline.co.kr/gnb/get_billingurl.asp");
    var hh = document.getElementsByTagName("head")[0];
    hh.appendChild(script);
}
//joymax billing
function openBillingWinJoymax() {
    /*
    var nWin = window.open("http://kr.joymax.com/silkroad/Silkroad_Front.jmx?workURL=http://krsilkroadcp.joymax.com/itemmall/silkcharge.asp","chargeWinJoymax","toolbar=0,menubar=0,scrollbars=no,resizable=no,width=425,height=567;");
    if (nWin != undefined){
    nWin.focus();
    }else{
    alert("팝업 차단을 해제해 주시고 다시 시도해 주세요.");
    }
    */
    top.document.location.href = "http://kr.joymax.com/silkroad/Silkroad_Front.jmx?workURL=http://krsilkroadcp.joymax.com/itemmall/silkcharge.asp";
}
//lostsaga billing - editing
function openBillingWinLostsaga() {
    top.document.location.href = "http://www.lostsaga.com/support/cash.asp";
}
//open Charge Cash
function openChargePayWinGnb() {
    if (fnSiteType() == "JOY") {
        openBillingWinJoymax();
    } else if (fnSiteType() == "LTS") {
        openBillingWinLostsaga();
    } else {
        if (fnSiteType() == "MIR") {
            openGnbBillingWinMir();
        } else {
            openGnbBillingWinWmg();
        }
    }
}
// open Present Bill
function openPresentPayWinGnb() {
    if (fnSiteType() == "MIR") {
        openGnbBillingWinMir("P");
    } else {
        var script = document.createElement('script');
        script.setAttribute("type", "text/javascript");
        script.setAttribute('src', "//common.icarusonline.co.kr/gnb/get_present_billingurl.asp?f=W");
        var hh = document.getElementsByTagName('head')[0];
        hh.appendChild(script);
    }
}
//go CS Site
function goCsUrl() {
    if (fnSiteType() == "MIR") {
		window.open(onestopRenewalDomain, '_blank');
    } else {
		window.open("http://cs.icarusonline.co.kr", '_blank');
    }
}
//goStartGame 20110919 by smalldevil
if (getGnbCookie("gnbGameStart") != null) {
    if (getGnbCookie("gnbGameStart") == document.location.hostname) {
        goStartGame();
        delGnbCookie("gnbGameStart");
    }
}

document.write("\
<style type=\"text/css\">\
	#gnb{height:auto;}\
	#Gnbwrap {margin:0;padding:0;}\
	#Gnbwrap * {margin: 0;padding: 0;font-family: '맑은고딕',malgun gothic,'돋움',dotum,'굴림',gulim,arial,verdana,helvetica,sans-serif;font-size: 12px;font-weight: normal;line-height: 100%;color: #282828;letter-spacing: 0;word-break: break-all;}\
	#Gnbwrap, #Gnbwrap div, #Gnbwrap ul, #Gnbwrap li, #Gnbwrap p, #Gnbwrap strong, #Gnbwrap span, #Gnbwrap h1, #Gnbwrap h2{margin:0;padding:0;border:0;font-style:inherit;font-size:100%;font-family:inherit;line-height:18px;list-style-type:none;font-size:12px;}\
	#Gnbwrap a {display:inline-block;line-height:16px;text-decoration:none;outline:none;select-dummy:expression(this.hideFocus=true);}\
	#Gnbwrap a:hover,a:active,a:focus {text-decoration:none;}\
	img{border:0;}\
	#Gnbwrap{position:relative;z-index:9000;width:100%;min-width:1100px;height:37px;background:#fefefe url(\'//web-cdn.mironline.co.kr/gnb/images/common/gnb_bg.png\') repeat-x left bottom;}\
	#Gnbwrap .Gheader{position:relative;overflow:hidden;vertical-align:top;z-index:100;width:1100px;margin:0 auto;}\
	#Gnbwrap h1{float:left;padding-top:3px;}\
	#Gnbwrap ul.firstMenu{float:left;padding:9px 0 0 24px;}\
	#Gnbwrap ul.firstMenu li{float:left;height:16px;margin:0 0 0 10px;padding:0 0 0 11px;background: url(\'//web-cdn.mironline.co.kr/gnb/images/common/gnb_dot_01.png\') no-repeat left 5px;}\
	#Gnbwrap ul.firstMenu li a {color:#4e4e4e;letter-spacing:-1px;}\
	#Gnbwrap ul.firstMenu li a:hover {color:#2fc675;}\
	#Gnbwrap ul.firstMenu li img, #Gnbwrap ul.Gutil li img{display:block;vertical-align:top;}\
	#Gnbwrap ul.firstMenu li.first{margin:0;padding:0;background:none;}\
");
// joymax, lostsaga
if (fnSiteType() == "JOY" || fnSiteType() == "LTS") {
    document.write("\
			#Gnbwrap ul.Gutil{overflow:hidden;position:absolute;right:0;top:9px;}\
		");
} else {
    document.write("\
			#Gnbwrap ul.Gutil{overflow:hidden;position:absolute;right:0;top:9px;}\
		");
}
document.write("\
	#Gnbwrap ul.Gutil li{float:left;height:16px;margin:0 0 0 10px;padding:0 0 0 11px;background: url(\'//web-cdn.mironline.co.kr/gnb/images/common/gnb_dot_01.png\') no-repeat left 5px;}\
	#Gnbwrap ul.Gutil li.banner{width:168px;height:37px;padding:0;background:none;}\
	#Gnbwrap ul.Gutil li.first{margin:0;padding:0;background:none;}\
	#Gnbwrap ul.Gutil li a {color:#8e8e8e;letter-spacing:-1px;}\
	#Gnbwrap ul.Gutil li a strong {font-weight:bold;color:#6e6e6e;line-height:16px;}\
	#Gnbwrap ul.Gutil li.weme{padding:1px 0 0 11px;background: url(\'//web-cdn.mironline.co.kr/gnb/images/common/gnb_dot_02.png\') no-repeat left 2px;}}\
	#Gnbwrap ul.Gutil li.more{position:relative;z-index:120;padding:10px 0 0 0;background:none;}\
	#Gnbwrap ul.Gutil li.more div.morewrap{position:absolute;left:0;top:10px;z-index:120;width:76px;height:0px;overflow:hidden;cursor:pointer;background: url(\'//web-cdn.mironline.co.kr/gnb/images/common/gnb_more_bg.gif\') no-repeat 0 16px;}\
	#Gnbwrap ul.Gutil li.more div.morewrap div.morelist{padding:18px 0 0 4px;line-height:0!important;font-size:0!important;}\
	#Gnbwrap ul.Gutil li.more div.morewrap div.morelist a{display:block;padding-top:5px;height:15px;overflow:hidden;font-family:dotum;text-align:left;color:#fff;padding-left:4px;letter-spacing:-1px;text-decoration:none;background: url(\'//web-cdn.mironline.co.kr/gnb/images/common/gnb_more_dot.gif\') no-repeat;}\
	#Gnbwrap ul.Gutil li.more div.morewrap div.morelist a.first{background:none;}\
	#Gnbwrap ul.Gutil li.more div.morewrap div.morelist a img{display:block;}\
</style>\
");
document.write("\
<div id=\"Gnbwrap\">\
	<div class=\"Gheader\">\
		<h1><a href=\"http://www.ydonline.co.kr\" target=\"_top\"><img src=\"\" alt=\"\" title=\"ydonline\" /></a></h1>\
		<ul class=\"firstMenu\">\
			<li><a href=\"" + mir1Domain + "\" target=\"_top\">The Legend of Mir 1</a></li>\
		</ul>\
	");
document.write("\
		<ul class=\"Gutil\">\
");
//Joymax : JOY, LTS
/*if (fnSiteType() == "MIR") {
    document.write("\
			<li class=\"first\"><a href=\"https://mir2-stage-onestop.mironline.co.kr/auth/signin/mirRedirect?gameCode=2&returnUrl=/member/hwan/gift\" target=\"_blank\">Give a gift</a></li>\
			<li><a href=\"https://mir2-stage-onestop.mironline.co.kr/auth/signin/mirRedirect?gameCode=2&returnUrl=/pay\" target=\"_blank\">Charge</a></li>\
		");
} else {
    document.write("\
			<li class=\"first\"><a href=\"javascript:void(0);\" onclick=\"openChargePayWinGnb();\">Charge</a></li>\
		");
}
document.write("\
			<li><a href=\"https://mir2-stage-onestop.mironline.co.kr/auth/signin/mirRedirect?gameCode=2&returnUrl=\" target=\"_blank\">Customer Service Center</a></li>\
");*/
document.write("\
            <li class=\"first\"><a href=\"" + onestopRenewalDomain + "/auth/signin/mirRedirect?gameCode=2&returnUrl=/member/hwan/gift\" target=\"_blank\">Give a gift</a></li>\
			<li><a href=\"" + onestopRenewalDomain + "/auth/signin/mirRedirect?gameCode=2&returnUrl=/pay\" target=\"_blank\">Charge</a></li>\
			<li><a href=\"" + onestopRenewalDomain + "/auth/signin/mirRedirect?gameCode=2&returnUrl=\" target=\"_blank\">Customer Service Center</a></li>\
");
/* gnb 배너영역 추가시작*/
if (document.location.hostname.indexOf("mir2E") > -1 ) {
    document.write("\
			<li>\
				<a href=\"javascript:void(0);\" id=\"nav_shop\"><img src=\"//web-cdn.mironline.co.kr/mir2/2016_event/cash_shop/images/nav_shop.gif\" alt=\"Limited sale at Fantasy Point!\" /></a>\
		    </li>\
		");
	document.write("\
			</ul>\
		</div>\
		<div id=\"gnb_banner\">\
			<div class=\"gnb_banner_tit\"><img src=\"//web-cdn.mironline.co.kr/mir2/2016_event/cash_shop/images/nav_banner_tit.gif\" alt=\"\" /></div>\
			<div class=\"gnb_banner_img\"><a href=\"/hwan/store\"><img src=\"//web-cdn.mironline.co.kr/mir2/2016_event/cash_shop/images/gnb_banner.gif\" alt=\"\" /></a></div>\
		</div>\
	</div>\
	");
}else{
	
	document.write("\
			</ul>\
		</div>\
	</div>\
	");
}
$(document).ready(function(){
	$('#gnb_banner').bind('mousemove', clearLayerTimer);
	$('#gnb_banner').bind('mouseout', function() {setLayerTimer(3500)});
	$('#nav_shop').bind('mousemove', clearLayerTimer);

	$('#nav_shop').on('click',function(){
		$('#gnb_banner').fadeIn(1500);
	});

});
var timer1 = "";
function setLayerTimer(seq){
	timer1 = setTimeout(function() {
		$('#gnb_banner').fadeOut(1500);
	}, seq); //1000 = 1초
}
function clearLayerTimer(){
	clearTimeout(timer1);
}