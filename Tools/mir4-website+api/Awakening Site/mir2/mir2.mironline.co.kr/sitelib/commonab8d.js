/*
 * JQuery 용 ajax
 */
function callAjax(type, url, data, functionName) {
	jQuery.ajax( {
		type : type,
		url : url,
		data : data,
		success : function(msg) {
			functionName(jQuery.trim(msg));
		},
		error : function(msg) {
			//alert("에러");
		}
	});
}

/*
	ajax용
*/
function getListAfter(rData){
	if (rData.split(":")[0]=="[ERR]"){
		alert(rData.split(":")[1]);
		return false;
	}
	return true;
}

function dummyFunction(){
	
}

function chkRegPattern(pType,strInput){
	var regPattern;
	var arrResult;
	if(pType != 'undefined' && strInput != 'undefined'){
		switch (pType){
			case 'num' : regPattern = /^[\d]+[\d]$/;break;
			case 'han' : regPattern = /^[가-힣]+[가-힣]$/;break;
			case 'eng' : regPattern = /^[a-zA-Z]+[a-zA-Z]$/;break;
			case 'ju1' : regPattern = /^([\d]{2})(0[1-9]{1}|1[0-2]{1})(0[1-9]{1}|[1-2][\d]{1}|3[0-1]{1})$/;break;
			case 'ju2' : regPattern = /^([1-4]{1})([\d]{6})$/;break;
			case 'uid' : regPattern = /^([a-zA-Z]{1})([\w-]{5,14})/;break;
			case 'pwd' : regPattern = /^[\w\W]{6,15}/;break;
			case 'ans' : regPattern = /^[가-힣\w\s-]{2,20}/;break;
			case 'ma1' : regPattern = /^[\w]+/;break;
			case 'ma2' : regPattern = /^[\w.-]+.[a-zA-Z]{2,5}/;break;
			case 'con1' : regPattern = /^[0]{1}[1-6]{1}[0-9]{0,1}/;break;
			case 'con2' : regPattern = /^[1-9]{1}[0-9]{2,3}/;break;
			case 'con3' : regPattern = /^[0-9]{4}/;break;
			case 'nick' : regPattern = /^[a-zA-Z가-힣]{1}[가-힣\w-]{1,11}/;break;
			case 'char' : regPattern = /^[a-zA-Z가-힣]{1}[a-zA-Z가-힣]{1,19}/;break;
			case 'email' : regPattern = /^([0-9a-zA-Z_-]+)@([0-9a-zA-Z_-]+)(\.[0-9a-zA-Z_-]+){1,2}$/;break;
			default : return false; break;
		}
		result = regPattern.test(strInput);
		return result;
		//result = strInput.match(regPattern);
		/*result = regPattern.exec(strInput);
		if ( result.split(",")(0) == strInput){return true;
		}else{return false;}*/
	}else{
		return false;
	}
}

function chkBlank(str){
	if (str.indexOf(" ") != -1){
		return false;
	}else{
		return true;
	}
}

function chkStrLen(str) {
	var strCnt = 0;
	for(var i = 0; i < str.length; i++){
		strCnt += (str.charCodeAt(i) > 127) ? 2: 1;
	}
	return strCnt;
}

function errMsg(thisfield, msg) {
	alert(msg);
	thisfield.focus();
}

function createForm(fName, fMethod, fAction, fTarget){
	var objForm = document.createElement("form");
	objForm.name=fName;
	objForm.method=fMethod;
	objForm.action=fAction;
	objForm.target=fTarget;
	return objForm;
}

function addHidden(objF, iptName, iptValue){
	var objInput = document.createElement("input");
	var isIE  = (navigator.appVersion.indexOf("MSIE") != -1) ? true : false;
	if (objF.iptName == undefined){
		objInput.type = "hidden";
		objInput.id = iptName;
		objInput.name = iptName;
		objInput.value = iptValue;
		if (isIE == true){
			objF.insertBefore(objInput);
		}else{
			objF.appendChild(objInput);
		}
	}else{
		objF.iptName.value = iptValue;
	}
	return objF;
}

function getCookie(sName){
	var aCookie = document.cookie.split("; ");
	for (var i=0; i < aCookie.length; i++){
		var aCrumb = aCookie[i].split("=");
		if (sName == aCrumb[0]){
			return unescape(aCrumb[1]);
		}
	}
	return null;
}


