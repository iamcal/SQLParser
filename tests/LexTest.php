<?php
	use PHPUnit\Framework\TestCase;

	final class LexTest extends TestCase{

		private function lex_test($str, $tokens){
			$obj = new iamcal\SQLParser();

			$this->assertEquals($obj->lex($str), $tokens);
		}

		public function test_simple_word_tokens(){

			$this->lex_test('hello world', array('hello', 'world'));
		}

		public function test_strip_comments_whitespace(){

			$this->lex_test("hello \nworld-- foo\nyeah", array('hello', 'world', 'yeah'));
		}
	}
