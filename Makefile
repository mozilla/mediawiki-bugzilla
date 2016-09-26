

install-dev:
	composer install

test: lint cs

lint: phplint

cs: phpcs

phplint:
	./vendor/bin/parallel-lint --exclude vendor/ .

phpcs:
	./vendor/bin/phpcs -p --colors .

phpcsfix:
	./vendor/bin/phpcbf .

.PHONY: test lint cs phpcs phplint phpcsfix
