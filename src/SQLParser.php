<?php

namespace iamcal;

class SQLParser{

	#
	# the main public interface is very simple
	#

	public $tokens = array();
	public $tables = array();
	public $source_map = array();

	public function parse($sql){

		// stashes tokens and source_map in $this
		$this->lex($sql);
		$ret = $this->walk($this->tokens, $sql, $this->source_map);

		$this->tables = $ret['tables'];
		return $this->tables;
	}

	#
	# lex and collapse tokens
	#
	public function lex($sql) {
		$this->source_map = $this->_lex($sql);
		$this->tokens = $this->_extract_tokens($sql, $this->source_map);
		return $this->tokens;
	}

	#
	# simple lexer based on http://www.contrib.andrew.cmu.edu/~shadow/sql/sql1992.txt
	#
	# returns an array of [position, len] tuples for each token

	private function _lex($sql){

		$pos = 0;
		$len = strlen($sql);

		$source_map = array();

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
				$source_map[] = [$pos, strlen($m[0])];
				$pos += strlen($m[0]);
				continue;
			}

			# backtick quoted field
			if (substr($sql, $pos, 1) == '`'){
				$p2 = strpos($sql, "`", $pos+1);
				if ($p2 === false){
					$pos = $len;
				}else{
					$source_map[] = [$pos, 1+$p2-$pos];
					$pos = $p2+1;
				}
				continue;
			}

			# <unsigned numeric literal>
			#	<unsigned integer> [ <period> [ <unsigned integer> ] ]
			#	<period> <unsigned integer>
			#	<unsigned integer> ::= <digit>...
			if (preg_match('!(\d+\.?\d*|\.\d+)!A', $sql, $m, 0, $pos)){
				$source_map[] = [$pos, strlen($m[0])];
				$pos += strlen($m[0]);
				continue;
			}

			# <approximate numeric literal> :: <mantissa> E <exponent>
			# <national character string literal>
			# <bit string literal>
			# <hex string literal>

