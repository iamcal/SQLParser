<?php

require_once('PHP-SQL-Parser/php-sql-parser.php');

$sql = "CREATE TABLE `achievements_counts` (\n";
$sql .= "`achievement_id` int(10) unsigned NOT NULL,\n";
$sql .= "`num_players` int(10) unsigned NOT NULL,\n";
$sql .= "`date_updated` int(10) unsigned NOT NULL,\n";
$sql .= "PRIMARY KEY (`achievement_id`)\n";
$sql .= ") ENGINE=InnoDB DEFAULT CHARSET=utf8;";
$sql .= "\n";
$sql .= "SET character_set_client = @saved_cs_client;\n";


#$parser = new PHPSQLParser($sql, true);
#print_r($parser->parsed);

#$lexer = new PHPSQLLexer();
#var_dump($lexer->split($sql));

$sql = file_get_contents('glitch_full.sql');

var_export(process_statements($sql));



function process_statements($sql){

$start = microtime(true);

	$lexer = new PHPSQLLexer();
	$tokens = $lexer->split($sql);

$took = microtime(true) - $start;

print_r($tokens);

echo "lex time: $took\n";
exit;
	$statements = array();
	$buffer = array();

	foreach ($tokens as $token){
		if ($token == ';'){
			if (count($buffer)){
				$statements[] = $buffer;
				$buffer = array();			
			}
			continue;
		}

		if (trim($token) === "") continue;

		$buffer[] = $token;
	}

	if (count($buffer)){
		$statements[] = $buffer;
	}

	return $statements;
}
