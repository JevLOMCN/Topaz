var url_media = "https://media.mir4.co.kr";
var option = {
	"nativeControlsForTouch": false,
	controls: false,
	autoplay: true,
	loop: true,
	muted : true,
	heuristicProfile : "High Quality",
	"logo": { "enabled": false }
}


//전설의시작 영상
option.width = "640";
option.height = "400";
player_story = amp("bg_story", option);


//직업 배경 영상
option.width = "1920";
option.height = "1080";
player_class = amp("bg_class", option);


//성장 배경 영상
option.width = "1920";
option.height = "1080";
player_growth = amp("bg_growth", option);


//성장 슬라이드영상
option.width = "640";
option.height = "400";
player_growthSlide1 = amp("growthSlide1", option);
player_growthSlide2 = amp("growthSlide2", option);
player_growthSlide3 = amp("growthSlide3", option);
player_growthSlide4 = amp("growthSlide4", option);