<?php
	$sql = file_get_contents('glitch_full.sql');

	var_export(lex_sql($sql));
	var_export(lex_sql("hello \nworld-- foo\nyeah"));



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

			# <regular identifier>
			# <key word>
			if (preg_match('![[:alpha:]][[:alnum:]_]*!A', $sql, $m, 0, $pos)){
				$tokens[] = substr($sql, $pos, strlen($m[0]));
				$pos += strlen($m[0]);
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
