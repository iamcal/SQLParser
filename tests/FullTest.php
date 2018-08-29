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
        return [
            [
                "CREATE TABLE table_name (a INT);\n"
                . "-- ignored comment\n\n"
                . "CREATE TABLE t2 (b VARCHAR)\n\n;\n",
                [
                    'table_name' => [
                        'name' => 'table_name',
                        'database' => null,
                        'fields' => [
                            [
                                'name' => 'a',
                                'type' => 'INT',
                            ],
                        ],
                        'indexes' => [],
                        'props' => [],
                        'more' => [],
                        'sql' => 'CREATE TABLE table_name (a INT);',
                    ],
                    't2' => [
                        'name' => 't2',
                        'database' => null,
                        'fields' => [
                            [
                                'name' => 'b',
                                'type' => 'VARCHAR',
                            ],
                        ],
                        'indexes' => [],
                        'props' => [],
                        'more' => [],
                        'sql' => "CREATE TABLE t2 (b VARCHAR)\n\n;",
                    ],

                ]
            ],
            [
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
                [
                    'DbName.TableName' => [
                        'name' => 'TableName',
                        'database' => 'DbName',
                        'fields' => [
                            [
                                'name' => 'id',
                                'type' => 'BIGINT',
                                'unsigned' => true,
                                'null' => false,
                                'auto_increment' => true,
                            ],
                            [
                                'name' => 'errcnt',
                                'type' => 'INT',
                                'length' => '10',
                                'unsigned' => true,
                                'null' => false,
                                'default' => '0',
                            ],
                            [
                                'name' => 'user_id',
                                'type' => 'INT',
                                'unsigned' => true,
                                'null' => false,
                            ],
                            [
                                'name' => 'photo_id',
                                'type' => 'INT',
                                'unsigned' => true,
                                'null' => false,
                            ],
                            [
                                'name' => 'place_id',
                                'type' => 'INT',
                                'unsigned' => true,
                                'null' => false,
                            ],
                            [
                                'name' => 'next_processing_time',
                                'type' => 'TIMESTAMP',
                                'null' => false,
                                'default' => 'CURRENT_TIMESTAMP',
                            ],
                            [
                                'name' => 'created',
                                'type' => 'TIMESTAMP',
                                'null' => false,
                                'default' => 'CURRENT_TIMESTAMP',
                            ],
                        ],
                        'indexes' => [
                            [
                                'type' => 'PRIMARY',
                                'cols' =>
                                    [
                                        [
                                            'name' => 'id',
                                        ],
                                    ],
                            ],
                            [
                                'type' => 'INDEX',
                                'cols' =>
                                    [
                                        [
                                            'name' => 'place_id',
                                        ],
                                        [
                                            'name' => 'next_processing_time',
                                        ],
                                    ],
                            ],
                            [
                                'type' => 'UNIQUE',
                                'cols' =>
                                    [
                                        [
                                            'name' => 'user_id',
                                        ],
                                        [
                                            'name' => 'place_id',
                                        ],
                                        [
                                            'name' => 'photo_id',
                                        ],
                                    ],
                            ],
                        ],
                        'props' => [],
                        'more' => [],
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
                    ],

                ]
            ]

        ];
    }

}
