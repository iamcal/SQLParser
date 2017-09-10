<?php
	use PHPUnit\Framework\TestCase;

        final class CollapseTest extends TestCase{

		function collapse_test($in, $out){
			$obj = new iamcal\SQLParser();
			$this->assertEquals($obj->lex($in), $out);
		}

		function testCollapsing(){

			$this->collapse_test('a b', array('a', 'b'));
			$this->collapse_test('UNIQUE key', array('UNIQUE KEY'));
		}
	}
