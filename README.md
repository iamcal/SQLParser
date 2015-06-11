# SQLParser - Parse MySQL schemas in PHP, fast

This library was created to parse multiple `CREATE TABLE` schemas and compare them, so
figure out what needs to be done to migrate one to the other.

This is based on the system used at b3ta, Flickr and then Tiny Speck to check the differences
between production and development databases and between shard instances. The original system 
just showed a diff (see [SchemaDiff](https://github.com/iamcal/SchemaDiff)), but that was a bit
of a pain.


## Performance

My test target is an 88K SQL file containing 114 tables from Glitch's main database.

The first version, using [php-sql-parser](http://code.google.com/p/php-sql-parser/), took over 60
seconds just to lex the input. This was obviously not a great option.

The current implementation uses a hand-written lexer which takes around 140ms to lex the same
input and imposes less odd restrictions. This seems to be the way to go.

Create table syntax: http://dev.mysql.com/doc/refman/5.1/en/create-table.html


## Unsupported features

MySQL table definitions have a *lot* of options, so some things just aren't supported. They include:

* `UNION` table properties
* `TABLESPACE` table properties
* table partitions
* Spatial field types

If you need support for one of these features, open an issue or (better) send a pull request with tests.
