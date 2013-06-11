<?php
	include(dirname(__FILE__).'/testmore.php');
	include(dirname(__FILE__).'/../lex.php');

	plan(2);


	function collapse_test($in, $out){
		$obj = new SchemaCompSchema();
		is_deeply($obj->collapse_tokens($in), $out);
	}


	collapse_test(array('a', 'b'), array('a', 'b'));
	collapse_test(array('UNIQUE', 'key'), array('UNIQUE KEY'));
