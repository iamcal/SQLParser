<?php

	#
	# simple lexer based on http://www.contrib.andrew.cmu.edu/~shadow/sql/sql1992.txt
	#

	function lex_sql($sql){

		$pos = 0;
		$len = strlen($sql);

		$tokens = array();

		while ($pos < $len){

			# <space>
			# <newline>
			if (preg_match('!\s+!A', $sql, $m, 0, $pos)){
				$pos += strlen($m[0]);
				continue;
			}

			# <comment>
			if (preg_match('!--!A', $sql, $m, 0, $pos)){
				$p2 = strpos($sql, "\n", $pos);
				if ($p2 === false){
					$pos = $len;
				}else{
					$pos = $p2+1;
				}
				continue;
			}
			if (preg_match('!/\\*!A', $sql, $m, 0, $pos)){
				$p2 = strpos($sql, "*/", $pos);
				if ($p2 === false){
					$pos = $len;
				}else{
					$pos = $p2+2;
				}
				continue;
			}


			# <regular identifier>
			# <key word>
			if (preg_match('![[:alpha:]][[:alnum:]_]*!A', $sql, $m, 0, $pos)){
				$tokens[] = substr($sql, $pos, strlen($m[0]));
				$pos += strlen($m[0]);
				continue;
			}

			# backtick quoted field
			if (substr($sql, $pos, 1) == '`'){
				$p2 = strpos($sql, "`", $pos+1);
				if ($p2 === false){
					$pos = $len;
				}else{
					$tokens[] = substr($sql, $pos, 1+$p2-$pos);
					$pos = $p2+1;
				}
				continue;
			}

			# <unsigned numeric literal>
			# <national character string literal>
			# <bit string literal>
			# <hex string literal>
			# <character string literal>
			# <date string>
			# <time string>
			# <timestamp string>
			# <interval string>
			# <delimited identifier>
			# <SQL special character>
			# <not equals operator>
			# <greater than or equals operator>
			# <less than or equals operator>
			# <concatenation operator>
			# <double period>
			# <left bracket>
			# <right bracket>

			$tokens[] = substr($sql, $pos, 1);
			$pos++;
		}

		return $tokens;
	}


	function parse_sql($tokens){


		#
		# split into statements
		#

		$statements = array();
		$temp = array();
		foreach ($tokens as $t){
			if ($t == ';'){
				if (count($temp)) $statements[] = $temp;
				$temp = array();
			}else{
				$temp[] = $t;
			}
		}
		if (count($temp)) $statements[] = $temp;


		#
		# find CREATE TABLE statements
		#

		$tables = array();

		foreach ($statements as $s){

			if (next_tokens($s, 'CREATE', 'TABLE')){

				array_shift($s); # CREATE
				array_shift($s); # TABLE

				$table = parse_create_table($s);
				$tables[$table['name']] = $table;
			}

			if (next_tokens($s, 'CREATE', 'TEMPORARY', 'TABLE')){

				array_shift($s); # CREATE
				array_shift($s); # TEMPORARY
				array_shift($s); # TABLE

				$table = parse_create_table($s);
				$table['props']['temp'] = true;
				$tables[$table['name']] = $table;
			}
		}

		return array(
			'tables' => $tables,
		);
	}


	function parse_create_table($tokens){

		if (next_tokens($tokens, 'IF', 'NOT', 'EXISTS')){
			array_shift($tokens);
			array_shift($tokens);
			array_shift($tokens);
		}


		#
		# name
		#

		$name = shift_field_name($tokens);


		#
		# CREATE TABLE x LIKE y
		#

		if (next_tokens($tokens, 'LIKE')){
			array_shift($tokens);
			$old_name = shift_field_name($tokens);

			return array(
				'name'	=> $name,
				'like'	=> $old_name,
			);
		}


		#
		# create_definition
		#

		$fields = array();

		if (next_tokens($tokens, '(')){
			array_shift($tokens);
			$fields = parse_create_definition($tokens);
		}



		$table = array(
			'name'		=> $name,
			'fields'	=> $fields,
			'props'		=> $props,
			'more'		=> $tokens,
		);

echo "CREATE TABLE {$name}\n";
print_r($table);
#exit;
	}


	function next_tokens($tokens){

		$args = func_get_args();
		array_shift($args);

		$i = 0;
		foreach ($args as $v){
			if (StrToUpper($tokens[$i]) != $v)return false;
			$i++;
		}
		return true;
	}

	function shift_field_name(&$tokens){
		$name = array_shift($tokens);
		if ($name{0} == '`'){
			$name = substr($name, 1, -1);
		}
		return $name;
	}

	function parse_create_definition(&$tokens){

		$fields = array();

		while (!next_tokens($tokens, ')')){

			#
			# parse a single create_definition
			#

			$is_key = false;
			$is_primary = false;
			$is_fulltext = false;
			$is_spatial = false;

			$next = StrToUpper($tokens[0]);


			# short-circuit indexes

			if ($next == 'INDEX' || $next == 'KEY'){
				array_shift($tokens);
				$fields[] = parse_key(slice_until_next_field($tokens));
				continue;
			}

			if ($next == 'FULLTEXT' || $next == 'SPATIAL'){
				$next2 = StrToUpper($tokens[1]);
				if ($next2 == 'INDEX' || $next2 == 'KEY'){
					array_shift($tokens);
					array_shift($tokens);
					$fields[] = parse_key(slice_until_next_field($tokens), $next);
					continue;
				}				
			}


			# exotic things

			if ($next == 'CONSTRAINT'){

				$fields[] = array(
					'_'		=> 'CONSTRAINT',
					'tokens'	=> slice_until_next_field($tokens),
				);
				continue;
			}

			if ($next == 'CHECK'){

				$fields[] = array(
					'_'		=> 'CHECK',
					'tokens'	=> slice_until_next_field($tokens),
				);
				continue;
			}

			$fields[] = parse_field(slice_until_next_field($tokens));
		}

		array_shift($tokens); # closing paren

		return $fields;
	}


	function slice_until_next_field(&$tokens){

		$out = array();
		$stack = 0;

		while (count($tokens)){
			$next = $tokens[0];
			if ($next == '('){
				$stack++;
				$out[] = array_shift($tokens);
			}elseif ($next == ')'){
				if ($stack){
					$stack--;
					$out[] = array_shift($tokens);
				}else{
					return $out;
				}
			}elseif ($next == ','){
				if ($stack){
					$out[] = array_shift($tokens);
				}else{
					array_shift($tokens);
					return $out;
				}
			}else{
				$out[] = array_shift($tokens);
			}
		}

		return $out;
	}

	function parse_field($tokens){

		$_bare_types	= array('DATE', 'TIME', 'TIMESTAMP', 'DATETIME', 'YEAR', 'TINYBLOB', 'BLOB', 'MEDIUMBLOB', 'LONGBLOB');
		$_num_types	= array('TINYINT', 'SMALLINT', 'MEDIUMINT', 'INT', 'INTEGER', 'BIGINT', 'REAL', 'DOUBLE', 'FLOAT', 'DECIMAL', 'NUMERIC');

		$f = array(
			'_'	=> 'fields',
			'name'	=> shift_field_name($tokens),
			'type'	=> StrToUpper(array_shift($tokens)),
		);

		if (in_array($f['type'], $_bare_types)){

			# nothing more to read

		}elseif (in_array($f['type'], $_num_types)){

			# optional length (maybe with 2 parts
			# optional unsigned
			# optional zerofill

		}elseif ($f['type'] == 'BIT' || $f['type'] == 'BINARY'){

			# optional size

		}elseif ($f['type'] == 'VARBINARY'){

			# required size

		}

		$f['tokens'] = slice_until_next_field($tokens);

		return $f;
	}

	function parse_key($tokens, $type=null){

		return array(
			'_'		=> 'KEY',
			'type'		=> $type,
			'tokens'	=> $tokens,
		);
	}
