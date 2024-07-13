.PHONY: data

all: data

data:
	php bin/minify js tests/data/sourcemap/advanced.js > tests/data/sourcemap/advanced.min.js
	php bin/minify jsmap-web tests/data/sourcemap/advanced.js > tests/data/sourcemap/advanced.min.js.map

	php bin/minify jsmap-raw tests/data/sourcemap/simple.js > tests/data/sourcemap/simple.min.js.map
	php bin/minify js tests/data/sourcemap/simple.js > tests/data/sourcemap/simple.min.js
	printf "//# sourceMappingURL=simple.min.js.map" >> tests/data/sourcemap/simple.min.js

	php tests/data/sourcemap/combine.php
	php tests/data/sourcemap/production.php
