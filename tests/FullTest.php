<?php
	use PHPUnit\Framework\TestCase;

	final class FullTest extends TestCase{

		function full_test($sql, $expected){

			$obj = new iamcal\SQLParser();
			$obj->parse($sql);

			$lines = array();
			foreach ($obj->tables as $table){
				$lines[] = "TABLE:{$table['name']}";
				$lines[] = "SQL:{$table['sql']}";
				foreach ($table['fields'] as $field){
					$lines[] = "-FIELD:{$field['name']}:{$field['type']}";
				}
			}

			$this->assertEquals($lines, $expected);
		}


		function testBasicCases(){

			$this->full_test("CREATE TABLE table_name (a INT);\n" .
				  "-- ignored comment\n\n" .
				  "CREATE TABLE t2 (b VARCHAR)\n\n;\n",
				array(
					"TABLE:table_name",
					"SQL:CREATE TABLE table_name (a INT);",
					"-FIELD:a:INT",
					"TABLE:t2",
					"SQL:CREATE TABLE t2 (b VARCHAR)\n\n;",
					"-FIELD:b:VARCHAR",
			));
		}
	}
