CREATE TABLE /*_*/oathauth_users_tmp (
	-- User ID
	id int not null primary key,

	-- Secret key
	secret text NULL DEFAULT NULL,

	-- Scratch tokens
	scratch_tokens blob NULL DEFAULT NULL,

	-- Module user has selected
	module text not null,

	-- Data
	data blob null
);

INSERT INTO /*_*/oathauth_users_tmp (id, secret, scratch_tokens, module, data)
	SELECT id, secret, scratch_tokens, '', null FROM /*_*/oathauth_users;

DROP TABLE /*_*/oathauth_users;

ALTER TABLE /*_*/oathauth_users_tmp RENAME TO /*_*/oathauth_users;
