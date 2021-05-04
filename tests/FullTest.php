<?php

use PHPUnit\Framework\TestCase;

final class FullTest extends TestCase
{
    /**
     * @param $sql
     * @param $expected
     * @dataProvider parseProvider
     */
    public function testBasicCases($sql, $expected)
    {
        $obj = new iamcal\SQLParser();
        $tables = $obj->parse($sql);

        $this->assertEquals($expected, $tables);
    }

    public function parseProvider()
    {
        return array(
            array(
                "CREATE TABLE table_name (a INT);\n"
                . "-- ignored comment\n\n"
                . "CREATE TABLE t2 (b VARCHAR)\n\n;\n",
                array(
                    'table_name' => array(
                        'name' => 'table_name',
                        'database' => null,
                        'fields' => array(
                            array(
                                'name' => 'a',
                                'type' => 'INT',
                            ),
                        ),
                        'indexes' => array(),
                        'props' => array(),
                        'more' => array(),
                        'sql' => 'CREATE TABLE table_name (a INT);',
                    ),
                    't2' => array(
                        'name' => 't2',
                        'database' => null,
                        'fields' => array(
                            array(
                                'name' => 'b',
                                'type' => 'VARCHAR',
                            ),
                        ),
                        'indexes' => array(),
                        'props' => array(),
                        'more' => array(),
                        'sql' => "CREATE TABLE t2 (b VARCHAR)\n\n;",
                    ),

                )
            ),
            array(
                'CREATE TABLE DbName.TableName ( 
                    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT, 
                    errcnt INT(10) UNSIGNED NOT NULL DEFAULT \'0\', 
                    user_id INT UNSIGNED NOT NULL, 
                    photo_id INT UNSIGNED NOT NULL, 
                    place_id INT UNSIGNED NOT NULL, 
                    next_processing_time TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP, 
                    created TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP, 
                    PRIMARY KEY (id), 
                    KEY (place_id, next_processing_time), 
                    UNIQUE KEY (user_id, place_id, photo_id) 
                );',
                array(
                    'DbName.TableName' => array(
                        'name' => 'TableName',
                        'database' => 'DbName',
                        'fields' => array(
                            array(
                                'name' => 'id',
                                'type' => 'BIGINT',
                                'unsigned' => true,
                                'null' => false,
                                'auto_increment' => true,
                            ),
                            array(
                                'name' => 'errcnt',
                                'type' => 'INT',
                                'length' => '10',
                                'unsigned' => true,
                                'null' => false,
                                'default' => '0',
                            ),
                            array(
                                'name' => 'user_id',
                                'type' => 'INT',
                                'unsigned' => true,
                                'null' => false,
                            ),
                            array(
                                'name' => 'photo_id',
                                'type' => 'INT',
                                'unsigned' => true,
                                'null' => false,
                            ),
                            array(
                                'name' => 'place_id',
                                'type' => 'INT',
                                'unsigned' => true,
                                'null' => false,
                            ),
                            array(
                                'name' => 'next_processing_time',
                                'type' => 'TIMESTAMP',
                                'null' => false,
                                'default' => 'CURRENT_TIMESTAMP',
                            ),
                            array(
                                'name' => 'created',
                                'type' => 'TIMESTAMP',
                                'null' => false,
                                'default' => 'CURRENT_TIMESTAMP',
                            ),
                        ),
                        'indexes' => array(
                            array(
                                'type' => 'PRIMARY',
                                'cols' =>
                                    array(
                                        array(
                                            'name' => 'id',
                                        ),
                                    ),
                            ),
                            array(
                                'type' => 'INDEX',
                                'cols' =>
                                    array(
                                        array(
                                            'name' => 'place_id',
                                        ),
                                        array(
                                            'name' => 'next_processing_time',
                                        ),
                                    ),
                            ),
                            array(
                                'type' => 'UNIQUE',
                                'cols' =>
                                    array(
                                        array(
                                            'name' => 'user_id',
                                        ),
                                        array(
                                            'name' => 'place_id',
                                        ),
                                        array(
                                            'name' => 'photo_id',
                                        ),
                                    ),
                            ),
                        ),
                        'props' => array(),
                        'more' => array(),
                        'sql' => 'CREATE TABLE DbName.TableName ( 
                    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT, 
                    errcnt INT(10) UNSIGNED NOT NULL DEFAULT \'0\', 
                    user_id INT UNSIGNED NOT NULL, 
                    photo_id INT UNSIGNED NOT NULL, 
                    place_id INT UNSIGNED NOT NULL, 
                    next_processing_time TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP, 
                    created TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP, 
                    PRIMARY KEY (id), 
                    KEY (place_id, next_processing_time), 
                    UNIQUE KEY (user_id, place_id, photo_id) 
                );',
                    ),

                )
            )

        );
    }

}
