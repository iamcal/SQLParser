<?php
	include('lex.php');

	$sql = file_get_contents('glitch_full.sql');

	$s = microtime(true);
	$tokens = lex_sql($sql);
	$e = microtime(true);

	print_r($tokens);

	$ms = round(1000 * ($e - $s));
	echo "Lexing took $ms ms\n";

	#var_export(parse_sql(lex_sql($sql)));

	#var_export(lex_sql("hello \nworld-- foo\nyeah"));
