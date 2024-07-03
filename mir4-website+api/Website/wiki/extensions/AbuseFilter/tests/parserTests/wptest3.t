/* Filter 18 from English Wikipedia (test type edits from clicking on edit bar) */
user_groups_test := ["*"];
article_namespace_test := 0;
added_lines_test := ["Hello world! '''Bold text''' [http://www.example.com link title]"];

(article_namespace_test == 0) &
!("autoconfirmed" in user_groups_test) &
(contains_any(string(added_lines_test), 
	"'''Bold text'''", 
	"''Italic text''", 
	"[[Link title]]", 
	"[http://www.example.com link title]", 
	"== Headline text ==", 
	"[[File:Example.jpg]]", 
	"[[Media:Example.ogg]]", 
	"<math>Insert formula here</math>", 
	"<nowiki>Insert non-formatted text here</nowiki>", 
	"#REDIRECT [[Target page name]]", 
	"<s>Strike-through text</s>", 
	"<sup>Superscript text</sup>", 
	"<sub>Subscript text</sub>", 
	"<small>Small Text</small>", 
	"<!-- Comment -->", 
	"Image:Example.jpg|Caption", 
	"<ref>Insert footnote text here</ref>",
	"Ǣ ǣ ǖ ǘ ǚ ǜ Ă"
))

