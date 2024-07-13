CREATE TABLE /*_*/oathauth_users_tmp (
	-- User ID
	id int not null primary key,

	-- Module user has selected
	module text not null,

	-- Data
	data blob null
);

INSERT INTO /*_*/oathauth_users_tmp (id, module, data)
	SELECT id, module, data FROM /*_*/oathauth_users;

DROP TABLE /*_*/oathauth_users;

ALTER TABLE /*_*/oathauth_users_tmp RENAME TO /*_*/oathauth_users;
