/*
$(document).ready(function(){
	
    var isMobile = false;
    var filter = "win16|win32|win64|mac";
    
    
    if(navigator.platform && 0 > filter.indexOf(navigator.platform.toLowerCase()))
    {
        isMobile = true; 
    }
    
    
    if(navigator.platform && navigator.platform.toLowerCase() == 'macintel' && navigator.maxTouchPoints > 0)
    {
        isMobile = true;
    }

	var arrayData = {};  
	arrayData['isMobile'] = isMobile;

    $.ajax({
        type: 'post',
        contentType: "application/json; charset=utf-8",
        cache: false,        
        data : JSON.stringify(arrayData), 
        url: 'browser',
        error: function (jqXHR, textStatus, errorThrown) {                
            console.log("Server Error");      
        },
        success: function (data, textStatus, jqXHR){                                     
            
            if(true == data.Code)
            {
                location.reload();
            }
        }

      }); 

});
*/

window.addEventListener('DOMContentLoaded', function() {
  var isMobile = false;
  var filter = "win16|win32|win64|mac";
  var arrayData = {};

  // Apple 디바이스 && 최대 터치포인트 갯수가 1개 이상이라면 
  if( (navigator.platform && navigator.platform.toLowerCase() === 'macintel') && navigator.maxTouchPoints )
  {
      isMobile = true;
  }

  arrayData['isMobile'] = isMobile;

  $.ajax({
    type: 'post',
    contentType: "application/json; charset=utf-8",
    cache: false,
    data : JSON.stringify(arrayData), 
    url: 'browser',
    error: function (jqXHR, textStatus, errorThrown) {
      console.log("Server Error");
    },
    success: function (data, textStatus, jqXHR) {
      if(true === data.Code && navigator.platform.toLowerCase() === 'macintel')
      {
        //210514 맥 무한 리로딩 방지를 위한 소스 

        if(!window.location.hash) {
          window.location = window.location + '#loaded';
          window.location.reload();
        }
        
      }
    }
  });
});
