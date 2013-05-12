all:
	@echo "Future build step will go here";

test: all
	@prove --exec 'php' tests/*.t
