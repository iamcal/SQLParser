<?php
	use PHPUnit\Framework\TestCase;

	final class OddsTest extends TestCase{

		# tests for odds and ends

		function testSingleTable(){

			$obj = new iamcal\SQLParser();

			$obj->parse("CREATE TABLE foo; CREATE TABLE bar");
			$this->assertEquals(count($obj->tables), 2);

			$obj->find_single_table = true;
			$obj->parse("CREATE TABLE foo; CREATE TABLE bar");
			$this->assertEquals(count($obj->tables), 1);
		}
	}
