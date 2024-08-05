window.addEventListener('DOMContentLoaded', function(){
    //url 링크 복사 버튼
    $( ".btnUrl" ).click(function(){        

        if(false == isLogin && false == isEvent)
        {
            mir.contentOpen('.cont06-1');
            
        }
        else if(true == isLogin && false == isEvent)
        {
            var popup = ".cont13";
            if(202 == mode) popup = "#layerPopupType1";


            mir.contentOpen(popup);
        }
        else
        {
            var obj = document.createElement("textarea");    
            document.body.appendChild(obj);

            obj.style.position = 'fixed';
            obj.style.top = 0;
            obj.style.left = 0;
            obj.style.width = '1px';
            obj.style.height = '1px'

            var url = inviteURL; 

            var text ="미르4 연대기 제 8장 : 흑룡의 탑 업데이트";
            text +="바로가기 :" + url;
        
            obj.value = text;      
            obj.select();        
    
            var successful = document.execCommand('copy');
            var msg = successful ? 'successful' : 'unsuccessful';
    
            if('unsuccessful' == msg)
            {
              var range = document.createRange();
              range.selectNodeContents(obj);      
              var selection = window.getSelection();        
              selection.addRange(range);
              obj.setSelectionRange(0, 500);
                      
              document.execCommand('copy');  
              selection.removeAllRanges();
            }
                  
            window.scrollTo(0, 0);
            mir.alertOpen('URL 복사가 완료되었습니다.');
        }        
    });
    

    $( ".btnShareUrl" ).click(function(){        
        //210524 텍스트입력 창이 계속 올라와서 obj 사용하는 부분 모두 주석 
        var obj = document.createElement("textarea");    
        document.body.appendChild(obj);

        obj.style.position = 'fixed';
        obj.style.top = 0;
        obj.style.left = 0;
        obj.style.width = '1px';
        obj.style.height = '1px'

        var url = window.location.href; 

        var text ="세상이 기다렸던 K-FANTASY 미르4";
        text +="사전예약 및 친구초대 이벤트 참여시,";
        text +="인게임 보상과 다양한 현물 경품을 드립니다.";
        text +="바로가기 :" + url;

        obj.value = url;      
        obj.select();        

        var successful = document.execCommand('copy');
        var msg = successful ? 'successful' : 'unsuccessful';
        if('unsuccessful' == msg)
        {
          var range = document.createRange();
          //range.selectNodeContents(obj);      
          var selection = window.getSelection();        
          selection.addRange(range);
          //obj.setSelectionRange(0, 500);
                  
          document.execCommand('copy');  
          selection.removeAllRanges();
        }
        window.scrollTo(0, 0);
        //obj.style.visibility = 'hidden';
        
        //mir.alertOpen('URL 복사가 완료되었습니다.');    
        alert('URL 복사가 완료되었습니다.');
    });

    $( ".btnCopyUrl" ).click(function(){        
        var url = window.location.href; 
        copyToClipboard(url);
        Ui.alert('URL 복사가 완료되었습니다.');
    });
    
    $( ".copy" ).click(function(){        
        var url = window.location.href; 
        copyToClipboard(url);
        //Ui.alert('URL 복사가 완료되었습니다.');
    });

    function copyToClipboard(val) {
        const t = document.createElement("textarea");
        document.body.appendChild(t);
        t.value = val;
        t.select();
        document.execCommand('copy');
        document.body.removeChild(t);
    }

    $( ".systemBtnShareUrl" ).click(function(){        

        var obj = document.createElement("textarea");    
        document.body.appendChild(obj);

        obj.style.position = 'fixed';
        obj.style.top = 0;
        obj.style.left = 0;
        obj.style.width = '1px';
        obj.style.height = '1px'

        var url = window.location.href; 

        //var text ="세상이 기다렸던 K-FANTASY 미르4";
        //text +="사전예약 및 친구초대 이벤트 참여시,";
        //text +="인게임 보상과 다양한 현물 경품을 드립니다.";
        //text +="바로가기 :" + url;

        obj.value = url;      
        obj.select();        

        var successful = document.execCommand('copy');
        var msg = successful ? 'successful' : 'unsuccessful';

        if('unsuccessful' == msg)
        {
          var range = document.createRange();
          range.selectNodeContents(obj);      
          var selection = window.getSelection();        
          selection.addRange(range);
          obj.setSelectionRange(0, 500);
                  
          document.execCommand('copy');  
          selection.removeAllRanges();
        }
              
        window.scrollTo(0, 0);
        obj.style.visibility = 'hidden';
        
        alert('URL 복사가 완료되었습니다.');
    });


    $( ".facebook" ).click(function() 
    {
        var url = window.location.href;
        var msg = "미르4 연대기 제 8장 : 흑룡의 탑 업데이트";

        info = {
            method:'popup',
            url:'https://www.facebook.com/sharer/sharer.php?u=' + url + '&quote=' + msg
        };

        switch(info.method)
        {
            case 'popup':
                window.open(info.url, "_blank", 'top=10, left=10, width=500, height=600, status=no, menubar=no, toolbar=no, resizable=no');
                break;
        }

    });

    $( ".twitter" ).click(function() 
    {
        var url = window.location.href;
        var msg = "미르4 연대기 제 8장 : 흑룡의 탑 업데이트";

        info = {
            method:'popup',
            url:'https://twitter.com/intent/tweet?text=' + msg + '&url=' + url
        };

        switch(info.method)
        {
            case 'popup':
                window.open(info.url, "_blank", 'top=10, left=10, width=500, height=600, status=no, menubar=no, toolbar=no, resizable=no');
                break;
        }

    });

    Kakao.init('ebbae767c26d2fa45e0107fd83c9ef94');
    $( ".kakao" ).click(function() 
    {
        Kakao.Story.share({
            url: "https://mir4.co.kr",
            text: '미르4 연대기 제 8장 : 흑룡의 탑 업데이트',
          })
        return;
    });
});


