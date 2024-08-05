<!DOCTYPE html>
<html>
<head>
<meta charset="utf-8"">
<meta name=""viewport" content="width=device-width,initial-scale=1.0,minimum-scale=1.0,maximum-scale=1.0,user-scalable=no">
<meta name="format-detection" content="telephone=no"">
<meta name="robots" content="all">
<title>Server Dev Update</title>
<script src="https://file.mir4global.com/mir4-forum/js/ingame/first.js"></script>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Noto+Sans:wght@400;700;900&family=Noto+Sans+JP:wght@400;700;900&family=Noto+Sans+SC:wght@400;700;900&family=Noto+Sans+TC:wght@400;700;900&display=swap" rel="stylesheet">
<link rel="shortcut icon" href="https://mirfiles.co.uk/resources/mir2/users/Jev/Mir%204/favicon_1.png">
<link rel="stylesheet" href="https://file.mir4global.com/mir4-forum/css/mobile/inspection-ingame.css?v01.08.2">
<script src="https://file.mir4global.com/mir4-forum/js/vendor/jquery-1.12.2.min.js"></script>
<script src="https://file.mir4global.com/mir4-forum/js/vendor/swiper.min.js"></script>
<script src="https://file.mir4global.com/mir4-forum/js/ingame/ingame.js"></script>
<script src="https://file.mir4global.com/live_global/maintenance/moment_full.js"></script>
<script src="https://file.mir4global.com/live_global/maintenance/moment_locales.js"></script>
<script src="https://file.mir4global.com/live_global/maintenance/moment_timezone_data.js"></script>
<script>var initInspection = new inspection();</script>
</head>
<body>
<div id="container" class="container inspection">
<!-- Content -->
<main id="content" class="content">
<div class="content_inner">
<!-- 1번째 컨텐츠 -->
<h2 class="content_title"></h2>
<!-- 3번째 컨텐츠 -->
<div class="wrap_bottom">
<div class="right wrap_notice">
<div class="wrap_notice_title">
<h3 class="notice_title">Announcement</h3>
<hr class="line">
</div>
<div class="notice_inner">
<div class="notice"></div>
</div>
</div>
</div>
<!-- 4번째 컨텐츠 -->
<div class="wrap_buttons">
<a href="https://discord.gg/KCnHvwJJWN" class="button" id="forum_link"><span>Discord</span></a>
<a href="https://thelegendofmir.uk" class="button" id="patch_link"><span>Website</span></a>
</div>
</div>
</main>
</div>
<script>
function getQueryStringObject() {
var a = window.location.search.substr(1).split('&');
if (a == '') return {};
var b = {};
for (var i = 0; i < a.length; ++i) {
var p = a[i].split('=', 2);
b[p[0]] = (p.length == 1) ? '' : decodeURIComponent(p[1].replace(/\+/g, ' '));
}
return b;
}
var params = getQueryStringObject();
// 점검 링크 주소
var forum_link = 'https://discord.gg/KCnHvwJJWN';
var patch_link = 'https://thelegendofmir.uk';
//title
var titleArray = {"en":"Server Dev Update - June 7th",};
//content
var contentArray = {
    "en": `From my battle to our war, MMORPG MIR4

Greetings, This is AVA MIR4. 

Server Update.

--Client--

We are looking to switch from our current CN client to the Global Closed Beta Test Client.

Pros:

- API

  The CBT Client allows us to edit the following via API instead of Hex editing the Client:
  - Server IP/Ports
  - Maintenance
  - Events
  - Version Checking
  - Payment Links
  
  This will allow us to process Client side updates easier.

  APIs will be released to everyone prior to us releasing the Source Code. This will allow users who wish to have their own server to edit these ready for their own launch.

- Stability

  After internal testing we have found that the CBT client is a lot more stable than the CN client, this includes bug fixes.

- Paks

  Currently, the CN Client has a single 8GB Pak file which contains all the Client Data. For comparison, the CBT client splits the 8GB Pak into multiple smaller files. 
  This will help with editing, updating, and pushing updates to everyone without the need to redownload a huge PAK file for every update.

--Server--

- Logging

  We have pushed source code updates to the servers which now have improved logging.

--Source Code--

- VS Version

  When we obtained the source, we were forced to use Visual Studio 2019 due to compatibility. The source now uses Visual Studio 2022.

  The source has currently received a total of 180 updates. This includes: 
  - Bug Fixes
  - Packet changes
  - Compacting code
  - Translation of code comments 
  - And a lot more.

--Beta--

We still have a lot of internal testing to complete before we are at a stage where we feel comfortable releasing a Beta Server. As soon as we have more info on dates, rates, etc., 
we will keep you posted.

--Website--

- Improvements

  - URL file extensions hidden (shortens URL and makes it more appealing).
  - Coming Soon page will be added to any unfinished web pages.
  
  We will release the website's full HTML and API together to allow users to create their own website if they plan to release their own server.

--Client Editor--

We still plan on finishing the Client Editor. This will help reduce the Export/Edit/RePak process from three programs to a single custom-made program.

--Server Console--

Front Server Config page to get code changes to process the contents of \`config.json\` and display on the form.

--Client Launcher--

Client patching is still to come. This will allow us to cross-reference the current Pak files with our API, which will enable the launcher to automatically download new updates.

■ Announcement Schedule
UK (UTC+0): Friday, June. 07, 2024`
};

var title = titleArray[params.lang];
var content = contentArray[params.lang];
if(typeof(title)=='undefined') var title = titleArray['en'];
if(typeof(content)=='undefined') var content = contentArray['en'];
$('.content_title').html(title);
$('.notice').html(content);
$('#forum_link').attr('href',forum_link);
if(patch_link == '') { $('#patch_link').hide(); } else { $('#patch_link').attr('href',patch_link); }
</script>
</body>
</html>