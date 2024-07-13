-- Table for normalizing APNS push message topics, for use with the NameTableStore construct.
CREATE TABLE /*_*/echo_push_topic (
	ept_id TINYINT UNSIGNED NOT NULL PRIMARY KEY auto_increment,
	-- full topic text
	ept_text TINYBLOB NOT NULL
) /*$wgDBTableOptions*/;
