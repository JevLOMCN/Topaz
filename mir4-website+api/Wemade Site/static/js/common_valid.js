/* MIR4 유효성 체크 */
var mir_valid = {
  getAuth: function () { //인증번호 받기 체크

	if (!$('[name="USER_DEVICE"]').cmmAjax()) {
		mir.alertOpen('AOS / iOS<br />운영체제를 선택해주세요');
		return false;
	}
	if (!$('[name="USER_PHONENUMBER"]').cmmAjax()) {
		mir.alertOpen('잘못된 번호입니다.<br />확인 후 다시 입력해주세요');
		return false;
	}

	if (!$('[name="USER_PHONENUMBER"]').cmmAjax(8, 8)) { //min , max 글자수 체크 
		mir.alertOpen('잘못된 번호입니다.<br />확인 후 다시 입력해주세요');
		return false;
	}

	//mir.alertOpen('인증번호를 발송하였습니다.');

  },

  reserve: function () { //사전예약 하기 체크
	
	if (!$('[name="USER_DEVICE"]').cmmAjax()) {
		mir.alertOpen('AOS / iOS<br />운영체제를 선택해주세요');
		return false;
	}

	if (!$('[name="USER_PHONENUMBER"]').cmmAjax()) {
		mir.alertOpen('잘못된 번호입니다.<br />확인 후 다시 입력해주세요');
		return false;
	}

	if (!$('[name="USER_PHONENUMBER"]').cmmAjax(8, 8)) { //min , max 글자수 체크 
		mir.alertOpen('잘못된 번호입니다.<br />확인 후 다시 입력해주세요');
		return false;
	}

	if (!$('[name="USER_AUTH"]').cmmAjax()) {
		mir.alertOpen('인증번호를 입력하세요.');
		return false;
	}

	if (!$('[name="USER_CHK1"]:required').cmmAjax()) {
		mir.alertOpen('개인 정보 수집 이용에<br />동의하여 주세요.');
		return false;
	}

	if (!$('[name="USER_CHK2"]:required').cmmAjax()) {
		mir.alertOpen('개인 정보 처리 위탁에<br />동의하여 주세요.');
		return false;
	}

	//mir.alertOpen('잘못된 인증번호입니다.');
	//mir.alertOpen('인증되었습니다.');
	//mir.alertOpen('전화 번호 인증 후,<br />사전예약이 가능합니다.');
	//mir.alertOpen('이미 사전예약을 완료하였습니다.');
	//mir.contentOpen('.cont05'); //사전예약 완료

	//$('.reserveForm').cmmAjax('clear'); //Form 초기화

  },
  square: function () { //마방진 이벤트 체크
	
	//mir.alertOpen('SNS 로그인이 필요합니다.');
	//mir.alertOpen('참여 가능 횟수가 부족합니다.');
	//mir.alertOpen('경품을 선택해주세요');
	//mir.alertOpen('마석이 부족합니다.');
	//mir.alertOpen('사전예약 후,<br />참여 가능합니다. ');

	mir.alertOpen('경품 응모가 완료되었습니다.');

  },

  getAuth2: function () { //인증번호 받기 체크

	if (!$('[name="USER_PHONENUMBER2"]').cmmAjax()) {
		mir.alertOpen('잘못된 번호입니다.<br />확인 후 다시 입력해주세요');
		return false;
	}

	if (!$('[name="USER_PHONENUMBER2"]').cmmAjax(8, 8)) { //min , max 글자수 체크 
		mir.alertOpen('잘못된 번호입니다.<br />확인 후 다시 입력해주세요');
		return false;
	}
  },

  reserve2: function () { //사전예약 하기 체크
	
	if (!$('[name="USER_PHONENUMBER2"]').cmmAjax()) {
		mir.alertOpen('잘못된 번호입니다.<br />확인 후 다시 입력해주세요');
		return false;
	}

	if (!$('[name="USER_PHONENUMBER2"]').cmmAjax(8, 8)) { //min , max 글자수 체크 
		mir.alertOpen('잘못된 번호입니다.<br />확인 후 다시 입력해주세요');
		return false;
	}

	if (!$('[name="USER_AUTH2"]').cmmAjax()) {
		mir.alertOpen('인증번호를 입력하세요.');
		return false;
	}

  },


}

