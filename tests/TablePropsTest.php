<?php
	use PHPUnit\Framework\TestCase;

	final class TablePropsTest extends TestCase{

		# table_options:
		#     table_option [[,] table_option] ...
		#
		# table_option:
		#     AUTO_INCREMENT [=] value
		#   | AVG_ROW_LENGTH [=] value
		#   | [DEFAULT] CHARACTER SET [=] charset_name
		#   | CHECKSUM [=] {0 | 1}
		#   | [DEFAULT] COLLATE [=] collation_name
		#   | COMMENT [=] 'string'
		#   | COMPRESSION [=] {'ZLIB'|'LZ4'|'NONE'}
		#   | CONNECTION [=] 'connect_string'
		#   | {DATA|INDEX} DIRECTORY [=] 'absolute path to directory'
		#   | DELAY_KEY_WRITE [=] {0 | 1}
		#   | ENCRYPTION [=] {'Y' | 'N'}
		#   | ENGINE [=] engine_name
		#   | INSERT_METHOD [=] { NO | FIRST | LAST }
		#   | KEY_BLOCK_SIZE [=] value
		#   | MAX_ROWS [=] value
		#   | MIN_ROWS [=] value
		#   | PACK_KEYS [=] {0 | 1 | DEFAULT}
		#   | PASSWORD [=] 'string'
		#   | ROW_FORMAT [=] {DEFAULT|DYNAMIC|FIXED|COMPRESSED|REDUNDANT|COMPACT}
		#   | STATS_AUTO_RECALC [=] {DEFAULT|0|1}
		#   | STATS_PERSISTENT [=] {DEFAULT|0|1}
		#   | STATS_SAMPLE_PAGES [=] value
		#   | TABLESPACE tablespace_name [STORAGE {DISK|MEMORY|DEFAULT}]
		#   | UNION [=] (tbl_name[,tbl_name]...)


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


		function table_props_test($tokens, $props_expect){

			$obj = new iamcal\SQLParser();
			$i = 0;
			$props = $obj->parse_table_props($tokens, $i);

			$this->assertEquals($props, $props_expect);
		}
	}
