$(document).ready(function(){
	
		$('#containerWrap').formSelectbox({
			wrapSelect:'.formenable',
			opacity:0.5,
			parent:'.category',
			parents:'tr',
			className:'.select',
			listPosition:'dropdown'
			},function(){
				//callback function executed at select list click
				//alert('test');
				//ex) checkIP(), saveCookie()
		});
				
		$('#serverName li').on('click',function(e){
			jQuery('#serverName li').removeClass('on');
			jQuery('#serverName').find('li').eq(this.id).addClass('on');
		});
		
		$('#serverSelect li').on('click',function(e){
			jQuery('#serverName li').removeClass('on');
			jQuery('#serverName').find('li').eq(this.id).addClass('on');
			
		});
		
	});
	var reJob;
	var reServerName = "";
	var strCharName;
	var moreNum=1;
	var reAtypa;
	var relistType;
	function moreRank(){
		if(reAtypa !='c'){
			moreNum = moreNum+1;
			//severNameSubmit('all','','','m');	
			
			if(moreNum>10) {
				alert("1,000위까지만 검색됩니다.");
				return;
			}
			
			$.post('/ranking/ajax/list',
					{serverName : reServerName, job : reJob,
					charName : strCharName, aType : reAtypa,moreNum : moreNum, listType:relistType},
					function(data){
						$('#getRankingList'+moreNum).show();
						
						$('#getRankingList'+moreNum).html(data);
						
						jQuery('.link_table .title').on('mouseover',function(e) {
							jQuery(this).addClass('on');
							jQuery(this).find('.char_pop').show();
						}).on('mouseleave',function(e) {
							jQuery(this).removeClass('on');
							jQuery(this).find('.char_pop').hide();
						});
						jQuery('.link_table .title a').on('focus',function(e) {
							jQuery(this).parents('td').addClass('on');
							jQuery(this).find('.char_pop').show();
						}).on('focusout',function(e) {
							jQuery(this).parents('td').removeClass('on');
							jQuery(this).find('.char_pop').hide();
						});
						//alert("리스트 가져왔어.");
					}	
			);
		}else{
			alert("서버를 선택해 주세요.");
		}
		if($.browser.msie){
			var expr = new RegExp('>[ \t\r\n\v\f]*<', 'g');
			$('.character').html( ($('.character').html() + "").replace(expr, '><')  );
		}
	}

	function dataRest(){
		
		moreNum=1;
		for(i=2;i<=10;i++){
			$('#getRankingList'+i).html("");
		}
		
	}
	var intputTextName;
	function severNameSubmit(serverName,job,charName,aType){
		if($.browser.msie){
			var expr = new RegExp('>[ \t\r\n\v\f]*<', 'g');
			$('.character').html( ($('.character').html() + "").replace(expr, '><')  );
		}
		var location=window.location.href.split("/");
		
		var reLocation=location[location.length-1].split("?");
		reLocation=reLocation[0].split("#");
		
		var listType = reLocation[0];
		if(reLocation[0]=="ranking"){
			//케릭터랭킹
			intputTextName="캐릭터명 입력";
		}else{
			//영웅랭킹
			intputTextName="영웅 입력";
		}
		
		//alert("호출 시작");
		
		reAtypa=aType;
		relistType = listType;
		serverSelect(serverName,aType);
		if(aType!='m')dataRest();
		if(job !='')reJob=job;
		var f = document.searchForm;

		if (aType == "c" ) {
			serverSelect(serverName);
			if(f.char_search.value.length<2 && f.char_search.value !=intputTextName){
				alert("캐릭터 명은 2글자 이상 입력해 주세요.");
				f.char_search.value=intputTextName
			}else{
				
				strCharName=f.char_search.value;
			}
			
		}else{
			strCharName="";
			f.char_search.value=intputTextName;
		}
		
		if(strCharName==intputTextName)strCharName="";
		
		//window.open(url);
		//alert(reServerName);
		$.post('/ranking/ajax/list',
				{serverName : reServerName, job : reJob,
				charName : strCharName, aType : aType,moreNum : moreNum, listType:listType},
				function(data){
					$('#getRankingList'+moreNum).show();
					
					$('#getRankingList'+moreNum).html(data);
					
					jQuery('.link_table .title').on('mouseover',function(e) {
						jQuery(this).addClass('on');
						jQuery(this).find('.char_pop').show();
					}).on('mouseleave',function(e) {
						jQuery(this).removeClass('on');
						jQuery(this).find('.char_pop').hide();
					});
					jQuery('.link_table .title a').on('focus',function(e) {
						jQuery(this).parents('td').addClass('on');
						jQuery(this).find('.char_pop').show();
					}).on('focusout',function(e) {
						jQuery(this).parents('td').removeClass('on');
						jQuery(this).find('.char_pop').hide();
					});
					//alert("리스트 가져왔어.");
				}	
		);	
		//alert("호출 끝났어");
	}
	
	function serverSelect(serverName,aType){
		if (aType == "s" ) {
			if(serverName !='')reServerName=serverName;
			if(serverName =='all' || serverName ==''){
				reServerName='';
				jQuery('#sel-servername').html('전체서버');
			
			}else{
				jQuery('#sel-servername').html(serverName);
			}	
		}else{
			reServerName='';
		}
	}
	
	/* IE7 관련 z-index */
	$(function(){
		var zIndexNumber = 10000;
		$('.titleWrap').each(function() {
			$(this).css('zIndex', zIndexNumber);
			zIndexNumber -= 10;
		});
	});
	
	/* IE9 관련 테이블 버그 */
	if($.browser.msie){
		var expr = new RegExp('>[ \t\r\n\v\f]*<', 'g');
		$('.character').html( ($('.character').html() + "").replace(expr, '><')  );
	}
	
	