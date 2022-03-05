<?php
	use PHPUnit\Framework\TestCase;

	final class InvalidTest extends TestCase{

		# tests for invalid inputs

		function testBrokenSyntaxRegular(){

			// by default, bad syntax (unterminated strings, comments, etc) will just not produce a token

			$obj = new iamcal\SQLParser();

			$tokens = $obj->lex("CREATE TABLE `users ( id int(10) )");
			$this->assertEquals(count($tokens), 1);

			$tokens = $obj->lex("CREATE TABLE `users` ' ( `id` int(10) )");
			$this->assertEquals(count($tokens), 2);
		}

		function testBrokenSyntaxException1(){

			// in exception mode, it throws an exception...

			$obj = new iamcal\SQLParser();
			$obj->throw_on_bad_syntax = true;

			$this->expectException(iamcal\SQLParserSyntaxException::class);
			$obj->lex("CREATE TABLE `users ( id int(10) )");
		}

		function testBrokenSyntaxException2(){

			$obj = new iamcal\SQLParser();
			$obj->throw_on_bad_syntax = true;

			$this->expectException(iamcal\SQLParserSyntaxException::class);
			$obj->lex("CREATE TABLE `users` ' ( `id` int(10) )");
		}
	}
