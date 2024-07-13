BEGIN;

DROP TABLE IF EXISTS /*_*/abuse_filter_log_tmp;
CREATE TABLE /*_*/abuse_filter_log_tmp (
	afl_id INTEGER NOT NULL PRIMARY KEY AUTOINCREMENT,
	afl_global tinyint(1) NOT NULL,
	afl_filter_id INTEGER NOT NULL,
	afl_user BIGINT unsigned NOT NULL,
	afl_user_text varbinary(255) NOT NULL,
	afl_ip varbinary(255) not null,
	afl_action varbinary(255) not null,
	afl_actions varbinary(255) not null,
	afl_var_dump BLOB NOT NULL,
	afl_timestamp varbinary(14) NOT NULL,
	afl_namespace int NOT NULL,
	afl_title varbinary(255) NOT NULL,
	afl_wiki varbinary(64) NULL,
	afl_deleted tinyint(1) NOT NULL DEFAULT 0,
	afl_patrolled_by int unsigned NOT NULL DEFAULT 0,
	afl_rev_id int unsigned
) /*$wgDBTableOptions*/;

INSERT INTO abuse_filter_log_tmp

	-- all but afl_filter
	SELECT afl_id, afl_global, afl_filter_id, afl_user, afl_user_text, afl_ip, afl_action,
		afl_actions, afl_var_dump, afl_timestamp, afl_namespace, afl_title, afl_wiki,
		afl_deleted, afl_patrolled_by, afl_rev_id

		FROM /*_*/abuse_filter_log;

DROP TABLE /*_*/abuse_filter_log;

ALTER TABLE /*_*/abuse_filter_log_tmp RENAME TO /*_*/abuse_filter_log;

CREATE INDEX /*i*/afl_filter_timestamp_full ON /*_*/abuse_filter_log (afl_global,afl_filter_id,afl_timestamp);
CREATE INDEX /*i*/afl_user_timestamp ON /*_*/abuse_filter_log (afl_user,afl_user_text,afl_timestamp);
CREATE INDEX /*i*/afl_timestamp ON /*_*/abuse_filter_log (afl_timestamp);
CREATE INDEX /*i*/afl_page_timestamp ON /*_*/abuse_filter_log (afl_namespace, afl_title, afl_timestamp);
CREATE INDEX /*i*/afl_ip_timestamp ON /*_*/abuse_filter_log (afl_ip, afl_timestamp);
CREATE INDEX /*i*/afl_wiki_timestamp ON /*_*/abuse_filter_log (afl_wiki, afl_timestamp);
CREATE INDEX /*i*/afl_rev_id ON /*_*/abuse_filter_log (afl_rev_id);

COMMIT;