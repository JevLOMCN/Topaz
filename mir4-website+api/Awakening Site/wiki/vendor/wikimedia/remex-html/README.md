RemexHtml
=========

RemexHtml is a parser for HTML 5, written in PHP.

RemexHtml aims to be:

- Modular and flexible.
- Fast, as opposed to elegant. For example, we sometimes use direct member
  access instead of going through accessors, and manually inline some
  performance-sensitive code.
- Robust, aiming for O(N) worst-case performance.

RemexHtml contains the following modules:

- A compliant preprocessor and tokenizer. This generates a token event stream.
- Compliant tree construction, including error recovery. This generates a tree
  mutation event stream.
- A fast integrated HTML serializer, compliant with the HTML fragment
  serialization algorithm.
- DOMDocument construction.

RemexHtml presently lacks:

- Encoding support. The input is expected to be valid UTF-8.
- Scripting.
- Precise compliance with specified parse error generation.

RemexHtml aims to be compliant with W3C recommendation HTML 5.1, except for
minor backported bugfixes. We chose to implement the W3C standard rather than
the latest WHATWG draft because our application needs stability more than
feature completeness.

RemexHtml passes all [html5lib tests](https://github.com/html5lib/html5lib-tests),
except for parse error counts and tests which reference a future version of the
standard.

**WARNING** This is a new project, we are still developing use cases. So the API
is subject to change.

For example code, see `bin/test.php`.
