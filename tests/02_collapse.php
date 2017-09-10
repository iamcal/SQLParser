<?php
	include(dirname(__FILE__).'/testmore.php');
	include(dirname(__FILE__).'/../src/SQLParser.php');

	plan(2);


	function collapse_test($in, $out){
		$obj = new iamcal\SQLParser();
		is_deeply($obj->lex($in), $out);
	}


	collapse_test('a b', array('a', 'b'));
	collapse_test('UNIQUE key', array('UNIQUE KEY'));
