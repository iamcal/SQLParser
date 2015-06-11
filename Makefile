all:
	@echo "Future build step will go here";

test:
	@prove --exec 'php' tests/*_*.php
