<?php
declare(strict_types=1);

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

namespace Acl\Test\TestCase\Model\Table;

use Acl\Adapter\DbAcl;
use Acl\Model\Table\AcoActionsTable;
use Acl\Model\Table\AcosTable;
use Acl\Model\Table\ArosTable;
use Acl\Model\Table\PermissionsTable;
use Cake\Core\App;
use Cake\Core\Configure;
use Cake\ORM\Entity;
use Cake\ORM\TableRegistry;
use Cake\TestSuite\TestCase;

/**
 * Aro Test Wrapper
 */
class DbAroTest extends ArosTable
{
    /**
     * initialize
     *
     * @param array $config Configuration array
     * @return void
     */
    public function initialize(array $config): void
    {
        parent::initialize($config);
        $this->setAlias('DbAroTest');
        $this->associations()->removeAll();
        $this->belongsToMany('DbAcoTest', [
            'through' => __NAMESPACE__ . '\DbPermissionTest',
            'className' => __NAMESPACE__ . '\DbAcoTest',
            'targetForeignKey' => 'id',
            'foreignKey' => 'aro_id',
        ]);
    }
}

/**
 * Aco Test Wrapper
 */
class DbAcoTest extends AcosTable
{
    /**
     * initialize
     *
     * @param array $config Configuration array
     * @return void
     */
    public function initialize(array $config): void
    {
        parent::initialize($config);
        $this->setAlias('DbAcoTest');
        $this->associations()->removeAll();
        $this->belongsToMany('DbAroTest', [
            'through' => __NAMESPACE__ . '\DbPermissionTest',
            'className' => __NAMESPACE__ . '\DbAroTest',
            'targetForeignKey' => 'id',
            'foreignKey' => 'aco_id',
        ]);
    }
}

/**
 * Permission Test Wrapper
 */
class DbPermissionTest extends PermissionsTable
{
    /**
     * initialize
     *
     * @param array $config Configuration array
     * @return void
     */
    public function initialize(array $config): void
    {
        parent::initialize($config);
        $this->setAlias('DbPermissionTest');
        $this->associations()->removeAll();
        $this->belongsTo('DbAroTest', [
            'className' => __NAMESPACE__ . '\DbAroTest',
            'foreignKey' => 'id',
        ]);
        $this->belongsTo('DbAcoTest', [
            'className' => __NAMESPACE__ . '\DbAcoTest',
            'foreignKey' => 'id',
        ]);
    }
}

/**
 * DboActionTest class
 */
class DbAcoActionTest extends AcoActionsTable
{
    /**
     * initialize
     *
     * @param array $config Configuration array
     * @return void
     */
    public function initialize(array $config): void
    {
        $this->setTable('aco_actions');
        $this->belongsTo('DbAcoTest', [
            'foreignKey' => 'aco_id',
        ]);
    }
}

/**
 * DbAroUserTest class
 */
class DbAroUserTest extends Entity
{
    /**
     * bindNode method
     *
     * @param string|array|Model $ref Ref
     * @return void
     */
    public function bindNode($ref = null)
    {
        if (Configure::read('DbAclbindMode') === 'string') {
            return 'ROOT/admins/Gandalf';
        } elseif (Configure::read('DbAclbindMode') === 'array') {
            return ['DbAroTest' => ['DbAroTest.model' => 'AuthUser', 'DbAroTest.foreign_key' => 2]];
        }
    }
}

/**
 * TestDbAcl class
 */
class TestDbAcl extends DbAcl
{
}

/**
 * AclNodeTest class
 */
class AclNodeTest extends TestCase
{
    /**
     * fixtures property
     *
     * @var array
     */
    public $fixtures = [
        'app.AcoActions',
        'app.Acos',
        'app.Aros',
        'app.ArosAcos',
        'core.AuthUsers',
    ];

    /**
     * setUp method
     *
     * @return void
     */
    public function setUp(): void
    {
        parent::setUp();
        Configure::write('Acl.classname', 'TestDbAcl');
        Configure::write('Acl.database', 'test');

        TableRegistry::getTableLocator()->clear();
        TableRegistry::getTableLocator()->get('DbAcoTest', [
            'className' => 'Acl\Test\TestCase\Model\Table\DbAcoTest',
        ]);
        TableRegistry::getTableLocator()->get('DbAroTest', [
            'className' => 'Acl\Test\TestCase\Model\Table\DbAroTest',
        ]);
    }

