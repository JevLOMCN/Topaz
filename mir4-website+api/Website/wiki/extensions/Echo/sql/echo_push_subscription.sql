-- Stores push subscriptions associated with wiki users.
CREATE TABLE /*_*/echo_push_subscription (
	eps_id INT UNSIGNED NOT NULL PRIMARY KEY auto_increment,
	-- central user ID
	eps_user INT UNSIGNED NOT NULL,
	-- platform-provided push subscription token
	eps_token BLOB NOT NULL,
	-- SHA256 digest of the push subscription token (to be used as a uniqueness constraint, since
	-- the tokens themselves may be large)
	eps_token_sha256 CHAR(64) NOT NULL,
	-- push provider ID, expected to reference values 'fcm' or 'apns'
	eps_provider TINYINT UNSIGNED NOT NULL,
	-- last updated timestamp
	eps_updated TIMESTAMP NOT NULL,
	-- push subscription metadata (e.g APNS notification topic)
	eps_data BLOB,
	-- APNS topic ID, references a row ID (ept_id) from echo_push_topic
	eps_topic TINYINT UNSIGNED
) /*$wgDBTableOptions*/;

CREATE UNIQUE INDEX /*i*/eps_token_sha256 ON /*_*/echo_push_subscription (eps_token_sha256);
CREATE INDEX /*i*/eps_provider ON /*_*/echo_push_subscription (eps_provider);
CREATE INDEX /*i*/eps_topic ON /*_*/echo_push_subscription (eps_topic);
CREATE INDEX /*i*/eps_user ON /*_*/echo_push_subscription (eps_user);
CREATE INDEX /*i*/eps_token ON /*_*/echo_push_subscription (eps_token(10));
