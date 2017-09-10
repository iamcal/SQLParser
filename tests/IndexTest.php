<?php
	use PHPUnit\Framework\TestCase;

	final class IndexTest extends TestCase{

		function testBasicIndex(){

			$tbl = $this->get_first_table("CREATE TABLE foo (bar INT, INDEX(bar))");
			$this->assertEquals(count($tbl['indexes']), 1);
			$this->assertEquals($tbl['indexes'][0]['type'], "INDEX");
			$this->assertEquals(count($tbl['indexes'][0]['cols']), 1);
			$this->assertEquals($tbl['indexes'][0]['cols'][0]['name'], "bar");
		}

		function testIndexTypes(){

			$tbl = $this->get_first_table("CREATE TABLE foo (bar INT, INDEX(bar))");
			$this->assertEquals($tbl['indexes'][0]['type'], "INDEX");

			$tbl = $this->get_first_table("CREATE TABLE foo (bar INT, KEY (bar))");
			$this->assertEquals($tbl['indexes'][0]['type'], "INDEX");

			$tbl = $this->get_first_table("CREATE TABLE foo (bar INT, UNIQUE(bar))");
			$this->assertEquals($tbl['indexes'][0]['type'], "UNIQUE");

			$tbl = $this->get_first_table("CREATE TABLE foo (bar INT, UNIQUE INDEX(bar))");
			$this->assertEquals($tbl['indexes'][0]['type'], "UNIQUE");

			$tbl = $this->get_first_table("CREATE TABLE foo (bar INT, UNIQUE KEY (bar))");
			$this->assertEquals($tbl['indexes'][0]['type'], "UNIQUE");

			$tbl = $this->get_first_table("CREATE TABLE foo (bar INT, PRIMARY KEY(bar))");
			$this->assertEquals($tbl['indexes'][0]['type'], "PRIMARY");

			$tbl = $this->get_first_table("CREATE TABLE foo (bar INT, FULLTEXT(bar))");
			$this->assertEquals($tbl['indexes'][0]['type'], "FULLTEXT");

			$tbl = $this->get_first_table("CREATE TABLE foo (bar INT, FULLTEXT INDEX(bar))");
			$this->assertEquals($tbl['indexes'][0]['type'], "FULLTEXT");

			$tbl = $this->get_first_table("CREATE TABLE foo (bar INT, FULLTEXT KEY(bar))");
			$this->assertEquals($tbl['indexes'][0]['type'], "FULLTEXT");

			$tbl = $this->get_first_table("CREATE TABLE foo (bar INT, SPATIAL(bar))");
			$this->assertEquals($tbl['indexes'][0]['type'], "SPATIAL");

			$tbl = $this->get_first_table("CREATE TABLE foo (bar INT, SPATIAL INDEX(bar))");
			$this->assertEquals($tbl['indexes'][0]['type'], "SPATIAL");

			$tbl = $this->get_first_table("CREATE TABLE foo (bar INT, SPATIAL KEY(bar))");
			$this->assertEquals($tbl['indexes'][0]['type'], "SPATIAL");
		}

		function testConstraints(){

			# TODO
		}

		function testIndexNames(){

			$tbl = $this->get_first_table("CREATE TABLE foo (bar INT, INDEX(bar))");
			$this->assertEquals(array_key_exists('name', $tbl['indexes'][0]), false);

			$tbl = $this->get_first_table("CREATE TABLE foo (bar INT, INDEX baz(bar))");
			$this->assertEquals($tbl['indexes'][0]['name'], "baz");

			$tbl = $this->get_first_table("CREATE TABLE foo (bar INT, FULLTEXT KEY `baz` (bar))");
			$this->assertEquals($tbl['indexes'][0]['name'], "baz");
		}


		function testIndexModes(){

			$tbl = $this->get_first_table("CREATE TABLE foo (bar INT, INDEX(bar))");
			$this->assertEquals(array_key_exists('mode', $tbl['indexes'][0]), false);

			$tbl = $this->get_first_table("CREATE TABLE foo (bar INT, INDEX  USING BTREE(bar))");
			$this->assertEquals($tbl['indexes'][0]['mode'], "BTREE");

			$tbl = $this->get_first_table("CREATE TABLE foo (bar INT, INDEX  USING HASH (bar))");
			$this->assertEquals($tbl['indexes'][0]['mode'], "HASH");

			$tbl = $this->get_first_table("CREATE TABLE foo (bar INT, PRIMARY KEY USING HASH (bar))");
			$this->assertEquals($tbl['indexes'][0]['mode'], "HASH");

			# since fulltext and spatial indexes don't have a type/mode, this is invalid
			$tbl = $this->get_first_table("CREATE TABLE foo (bar INT, FULLTEXT USING HASH(bar))");
			$this->assertEquals(array_key_exists('mode', $tbl['indexes'][0]), false);
		}

		function testIndexCols(){

			# TODO
		}

		function testIndexOptions(){

			# TODO
		}

		function get_first_table($str){
			$obj = new iamcal\SQLParser();
			$obj->parse($str);

			$tables = array_keys($obj->tables);
			$first_key = $tables[0];

			return $obj->tables[$first_key];
		}
	}
