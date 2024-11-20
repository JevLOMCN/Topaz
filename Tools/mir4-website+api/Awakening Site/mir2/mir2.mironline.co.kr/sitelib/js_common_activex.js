// 기본이 되는 Handling Function
function comDomHandling(){
	this.config = {
		isIE : (navigator.userAgent.toLowerCase().indexOf("ms-ie") != -1)
	};
	this.$I = function(strId){
		return document.getElementById(strId);
	};
	this.$C = function(tag){
		return document.createElement(tag);
	};
	this.setCSS = function(oEl, strStyle) {
		if (!strStyle.charAt(strStyle.length - 1) == ';')
			return;
		if(this.config.isIE){
			var tmpStrSplit;
			tmpStrSplit = strStyle.slice(0,-1);
			tmpStrSplit = tmpStrSplit.split(";");
			for(var i = 0; i < tmpStrSplit.length; i++){
				oEl.style[tmpStrSplit[i].split(":")[0]] = tmpStrSplit[i].split(":")[1];
			}
		}else{
			oEl.setAttribute("style",strStyle);
		}
	};
	this.setAttr = function(oEl, arr){
		for(var prop in arr){
			oEl.setAttribute(prop, arr[prop]);
		}
	};
	this.dataPack = function(){
		return {
			add : function(pName){
				this[pName] = null;
			},
			remove : function(pName){
				(typeof(pName) != "function") ? delete this[pName] : "";
			},
			removeAll : function(){
				for(var pName in this)
					this.remove(pName);
			}
		}
	};
	this.setData = function(arrDpack, pName, pValue){
		arrDpack.add(pName);
		arrDpack[pName] = pValue;
	}
}


