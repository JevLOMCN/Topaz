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
<link rel="shortcut icon" href="https://file.mir4global.com/mir4-forum/img/favicon.ico">
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
var titleArray = {"en":"Server Dev Update - July 3rd",};
//content
var contentArray = {
    "en": `From my battle to our war, MMORPG MIR4

Greetings, This is AVA MIR4. 

Server Update.

--We need YOU!--

Currently, the process of exporting/editing/repacking the Mir 4 Client's "Pak" file, this currently takes three separate tools which as I'm sure you can imagine is not only a ball ache but the programs being used were not made to handle the data that our pak file contains.

Due to this, two of the programs crashes and are very temperamental.

We would prefer to have a single program that can do everything we need, creating one dedicated for the Mir 4 Client will allow us to edit client's quicker and also make the process of editing more stable and easier.

A guide to the current process of Export/Edit/Repak can be found here: https://shorturl.at/93hNO

Our current Client Editor can be found here: https://shorturl.at/FCPHm

■ Announcement Date
16:00 UK (UTC+0): Friday, July. 03, 2024`
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