<?php
	include(dirname(__FILE__).'/testmore.php');
	include(dirname(__FILE__).'/../lex.php');

	function lex_test($str, $tokens){
		$obj = new SchemaCompSchema();

		is_deeply($obj->lex($str), $tokens);
	}

	plan(2);

	# simple word tokens
	lex_test('hello world', array('hello', 'world'));

	# comments and whitespace get stripped
	lex_test("hello \nworld-- foo\nyeah", array('hello', 'world', 'yeah'));
