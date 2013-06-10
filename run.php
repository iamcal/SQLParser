<?php
	include('lex.php');

	$sql = file_get_contents('glitch_full.sql');

	$obj = new SchemaCompSchema();

if (0){
	$s = microtime(true);
	$tokens = lex_sql($sql);
	$e = microtime(true);

	print_r($tokens);

	$ms = round(1000 * ($e - $s));
	echo "Lexing took $ms ms\n";
}

	$obj->parse($sql);

	var_export($obj->tables);