			# <character string literal>
			if ($sql[$pos] == "'" || $sql[$pos] == '"'){
				$c = $pos+1;
				$q = $sql[$pos];
				while ($c < strlen($sql)){
					if ($sql[$c] == '\\'){
						$c += 2;
						continue;
					}
					if ($sql[$c] == $q){
						$slen = $c + 1 - $pos;
						$source_map[] = [$pos, $slen];
						$pos += $slen;
						break;
					}
					$c++;
				}
				continue;
			}

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
			$source_map[] = [$pos, 1];
			$pos++;
		}

		return $source_map;
	}


	function walk($tokens, $sql, $source_map){


		#
		# split into statements
		#

		$statements = array();
		$temp = array();
		$start = 0;
		for ($i = 0; $i < count($tokens); $i++) {
			$t = $tokens[$i];
			if ($t == ';'){
				if (count($temp)) {
					$statements[] = array(
						"tuples" => $temp,
						"sql" => substr($sql, $source_map[$start][0], $source_map[$i][0] - $source_map[$start][0] + $source_map[$i][1]),
					);
				}
				$temp = array();
				$start = $i + 1;
			}else{
				$temp[] = $t;
			}
		}
		if (count($temp)) {
			$statements[] = array(
				"tuples" => $temp,
				"sql" => substr($sql, $source_map[$start][0], $source_map[$i-1][0] - $source_map[$start][0] + $source_map[$i-1][1]),
			);
		}

		#
		# find CREATE TABLE statements
		#

		$tables = array();

		foreach ($statements as $stmt){
			$s = $stmt['tuples'];

			if (StrToUpper($s[0]) == 'CREATE TABLE'){

				$table = $this->parse_create_table($s, 1, count($s));
				$table['sql'] = $stmt['sql'];
				$tables[$table['name']] = $table;
			}

			if (StrToUpper($s[0]) == 'CREATE TEMPORARY TABLE'){

				$table = $this->parse_create_table($s, 1, count($s));
				$table['props']['temporary'] = true;
				$tables[$table['name']] = $table;
				$table['sql'] = $stmt['sql'];
			}

			if (isset($GLOBALS['_find_single_table']) && $GLOBALS['_find_single_table'] && count($tables)) return array(
				'tables' => $tables,
			);
		}

		return array(
			'tables' => $tables,
		);
	}


	function parse_create_table($tokens, $i, $num){

		if ($tokens[$i] == 'IF NOT EXISTS'){
			$i++;
		}


		#
		# name
		#

		$name = $this->decode_identifier($tokens[$i++]);


		#
		# CREATE TABLE x LIKE y
		#

		if ($this->next_tokens($tokens, $i, 'LIKE')){
			$i++;
			$old_name = $this->decode_identifier($tokens[$i++]);

			return array(
				'name'	=> $name,
				'like'	=> $old_name,
			);
		}


		#
		# create_definition
		#

		$fields = array();
		$indexes = array();

		if ($this->next_tokens($tokens, $i, '(')){
			$i++;
			$ret = $this->parse_create_definition($tokens, $i);
			$fields = $ret['fields'];
			$indexes = $ret['indexes'];
		}

		$props = $this->parse_table_props($tokens, $i);

		$table = array(
			'name'		=> $name,
			'fields'	=> $fields,
			'indexes'	=> $indexes,
			'props'		=> $props,
		);

		if ($i <= count($tokens)) $table['more'] = array_slice($tokens, $i);

		return $table;
	}


	function next_tokens($tokens, $i){

		$args = func_get_args();
		array_shift($args);
		array_shift($args);

		foreach ($args as $v){
			if ($i >= count($tokens) ) return false;
			if (StrToUpper($tokens[$i]) != $v) return false;
			$i++;
		}
		return true;
	}

	function parse_create_definition($tokens, &$i){

		$fields = array();
		$indexes = array();

		while ($tokens[$i] != ')'){

			$start = $i;
			$end = $this->find_next_field($tokens, $i);

			$this->parse_field_or_key($tokens, $start, $end, $fields, $indexes);
		}

		$i++;

		return array(
			'fields'	=> $fields,
			'indexes'	=> $indexes,
		);
	}

	function parse_field_or_key($tokens, $i, $max, &$fields, &$indexes){

		#
		# parse a single create_definition
		#

		$has_constraint = false;
		$constraint = null;


		#
		# constraints can come before a few different things
		#

		if ($tokens[$i] == 'CONSTRAINT'){

			$has_constraint = true;

			if ($tokens[$i+1] == 'PRIMARY KEY'
				|| $tokens[$i+1] == 'UNIQUE'
				|| $tokens[$i+1] == 'UNIQUE KEY'
				|| $tokens[$i+1] == 'UNIQUE INDEX'
				|| $tokens[$i+1] == 'FOREIGN KEY'){
				$i++;
			}else{
				$i++;
				$constraint = $tokens[$i++];
			}
		}


		switch ($tokens[$i]){

			#
			# named indexes
			#
			# INDEX		[index_name]	[index_type] (index_col_name,...) [index_option] ...
			# KEY		[index_name]	[index_type] (index_col_name,...) [index_option] ...
			# UNIQUE	[index_name]	[index_type] (index_col_name,...) [index_option] ...
			# UNIQUE INDEX	[index_name]	[index_type] (index_col_name,...) [index_option] ...
			# UNIQUE KEY	[index_name]	[index_type] (index_col_name,...) [index_option] ...
			#

			case 'INDEX':
			case 'KEY':
			case 'UNIQUE':
			case 'UNIQUE INDEX':
			case 'UNIQUE KEY':

				$index = array(
					'type' => 'INDEX',
				);

				if ($tokens[$i] == 'UNIQUE'	 ) $index['type'] = 'UNIQUE';
				if ($tokens[$i] == 'UNIQUE INDEX') $index['type'] = 'UNIQUE';
				if ($tokens[$i] == 'UNIQUE KEY'	 ) $index['type'] = 'UNIQUE';

				$i++;

				if ($tokens[$i] != '(' && $tokens[$i] != 'USING BTREE' && $tokens[$i] != 'USING HASH'){
					$index['name'] = $this->decode_identifier($tokens[$i++]);
				}

				$this->parse_index_type($tokens, $i, $max, $index);
				$this->parse_index_columns($tokens, $i, $index);
				$this->parse_index_options($tokens, $i, $max, $index);


				if ($i < $max) $index['more'] = array_slice($tokens, $i, $max-$i);
				$indexes[] = $index;
				return;


			#
			# PRIMARY KEY [index_type] (index_col_name,...) [index_option] ...
			#

			case 'PRIMARY KEY':

				$index = array(
					'type'	=> 'PRIMARY',
				);

				$i++;

				$this->parse_index_type($tokens, $i, $max, $index);
				$this->parse_index_columns($tokens, $i, $index);
				$this->parse_index_options($tokens, $i, $max, $index);

				if ($i < $max) $index['more'] = array_slice($tokens, $i, $max-$i);
				$indexes[] = $index;
				return;


			# FULLTEXT		[index_name] (index_col_name,...) [index_option] ...
			# FULLTEXT INDEX	[index_name] (index_col_name,...) [index_option] ...
			# FULLTEXT KEY		[index_name] (index_col_name,...) [index_option] ...
			# SPATIAL		[index_name] (index_col_name,...) [index_option] ...
			# SPATIAL INDEX		[index_name] (index_col_name,...) [index_option] ...
			# SPATIAL KEY		[index_name] (index_col_name,...) [index_option] ...

			case 'FULLTEXT':
			case 'FULLTEXT INDEX':
			case 'FULLTEXT KEY':
			case 'SPATIAL':
			case 'SPATIAL INDEX':
			case 'SPATIAL KEY':

				$index = array(
					'type' => 'FULLTEXT',
				);

				if ($tokens[$i] == 'SPATIAL'      ) $index['type'] = 'SPATIAL';
				if ($tokens[$i] == 'SPATIAL INDEX') $index['type'] = 'SPATIAL';
				if ($tokens[$i] == 'SPATIAL KEY'  ) $index['type'] = 'SPATIAL';

				$i++;

				if ($tokens[$i] != '('){
					$index['name'] = $this->decode_identifier($tokens[$i++]);
				}

				$this->parse_index_type($tokens, $i, $max, $index);
				$this->parse_index_columns($tokens, $i, $index);
				$this->parse_index_options($tokens, $i, $max, $index);

				if ($i < $max) $index['more'] = array_slice($tokens, $i, $max-$i);
				$indexes[] = $index;
				return;


			# older stuff

			case 'CHECK':

				$fields[] = array(
					'_'		=> 'CHECK',
					'tokens'	=> array_slice($tokens, $i, $max-$i),
				);
				return;
		}

		$fields[] = $this->parse_field($tokens, $i, $max);
	}

	function find_next_field($tokens, &$i){

		$stack = 0;

		while ($i <= count($tokens)){
			$next = $tokens[$i];
			if ($next == '('){
				$stack++;
				$i++;
			}elseif ($next == ')'){
				if ($stack){
					$stack--;
					$i++;
				}else{
					return $i;
				}
			}elseif ($next == ','){
				if ($stack){
					$i++;
				}else{
					$i++;
					return $i-1;
				}
			}else{
				$i++;
			}
		}

		return $i;
	}

	function parse_field($tokens, &$i, $max){

		$f = array(
			'name'	=> $this->decode_identifier($tokens[$i++]),
			'type'	=> StrToUpper($tokens[$i++]),
		);

		switch ($f['type']){

			# DATE
			case 'DATE':
			case 'TIME':
			case 'TIMESTAMP':
			case 'DATETIME':
			case 'YEAR':
			case 'TINYBLOB':
			case 'BLOB':
			case 'MEDIUMBLOB':
			case 'LONGBLOB':

				# nothing more to read
				break;


			# TINYINT[(length)] [UNSIGNED] [ZEROFILL]
			case 'TINYINT':
			case 'SMALLINT':
			case 'MEDIUMINT':
			case 'INT':
			case 'INTEGER':
			case 'BIGINT':

				$this->parse_field_length($tokens, $i, $max, $f);
				$this->parse_field_unsigned($tokens, $i, $max, $f);
				$this->parse_field_zerofill($tokens, $i, $max, $f);
				break;


			# REAL[(length,decimals)] [UNSIGNED] [ZEROFILL]
			case 'REAL':
			case 'DOUBLE':
			case 'FLOAT':

				$this->parse_field_length_decimals($tokens, $i, $max, $f);
				$this->parse_field_unsigned($tokens, $i, $max, $f);
				$this->parse_field_zerofill($tokens, $i, $max, $f);
				break;


			# DECIMAL[(length[,decimals])] [UNSIGNED] [ZEROFILL]
			case 'DECIMAL':
			case 'NUMERIC':

				$this->parse_field_length_decimals($tokens, $i, $max, $f);
				$this->parse_field_length($tokens, $i, $max, $f);
				$this->parse_field_unsigned($tokens, $i, $max, $f);
				$this->parse_field_zerofill($tokens, $i, $max, $f);
				break;


			# BIT[(length)]
			# BINARY[(length)]
			case 'BIT':
			case 'BINARY':

				$this->parse_field_length($tokens, $i, $max, $f);
				break;


			# VARBINARY(length)
			case 'VARBINARY':

				$this->parse_field_length($tokens, $i, $max, $f);
				break;

			# CHAR[(length)] [CHARACTER SET charset_name] [COLLATE collation_name]
			case 'CHAR':

				$this->parse_field_length($tokens, $i, $max, $f);
				$this->parse_field_charset($tokens, $i, $f);
				$this->parse_field_collate($tokens, $i, $f);
				break;

			# VARCHAR(length) [CHARACTER SET charset_name] [COLLATE collation_name]
			case 'VARCHAR':

				$this->parse_field_length($tokens, $i, $max, $f);
				$this->parse_field_charset($tokens, $i, $f);
				$this->parse_field_collate($tokens, $i, $f);
				break;

			# TINYTEXT   [BINARY] [CHARACTER SET charset_name] [COLLATE collation_name]
			# TEXT       [BINARY] [CHARACTER SET charset_name] [COLLATE collation_name]
			# MEDIUMTEXT [BINARY] [CHARACTER SET charset_name] [COLLATE collation_name]
			# LONGTEXT   [BINARY] [CHARACTER SET charset_name] [COLLATE collation_name]
			case 'TINYTEXT':
			case 'TEXT':
			case 'MEDIUMTEXT':
			case 'LONGTEXT':

				# binary
				$this->parse_field_charset($tokens, $i, $f);
				$this->parse_field_collate($tokens, $i, $f);
				break;

			# ENUM(value1,value2,value3,...) [CHARACTER SET charset_name] [COLLATE collation_name]
			# SET (value1,value2,value3,...) [CHARACTER SET charset_name] [COLLATE collation_name]
			case 'ENUM':
			case 'SET':

				$f['values'] = $this->parse_value_list($tokens, $i);
				$this->parse_field_charset($tokens, $i, $f);
				$this->parse_field_collate($tokens, $i, $f);
				break;

			default:
				die("Unsupported field type: {$f['type']}");
		}


		# [NOT NULL | NULL]
		if ($i <= $max && StrToUpper($tokens[$i]) == 'NOT NULL'){
			$f['null'] = false;
			$i++;
		}
		if ($i <= $max && StrToUpper($tokens[$i]) == 'NULL'){
			$f['null'] = true;
			$i++;
		}

		# [DEFAULT default_value]
		if ($i+1 <= $max && StrToUpper($tokens[$i]) == 'DEFAULT'){
			$i++;
			$f['default'] = $this->decode_value($tokens[$i++]);
		}

		# [AUTO_INCREMENT]
		if ($i <= $max && StrToUpper($tokens[$i]) == 'AUTO_INCREMENT'){
			$f['auto_increment'] = true;
			$i++;
		}

		# [UNIQUE [KEY] | [PRIMARY] KEY]
		# [COMMENT 'string']
		# [COLUMN_FORMAT {FIXED|DYNAMIC|DEFAULT}]
		# [STORAGE {DISK|MEMORY|DEFAULT}]
		# [reference_definition]

		if ($i < $max) $f['more'] = array_slice($tokens, $i, $max-$i);

		return $f;
	}

	function parse_table_props($tokens, &$i){

		$alt_names = array(
			'CHARACTER SET'		=> 'CHARSET',
			'DEFAULT CHARACTER SET'	=> 'CHARSET',
			'DEFAULT CHARSET'	=> 'CHARSET',
			'DEFAULT COLLATE'	=> 'COLLATE',
		);

		$props = array();

		while ($i < count($tokens)){

		switch (StrToUpper($tokens[$i])){
			case 'ENGINE':
			case 'AUTO_INCREMENT':
			case 'AVG_ROW_LENGTH':
			case 'CHECKSUM':
			case 'COMMENT':
			case 'CONNECTION':
			case 'DELAY_KEY_WRITE':
			case 'INSERT_METHOD':
			case 'KEY_BLOCK_SIZE':
			case 'MAX_ROWS':
			case 'MIN_ROWS':
			case 'PACK_KEYS':
			case 'PASSWORD':
			case 'ROW_FORMAT':
			case 'COLLATE':
			case 'CHARSET':
			case 'DATA DIRECTORY':
			case 'INDEX DIRECTORY':
				$prop = StrToUpper($tokens[$i++]);
				if (isset($tokens[$i]) && $tokens[$i] == '=') $i++;
				$props[$prop] = $tokens[$i++];
				if (isset($tokens[$i]) && $tokens[$i] == ',') $i++;
				break;

			case 'CHARACTER SET':
			case 'DEFAULT COLLATE':
			case 'DEFAULT CHARACTER SET':
			case 'DEFAULT CHARSET':
				$prop = $alt_names[StrToUpper($tokens[$i++])];
				if (isset($tokens[$i]) && $tokens[$i] == '=') $i++;
				$props[$prop] = $tokens[$i++];
				if (isset($tokens[$i]) && $tokens[$i] == ',') $i++;
				break;

			default:
				break 2;
		}
		}

		return $props;
	}


	# Given the source map, extract the tokens from the original sql,
	# Along the way, simplify parsing by merging certain tokens when
	# they occur next to each other. MySQL treats these productions
	# equally: 'UNIQUE|UNIQUE INDEX|UNIQUE KEY' and if they are
	# all always a single token it makes parsing easier.

	function _extract_tokens($sql, &$source_map){
		$lists = array(
			'FULLTEXT INDEX',
			'FULLTEXT KEY',
			'SPATIAL INDEX',
			'SPATIAL KEY',
			'FOREIGN KEY',
			'USING BTREE',
			'USING HASH',
			'PRIMARY KEY',
			'UNIQUE INDEX',
			'UNIQUE KEY',
			'CREATE TABLE',
			'CREATE TEMPORARY TABLE',
			'DATA DIRECTORY',
			'INDEX DIRECTORY',
			'DEFAULT CHARACTER SET',
			'CHARACTER SET',
			'DEFAULT CHARSET',
			'DEFAULT COLLATE',
			'IF NOT EXISTS',
			'NOT NULL',
			'WITH PARSER',
		);

		$singles = array(
			'NULL',
			'CONSTRAINT',
			'INDEX',
			'KEY',
			'UNIQUE',
		);


		$maps = array();
		foreach ($lists as $l){
			$a = explode(' ', $l);
			$maps[$a[0]][] = $a;
		}
		$smap = array();
		foreach ($singles as $s) $smap[$s] = 1;

		$out = array();
		$out_map = [];

		$i = 0;
		$len = count($source_map);
		while ($i < $len){
			$token = substr($sql, $source_map[$i][0], $source_map[$i][1]);
			$tokenUpper = StrToUpper($token);
			if (isset($maps[$tokenUpper]) && is_array($maps[$tokenUpper])){
				$found = false;
				foreach ($maps[$tokenUpper] as $list){
					$fail = false;
					foreach ($list as $k => $v){
						$next = StrToUpper(substr($sql, $source_map[$k+$i][0], $source_map[$k+$i][1]));
						if ($v != $next){
							$fail = true;
							break;
						}
					}
					if (!$fail){
						$out[] = implode(' ', $list);

						# Extend the length of the first token to include everything
						# up through the last in the sequence.
						$j = $i + count($list) - 1;
						$out_map[] = array($source_map[$i][0], ($source_map[$j][0] - $source_map[$i][0]) + $source_map[$j][1]);

						$i = $j + 1;
						$found = true;
						break;
					}
				}
				if ($found) continue;
			}
			if (isset($smap[$tokenUpper])){
				$out[] = $tokenUpper;
				$out_map[]= $source_map[$i];
				$i++;
				continue;
			}
			$out[] = $token;
			$out_map[]= $source_map[$i];
			$i++;
		}

		$source_map = $out_map;
		return $out;
	}

	function parse_index_type($tokens, &$i, $max, &$index){
		if ($i <= $max){
			if ($tokens[$i] == 'USING BTREE'){ $index['mode'] = 'btree'; $i++; }
			if ($tokens[$i] == 'USING HASH' ){ $index['mode'] = 'hash'; $i++; }
		}
	}

	function parse_index_columns(&$tokens, &$index){

		# col_name [(length)] [ASC | DESC]

		if ($tokens[0] != '(') return;
		array_shift($tokens);

		while (true){

			$col = array(
				'name' => $this->decode_identifier(array_shift($tokens)),
			);

			if ($tokens[0] == '(' && $tokens[2] == ')'){
				$col['length'] = $tokens[1];
				array_shift($tokens);
				array_shift($tokens);
				array_shift($tokens);
			}

			if (StrToUpper($tokens[0]) == 'ASC'){
				$col['direction'] = 'asc';
				array_shift($tokens);
			}elseif (StrToUpper($tokens[0]) == 'DESC'){
				$col['direction'] = 'desc';
				array_shift($tokens);
			}

			$index['cols'][] = $col;

			if ($tokens[0] == ')'){
				array_shift($tokens);
				return;
			}

			if ($tokens[0] == ','){
				array_shift($tokens);
				continue;
			}

			# hmm, an unexpected token
			return;
		}
	}

	function parse_index_options($tokens, &$i, $max, &$index){

		# index_option:
		#    KEY_BLOCK_SIZE [=] value
		#  | index_type
		#  | WITH PARSER parser_name

		if ($i <= $max){
			if ($tokens[$i] == 'KEY_BLOCK_SIZE'){
				$i++;
				if ($tokens[$i] == '=') $i++;
				$index['key_block_size'] = $tokens[$i++];
			}
		}

		$this->parse_index_type($tokens, $i, $max, $index);

		if ($i <= $max){
			if ($tokens[$i] == 'WITH PARSER'){
				$i++;
				$index['parser'] = $tokens[$i++];
			}
		}
	}


	#
	# helper functions for parsing bits of field definitions
	#

	function parse_field_length($tokens, &$i, $max, &$f){
		if ($i+2 <= $max){
			if ($tokens[$i] == '(' && $tokens[$i+2] == ')'){
				$f['length'] = $tokens[$i+1];
				$i += 3;
			}
		}
	}

	function parse_field_length_decimals($tokens, &$i, $max, &$f){
		if ($i+4 <= $max){
			if ($tokens[$i] == '(' && $tokens[$i+2] == ',' && $tokens[$i+4] == ')'){
				$f['length'] = $tokens[$i+1];
				$f['decimals'] = $tokens[$i+3];
				$i += 5;
			}
		}
	}

	function parse_field_unsigned($tokens, &$i, $max, &$f){
		if ($i <= $max){
			if (StrToUpper($tokens[$i]) == 'UNSIGNED'){
				$f['unsigned'] = true;
				$i++;
			}
		}
	}

	function parse_field_zerofill($tokens, &$i, $max, &$f){
		if ($i <= $max){
			if (StrToUpper($tokens[$i]) == 'ZEROFILL'){
				$f['zerofill'] = true;
				$i++;
			}
		}
	}


