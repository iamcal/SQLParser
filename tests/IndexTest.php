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

			$tbl = $this->get_first_table("CREATE TABLE foo (bar INT, CONSTRAINT UNIQUE INDEX(bar))");
			$this->assertEquals($tbl['indexes'][0]['constraint'], true);
			$this->assertEquals(array_key_exists('constraint_name', $tbl['indexes'][0]), false);

			$tbl = $this->get_first_table("CREATE TABLE foo (bar INT, CONSTRAINT `qux` UNIQUE INDEX(bar))");
			$this->assertEquals($tbl['indexes'][0]['constraint'], true);
			$this->assertEquals($tbl['indexes'][0]['constraint_name'], 'qux');

			$tbl = $this->get_first_table("CREATE TABLE foo (bar INT, CONSTRAINT `qux` PRIMARY KEY(bar))");
			$this->assertEquals($tbl['indexes'][0]['constraint'], true);
			$this->assertEquals($tbl['indexes'][0]['constraint_name'], 'qux');
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

			# single column
			$tbl = $this->get_first_table("CREATE TABLE foo (bar INT, baz INT, PRIMARY KEY (bar))");
			$this->assertEquals(count($tbl['indexes'][0]['cols']), 1);
			$this->assertEquals($tbl['indexes'][0]['cols'][0]['name'], 'bar');

			# multi column
			$tbl = $this->get_first_table("CREATE TABLE foo (bar INT, baz INT, PRIMARY KEY (bar, baz))");
			$this->assertEquals(count($tbl['indexes'][0]['cols']), 2);
			$this->assertEquals($tbl['indexes'][0]['cols'][0]['name'], 'bar');
			$this->assertEquals($tbl['indexes'][0]['cols'][1]['name'], 'baz');

			# length (or not)
			$tbl = $this->get_first_table("CREATE TABLE foo (bar INT, baz INT, PRIMARY KEY (bar))");
			$this->assertEquals($tbl['indexes'][0]['cols'][0]['name'], 'bar');
			$this->assertEquals(array_key_exists('length', $tbl['indexes'][0]['cols'][0]), false);

			$tbl = $this->get_first_table("CREATE TABLE foo (bar INT, baz INT, PRIMARY KEY (bar (100)))");
			$this->assertEquals($tbl['indexes'][0]['cols'][0]['name'], 'bar');
			$this->assertEquals($tbl['indexes'][0]['cols'][0]['length'], '100');

			# direction (or not)
			$tbl = $this->get_first_table("CREATE TABLE foo (bar INT, baz INT, PRIMARY KEY (bar))");
			$this->assertEquals($tbl['indexes'][0]['cols'][0]['name'], 'bar');
			$this->assertEquals(array_key_exists('direction', $tbl['indexes'][0]['cols'][0]), false);

			$tbl = $this->get_first_table("CREATE TABLE foo (bar INT, baz INT, PRIMARY KEY (bar ASC))");
			$this->assertEquals($tbl['indexes'][0]['cols'][0]['name'], 'bar');
			$this->assertEquals($tbl['indexes'][0]['cols'][0]['direction'], "ASC");

			$tbl = $this->get_first_table("CREATE TABLE foo (bar INT, baz INT, PRIMARY KEY (bar desc))");
			$this->assertEquals($tbl['indexes'][0]['cols'][0]['name'], 'bar');
			$this->assertEquals($tbl['indexes'][0]['cols'][0]['direction'], "DESC");

			# everything
			$tbl = $this->get_first_table("CREATE TABLE foo (bar INT, baz INT, PRIMARY KEY (bar (10) ASC, baz DESC))");
			$this->assertEquals($tbl['indexes'][0]['cols'][0]['name'], 'bar');
			$this->assertEquals($tbl['indexes'][0]['cols'][0]['length'], '10');
			$this->assertEquals($tbl['indexes'][0]['cols'][0]['direction'], "ASC");
			$this->assertEquals($tbl['indexes'][0]['cols'][1]['name'], 'baz');
			$this->assertEquals($tbl['indexes'][0]['cols'][1]['direction'], "DESC");
		}

		function testIndexOptions(){

			# key block size
			$tbl = $this->get_first_table("CREATE TABLE foo (bar INT, baz INT, PRIMARY KEY (bar) KEY_BLOCK_SIZE 4)");
			$this->assertEquals($tbl['indexes'][0]['key_block_size'], "4");

			$tbl = $this->get_first_table("CREATE TABLE foo (bar INT, baz INT, PRIMARY KEY (bar) KEY_BLOCK_SIZE=4)");
			$this->assertEquals($tbl['indexes'][0]['key_block_size'], "4");

			# key mode
			$tbl = $this->get_first_table("CREATE TABLE foo (bar INT, baz INT, PRIMARY KEY (bar) USING BTREE)");
			$this->assertEquals($tbl['indexes'][0]['mode'], "BTREE");

			$tbl = $this->get_first_table("CREATE TABLE foo (bar INT, baz INT, PRIMARY KEY (bar) USING HASH)");
			$this->assertEquals($tbl['indexes'][0]['mode'], "HASH");

			# parser
			$tbl = $this->get_first_table("CREATE TABLE foo (bar INT, baz INT, PRIMARY KEY (bar) WITH PARSER foo)");
			$this->assertEquals($tbl['indexes'][0]['parser'], "foo");

			# comment
			$tbl = $this->get_first_table("CREATE TABLE foo (bar INT, baz INT, PRIMARY KEY (bar) COMMENT \"hello world\")");
			$this->assertEquals($tbl['indexes'][0]['comment'], "hello world");

			# everything
			$tbl = $this->get_first_table("CREATE TABLE foo (bar INT, baz INT, PRIMARY KEY (bar) COMMENT \"hello world\" USING HASH KEY_BLOCK_SIZE=4 WITH PARSER foo)");
			$this->assertEquals($tbl['indexes'][0]['comment'], "hello world");
			$this->assertEquals($tbl['indexes'][0]['mode'], "HASH");
			$this->assertEquals($tbl['indexes'][0]['key_block_size'], "4");
			$this->assertEquals($tbl['indexes'][0]['parser'], "foo");
		}

		function testForeignKeys(){

			# [CONSTRAINT [symbol]] FOREIGN KEY [index_name] (index_col_name,...) reference_definition
			#   reference_definition:
			#    REFERENCES tbl_name (index_col_name,...)
			#      [MATCH FULL | MATCH PARTIAL | MATCH SIMPLE]
			#      [ON DELETE reference_option]
			#      [ON UPDATE reference_option]


			$tbl = $this->get_first_table("CREATE TABLE foo (bar INT, FOREIGN KEY (bar) REFERENCES f_foo (f_bar) MATCH FULL ON DELETE SET NULL");
			$this->assertEquals($tbl['indexes'][0]['type'], "FOREIGN");
			$this->assertEquals($tbl['indexes'][0]['cols'], array(array("name" => "bar")));
			$this->assertEquals($tbl['indexes'][0]['ref_table'], "f_foo");
			$this->assertEquals($tbl['indexes'][0]['ref_cols'], array(array("name" => "f_bar")));
			$this->assertEquals($tbl['indexes'][0]['ref_match'], "FULL");
			$this->assertEquals($tbl['indexes'][0]['ref_on_delete'], "SET NULL");

			$tbl = $this->get_first_table("CREATE TABLE foo (bar INT, FOREIGN KEY bar (qux, `quux`) REFERENCES f_foo (f_bar)");
			$this->assertEquals($tbl['indexes'][0]['type'], "FOREIGN");
			$this->assertEquals($tbl['indexes'][0]['name'], "bar");
			$this->assertEquals($tbl['indexes'][0]['cols'], array(
				array("name" => "qux"),
				array("name" => "quux"),
			));
			$this->assertEquals($tbl['indexes'][0]['ref_table'], "f_foo");
			$this->assertEquals($tbl['indexes'][0]['ref_cols'], array(array("name" => "f_bar")));
		}

		function testChecks(){

			# CHECK (expr)

			# checks are not actually supported in MySQL, but can be defined.
			# just extract the expression

			$tbl = $this->get_first_table("CREATE TABLE foo (bar INT, CHECK (bar > 4)");
			$this->assertEquals($tbl['indexes'][0]['type'], 'CHECK');
			$this->assertEquals(count($tbl['indexes'][0]['tokens']), 5);

			$tbl = $this->get_first_table("CREATE TABLE foo (bar INT, CHECK (bar > 2 AND (bar <> 5 OR bar=6))");
			$this->assertEquals($tbl['indexes'][0]['type'], 'CHECK');
			$this->assertEquals(count($tbl['indexes'][0]['tokens']), 16);
		}

		function get_first_table($str){
			$obj = new iamcal\SQLParser();
			$obj->parse($str);

			$tables = array_keys($obj->tables);
			$first_key = $tables[0];

			return $obj->tables[$first_key];
		}
	}
