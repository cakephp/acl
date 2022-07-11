<?php
declare(strict_types=1);

/**
 * Abstract schema for CakePHP tests.
 *
 * This format resembles the existing fixture schema
 * and is converted to SQL via the Schema generation
 * features of the Database package.
 */

/**
 * Load CakePHP core test schema
 */
$core = include './vendor/cakephp/cakephp/tests/schema.php';

return array_merge($core, [
    [
        'table' => 'aros',
        'columns' => [
            'id' => [
                'type' => 'integer'
            ],
            'parent_id' => [
                'type' => 'integer',
                'length' => 10,
                'null' => true
            ],
            'model' => [
                'type' => 'string',
                'null' => true
            ],
            'foreign_key' => [
                'type' => 'integer',
                'length' => 10,
                'null' => true
            ],
            'alias' => [
                'type' => 'string',
                'default' => ''
            ],
            'lft' => [
                'type' => 'integer',
                'length' => 10,
                'null' => true
            ],
            'rght' => [
                'type' => 'integer',
                'length' => 10,
                'null' => true
            ]
        ],
        'constraints' => [
            'primary' =>
                [
                    'type' => 'primary',
                    'columns' => ['id']
                ]
        ]
    ],
    [
        'table' => 'acos',
        'columns' => [
            'id' => [
                'type' => 'integer'
            ],
            'parent_id' => [
                'type' => 'integer',
                'length' => 10,
                'null' => true
            ],
            'model' => [
                'type' => 'string',
                'null' => true
            ],
            'foreign_key' => [
                'type' => 'integer',
                'length' => 10,
                'null' => true
            ],
            'alias' => [
                'type' => 'string',
                'default' => ''
            ],
            'lft' => [
                'type' => 'integer',
                'length' => 10,
                'null' => true
            ],
            'rght' => [
                'type' => 'integer',
                'length' => 10,
                'null' => true
            ]
        ],
        'constraints' => [
            'primary' =>
                [
                    'type' => 'primary',
                    'columns' => ['id']
                ]
        ]
    ],
    [
        'table' => 'aros_acos',
        'columns' => [
            'id' => [
                'type' => 'integer'
            ],
            'aro_id' => [
                'type' => 'integer',
                'length' => 10,
                'null' => false
            ],
            'aco_id' => [
                'type' => 'integer',
                'length' => 10,
                'null' => false
            ],
            '_create' => [
                'type' => 'string',
                'length' => 2,
                'default' => 0
            ],
            '_read' => [
                'type' => 'string',
                'length' => 2,
                'default' => 0
            ],
            '_update' => [
                'type' => 'string',
                'length' => 2,
                'default' => 0
            ],
            '_delete' => [
                'type' => 'string',
                'length' => 2,
                'default' => 0
            ],
        ],
        'constraints' => [
            'primary' =>
                [
                    'type' => 'primary',
                    'columns' => ['id']
                ]
        ]
    ],
    [
        'table' => 'aco_actions',
        'columns' => [
            'id' => [
                'type' => 'integer'
            ],
            'parent_id' => [
                'type' => 'integer',
                'length' => 10,
                'null' => true
            ],
            'model' => [
                'type' => 'string',
                'null' => true
            ],
            'foreign_key' => [
                'type' => 'integer',
                'length' => 10,
                'null' => true
            ],
            'alias' => [
                'type' => 'string',
                'default' => ''
            ],
            'lft' => [
                'type' => 'integer',
                'length' => 10,
                'null' => true
            ],
            'rght' => [
                'type' => 'integer',
                'length' => 10,
                'null' => true
            ]
        ],
        'constraints' => [
            'primary' =>
                ['type' => 'primary',
                    'columns' => ['id']
                ]
        ]
    ],
    [
        'table' => 'aco_twos',
        'columns' => [
            'id' => [
                'type' => 'integer'
            ],
            'parent_id' => [
                'type' => 'integer',
                'length' => 10,
                'null' => true
            ],
            'model' => [
                'type' => 'string',
                'null' => true
            ],
            'foreign_key' => [
                'type' => 'integer',
                'length' => 10,
                'null' => true],
            'alias' => [
                'type' => 'string',
                'default' => ''
            ],
            'lft' => [
                'type' => 'integer',
                'length' => 10,
                'null' => true
            ],
            'rght' => [
                'type' => 'integer',
                'length' => 10,
                'null' => true
            ],
        ],
        'constraints' => [
            'primary' => [
                'type' => 'primary',
                'columns' => [
                    'id'
                ]
            ]
        ],
    ],
    [
        'table' => 'aro_twos',
        'columns' => [
            'id' => [
                'type' => 'integer'
            ],
            'parent_id' => [
                'type' => 'integer',
                'length' => 10,
                'null' => true
            ],
            'model' => [
                'type' => 'string',
                'null' => true
            ],
            'foreign_key' => [
                'type' => 'integer',
                'length' => 10,
                'null' => true
            ],
            'alias' => [
                'type' => 'string',
                'default' => ''
            ],
            'lft' => [
                'type' => 'integer',
                'length' => 10,
                'null' => true
            ],
            'rght' => [
                'type' => 'integer',
                'length' => 10,
                'null' => true
            ],
        ],
        'constraints' => [
            'primary' => [
                'type' => 'primary',
                'columns' => [
                    'id'
                ]
            ]
        ],
    ],
    [
        'table' => 'aros_aco_twos',
        'columns' => [
            'id' => [
                'type' => 'integer'
            ],
            'aro_id' => [
                'type' => 'integer',
                'length' => 10,
                'null' => false
            ],
            'aco_id' => [
                'type' => 'integer',
                'length' => 10,
                'null' => false
            ],
            '_create' => [
                'type' => 'string',
                'length' => 2,
                'default' => 0
            ],
            '_read' => [
                'type' => 'string',
                'length' => 2,
                'default' => 0
            ],
            '_update' => [
                'type' => 'string',
                'length' => 2,
                'default' => 0
            ],
            '_delete' => [
                'type' => 'string',
                'length' => 2,
                'default' => 0
            ],
        ],
        'constraints' => [
            'primary' => [
                'type' => 'primary',
                'columns' => ['id']
            ]
        ],
    ],
    [
        'table' => 'people',
        'columns' => [
            'id' => [
                'type' => 'integer',
                'null' => false
            ],
            'name' => [
                'type' => 'string',
                'null' => false,
                'length' => 32
            ],
            'mother_id' => [
                'type' => 'integer',
                'null' => false
            ],
            'father_id' => [
                'type' => 'integer',
                'null' => false
            ],
        ],
        'constraints' => [
            'PRIMARY' => [
                'type' => 'primary',
                'columns' => [
                    'id'
                ]
            ],
        ],
    ],
]);