//배너 로그 저장용 호출 (로더용)
function setBannerLog(bannerSeq, subSeq, strTarget, strLinkUrl){
	/*
	var folder = "";
	var frm = document.frmPageLog;
	if (frm !=undefined) {
		document.getElementById('intBmsSeq').value = encSSeq;
		document.getElementById('intBltSeq').value = encSeq;
		saveCurBannerLog();
	}
	*/
	if (strTarget == 'BLANK'){
		winBannerLoc = window.open('about:blank', 'winBanner','');
		if (winBannerLoc != undefined){
			winBannerLoc.location.href = strLinkUrl; return;
		}
		location.reload(); return;
	}else{
		if (strLinkUrl != '#'){
			location.href = strLinkUrl; return;
		}
	}
}

//tracking code
function goCross(v1,v2,v3,v4,type){
    var df3 = document.fmEC3;
    df3.target="hiddenfrm";
    df3.cd.value = v1;
    df3.td.value = v2;
    df3.nd.value = v3;
    df3.action = "http://green.crossmedia.co.kr/rvp?";
    df3.submit();
    if (type == 'login') {
    	frmLoginSubmit();
    } else if (type == 'window') {
    	window.open(v4, '_blank','');
    } else {
    	top.document.location.href=v4;
    }
}

function alertClose(){
	alert("준비중입니다.");
}

function alertAction(msg, script){
	if (msg != ''){
		alert(msg);
		msg = '';
	}
	eval(script);
}

//facebook - left
function facebook_common_share() {
	u = document.location.hostname;
	t = "Fly to the Fantasy! 이카루스";
	window.open('http://www.facebook.com/sharer.php?u=' + encodeURIComponent(u) + '&t=' + encodeURIComponent(t), 'ShareOnFacebook','toolbar=0,status=0,width=626,height=436');
}
// twitter - left
function twitter_common_share() {
	var twitterUrl = 'http://twitter.com/home?status=';
	twitterUrl += encodeURIComponent("Fly to the Fantasy! 이카루스") + ' ';
	twitterUrl += encodeURIComponent(document.location.hostname);
	window.open(twitterUrl, 'ShareOnTwitter', '');
}
// me2DAY - left
function me2day_common_share() {
	var me2dayUrl = 'http://me2day.net/posts/new?new_post[body]=';
	var url = encodeURIComponent(document.location.hostname);
	var title = encodeURIComponent("Fly to the Fantasy! 이카루스");
	window.open(me2dayUrl + '\"' + title + '\":' + url, 'ShareOnMe2Day','');
}

function needLogin(){
	var objID = $('#frmLogin input[name=id]');
	objID.focus();
}

function goMemberLogin() {
	needLoginMsg();
}

function needLoginMsg(){
	alert("로그인이 필요한 서비스 입니다. 로그인 후 이용해 주세요.");
	needLogin();
}
function needNickname(){
	setMemberNick();
}
function needNicknameMsg(){
	if (confirm('닉네임이 필요한 서비스 입니다.\n\n닉네임 설정 하시겠습니까?') == true){
		needNickname();
	}
}

//닉네임 설정 팝업
function goMemberNickName() {
	openNewWin("/member/nickname", "setNickName", 600, 330);
}

//대표 캐릭터 설정 팝업
function goMemberCharacter() {
	openNewWin("/member/character", "setCharacter", 600, 330);
}
//웹포인트 사용 내역 팝업
function goPointCheck(){
	openNewWin("/sales/pointCheck", "포인트 사용내역", 600, 555);
}
//게임 이용 제한 안내 팝업
function goGameUseGrade() {
	openNewWin("/member/gameUseGrade", "viewGameUseGrade", 600, 320);
}

//팝업 띄울 때 사용
function openNewWin(uVal,nVal,wVal,hVal){
	var x = (screen.width - wVal) / 2;
	var y = (screen.height - hVal) / 2;
	nWin = window.open(uVal,nVal,"toolbar=0,menubar=0,scrollbars=no,resizable=no,left="+x+",top="+y+",width=" + wVal +",height=" + hVal + ";");
	if (nWin != undefined){
		nWin.focus();
	}else{
		alert('팝업 차단을 해제해 주시고 다시 시도해 주세요.');
	}
}


