-- Drop foreign keys from echo_push_subscription and rename index to match table prefix - T306473

DROP TABLE IF EXISTS /*_*/echo_push_subscription_tmp;
CREATE TABLE /*_*/echo_push_subscription_tmp (
	eps_id INT UNSIGNED NOT NULL PRIMARY KEY auto_increment,
	eps_user INT UNSIGNED NOT NULL,
	eps_token BLOB NOT NULL,
	eps_token_sha256 CHAR(64) NOT NULL,
	eps_provider TINYINT UNSIGNED NOT NULL,
	eps_updated TIMESTAMP NOT NULL,
	eps_data BLOB,
	eps_topic TINYINT UNSIGNED
) /*$wgDBTableOptions*/;

INSERT INTO /*_*/echo_push_subscription_tmp
	SELECT eps_id, eps_user, eps_token, eps_token_sha256, eps_provider, eps_updated, eps_data, eps_topic
		FROM /*_*/echo_push_subscription;

DROP TABLE /*_*/echo_push_subscription;

ALTER TABLE /*_*/echo_push_subscription_tmp RENAME TO /*_*/echo_push_subscription;

CREATE UNIQUE INDEX /*i*/eps_token_sha256 ON /*_*/echo_push_subscription (eps_token_sha256);
CREATE INDEX /*i*/eps_provider ON /*_*/echo_push_subscription (eps_provider);
CREATE INDEX /*i*/eps_topic ON /*_*/echo_push_subscription (eps_topic);
CREATE INDEX /*i*/eps_user ON /*_*/echo_push_subscription (eps_user);
CREATE INDEX /*i*/eps_token ON /*_*/echo_push_subscription (eps_token(10));
