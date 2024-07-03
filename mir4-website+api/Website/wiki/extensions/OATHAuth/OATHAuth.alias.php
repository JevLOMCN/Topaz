<?php
/**
 * Aliases for OATHAuth's special pages
 *
 * @file
 * @ingroup Extensions
 */

$specialPageAliases = [];

/** English (English) */
$specialPageAliases['en'] = [
	'DisableOATHForUser' => [ 'DisableOATHForUser' ],
	'OATHManage' => [ 'Manage_Two-factor_authentication', 'OATH_Manage', 'OATHManage',
		'OATH', 'Two-factor_authentication', 'OATHAuth' ],
	'VerifyOATHForUser' => [ 'VerifyOATHForUser' ],
];

/** Arabic (العربية) */
$specialPageAliases['ar'] = [
	'OATHManage' => [ 'أواث', 'أواث_أوث' ],
];

/** Egyptian Arabic (مصرى) */
$specialPageAliases['arz'] = [
	'OATHManage' => [ 'اواث', 'اواث_اوث' ],
];

/** Czech (čeština) */
$specialPageAliases['cs'] = [
	'OATHManage' => [ 'Spravovat_dvoufaktorové_ověření', 'Dvoufaktorové_ověření' ],
	'DisableOATHForUser' => [ 'Deaktivovat_uživateli_dvoufaktorové_ověření' ],
	'VerifyOATHForUser' => [ 'Ověřit_dvoufaktorové_ověření' ],
];

/** Spanish (Español) */
$specialPageAliases['es'] = [
	'DisableOATHForUser' => [
		'Desactivar_la_autenticación_de_dos_factores_de_un_usuario',
		'Desactivar_OATH_de_un_usuario'
	],
	'OATHManage' => [
		'Autenticación_de_dos_factores',
		'Gestionar_la_autenticación_de_dos_factores',
		'Gestionar_OATH'
	]
];

/** Galician (Galego) */
$specialPageAliases['gl'] = [
	'DisableOATHForUser' => [
		'Desactivar_a_autenticación_de_dous_factores_dun_usuario',
		'Desactivar_OATH_dun_usuario'
	],
	'OATHManage' => [
		'Autenticación_de_dous_factores',
		'Xestionar_a_autenticación_de_dous_factores',
		'Xestionar_OATH'
	]
];

/** Korean (한국어) */
$specialPageAliases['ko'] = [
	'OATHManage' => [
		'2요소_인증_관리', '2요소인증관리', '2요소인증',
		'2단계인증관리', '2단계인증',
		'OATH_관리', 'OATH관리'
	]
];

/** Northern Luri (لۊری شومالی) */
$specialPageAliases['lrc'] = [
	'OATHManage' => [ 'قأسأم' ],
];

/** Polish (polski) */
$specialPageAliases['pl'] = [
	'DisableOATHForUser' => [
		'Wyłącz_OATH_użytkownika',
		'Wyłącz_weryfikację_dwuetapową_użytkownika'
	],
	'OATHManage' => [
		'Zarządzanie_weryfikacją_dwuetapową',
		'Zarządzanie_OATH',
		'Weryfikacja_dwuetapowa'
	]
];

/** Serbian Cyrillic (српски (ћирилица)) */
$specialPageAliases['sr-ec'] = [
	'DisableOATHForUser' => [ 'Онемогућавање_двофакторске_потврде_идентитета' ],
	'OATHManage' => [ 'Двофакторска_потврда_идентитета' ],
];

/** Serbian Latin (srpski (latinica)) */
$specialPageAliases['sr-el'] = [
	'DisableOATHForUser' => [ 'Onemogućavanje_dvofaktorske_potvrde_identiteta' ],
	'OATHManage' => [ 'Dvofaktorska_potvrda_identiteta' ],
];

/** Urdu (اردو) */
$specialPageAliases['ur'] = [
	'OATHManage' => [ 'حلف_نامہ' ],
];

/** Simplified Chinese (中文（简体）‎) */
$specialPageAliases['zh-hans'] = [
	'DisableOATHForUser' => [ '禁用用户OATH' ],
	'OATHManage' => [ 'OATH验证' ],
	'VerifyOATHForUser' => [ '验证用户OATH' ],
];

/** Traditional Chinese (中文（繁體）‎) */
$specialPageAliases['zh-hant'] = [
	'DisableOATHForUser' => [ '停用使用者OATH' ],
	'OATHManage' => [ 'OATH驗證', 'OATH_認證' ],
	'VerifyOATHForUser' => [ '確認使用者OATH' ],
];

/** Chinese (Hong Kong) (中文（香港）‎) */
$specialPageAliases['zh-hk'] = [
	'DisableOATHForUser' => [ '停用用戶OATH' ],
	'VerifyOATHForUser' => [ '確認用戶OATH' ],
];
