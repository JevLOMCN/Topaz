
var realDomain = document.domain;

var documentDomainName = "mironline.co.kr";
var urlDelimeter = "mironline.co.kr";

if(realDomain.indexOf("mironline.co.kr", 0) != -1) {
	documentDomainName = "mironline.co.kr";
	urlDelimeter = "mironline.co.kr";
} else if (realDomain.indexOf("wemade.net", 0) != -1){
	documentDomainName = "wemade.net";
	urlDelimeter = "wemade.net";
} else if (realDomain.indexOf("game.daum.net", 0) != -1){
	documentDomainName = "game.daum.net";
	urlDelimeter = "game.daum.net";
} else if (realDomain.indexOf("playnetwork.co.kr", 0) != -1){
	documentDomainName = "playnetwork.co.kr";
	urlDelimeter = "playnetwork.co.kr";
} else if (realDomain.indexOf("wemadeusa.com", 0) != -1){
	documentDomainName = "wemadeusa.com";
	urlDelimeter = "wemadeusa.com";
} else if (realDomain.indexOf("mir2.co.kr", 0) != -1){
	documentDomainName = "mir2.co.kr";
	urlDelimeter = "mir2.co.kr";
} else if (realDomain.indexOf("zeus.kr", 0) != -1){
	documentDomainName = "zeus.kr";
	urlDelimeter = "zeus.kr";	
} 

var domainName = documentDomainName;
var bbsUrlDelimeter = urlDelimeter;

// 20230821 크롬정책변경으로 document.domain setter 사용할 수 없어 주석함.
//document.domain = documentDomainName;