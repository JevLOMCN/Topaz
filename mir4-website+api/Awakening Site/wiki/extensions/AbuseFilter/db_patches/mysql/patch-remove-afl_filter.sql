DROP INDEX /*i*/afl_filter_timestamp ON /*_*/abuse_filter_log;
ALTER TABLE /*_*/abuse_filter_log
	DROP COLUMN afl_filter,
	ALTER COLUMN afl_filter_id DROP DEFAULT,
	ALTER COLUMN afl_global DROP DEFAULT;
