ALTER TABLE /*_*/abuse_filter_log DROP INDEX /*i*/filter_timestamp;
CREATE INDEX /*i*/afl_filter_timestamp ON /*$wgDBprefix*/abuse_filter_log (afl_filter,afl_timestamp);
