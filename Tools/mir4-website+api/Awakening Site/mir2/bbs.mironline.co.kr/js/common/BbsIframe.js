var wemade = {};

wemade.BbsIframe = {
	ifrId : "ifr_bbs",
	hash : null,
	tid : null,

	init : function(url,target){
		var _src = this.getInitIfrSRC(url);
		var _fn = "";
		
		if (target) {
			//$(target).innerHTML = '<iframe id="'+this.ifrId+'" src="'+_src+'" allowtransparency="true" scrolling="no" width="100%" height="800" frameborder="0" marginheight="0" marginwidth="0" '+_fn+'></iframe>';
			document.getElementById(target).innerHTML = '<iframe id="'+this.ifrId+'" src="'+_src+'" allowtransparency="true" scrolling="no" width="100%" height="800" frameborder="0" marginheight="0" marginwidth="0" '+_fn+'></iframe>';
		} else {
			
			document.write('<iframe id="'+this.ifrId+'" src="'+_src+'" allowtransparency="true" scrolling="no" width="100%" height="800" frameborder="0" '+_fn+'></iframe>');
		}

		this.checkHash();
	},
	
	initWithWidth : function(url,target,width){
		var _src = this.getInitIfrSRC(url);
		var _fn = "";
		
		if (target) {
			//$(target).innerHTML = '<iframe id="'+this.ifrId+'" src="'+_src+'" allowtransparency="true" scrolling="no" width="100%" height="800" frameborder="0" marginheight="0" marginwidth="0" '+_fn+'></iframe>';
			top.document.getElementById(target).innerHTML = '<iframe id="'+this.ifrId+'" src="'+_src+'" allowtransparency="true" scrolling="no" width="'+width+'" height="800" frameborder="0" marginheight="0" marginwidth="0" '+_fn+'></iframe>';
		} else {
			
			document.write('<iframe id="'+this.ifrId+'" src="'+_src+'" allowtransparency="true" scrolling="no" width="'+width+'" height="800" frameborder="0" '+_fn+'></iframe>');
		}

		this.checkHash();
	},
	
	checkHash : function(){
		this.tid = null;
		var hash = location.href.split("#")[1];
		if(this.hash && this.hash != hash){
			location.reload();
			return;
		}



		if(this.hash != null)
		{
			// 20230822 크롬정책변경(cross-origin) 문제로 정상작동하지 않아 PostMessage 방식으로 변경함
			//resizeFrame();
			document.getElementById('ifr_bbs').contentWindow.postMessage({type: 'bbsResizeByParent', iframeId: 'ifr_bbs'}, '*');

		}

		this.tid = setTimeout("wemade.BbsIframe.checkHash()",500);
		

	},

	getInitIfrSRC : function(url){
		var _hash = this.getTopHash();

		if (_hash) {
			_hash = _hash.split('<').join('');
			_hash = _hash.split('>').join('');
			var _arr = url.split(bbsUrlDelimeter);
			var _root = url.replace( _arr[_arr.length-1], "");

			return  _root + _hash;
		}
		else {
			var returnURL = url;
			returnURL = returnURL.split('<').join('');
			returnURL = returnURL.split('>').join('');

			return returnURL;
		}
	},
	
	getTopHash : function(){
		if(!location.href.split("#")[1])return;
		var _h = location.href.split("#")[1];

		if(_h.match(/list|view|write|edit|reply|loginRecommendArticle/)){
			return _h;
		}else{
			return false;
		}
	},

	onloadIfr : function(){
		this.changeTopLocation();
	},

	changeTopLocation : function(_q){
		this.setScroll();

		var _url = location.href.split("#")[0] + "#";
		// 20230822  20230822 크롬정책변경(cross-origin) 문제로
		// getIfrQuery 함수 내 contentWindow.location.href 가져올 수 없어 인자 값으로 전달받도록 수정함
		//var _q = this.getIfrQuery();
		this.hash = _q;
		if(_q.match(/list|view|write|edit|reply|loginRecommendArticle/)){
			location.href = _url + _q;
		}
	},

	setScroll : function(){
		window.scrollTo(0,0);
		return;
		var _t = $E($(this.ifrId)).getCoords().top;
		window.scrollTo(0,_t);
	},

	getIfrQuery : function(){
		var _url = document.getElementById(this.ifrId).contentWindow.location.href;
		var _arr = _url.split(bbsUrlDelimeter);
		var _size = _arr.length - 1;
		var _q = _arr[_size];
		return _q;
	}
};