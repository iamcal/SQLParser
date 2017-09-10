all:
	@echo "Future build step will go here";

test:
	@phpunit --bootstrap src/SQLParser.php --testdox tests