// Wemade ActiveX Controller 
comDomHandling.prototype = {
	baseData : {
//		axInstallManager : {classid:"clsid:BD68328E-1222-4A62-BA16-E6F42CA49A64",id:"YDSystemInfo_x64",codebase:"YDSystemInfo_x64.cab#version=1,0,0,4", fileName:"WMInstallMgr.dll", viewTxt:"ActiveX Manager", fcount:"1", width:"0", height:"0"},
//		axInstallManager : {classid:"clsid:811C79F7-DF8C-4EC1-BD39-B3364E19CE29",id:"YDSystemInfo",codebase:"YDSystemInfo_x86.cab#version=1,0,0,1", fileName:"YDSystemInfo.dll", viewTxt:"ActiveX Manager", fcount:"1", width:"0", height:"0"},
		weblauncher : {classid:"clsid:B8339132-E751-452b-87F5-5F3D4365638B",id:"objWeGameRun",codebase:"weGameLauncher.cab#version=1,0,2,22", fileName:"WemadeWebLauncher.dll", viewTxt:"Wemade Web Launcher", fcount:"5", width:"0", height:"0"},
		comicviewer : {classid:"clsid:D08593B1-B6F7-4A53-9E34-178E06568312",id:"WMCartoonViewer",codebase:"WMCartoonViewer.cab#version=1,0,0,18", fileName:"WMCartoonViewer.ocx", viewTxt:"Wemade Comic Viewer", fcount:"3", width:"100%", height:"100%"},
	    systemobserver : {classid:"clsid:811C79F7-DF8C-4EC1-BD39-B3364E19CE29",id:"YDSystemInfo",codebase:"YDSystemInfo_x86.cab#version=1,0,0,1", fileName:"YDSystemInfo.dll", viewTxt:"System Observer", fcount:"4", width:"0", height:"0"},

//        if(navigator.userAgent.indexOf("Win64") != -1 && navigator.userAgent.indexOf("x64") != -1){
//            systemobserver : {classid:"clsid:BE9743C8-C03B-4B8C-93CB-098E63642023",id:"YDSystemInfo",codebase:"YDSystemInfo_x64.cab#version=1,0,0,1", fileName:"YDSystemInfo64.dll", viewTxt:"System Observer", fcount:"4", width:"0", height:"0"},
//        }else{
//		    systemobserver : {classid:"clsid:811C79F7-DF8C-4EC1-BD39-B3364E19CE29",id:"YDSystemInfo",codebase:"YDSystemInfo_x86.cab#version=1,0,0,1", fileName:"YDSystemInfo.dll", viewTxt:"System Observer", fcount:"4", width:"0", height:"0"},
//        }
	},
	setAXObject : function(strName){
		var t = this;
		var b = this.baseData;
		var strActiveRoot = this.rtnCurrent();

		if(t.$I("objHiddenFrame") == undefined){
			var I = t.$C("iframe");
			t.setAttr(I,{id:"objHiddenFrame", name:"txtHiddenFrame"});
			t.setCSS(I,"width:0;height:0;position:absolute;left:-100;");
			(t.config.isIE) ? document.insertBefore(I) : document.body.appendChild(I);
		}

		var iWindow = t.$I("objHiddenFrame").contentWindow;

		//iWindow.document.clear();
		
		iWindow.document.open("text/html", "replace");
		iWindow.document.write("<html><head>");
		iWindow.document.write("<SCRIPT LANGUAGE='JavaScript'>");
//		iWindow.document.write(" function activex_error(){ alert(\"설치가 되어야 이용하실 수 있습니다.\"); } ");
		iWindow.document.write("</SCRIPT>");
		iWindow.document.write("</head><body>");
		iWindow.document.write('<OBJECT classid="' + eval("b." + strName + ".classid") + '" width=0 height=0 ');
		iWindow.document.write('id="' + eval("b." + strName + ".id") + '" codebase="');
		iWindow.document.write(strActiveRoot + eval("b." + strName + ".codebase") + '" VIEWASTEXT onError="activex_error()"></OBJECT>');
		iWindow.document.write("</body></html>");
		iWindow.document.close();

		// 설치 여부 반환 해준다.
		var tmpDataPack = t.dataPack();
		t.setData(tmpDataPack, "obj", iWindow.document.getElementById(eval("b." + strName + ".id")));
		t.setData(tmpDataPack, "bitInstall", t.getInstall(strName, iWindow.document.getElementById(eval("b." + strName + ".id"))));
		// 만약에 설치 되어 있다면 등록을 위해 잠깐 보여주고 지워준다.
		if (tmpDataPack.bitInstall && strName == "systemobserver"){
			var tmpObj = t.setDocument(strName);
		}
		return tmpDataPack;
	},
	setInstall : function(strName){
		var t = this;
		var objAXManager = t.setAXObject("systemobserver");
		if(objAXManager.bitInstall == true){
			var tmpStrData = eval("this.baseData." + strName);
			// Version Data 
			var tmpStrVersion = tmpStrData.codebase.substring(tmpStrData.codebase.lastIndexOf("#version=") + 9).split(",");
			// AX URL 
			var tmpStrUrl = "http://" + document.location.hostname + this.rtnCurrent() + tmpStrData.codebase.substring(0, tmpStrData.codebase.indexOf("#"));

			// ActiveX 파일명
			objAXManager.obj.szAXFileName = tmpStrData.fileName;
			objAXManager.obj.Version01 = tmpStrVersion[0];
			objAXManager.obj.Version02 = tmpStrVersion[1];
			objAXManager.obj.Version03 = tmpStrVersion[2];
			objAXManager.obj.Version04 = tmpStrVersion[3];
			objAXManager.obj.szModName = tmpStrData.viewTxt;
			objAXManager.obj.lFileCount = tmpStrData.fcount;
			//objAXManager.obj.StartInstall(tmpStrUrl);
			// 사용을 위해 해당 페이지에 히든 레이어 형태로 셋팅
			return t.setDocument(strName);
		}else{
			return null;
		}
	},
	setDocument : function(strName){
		var obj = "";
		var t = this;
		var b = t.baseData;

		obj = "<OBJECT CLASSID='" + eval("b." + strName + ".classid") + "' ID='" + eval("b." + strName + ".id") + "' WIDTH='" + eval("b." + strName + ".width") + "' HEIGHT='" + eval("b." + strName + ".height") + "' VIEWASTEXT></OBJECT>" ;

		t.removeDocument(t.$I(strName+'layer'));

		var o = t.$C("div");
		t.setAttr(o, {id:strName+'layer'});
		(t.config.isIE) ? document.insertBefore(o) : document.body.appendChild(o);
		t.$I(strName+'layer').innerHTML = obj;

		return t.$I(eval("b." + strName + ".id"));
	},
	removeDocument : function(obj){
		if (obj){
			obj.parentNode.removeChild(obj);
		}
	},
	getInstall : function(strName, obj){
		var bitWork = false;
		if (obj == undefined || obj == null){
			obj = this.setDocument(strName);
		}
		switch(strName){
			case "axInstallManager" :
				try{ obj.szModName.length == 0; bitWork = true; }
				catch(ex){ bitWork = false; }
				break;
			case "weblauncher" :
				bitWork = (obj.build_date == undefined) ? false : true;
				break;
			case "comicviewer" :
				bitWork = (obj.value == null) ? false : true;
				break;
			case "systemobserver" :
				try{ obj.GetInformation(); bitWork = true; }
				catch(ex){ bitWork = false; }
				break;
			default : return false; break;
		}
		return bitWork;
	},
	rtnCurrent : function(){
		var strHrefHost = String(document.location.hostname).replace("dev.mironline.co.kr","");
		strHrefHost = strHrefHost.replace("test.mironline.co.kr","");
		strHrefHost = strHrefHost.replace(".mironline.co.kr","");
		var strActiveXRoot = "/sitelib/activex/";
		switch (strHrefHost){
			case "cs" :
				strActiveXRoot = "/sitelib/activex/";
				break;
			default : 
				strActiveXRoot = "/sitelib/activex/";
				break;
		}
		return strActiveXRoot;
	}
};

