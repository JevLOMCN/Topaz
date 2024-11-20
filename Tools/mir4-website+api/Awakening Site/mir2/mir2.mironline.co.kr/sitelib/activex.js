// for naver
    var realDomain = document.domain;
    if (realDomain.indexOf("naver.com", 0) != -1){
    	documentDomainName = "playnetwork.co.kr";
    } else {
    	documentDomainName = realDomain;
    }
    var domainName = documentDomainName;
	
	var gameFlag = false;
	function goStartGame(strStartVal){
        cdh = new comDomHandling2();
        if(cdh.setAXObjectNewVersion("systemobserver").bitInstall != true) {
            alert("먼저 ActiveX 컨트롤을 설치하여야 합니다.");
            return;
        }
	}

    function comDomHandling2(){
        this.config = {
        	isIE : (navigator.userAgent.toLowerCase().indexOf("msie") != -1)
        	, isFF : (navigator.userAgent.toLowerCase().indexOf("firefox") != -1)
        	, isCR : (navigator.userAgent.toLowerCase().indexOf("chrome") != -1)
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
comDomHandling2.prototype = {
        baseData : {
                axInstallManager : {classid:"clsid:BD68328E-1222-4A62-BA16-E6F42CA49A64",id:"WMInstallMgr",codebase:"WMInstallMgr.cab#version=1,0,0,4", fileName:"WMInstallMgr.dll", viewTxt:"ActiveX Manager", fcount:"1", width:"0", height:"0"},
                weblauncher : {classid:"clsid:B8339132-E751-452b-87F5-5F3D4365638B",id:"objWeGameRun",codebase:"weGameLauncher.cab#version=1,1,2,35", fileName:"WemadeWebLauncher.dll", viewTxt:"Wemade Web Launcher", fcount:"7", width:"0", height:"0"},
                comicviewer : {classid:"clsid:D08593B1-B6F7-4A53-9E34-178E06568312",id:"WMCartoonViewer",codebase:"WMCartoonViewer.cab#version=1,0,0,22", fileName:"WMCartoonViewer.ocx", viewTxt:"Wemade Comic Viewer", fcount:"4", width:"100%", height:"100%"},
             //systemobserver : {classid:"clsid:D22EEF80-9A66-4A52-8361-670EBBD5EF2B",id:"WMSysObserver",codebase:"WMSysInfo.cab#version=1,0,0,48", fileName:"WMSysInfo.ocx", viewTxt:"Wemade System Observer", fcount:"5", width:"0", height:"0"}
                systemobserver : {classid:"clsid:811C79F7-DF8C-4EC1-BD39-B3364E19CE29",id:"YDSystemInfo",codebase:"YDSystemInfo_x86.cab#version=1,0,0,1", fileName:"YDSystemInfo.dll", viewTxt:"System Observer", fcount:"4", width:"0", height:"0"}
        },
        setAXObject : function(strName){ /* old version  */
                var t = this;
                var b = this.baseData;
                var strActiveRoot = this.rtnCurrent();

                if(t.$I("objHiddenFrame") == undefined){
                        var I = t.$C("iframe");
                        t.setAttr(I,{id:"objHiddenFrame", name:"txtHiddenFrame"});
                        t.setCSS(I,"width:0;height:0;position:absolute;left:-100;");
                        (t.config.isIE) ? document.body.insertBefore(I) : document.body.appendChild(I);
                }

                var iWindow = t.$I("objHiddenFrame").contentWindow;

			    //iWindow.document.clear();
                iWindow.document.open("text/html", "replace");
                iWindow.document.write("<html><head>");
                iWindow.document.write("</head><body>");
                iWindow.document.write('<OBJECT classid="' + eval("b." + strName + ".classid") + '" width=0 height=0 ');
                iWindow.document.write('id="' + eval("b." + strName + ".id") + '" codebase="');
                iWindow.document.write(strActiveRoot + eval("b." + strName + ".codebase") + '" VIEWASTEXT></OBJECT>');
                iWindow.document.write("</body></html>");
                iWindow.document.close();

                // 설치 여부 반환 해준다.
                var tmpDataPack = t.dataPack();
                t.setData(tmpDataPack, "obj", iWindow.document.getElementById(eval("b." + strName + ".id")));
                t.setData(tmpDataPack, "bitInstall", t.getInstall(strName, iWindow.document.getElementById(eval("b." + strName + ".id"))));
                // 만약에 설치 되어 있다면 등록을 위해 잠깐 보여주고 지워준다.
                if (tmpDataPack.bitInstall && strName == "axInstallManager"){
                        var tmpObj = t.setDocument(strName);
                }
                return tmpDataPack;
        },
        setAXObjectNewVersion : function(strName) { /* new version */
                var t = this;
                var b = this.baseData;

                // 설치 여부 반환 해준다.
                var tmpDataPack = t.dataPack();
                t.setData(tmpDataPack, "obj", document.getElementById(eval("b." + strName + ".id")));
                t.setData(tmpDataPack, "bitInstall", t.getInstall(strName, document.getElementById(eval("b." + strName + ".id"))));
                // 만약에 설치 되어 있다면 등록을 위해 잠깐 보여주고 지워준다.
                if (tmpDataPack.bitInstall && strName == "axInstallManager"){
                        var tmpObj = t.setDocument(strName);
                }
                return tmpDataPack;
        },
        setInstall : function(strName){
                var t = this;
                //var objAXManager = t.setAXObjectNewVersion("axInstallManager");
                var objAXManager = t.setAXObject("axInstallManager");
                 //alert(strName+ ": bitInstall = " + objAXManager.bitInstall);
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
                        objAXManager.obj.StartInstall(tmpStrUrl);
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
                (t.config.isIE) ? document.body.insertBefore(o) : document.body.appendChild(o);
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
			var strHrefHost = String(document.location.hostname).replace("playnetwork.co.kr","");
			var strActiveXRoot = "/sitelib/activex/";
			switch (strHrefHost){
				case "cs" :
					strActiveXRoot = "/sitelib/activex/";
					break;
				default : 
					strActiveXRoot = "/sitelib/activex/";
					break;
			}

			//var strActiveXRoot = "http://alpha-taoist.playnetwork.co.kr/center/";
                return strActiveXRoot;
        }
};