# SchemaComp - Compare two MySQL schemas in PHP

This experimental project aims to compare the schema of two MySQL databases by looking at 
their `CREATE TABLE` syntax, then figure out what needs to be done to migrate one to the 
other.

This is based on the system used at b3ta, Flickr and then Tiny Speck to check the differences
between production and development databases and between shard instances. The original system 
just showed a diff (see [SchemaDiff](https://github.com/iamcal/SchemaDiff)), but that was a bit
of a pain.

The SQL parsing is based on [php-sql-parser](http://code.google.com/p/php-sql-parser/), which is 
included here.