function setTitle(title) {
	jQuery(document).attr('title', title);
}

function setPng24(obj) {
	obj.width=obj.height=1;
	obj.className=obj.className.replace(/\bpng24\b/i,'');
	obj.style.filter = "progid:DXImageTransform.Microsoft.AlphaImageLoader(src='"+ obj.src +"',sizingMethod='image');";
	obj.src="//web-cdn.mironline.co.kr/null.gif";
	return null;
}

function changeClass(id, classname){
	 if(navigator.userAgent.indexOf("MSIE 6")>0){
	 	$("#"+id).removeClass("flash");
		$("#"+id).addClass("flash6");
	 }
}

function sendTracking(url){
	$("#ifrTmp").attr('src',url);
}

function getDocumentDomainType(){
	if (document.domain == "mironline.co.kr") {
		return 1;
	} else {
		return 0;
	}
}

//아이프레임 리사이즈
function resizeFrame(frm) {
	frm.style.height = "auto";
	contentHeight = frm.contentWindow.document.documentElement.scrollHeight;
	frm.style.height = contentHeight + "px";
}

//리사이즈 처리
function resizeToBody(width, height) {
	g_resizeWidth = width;
	g_resizeHeight = height;
	if (document.layers || document.body)
		resizeToBodyEx();
	else if (window.addEventListener)
		window.addEventListener("load", resizeToBodyEx, false);
	else if (window.attachEvent)
		window.attachEvent("onload", resizeToBodyEx);
}
function resizeToBodyEx() {
	var sw, sh;
	if (document.layers) {
		sw = window.innerWidth;
		sh = window.innerHeight;
	} else {
		sw = document.documentElement.clientWidth;
		sh = document.documentElement.clientHeight;
	}
	window.resizeBy(g_resizeWidth - sw, g_resizeHeight - sh);
}


//trim
function replaceTrim(str) {
	return str.replace(/^\s*|\s*$/g,"");
}

// 전역 변수임.
var sndFlag = false;


function sns_share(snsCode, msg, url){
	var snsTitle	= encodeURIComponent(document.title + msg);
	var snsUrl		= url;
	if (snsUrl == ""){
		snsUrl		= encodeURIComponent(document.location.href);
	}
	var snsSite;

	switch(snsCode){
		case "facebook" :
			snsSite = "http://www.facebook.com/sharer.php";
			snsSite += '?s=100&p[url]=' + snsUrl + '&p[title]=' + snsTitle;
			break;
		case "twitter" :
			snsSite = "http://twitter.com/home";
			snsSite += '?status=' + snsTitle + ' ' + snsUrl;
			break;
		case "me2day" :
			snsSite = "http://me2day.net/posts/new";
			snsSite += '?new_post[body]=' + snsTitle + ' ' + snsUrl;
			break;
		case "yozm" :
			snsSite = "http://yozm.daum.net/api/popup/post";
			snsSite += '?prefix=' + snsTitle + '&sourceid=54&meta=&key=&imgurl=&crossdomain=0&callback=&link=' + snsUrl;
			break;
		case "people" :
			snsSite = "https://m.mypeople.daum.net/mypeople/mweb/share.do";
			snsSite += '?link=' + snsUrl + '&prefix=' + snsTitle + '&source_id=none';
			break;

		default :
	}

	window.open(snsSite, 'Share'+snsCode,'');
}

// 밀환상점 목록
function FnShopList(cateNew, cateNum1, cateNum2, job, id){
	var addParam = '';
	// 직업별
	if (cateNum1 == 5 || cateNum1 == 7) {
		cateNum2 = 0;
	}
	else {
		job = 9;			
	}
	if (id != undefined){
		addParam = '#' + id;
	}

	// post로 넘기면 뒤로가기버튼 클릭시 사용기간 만료뜸
	var form = createForm('shopForm', 'get', '/item/shop' + addParam, '_self');
	form = addHidden(form, 'new', cateNew);
	form = addHidden(form, 'cate1', cateNum1);
	form = addHidden(form, 'cate2', cateNum2);
	form = addHidden(form, 'job', job);
	document.body.appendChild(form);
	form.submit();
	document.body.removeChild(form);
}


