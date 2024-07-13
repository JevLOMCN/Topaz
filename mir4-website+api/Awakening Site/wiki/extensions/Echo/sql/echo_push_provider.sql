-- Table for normalizing push providers; intended for use with the NameTableStore construct.
CREATE TABLE /*_*/echo_push_provider (
    epp_id TINYINT UNSIGNED NOT NULL PRIMARY KEY auto_increment,
    -- push provider name; expected values are 'fcm' and 'apns'
    epp_name TINYBLOB NOT NULL
) /*$wgDBTableOptions*/;
