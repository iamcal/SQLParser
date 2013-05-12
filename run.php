<?php
	include('lex.php');

	$sql = file_get_contents('glitch_full.sql');

	print_r(lex_sql($sql));

	#var_export(parse_sql(lex_sql($sql)));

	#var_export(lex_sql("hello \nworld-- foo\nyeah"));
