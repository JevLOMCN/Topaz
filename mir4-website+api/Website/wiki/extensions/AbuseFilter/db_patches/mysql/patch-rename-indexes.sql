-- Rename indexes with explicit names (T251613)

ALTER TABLE /*_*/abuse_filter
	DROP INDEX /*i*/af_group,
	ADD INDEX /*i*/af_group_enabled(af_group,af_enabled,af_id);

ALTER TABLE /*_*/abuse_filter_log
	DROP INDEX /*i*/filter_timestamp_full,
	DROP INDEX /*i*/user_timestamp,
	DROP INDEX /*i*/page_timestamp,
	DROP INDEX /*i*/ip_timestamp;
CREATE INDEX /*i*/afl_filter_timestamp_full ON /*$wgDBprefix*/abuse_filter_log (afl_global,afl_filter_id,afl_timestamp);
CREATE INDEX /*i*/afl_user_timestamp ON /*$wgDBprefix*/abuse_filter_log (afl_user,afl_user_text,afl_timestamp);
CREATE INDEX /*i*/afl_page_timestamp ON /*$wgDBprefix*/abuse_filter_log (afl_namespace, afl_title, afl_timestamp);
CREATE INDEX /*i*/afl_ip_timestamp ON /*$wgDBprefix*/abuse_filter_log (afl_ip, afl_timestamp);
