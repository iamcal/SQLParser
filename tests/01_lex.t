<?php
	include(dirname(__FILE__).'/testmore.php');
	include(dirname(__FILE__).'/../lex.php');

	function lex_test($str, $tokens){
		is_deeply(lex_sql($str), $tokens);
	}

	plan(1);

	lex_test('hello world', array('hello', 'world'));
