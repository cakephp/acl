<?php

/**
 * CakePHP(tm) Tests <https://book.cakephp.org/2.0/en/development/testing.html>
 * Copyright (c) Cake Software Foundation, Inc. (https://cakefoundation.org)
 *
 * Licensed under The MIT License
 * For full copyright and license information, please see the LICENSE.txt
 * Redistributions of files must retain the above copyright notice
 *
 * @copyright     Copyright (c) Cake Software Foundation, Inc. (https://cakefoundation.org)
 * @link          https://book.cakephp.org/2.0/en/development/testing.html CakePHP(tm) Tests
 * @license       https://www.opensource.org/licenses/mit-license.php MIT License
 */

namespace Acl\Test\Fixture;

use Cake\TestSuite\Fixture\TestFixture;

/**
 * Short description for class.
 *
 */
class AcosFixture extends TestFixture
{

    /**
     * records property
     *
     * @var array
     */
    public $records = [
        ['parent_id' => null, 'model' => null, 'foreign_key' => null, 'alias' => 'ROOT', 'lft' => 1, 'rght' => 24],
        ['parent_id' => 1, 'model' => null, 'foreign_key' => null, 'alias' => 'Controller1', 'lft' => 2, 'rght' => 9],
        ['parent_id' => 2, 'model' => null, 'foreign_key' => null, 'alias' => 'action1', 'lft' => 3, 'rght' => 6],
        ['parent_id' => 3, 'model' => null, 'foreign_key' => null, 'alias' => 'record1', 'lft' => 4, 'rght' => 5],
        ['parent_id' => 2, 'model' => null, 'foreign_key' => null, 'alias' => 'action2', 'lft' => 7, 'rght' => 8],
        ['parent_id' => 1, 'model' => null, 'foreign_key' => null, 'alias' => 'Controller2', 'lft' => 10, 'rght' => 17],
        ['parent_id' => 6, 'model' => null, 'foreign_key' => null, 'alias' => 'action1', 'lft' => 11, 'rght' => 14],
        ['parent_id' => 7, 'model' => null, 'foreign_key' => null, 'alias' => 'record1', 'lft' => 12, 'rght' => 13],
        ['parent_id' => 6, 'model' => null, 'foreign_key' => null, 'alias' => 'action2', 'lft' => 15, 'rght' => 16],
        ['parent_id' => 1, 'model' => null, 'foreign_key' => null, 'alias' => 'Users', 'lft' => 18, 'rght' => 23],
        ['parent_id' => 9, 'model' => null, 'foreign_key' => null, 'alias' => 'Users', 'lft' => 19, 'rght' => 22],
        ['parent_id' => 10, 'model' => null, 'foreign_key' => null, 'alias' => 'view', 'lft' => 20, 'rght' => 21],
    ];
}