    /**
     * testNode method
     *
     * @return void
     */
    public function testNode()
    {
        $Aco = TableRegistry::getTableLocator()->get('DbAcoTest');

        $result = $Aco->node('Controller1');
        $result = $result->all()->extract('id')->toArray();
        $expected = [2, 1];
        $this->assertSame($expected, $result);

        $result = $Aco->node('Controller1/action1');
        $result = $result->all()->extract('id')->toArray();
        $expected = [3, 2, 1];
        $this->assertSame($expected, $result);

        $result = $Aco->node('Controller2/action1');
        $result = $result->all()->extract('id')->toArray();
        $expected = [7, 6, 1];
        $this->assertSame($expected, $result);

        $result = $Aco->node('Controller1/action2');
        $result = $result->all()->extract('id')->toArray();
        $expected = [5, 2, 1];
        $this->assertSame($expected, $result);

        $result = $Aco->node('Controller1/action1/record1');
        $result = $result->all()->extract('id')->toArray();
        $expected = [4, 3, 2, 1];
        $this->assertSame($expected, $result);

        $result = $Aco->node('Controller2/action1/record1');
        $result = $result->all()->extract('id')->toArray();
        $expected = [8, 7, 6, 1];
        $this->assertSame($expected, $result);

        $result = $Aco->node(8);
        $result = $result->all()->extract('id')->toArray();
        $expected = [8, 7, 6, 1];
        $this->assertSame($expected, $result);

        $result = $Aco->node('8');
        $result = $result->all()->extract('id')->toArray();
        $expected = [8, 7, 6, 1];
        $this->assertSame($expected, $result);

        $result = $Aco->node(7);
        $result = $result->all()->extract('id')->toArray();
        $expected = [7, 6, 1];
        $this->assertSame($expected, $result);

        $result = $Aco->node('7');
        $result = $result->all()->extract('id')->toArray();
        $expected = [7, 6, 1];
        $this->assertSame($expected, $result);

        $result = $Aco->node(4);
        $result = $result->all()->extract('id')->toArray();
        $expected = [4, 3, 2, 1];
        $this->assertSame($expected, $result);

        $result = $Aco->node('4');
        $result = $result->all()->extract('id')->toArray();
        $expected = [4, 3, 2, 1];
        $this->assertSame($expected, $result);

        $result = $Aco->node(3);
        $result = $result->all()->extract('id')->toArray();
        $expected = [3, 2, 1];
        $this->assertSame($expected, $result);

        $result = $Aco->node('3');
        $result = $result->all()->extract('id')->toArray();
        $expected = [3, 2, 1];
        $this->assertSame($expected, $result);

        $this->assertFalse($Aco->node('Controller2/action3'));

        $this->assertFalse($Aco->node('Controller2/action3/record5'));

        $result = $Aco->node('');
        $this->assertNull($result);
    }

    /**
     * test that node() doesn't dig deeper than it should.
     *
     * @return void
     */
    public function testNodeWithDuplicatePathSegments()
    {
        $Aco = TableRegistry::getTableLocator()->get('DbAcoTest');
        $nodes = $Aco->node('ROOT/Users');
        $this->assertSame(1, $nodes->toArray()[0]->parent_id, 'Parent id does not point at ROOT. %s');
    }

    /**
     * testNodeArrayFind method
     *
     * @return void
     */
    public function testNodeArrayFind()
    {
        $Aro = TableRegistry::getTableLocator()->get('DbAroTest');
        $Aro->setEntityClass(__NAMESPACE__ . '\DbAroUserTest');
        Configure::write('DbAclbindMode', 'string');
        $result = $Aro->node(['DbAroTest' => ['id' => '1', 'foreign_key' => '1']])->all()->extract('id')->toArray();
        $expected = [3, 2, 1];
        $this->assertSame($expected, $result);

        Configure::write('DbAclbindMode', 'array');
        $result = $Aro->node(['DbAroTest' => ['id' => 4, 'foreign_key' => 2]])->all()->extract('id')->toArray();
        $expected = [4];
        $this->assertSame($expected, $result);
    }

    /**
     * testNodeObjectFind method
     *
     * @return void
     */
    public function testNodeObjectFind()
    {
        $Aro = TableRegistry::getTableLocator()->get('DbAroTest');
        $Model = new DbAroUserTest(['id' => 1]);
        $Model->setSource('AuthUser');
        $result = $Aro->node($Model)->all()->extract('id')->toArray();
        $expected = [3, 2, 1];
        $this->assertSame($expected, $result);

        $Model->id = 2;
        $result = $Aro->node($Model)->all()->extract('id')->toArray();
        $expected = [4, 2, 1];
        $this->assertSame($expected, $result);
    }

    /**
     * testNodeAliasParenting method
     *
     * @return void
     */
    public function testNodeAliasParenting()
    {
        $Aco = TableRegistry::getTableLocator()->get('DbAcoTest');
        $conn = $Aco->getConnection();
        $statements = $Aco->getSchema()->truncateSql($conn);
        foreach ($statements as $sql) {
            $conn->execute($sql);
        }

        $aco = $Aco->newEntity(['model' => null, 'foreign_key' => null, 'parent_id' => null, 'alias' => 'Application']);
        $aco = $Aco->save($aco);

        $aco = $Aco->newEntity(['model' => null, 'foreign_key' => null, 'parent_id' => $aco->id, 'alias' => 'Pages']);
        $Aco->save($aco);

        $result = $Aco->find('all');
        $result->contain('DbAroTest');
        $result->enableHydration(false);

        $result = $result->toArray();
        $expected = [
            ['id' => 1, 'parent_id' => null, 'model' => null, 'foreign_key' => null, 'alias' => 'Application', 'lft' => 1, 'rght' => 4, 'db_aro_test' => []],
            ['id' => 2, 'parent_id' => 1, 'model' => null, 'foreign_key' => null, 'alias' => 'Pages', 'lft' => 2, 'rght' => 3, 'db_aro_test' => []],
        ];
        $this->assertSame($expected, $result);
    }

    /**
     * testNodeActionAuthorize method
     *
     * @return void
     */
    public function testNodeActionAuthorize()
    {
        $this->loadPlugins([new \TestPlugin\Plugin()]);

        $Aro = TableRegistry::getTableLocator()->get('DbAroTest');
        $Aro->setEntityClass(App::className('TestPlugin.TestPluginAuthUser', 'Model/Entity'));

        $aro = $Aro->newEntity(['model' => 'TestPluginAuthUser', 'foreign_key' => 1]);
        $aro = $Aro->save($aro);
        $result = $aro->id;
        $expected = 5;
        $this->assertSame($expected, $result);

        $node = $Aro->node(['TestPlugin.TestPluginAuthUser' => ['id' => 1, 'user' => 'mariano']]);
        $result = $node->all()->extract('id')->toArray();
        $expected = $aro->id;
        $this->assertSame($expected, $result[0]);

        $this->clearPlugins();
    }
}
