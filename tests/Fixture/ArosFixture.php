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
class ArosFixture extends TestFixture
{

    /**
     * records property
     *
     * @var array
     */
    public $records = [
        ['parent_id' => null, 'model' => null, 'foreign_key' => null, 'alias' => 'ROOT', 'lft' => 1, 'rght' => 8],
        ['parent_id' => '1', 'model' => 'Group', 'foreign_key' => '1', 'alias' => 'admins', 'lft' => 2, 'rght' => 7],
        ['parent_id' => '2', 'model' => 'AuthUser', 'foreign_key' => '1', 'alias' => 'Gandalf', 'lft' => 3, 'rght' => 4],
        ['parent_id' => '2', 'model' => 'AuthUser', 'foreign_key' => '2', 'alias' => 'Elrond', 'lft' => 5, 'rght' => 6],
    ];
}
