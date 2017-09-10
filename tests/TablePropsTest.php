<?php
	use PHPUnit\Framework\TestCase;

	final class TablePropsTest extends TestCase{

		function table_props_test($tokens, $props_expect){

			$obj = new iamcal\SQLParser();
			$i = 0;
			$props = $obj->parse_table_props($tokens, $i);

			$this->assertEquals($props, $props_expect);
		}


		function testEqualsIsOptional(){

			# the equals is optional

			$this->table_props_test(array('ENGINE', '=', 'INNODB'), array('ENGINE' => 'INNODB'));
			$this->table_props_test(array('ENGINE',      'INNODB'), array('ENGINE' => 'INNODB'));
		}

		function testDefaultCharset(){

			# lots of ways to say this

			$this->table_props_test(array('DEFAULT CHARACTER SET',	'foo'), array('CHARSET' => 'foo'));
			$this->table_props_test(array('CHARACTER SET',		'foo'), array('CHARSET' => 'foo'));
			$this->table_props_test(array('DEFAULT CHARSET',	'foo'), array('CHARSET' => 'foo'));
			$this->table_props_test(array('CHARSET',		'foo'), array('CHARSET' => 'foo'));
		}

		function testDefaultCollation(){

			$this->table_props_test(array('DEFAULT COLLATE',	'bar'), array('COLLATE' => 'bar'));
			$this->table_props_test(array('COLLATE',		'bar'), array('COLLATE' => 'bar'));
		}

		function testTwoWordProps(){

			# more two-word props

			$this->table_props_test(array('DATA DIRECTORY',  '=', 'baz'), array('DATA DIRECTORY'  => 'baz'));
			$this->table_props_test(array('INDEX DIRECTORY', '=', 'baz'), array('INDEX DIRECTORY' => 'baz'));
		}


		# TODO: case conversion, multiple options, optional commas


		function get_first_table($str){
			$obj = new iamcal\SQLParser();
			$obj->parse($str);

			$tables = array_keys($obj->tables);
			$first_key = $tables[0];

			return $obj->tables[$first_key];
		}

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
	}
