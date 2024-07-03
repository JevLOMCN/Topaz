ALTER TABLE /*_*/abuse_filter_log DROP INDEX /*i*/wiki_timestamp;
CREATE INDEX /*i*/afl_wiki_timestamp ON /*$wgDBprefix*/abuse_filter_log (afl_wiki, afl_timestamp);