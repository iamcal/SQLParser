<?php
	include(dirname(__FILE__).'/testmore.php');
	include(dirname(__FILE__).'/../lex.php');

	function full_test($sql, $expected){

		$obj = new SchemaCompSchema();
		$obj->parse($sql);

		$lines = array();
		foreach ($obj->tables as $table){
			$lines[] = "TABLE:{$table['name']}";
			foreach ($table['fields'] as $field){
				$lines[] = "-FIELD:{$field['name']}:{$field['type']}";
			}
		}

		is_deeply($lines, $expected);
	}


	plan(1);


	full_test("CREATE TABLE table_name (a INT);", array(
		"TABLE:table_name",
		"-FIELD:a:INT",
	));
