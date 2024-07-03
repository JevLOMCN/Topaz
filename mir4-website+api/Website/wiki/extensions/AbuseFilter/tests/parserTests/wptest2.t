/* Filter 61 from English Wikipedia (new user removing references) */
user_groups_test := ["*"];
new_size_test := 100;
article_namespace_test := 0;
edit_delta_test := -22;
added_lines_test := ['<ref name="bah">test</ref> test2!'];
removed_lines_test := ['<ref name="bah">test</ref><ref name="wah">test2</ref>'];

!("autoconfirmed" in user_groups_test)
/* this edit_delta ignores large blankings that are treated by another filter */
& edit_delta_test >= -1000
& article_namespace_test == 0
/* No added lines usually mean a blanking which is dealt with by other filter */
& length(added_lines_test) != 0
& !("#redirect" in lcase(added_lines_test))
/*Counts of more reference tags are removed than added */
& (rcount("(<ref>|<ref\sname|</ref>)",removed_lines_test) > rcount("(<ref>|<ref\sname|</ref>)",added_lines_test))
/*Excludes changing to the named reference format and removing closing tags attached to formerly named refs. Unequality is to account for closing the first named tag */
& !(rcount("<ref>",removed_lines_test) === rcount("<ref\sname",added_lines_test) | rcount("</ref>",removed_lines_test) <= rcount("<ref\sname",added_lines_test))
/*Excludes removal of references to Wikipedia itself */
& !(count("http://en.wikipedia.org",string(removed_lines_test)) > count("http://en.wikipedia.org",string(added_lines_test)))
