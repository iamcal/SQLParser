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

		public function testWhitespace(){

			$this->lex_test("foo bar", array("foo", "bar"));
			$this->lex_test(" foo bar ", array("foo", "bar"));
			$this->lex_test("\n\nfoo\tbar\n", array("foo", "bar"));

			# with comments too
			$this->lex_test("hello \nworld-- foo\nyeah", array('hello', 'world', 'yeah'));
		}

		public function testComments(){
			$this->lex_test("foo -- bar", array("foo"));
			$this->lex_test("foo --bar", array("foo"));
			$this->lex_test("foo -- bar\n", array("foo"));
			$this->lex_test("foo -- bar \n", array("foo"));
			$this->lex_test("foo -- bar \n ", array("foo"));

			$this->lex_test("foo/"."* hello *"."/ bar", array("foo", "bar"));
			$this->lex_test("foo/"."*hello*"."/ bar", array("foo", "bar"));
			$this->lex_test("foo/"."* hello \n world *"."/ bar", array("foo", "bar"));
			$this->lex_test("foo/"."*hello \n world*"."/ bar", array("foo", "bar"));

			$this->lex_test("foo/"."* hello", array("foo"));
			$this->lex_test("foo/"."* hello *", array("foo"));
			$this->lex_test("foo/"."* hello \n world", array("foo"));
		}

		public function testBacktickFields(){

			$this->lex_test("hello `world` foo", array("hello", "`world`", "foo"));
			$this->lex_test("hello  `world`  foo", array("hello", "`world`", "foo"));

			# the token rules allow _anything_ inside backticks o_O
			$this->lex_test("hello `world \n test`  foo", array("hello", "`world \n test`", "foo"));

			$this->lex_test("hello `foo bar\n baz", array("hello"));
		}

		public function testNumericLiterals(){

			# normal cases
			$this->lex_test("1 12 12.3 12.34", array("1", "12", "12.3", "12.34"));

			# weird cases
			$this->lex_test("12. 1. .3 .34", array("12.", "1.", ".3", ".34"));
		}

		public function testStrings(){

			$this->lex_test("foo 'bar' baz", array("foo", "'bar'", "baz"));
			$this->lex_test("foo 'bar \\' baz' qux", array("foo", "'bar \\' baz'", "qux"));

			$this->lex_test("foo \"bar\" baz", array("foo", "\"bar\"", "baz"));
			$this->lex_test("foo \"bar \\\" baz\" qux", array("foo", "\"bar \\\" baz\"", "qux"));
		}
	}
