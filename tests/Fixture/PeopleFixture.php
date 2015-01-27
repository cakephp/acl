<?php
/**
 * CakePHP(tm) Tests <http://book.cakephp.org/2.0/en/development/testing.html>
 * Copyright (c) Cake Software Foundation, Inc. (http://cakefoundation.org)
 *
 * Licensed under The MIT License
 * For full copyright and license information, please see the LICENSE.txt
 * Redistributions of files must retain the above copyright notice
 *
 * @copyright     Copyright (c) Cake Software Foundation, Inc. (http://cakefoundation.org)
 * @link          http://book.cakephp.org/2.0/en/development/testing.html CakePHP(tm) Tests
 * @license       http://www.opensource.org/licenses/mit-license.php MIT License
 */
namespace Acl\Test\Fixture;

use Cake\TestSuite\Fixture\TestFixture;

/**
 * Class PeopleFixture
 */
class PeopleFixture extends TestFixture
{

    /**
     * fields property
     *
     * @var array
     */
    public $fields = [
        'id' => ['type' => 'integer', 'null' => false],
        'name' => ['type' => 'string', 'null' => false, 'length' => 32],
        'mother_id' => ['type' => 'integer', 'null' => false],
        'father_id' => ['type' => 'integer', 'null' => false],
        '_constraints' => [
            'PRIMARY' => ['type' => 'primary', 'columns' => ['id']],
        ],
        '_indexes' => [
            'mother_id' => ['type' => 'index', 'columns' => ['mother_id', 'father_id']]
        ]
    ];

    /**
     * records property
     *
     * @var array
     */
    public $records = [
        ['name' => 'person', 'mother_id' => 2, 'father_id' => 3],
        ['name' => 'mother', 'mother_id' => 4, 'father_id' => 5],
        ['name' => 'father', 'mother_id' => 6, 'father_id' => 7],
        ['name' => 'mother - grand mother', 'mother_id' => 0, 'father_id' => 0],
        ['name' => 'mother - grand father', 'mother_id' => 0, 'father_id' => 0],
        ['name' => 'father - grand mother', 'mother_id' => 0, 'father_id' => 0],
        ['name' => 'father - grand father', 'mother_id' => 0, 'father_id' => 0]
    ];
}
