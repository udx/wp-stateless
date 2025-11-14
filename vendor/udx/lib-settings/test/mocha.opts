REPORTER = list
JSON_FILE = static/all.json
HTML_FILE = static/coverage.html

test-all:
	clean
	document
	lib-cov
	test-code

document:
	yuidoc -q

test-code:
	@NODE_ENV=test mocha \
	--timeout 200 \
	--ui exports \
	--reporter $(REPORTER) \
	test/*.js

test-cov: 
	lib-cov
	@APP_COVERAGE=1 $(MAKE) test \
	REPORTER=html-cov > $(HTML_FILE)

lib-cov:
	jscoverage lib static/lib-cov

clean:
	rm -fr static/lib-cov
	rm -fr static/assets
	rm -fr static/classes
	rm -fr static/files
	rm -fr static/modules
	rm -f static/api.js
	rm -f static/data.json
	rm -f static/index.html
