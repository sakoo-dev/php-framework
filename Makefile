.PHONY: start
start:
	@make hello
	@chmod +x ./bin/.
	@cp -a ./bin/hooks/. ./.git/hooks
	@ln -snf ./bin/sakoo ./sakoo
	@cp ./stubs/environment/.env.example .env
	@cp ./stubs/environment/.env.testing.example .env.testing
	@./sakoo up -d --build
	@./sakoo composer install

.PHONY: up
up:
	@./sakoo up -d

.PHONY: down
down:
	@./sakoo down

.PHONY: restart
restart:
	@./sakoo restart

.PHONY: rm
rm:
	@./sakoo down -v --remove-orphans

.PHONY: lint
lint:
	@./sakoo php ./vendor/bin/php-cs-fixer fix .

.PHONY: check
check:
	@./sakoo composer lint
	@./sakoo composer test
	@./sakoo composer analyse
	@./sakoo composer validate --strict
	@./sakoo composer audit
	@docker build --check -f ./docker/sakoo.app/Dockerfile .
	@make doc
	@./bin/test-coverage

.PHONY: test
test:
	@./sakoo test

.PHONY: test-coverage
test-coverage:
	@rm -rf ./storage/tests/coverage/
	@./sakoo test --coverage-html=storage/tests/coverage/
	@open ./storage/tests/coverage/index.html

.PHONY: analyse
analyse:
	@./sakoo php ./vendor/bin/phpstan analyse --memory-limit 1G --debug

.PHONY: watch
watch:
	@./sakoo assist watch

.PHONY: doc
doc:
	@./sakoo assist doc:gen

.PHONY: shell
shell:
	@./sakoo shell

.PHONY: hello
hello:
	@echo "\t\t======================="
	@echo "\t\t========="
	@echo "  ======================="
	@echo "\nSakoo Development Group\n"

.PHONY: cache
cache:
	@./sakoo assist container:cache

.PHONY: cache-clear
cache-clear:
	@./sakoo assist container:cache --clear
	@./sakoo composer dump-autoload

.PHONY mcp-test:
	@npx @modelcontextprotocol/inspector ./bin/mcp
