<?php
	use PHPUnit\Framework\TestCase;

	final class TablesTest extends TestCase{

		function testGeneralCreation(){

			$tbl = $this->get_first_table("CREATE TABLE foo");
			$this->assertEquals($tbl['name'], "foo");
			$this->assertEquals(array_key_exists('temporary', $tbl['props']), false);

			$tbl = $this->get_first_table("CREATE TEMPORARY TABLE foo");
			$this->assertEquals($tbl['name'], "foo");
			$this->assertEquals($tbl['props']['temporary'], true);
		}

		function testIfNotExists(){

			$tbl1 = $this->get_first_table("CREATE TABLE bar");
			$tbl2 = $this->get_first_table("CREATE TABLE IF NOT EXISTS bar");

			# these props wont match, since it's the src sql
			unset($tbl1['sql']);
			unset($tbl2['sql']);

			$this->assertEquals(var_export($tbl1, true), var_export($tbl2, true));
		}

		function testCreateTableLike(){

			$tbl = $this->get_first_table("CREATE TABLE foo LIKE `bar`");

			$this->assertEquals($tbl['name'], "foo");
			$this->assertEquals($tbl['like'], "bar");
		}


		function get_first_table($str){
			$obj = new iamcal\SQLParser();
			$obj->parse($str);

			$tables = array_keys($obj->tables);
			$first_key = $tables[0];

			return $obj->tables[$first_key];
		}
	}
