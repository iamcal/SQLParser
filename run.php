<?php
	include('lex.php');

	$sql = file_get_contents('glitch_full.sql');

	$obj = new SchemaCompSchema();

if (0){
	$s = microtime(true);
	$tokens = $obj->lex($sql);
	$e = microtime(true);

	print_r($tokens);

	$ms = round(1000 * ($e - $s));
	echo "Lexing took $ms ms\n";
}

if (0){
	$s = microtime(true);
	$tokens = $obj->lex($sql);
	$e = microtime(true);
	$tokens = $obj->collapse_tokens($tokens);
	$e2 = microtime(true);

	print_r($tokens);

	$ms1 = round(1000 * ($e - $s));
	$ms2 = round(1000 * ($e2 - $e));
	echo "Lexing took $ms1 ms\n";
	echo "Collapsing took $ms2 ms\n";
}

if (1){

	$s = microtime(true);
	$obj->parse($sql);
	$e = microtime(true);

	var_export($obj->tables);

	$ms = round(1000 * ($e - $s));
	echo "Parse took $ms ms\n";
}
