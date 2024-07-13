/* Filter 30 from English Wikipedia (large deletion from article by new editors) */
user_groups_test := ["*"];
new_size_test := 100;
article_namespace_test := 0;
edit_delta_test := -5000;
added_lines_test := '';

!("autoconfirmed" in user_groups_test) & (new_size_test > 50) & (article_namespace_test == 0) &
	(edit_delta_test < -2000) & !("#redirect" in lcase(added_lines_test))