# EEEEEEEEEEEEEEEEEEEEEEEEEEEEEE FIX FUNCTIONS FROM HERE DOWNWARDS

	function parse_field_charset(&$tokens, &$f){
		if (count($tokens) >= 1){
			if (StrToUpper($tokens[0]) == 'CHARACTER SET'){
				$f['character_set'] = $tokens[1];
				array_shift($tokens);
				array_shift($tokens);
			}
		}
	}

	function parse_field_collate(&$tokens, &$f){
		if (count($tokens) >= 1){
			if (StrToUpper($tokens[0]) == 'COLLATE'){
				$f['collation'] = $tokens[1];
				array_shift($tokens);
				array_shift($tokens);
			}
		}
	}

	function parse_value_list(&$tokens){
		if ($tokens[0] != '(') return null;
		array_shift($tokens);

		$values = array();
		while (count($tokens)){

			if ($tokens[0] == ')'){
				array_shift($tokens);
				return $values;
			}

			$values[] = $this->decode_value(array_shift($tokens));

			if ($tokens[0] == ')'){
				array_shift($tokens);
				return $values;
			}

			if ($tokens[0] == ','){
				array_shift($tokens);
			}else{
				# error
				return $values;
			}
		}
	}

	function decode_identifier($token){
		if ($token[0] == '`'){
			return substr($token, 1, -1);
		}
		return $token;
	}

	function decode_value($token){

		#
		# decode strings
		#

		if ($token[0] == "'" || $token[0] == '"'){
			$map = array(
				'n'	=> "\n",
				'r'	=> "\r",
				't'	=> "\t",
			);
			$out = '';
			for ($i=1; $i<strlen($token)-1; $i++){
				if ($token[$i] == '\\'){
					if ($map[$token[$i+1]]){
						$out .= $map[$token[$i+1]];
					}else{
						$out .= $token[$i+1];
					}
					$i++;
				}else{
					$out .= $token[$i];
				}
			}
			return $out;
		}

		return $token;
	}
}