function kakaoLink(img = '', title = '', desc = '') 
{    
    var url = window.location.href;
    //var img = "https://mir4-live-hp.wemade.com/booking_mobile/mir4_sns.jpg";
    //var img = "https://mir4-live-hp.wemade.com/events/2021/wonjung/img/sns.jpg";
    if(img === ''){
        img = "https://mir4-live-hp.wemade.com/events/2022/chronicle-8th/img/share_sns.jpg";
    }
    if(title === ''){
        title = 'MIR4';
    }
    if(desc === ''){
        desc = '미르4 연대기 제 8장 : 흑룡의 탑 업데이트';
    }    
    Kakao.Link.sendDefault({
        objectType: 'feed',
         
        content: {
            title: title,
            description: desc,
            imageUrl: img,
            link: {
                mobileWebUrl: url,
                webUrl: url
            }
        },
        
        success: function(messageObj) {
            //... .. .
        },

        fail : function(){
            //... .. .
        },

        installTalk : true
    });
}

function copyUrl(url)
{
    if(false == isLogin && false == isEvent)
    {
        mir.contentOpen('.cont06-1');
        
    }
    else if(true == isLogin && false == isEvent)
    {
        var popup = ".cont13";
        if(202 == mode) popup = "#layerPopupType1";

        mir.contentOpen(popup);
    }
    else
    {
        var obj = document.createElement("textarea");    
        document.body.appendChild(obj);

        obj.style.position = 'fixed';
        obj.style.top = 0;
        obj.style.left = 0;
        obj.style.width = '1px';
        obj.style.height = '1px'

        var text ="미르4 연대기 제 8장 : 흑룡의 탑 업데이트";
        text +="바로가기 :" + url;
    
        obj.value = text;      
        obj.select();        

        var successful = document.execCommand('copy');
        var msg = successful ? 'successful' : 'unsuccessful';

        if('unsuccessful' == msg)
        {
          var range = document.createRange();
          range.selectNodeContents(obj);      
          var selection = window.getSelection();        
          selection.addRange(range);
          obj.setSelectionRange(0, 500);
                  
          document.execCommand('copy');  
          selection.removeAllRanges();
        }
              
        window.scrollTo(0, 0);
        mir.alertOpen('URL 복사가 완료되었습니다.');
    }
}