//백과서고 목록
function FndepthList(depthId1, depthId2, depthId3, targetName){
	if (targetName == undefined){
		targetName = '_self';
	}
	// post로 넘기면 뒤로가기버튼 클릭시 사용기간 만료뜸
	var form = createForm('dicForm', 'get', '/dic/dicView', targetName);
	form = addHidden(form, 'depth1', depthId1);
	form = addHidden(form, 'depth2', depthId2);
	form = addHidden(form, 'depth3', depthId3);
	document.body.appendChild(form);
	form.submit();
	document.body.removeChild(form);
}

//백과서고 검색
function goLibrarySearch(sCategory, searchType, searchWord)
{
    var searchWord = searchWord.replace("백과서고 검색하기","");

	if( searchWord == "" ){
		alert("검색어를 입력해 주세요");
		return ;
	}
	var dicAction = "/dic/dicSearchMain";
	if( sCategory != "0" ) 
		dicAction = "/dic/dicSearch";
	
	var form = createForm('dicLibrarySearch', 'get', dicAction, "_self");
		form = addHidden(form, 'cateC1d'	, sCategory);
		form = addHidden(form, 'txtSchType'	, searchType);
		form = addHidden(form, 'txtSchWord'	, searchWord);

		document.body.appendChild(form);
		form.submit();
		document.body.removeChild(form);
	
	return false;
}

//백과서고 검색
function goLibrarySearchPop(sCategory, searchType, searchWord)
{
	/*var searchWord = searchWord.replace("백과서고 검색하기","");*/
	if( searchWord == "" ){
		alert("검색어를 입력해 주세요.");
		return false;
	}
	
	var dicAction = "/dic/dicSearchMain";
	if( sCategory != "0" ) 
		dicAction = "/dic/dicSearch";
	
	var form = createForm('dicLibrarySearch', 'get', dicAction, "_blank");
		form = addHidden(form, 'cateC1d'	, sCategory);
		form = addHidden(form, 'txtSchType'	, searchType);
		form = addHidden(form, 'txtSchWord'	, searchWord);
	document.body.appendChild(form);
	form.submit();
	document.body.removeChild(form);
	
	return false;
}

//백과서고페이징
function goPage(pageNum){
	location.href = "?page=" + pageNum;
}

//We Cash Billing(mir billing)
function goWeCashBillingWin(obj){
	var getBillUrl = "/sitelib/get_billingurl.asp";
	if (document.location.hostname.indexOf("mir") == 0) {	//미르2
		getBillUrl = "/common/getBillUrl";
		if (obj == "P") 	getBillUrl = "/common/getBillUrl?t="+ obj;
	}
	var txtRtnVal = jQuery.ajax({ url: getBillUrl, type:"POST", dataType:"text", async:false}).responseText;
	txtRtnVal = replaceTrim(txtRtnVal);

	if (txtRtnVal.indexOf("/member/") == -1){
		if (document.location.hostname.indexOf("mir") == 0) obj = txtRtnVal;	//미르2
		goChargeHwan(obj);
	}else{
		goMemberLogin();
	}
}

// mir charge
function goChargeHwan(obj){
	var frm = document.frmMemInfo;
	if ( frm != undefined && frm.txtEncUID != undefined ){
		if(frm.txtEncUID.value.length > 10){
			var wUrl, winChgHwan;
			wUrl = '//onestop.mironline.co.kr/pay/settlement/chkUserMirID.asp';
			if (obj != undefined) {
				wUrl = (obj == "P") ? "//onestop.mironline.co.kr/pay/settlement/chkPresentUserMirID.asp" : obj;
			}
			var strPos = ',left='+(screen.availwidth-360)/2+',top='+(screen.availheight-500)/2;
			winChgHwan = window.open('about:blank', 'chargeHwan','width=360px,height=500px,status=no,toolbar=no,menubar=no,location=no,scrollbars=no'+strPos);
			if( winChgHwan != undefined ){
				frm.action = wUrl;
				frm.target = "chargeHwan";
				frm.submit();
				winChgHwan.focus();
			}
		}else{
			goMemberLogin();
		}
	}else{
		goMemberLogin();
	}
}

//이벤트 쿠키 생성
function setEvtCookie(name, value, expiredays){
	var todayDate = new Date();
	todayDate.setDate( todayDate.getDate() + expiredays );
	document.cookie = name + "=" + escape( value ) + "; path=/; expires=" + todayDate.toGMTString() + ";"
} 