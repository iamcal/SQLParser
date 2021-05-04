<?php
	use PHPUnit\Framework\TestCase;

	final class FieldTest extends TestCase{

		function testBasicFields(){

			$tbl = $this->get_first_table("CREATE TABLE foo (bar INT)");
			$this->assertEquals(count($tbl['fields']), 1);
			$this->assertEquals($tbl['fields'][0]['type'], "INT");
			$this->assertEquals($tbl['fields'][0]['name'], "bar");

			$tbl = $this->get_first_table("CREATE TABLE foo (bar INT, baz TINYINT)");
			$this->assertEquals(count($tbl['fields']), 2);
			$this->assertEquals($tbl['fields'][0]['type'], "INT");
			$this->assertEquals($tbl['fields'][0]['name'], "bar");
			$this->assertEquals($tbl['fields'][1]['type'], "TINYINT");
			$this->assertEquals($tbl['fields'][1]['name'], "baz");
		}

		function testSimpleFields(){

			# DATE
			# YEAR
			# TINYBLOB
			# BLOB
			# MEDIUMBLOB
			# LONGBLOB
			# JSON

			$tbl = $this->get_first_table("CREATE TABLE foo (bar DATE)");
			$this->assertEquals($tbl['fields'], array(
				array(
					'name' => "bar",
					'type' => "DATE",
				)
			));
		}

		function testInts(){

			$tbl = $this->get_first_table("CREATE TABLE foo (bar TINYINT)");
			$this->assertEquals($tbl['fields'], array(
				array(
					'name' => "bar",
					'type' => "TINYINT",
				)
			));

			$tbl = $this->get_first_table("CREATE TABLE foo (bar smallint (4))");
			$this->assertEquals($tbl['fields'], array(
				array(
					'name' => "bar",
					'type' => "SMALLINT",
					'length' => "4",
				)
			));

			$tbl = $this->get_first_table("CREATE TABLE foo (bar MEDIUMINT UNSIGNED)");
			$this->assertEquals($tbl['fields'], array(
				array(
					'name' => "bar",
					'type' => "MEDIUMINT",
					'unsigned' => true,
				)
			));

			$tbl = $this->get_first_table("CREATE TABLE foo (bar INT ZEROFILL)");
			$this->assertEquals($tbl['fields'], array(
				array(
					'name' => "bar",
					'type' => "INT",
					'zerofill' => true,
				)
			));

			$tbl = $this->get_first_table("CREATE TABLE foo (bar BIGINT(20) UNSIGNED ZEROFILL)");
			$this->assertEquals($tbl['fields'], array(
				array(
					'name' => "bar",
					'type' => "BIGINT",
					'length' => "20",
					'unsigned' => true,
					'zerofill' => true,
				)
			));


		}

		function testFloats(){

			# REAL[(length,decimals)] [UNSIGNED] [ZEROFILL]
			# DOUBLE[(length,decimals)] [UNSIGNED] [ZEROFILL]
			# FLOAT[(length,decimals)] [UNSIGNED] [ZEROFILL]

			$tbl = $this->get_first_table("CREATE TABLE foo (bar REAL)");
			$this->assertEquals($tbl['fields'], array(
				array(
					'name' => "bar",
					'type' => "REAL",
				)
			));

			$tbl = $this->get_first_table("CREATE TABLE foo (bar double (1,2))");
			$this->assertEquals($tbl['fields'], array(
				array(
					'name' => "bar",
					'type' => "DOUBLE",
					'length' => 1,
					'decimals' => 2,
				)
			));

			$tbl = $this->get_first_table("CREATE TABLE foo (bar Float(3,4) UNSIGNED ZEROFILL)");
			$this->assertEquals($tbl['fields'], array(
				array(
					'name' => "bar",
					'type' => "FLOAT",
					'length' => 3,
					'decimals' => 4,
					'unsigned' => true,
					'zerofill' => true,
				)
			));
		}

		function testNumerics(){

			# DECIMAL[(length[,decimals])] [UNSIGNED] [ZEROFILL]
			# NUMERIC[(length[,decimals])] [UNSIGNED] [ZEROFILL]

			$tbl = $this->get_first_table("CREATE TABLE foo (bar Decimal)");
			$this->assertEquals($tbl['fields'], array(
				array(
					'name' => "bar",
					'type' => "DECIMAL",
				)
			));

			$tbl = $this->get_first_table("CREATE TABLE foo (bar DECIMAL(1) UNSIGNED)");
			$this->assertEquals($tbl['fields'], array(
				array(
					'name' => "bar",
					'type' => "DECIMAL",
					'length' => 1,
					'unsigned' => true,
				)
			));

			$tbl = $this->get_first_table("CREATE TABLE foo (bar NUMERIC(1,2) ZEROFILL)");
			$this->assertEquals($tbl['fields'], array(
				array(
					'name' => "bar",
					'type' => "NUMERIC",
					'length' => 1,
					'decimals' => 2,
					'zerofill' => true,
				)
			));
		}

		function testTimes(){

			# TIME[(fsp)]
			# TIMESTAMP[(fsp)]
			# DATETIME[(fsp)]

			$tbl = $this->get_first_table("CREATE TABLE foo (bar TIME)");
			$this->assertEquals($tbl['fields'], array(
				array(
					'name' => "bar",
					'type' => "TIME",
				)
			));

			$tbl = $this->get_first_table("CREATE TABLE foo (bar TIMESTAMP(6))");
			$this->assertEquals($tbl['fields'], array(
				array(
					'name' => "bar",
					'type' => "TIMESTAMP",
					'fsp' => 6,
				)
			));
		}

		function testBits(){

			# BIT[(length)]

			$tbl = $this->get_first_table("CREATE TABLE foo (bar bit)");
			$this->assertEquals($tbl['fields'], array(
				array(
					'name' => "bar",
					'type' => "BIT",
				)
			));

			$tbl = $this->get_first_table("CREATE TABLE foo (bar BIT (999))");
			$this->assertEquals($tbl['fields'], array(
				array(
					'name' => "bar",
					'type' => "BIT",
					'length' => 999,
				)
			));
		}

		function testChars(){

			# CHAR[(length)] [BINARY] [CHARACTER SET charset_name] [COLLATE collation_name]
			# VARCHAR(length) [BINARY] [CHARACTER SET charset_name] [COLLATE collation_name]
			# BINARY[(length)]
			# VARBINARY(length)

			$fields = $this->get_fields("bar CHAR BINARY CHARACTER SET `utf19`");
			$this->assertEquals(array(
				array(
					'name' => "bar",
					'type' => "CHAR",
					'binary' => true,
					'character_set' => "utf19",

				)
			), $fields);

			$fields = $this->get_fields("bar CHAR(15) CHARACTER SET `utf19` COLLATE `utf19_awesome`");
			$this->assertEquals(array(
				array(
					'name' => "bar",
					'type' => "CHAR",
					'length' => 15,
					'character_set' => "utf19",
					'collation' => "utf19_awesome",
				)
			), $fields);

			$fields = $this->get_fields("bar VARCHAR(255)`");
			$this->assertEquals(array(
				array(
					'name' => "bar",
					'type' => "VARCHAR",
					'length' => 255,
				)
			), $fields);

			$fields = $this->get_fields("bar BINARY");
			$this->assertEquals(array(
				array(
					'name' => "bar",
					'type' => "BINARY",
				)
			), $fields);

			$fields = $this->get_fields("bar BINARY(66)");
			$this->assertEquals(array(
				array(
					'name' => "bar",
					'type' => "BINARY",
					'length' => 66,
				)
			), $fields);

			$fields = $this->get_fields("bar VARBINARY(1024)");
			$this->assertEquals(array(
				array(
					'name' => "bar",
					'type' => "VARBINARY",
					'length' => 1024,
				)
			), $fields);
		}

		function testTexts(){

			# TODO

			# TINYTEXT [BINARY] [CHARACTER SET charset_name] [COLLATE collation_name]
			# TEXT [BINARY] [CHARACTER SET charset_name] [COLLATE collation_name]
			# MEDIUMTEXT [BINARY] [CHARACTER SET charset_name] [COLLATE collation_name]
			# LONGTEXT [BINARY] [CHARACTER SET charset_name] [COLLATE collation_name]
		}

		function testSets(){

			# TODO

			# ENUM(value1,value2,value3,...) [CHARACTER SET charset_name] [COLLATE collation_name]
			# SET(value1,value2,value3,...) [CHARACTER SET charset_name] [COLLATE collation_name]
		}


		function testSpatials(){

			# TODO

			# GEOMETRY
			# POINT
			# LINESTRING
			# POLYGON
			# MULTIPOINT
			# MULTILINESTRING
			# MULTIPOLYGON
			# GEOMETRYCOLLECTION
		}

		function testJson(){

			# TODO

			# JSON
		}

		function testFieldOptions(){

			# TODO

			# data_type
			# [NOT NULL | NULL]
			# [DEFAULT default_value]
			# [AUTO_INCREMENT]
			# [UNIQUE [KEY] | [PRIMARY] KEY]
			# [COMMENT 'string']
			# [COLUMN_FORMAT {FIXED|DYNAMIC|DEFAULT}]
			# [STORAGE {DISK|MEMORY|DEFAULT}]
			# [reference_definition]

			$fields = $this->get_fields("bar VARCHAR(255) DEFAULT NULL");
			$this->assertEquals(array(
				array(
					'name' => "bar",
					'type' => "VARCHAR",
					'length' => 255,
					'default' => 'NULL',
					'null' => true,
				)
			), $fields);
		}

		function testVirtualOptions(){

			# TODO

			# data_type [GENERATED ALWAYS] AS (expression)
			# [VIRTUAL | STORED]
			# [UNIQUE [KEY]] | [[PRIMARY] KEY]
			# [COMMENT comment]
			# [NOT NULL | NULL]
		}


		function get_fields($indexes){

			$sql = "CREATE TABLE foo ($indexes)";
			$tbl = $this->get_first_table($sql);
			return $tbl['fields'];
		}

		function get_first_table($str){
			$obj = new iamcal\SQLParser();
			$obj->parse($str);

			$tables = array_keys($obj->tables);
			$first_key = $tables[0];

			return $obj->tables[$first_key];
		}
	}
