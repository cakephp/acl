<?php
/**
 * CakePHP(tm) : Rapid Development Framework (http://cakephp.org)
 * Copyright (c) Cake Software Foundation, Inc. (http://cakefoundation.org)
 *
 * Licensed under The MIT License
 * For full copyright and license information, please see the LICENSE.txt
 * Redistributions of files must retain the above copyright notice.
 *
 * @copyright     Copyright (c) Cake Software Foundation, Inc. (http://cakefoundation.org)
 * @link          http://cakephp.org CakePHP(tm) Project
 * @license       http://www.opensource.org/licenses/mit-license.php MIT License
 */
namespace Acl\Test\TestCase\Adapter;

use Acl\Adapter\DbAcl;
use Acl\Controller\Component\AclComponent;
use Acl\Model\Entity\Aco;
use Acl\Model\Entity\Aro;
use Acl\Model\Table\AclNodesTable;
use Acl\Model\Table\AcosTable;
use Acl\Model\Table\ArosTable;
use Acl\Model\Table\PermissionsTable;
use Cake\Controller\ComponentRegistry;
use Cake\Core\Configure;
use Cake\ORM\TableRegistry;
use Cake\TestSuite\Fixture\TestModel;
use Cake\TestSuite\TestCase;

/**
 * AroTwoTest class
 *
 */
class AroTwoTest extends ArosTable
{

    /**
     * initialize
     *
     * @param array $config Configuration array
     * @return void
     */
    public function initialize(array $config)
    {
        parent::initialize($config);
        $this->setAlias('AroTwoTest');
        $this->setTable('aro_twos');
        $this->associations()->removeAll();
        $this->belongsToMany('AcoTwoTest', [
            'through' => __NAMESPACE__ . '\PermissionTwoTest',
            'className' => __NAMESPACE__ . '\AroTwoTest',
        ]);
    }
}

/**
 * AcoTwoTest class
 *
 */
class AcoTwoTest extends AcosTable
{

    /**
     * initialize
     *
     * @param array $config Configuration array
     * @return void
     */
    public function initialize(array $config)
    {
        parent::initialize($config);
        $this->setAlias('AcoTwoTest');
        $this->setTable('aco_twos');
        $this->associations()->removeAll();
        $this->belongsToMany('AroTwoTest', [
            'through' => __NAMESPACE__ . '\PermissionTwoTest',
            'className' => __NAMESPACE__ . '\AroTwoTest',
        ]);
    }
}

/**
 * PermissionTwoTest class
 *
 */
class PermissionTwoTest extends PermissionsTable
{

    /**
     * initialize
     *
     * @param array $config Configuration array
     * @return void
     */
    public function initialize(array $config)
    {
        parent::initialize($config);
        $this->setAlias('PermissionTwoTest');
        $this->setEntityClass('Acl\Model\Entity\Permission');
        $this->setTable('aros_aco_twos');
        $this->associations()->removeAll();
        $this->belongsTo('AroTwoTest', [
            'foreignKey' => 'aro_id',
            'className' => __NAMESPACE__ . '\AroTwoTest',
        ]);
        $this->belongsTo('AcoTwoTest', [
            'foreignKey' => 'aco_id',
            'className' => __NAMESPACE__ . '\AcoTwoTest',
        ]);
    }
}

/**
 * DbAclTwoTest class
 *
 */
class DbAclTwoTest extends DbAcl
{

    /**
     * construct method
     *
     */
    public function __construct()
    {
        $this->Permission = TableRegistry::getTableLocator()->get('Permissions');
        $this->Aro = TableRegistry::getTableLocator()->get('AroTwoTest');
        $this->Aro->Permission = $this->Permission;
        $this->Aco = TableRegistry::getTableLocator()->get('AcoTwoTest');
        $this->Aco->Permission = $this->Permission;

        $this->Permission->Aro = $this->Aro;
        $this->Permission->Aco = $this->Aco;
    }
}

/**
 * Test case for AclComponent using the DbAcl implementation.
 *
 */
class DbAclTest extends TestCase
{

    /**
     * fixtures property
     *
     * @var array
     */
    public $fixtures = [
        'app.AcoTwos',
        'app.AroTwos',
        'app.ArosAcoTwos',
    ];

    /**
     * setUp method
     *
     * @return void
     */
    public function setUp()
    {
        parent::setUp();
        Configure::write('Acl.classname', __NAMESPACE__ . '\DbAclTwoTest');
        Configure::write('Acl.database', 'test');

        TableRegistry::getTableLocator()->clear();
        TableRegistry::getTableLocator()->get('Permissions', [
            'className' => __NAMESPACE__ . '\PermissionTwoTest',
        ]);
        TableRegistry::getTableLocator()->get('AroTwoTest', [
            'className' => __NAMESPACE__ . '\AroTwoTest',
        ]);
        TableRegistry::getTableLocator()->get('AcoTwoTest', [
            'className' => __NAMESPACE__ . '\AcoTwoTest',
        ]);

        $Collection = new ComponentRegistry();
        $this->Acl = new AclComponent($Collection);
    }

    /**
     * tearDown method
     *
     * @return void
     */
    public function tearDown()
    {
        parent::tearDown();
        unset($this->Acl);
    }

    /**
     * testAclCreate method
     *
     * @return void
     */
    public function testCreate()
    {
        $aro = new Aro(['alias' => 'Chotchkey']);
        $aro = $this->Acl->Aro->save($aro);
        $this->assertTrue((bool)$aro);

        $parent = $aro->id;

        $aro = new Aro(['parent_id' => $parent, 'alias' => 'Joanna']);
        $aro = $this->Acl->Aro->save($aro);
        $this->assertTrue((bool)$aro);

        $aro = new Aro(['parent_id' => $parent, 'alias' => 'Stapler']);
        $aro = $this->Acl->Aro->save($aro);
        $this->assertTrue((bool)$aro);

        $root = $this->Acl->Aco->node('ROOT')->first();
        $parent = $root->id;

        $aco = new Aco(['parent_id' => $parent, 'alias' => 'Drinks']);
        $aco = $this->Acl->Aco->save($aro);
        $this->assertTrue((bool)$aco);

        $aco = new Aco(['parent_id' => $parent, 'alias' => 'PiecesOfFlair']);
        $aco = $this->Acl->Aco->save($aro);
        $this->assertTrue((bool)$aco);
    }

    /**
     * testAclCreateWithParent method
     *
     * @return void
     */
    public function testCreateWithParent()
    {
        $parent = $this->Acl->Aro->findByAlias('Peter')->first();

        $this->Acl->Aro->save(new Aro([
            'alias' => 'Subordinate',
            'model' => 'User',
            'foreign_key' => 7,
            'parent_id' => $parent->id,
        ]));
        $result = $this->Acl->Aro->findByAlias('Subordinate')->first();
        $this->assertEquals('AroTwoTest', $result->getSource());
        $this->assertEquals(16, $result->lft);
        $this->assertEquals(17, $result->rght);
    }

    /**
     * testDbAclAllow method
     *
     * @expectedException PHPUnit\Framework\Error\Warning
     * @return void
     */
    public function testAllow()
    {
        $this->assertFalse($this->Acl->check('Micheal', 'tpsReports', 'read'));
        $this->assertTrue($this->Acl->allow('Micheal', 'tpsReports', ['read', 'delete', 'update']));
        $this->assertTrue($this->Acl->check('Micheal', 'tpsReports', 'update'));
        $this->assertTrue($this->Acl->check('Micheal', 'tpsReports', 'read'));
        $this->assertTrue($this->Acl->check('Micheal', 'tpsReports', 'delete'));

        $this->assertFalse($this->Acl->check('Micheal', 'tpsReports', 'create'));
        $this->assertTrue($this->Acl->allow('Micheal', 'ROOT/tpsReports', 'create'));
        $this->assertTrue($this->Acl->check('Micheal', 'tpsReports', 'create'));
        $this->assertTrue($this->Acl->check('Micheal', 'tpsReports', 'delete'));
        $this->assertTrue($this->Acl->allow('Micheal', 'printers', 'create'));
        // Michael no longer has his delete permission for tpsReports!
        $this->assertTrue($this->Acl->check('Micheal', 'tpsReports', 'delete'));
        $this->assertTrue($this->Acl->check('Micheal', 'printers', 'create'));

        $this->assertFalse($this->Acl->check('root/users/Samir', 'ROOT/tpsReports/view'));
        $this->assertTrue($this->Acl->allow('root/users/Samir', 'ROOT/tpsReports/view', '*'));
        $this->assertTrue($this->Acl->check('Samir', 'view', 'read'));
        $this->assertTrue($this->Acl->check('root/users/Samir', 'ROOT/tpsReports/view', 'update'));

        $this->assertFalse($this->Acl->check('root/users/Samir', 'ROOT/tpsReports/update', '*'));
        $this->assertTrue($this->Acl->allow('root/users/Samir', 'ROOT/tpsReports/update', '*'));
        $this->assertTrue($this->Acl->check('Samir', 'update', 'read'));
        $this->assertTrue($this->Acl->check('root/users/Samir', 'ROOT/tpsReports/update', 'update'));
        // Samir should still have his tpsReports/view permissions, but does not
        $this->assertTrue($this->Acl->check('root/users/Samir', 'ROOT/tpsReports/view', 'update'));

        $this->assertFalse($this->Acl->allow('Lumbergh', 'ROOT/tpsReports/DoesNotExist', 'create'));
    }

    /**
     * Test that allow() with an invalid permission name triggers an error.
     *
     * @expectedException Exception
     * @return void
     */
    public function testAllowInvalidPermission()
    {
        $this->Acl->allow('Micheal', 'tpsReports', 'derp');
    }

    /**
     * testAllowInvalidNode method
     *
     * @expectedException PHPUnit\Framework\Error\Warning
     * @return void
     */
    public function testAllowInvalidNode()
    {
        $this->Acl->allow('Homer', 'tpsReports', 'create');
    }

    /**
     * testDbAclCheck method
     *
     * @return void
     */
    public function testCheck()
    {
        $this->assertTrue($this->Acl->check('Samir', 'print', 'read'));
        $this->assertTrue($this->Acl->check('Lumbergh', 'current', 'read'));
        $this->assertFalse($this->Acl->check('Milton', 'smash', 'read'));
        $this->assertFalse($this->Acl->check('Milton', 'current', 'update'));

        $this->assertFalse($this->Acl->check(null, 'printers', 'create'));
        $this->assertFalse($this->Acl->check('managers', null, 'read'));

        $this->assertTrue($this->Acl->check('Bobs', 'ROOT/tpsReports/view/current', 'read'));
        $this->assertFalse($this->Acl->check('Samir', 'ROOT/tpsReports/update', 'read'));

        $this->assertFalse($this->Acl->check('root/users/Milton', 'smash', 'delete'));
    }

    /**
     * testCheckInvalidNode method
     *
     * @expectedException PHPUnit\Framework\Error\Warning
     * @return void
     */
    public function testCheckInvalidNode()
    {
        $this->assertFalse($this->Acl->check('WRONG', 'tpsReports', 'read'));
    }

    /**
     * testCheckInvalidPermission method
     *
     * @expectedException PHPUnit\Framework\Error\Notice
     * @return void
     */
    public function testCheckInvalidPermission()
    {
        $this->Acl->check('Lumbergh', 'smash', 'foobar');
    }

    /**
     * testCheckMissingPermission method
     *
     * @expectedException PHPUnit\Framework\Error\Warning
     * @return void
     */
    public function testCheckMissingPermission()
    {
        $this->Acl->check('users', 'NonExistent', 'read');
    }

    /**
     * testDbAclCascadingDeny function
     *
     * Setup the acl permissions such that Bobs inherits from admin.
     * deny Admin delete access to a specific resource, check the permissions are inherited.
     *
     * @return void
     */
    public function testAclCascadingDeny()
    {
        $this->Acl->inherit('Bobs', 'ROOT', '*');
        $this->assertTrue($this->Acl->check('admin', 'tpsReports', 'delete'));
        $this->assertTrue($this->Acl->check('Bobs', 'tpsReports', 'delete'));
        $this->Acl->deny('admin', 'tpsReports', 'delete');
        $this->assertFalse($this->Acl->check('admin', 'tpsReports', 'delete'));
        $this->assertFalse($this->Acl->check('Bobs', 'tpsReports', 'delete'));
    }

    /**
     * testDbAclDeny method
     *
     * @expectedException PHPUnit\Framework\Error\Warning
     * @return void
     */
    public function testDeny()
    {
        $this->assertTrue($this->Acl->check('Micheal', 'smash', 'delete'));
        $this->Acl->deny('Micheal', 'smash', 'delete');
        $this->assertFalse($this->Acl->check('Micheal', 'smash', 'delete'));
        $this->assertTrue($this->Acl->check('Micheal', 'smash', 'read'));
        $this->assertTrue($this->Acl->check('Micheal', 'smash', 'create'));
        $this->assertTrue($this->Acl->check('Micheal', 'smash', 'update'));
        $this->assertFalse($this->Acl->check('Micheal', 'smash', '*'));

        $this->assertTrue($this->Acl->check('Samir', 'refill', '*'));
        $this->Acl->deny('Samir', 'refill', '*');
        $this->assertFalse($this->Acl->check('Samir', 'refill', 'create'));
        $this->assertFalse($this->Acl->check('Samir', 'refill', 'update'));
        $this->assertFalse($this->Acl->check('Samir', 'refill', 'read'));
        $this->assertFalse($this->Acl->check('Samir', 'refill', 'delete'));

        $result = $this->Acl->Aro->Permission->find('all', [
            'conditions' => ['AroTwoTest.alias' => 'Samir'],
            'contain' => 'AroTwoTest',
        ])->toArray();
        $expected = '-1';
        $this->assertEquals($expected, $result[0]->_delete);

        $this->assertFalse($this->Acl->deny('Lumbergh', 'ROOT/tpsReports/DoesNotExist', 'create'));
    }

    /**
     * testAclNodeLookup method
     *
     * @return void
     */
    public function testAclNodeLookup()
    {
        $result = $this->Acl->Aro->node('root/users/Samir')->enableHydration(false)->toArray();
        $expected = [
            ['id' => '7', 'parent_id' => '4', 'model' => 'User', 'foreign_key' => 3, 'alias' => 'Samir'],
            ['id' => '4', 'parent_id' => '1', 'model' => 'Group', 'foreign_key' => 3, 'alias' => 'users'],
            ['id' => '1', 'parent_id' => null, 'model' => null, 'foreign_key' => null, 'alias' => 'root']
        ];
        $this->assertEquals($expected, $result);

        $result = $this->Acl->Aco->node('ROOT/tpsReports/view/current')->enableHydration(false)->toArray();
        $expected = [
            ['id' => '4', 'parent_id' => '3', 'model' => null, 'foreign_key' => null, 'alias' => 'current'],
            ['id' => '3', 'parent_id' => '2', 'model' => null, 'foreign_key' => null, 'alias' => 'view'],
            ['id' => '2', 'parent_id' => '1', 'model' => null, 'foreign_key' => null, 'alias' => 'tpsReports'],
            ['id' => '1', 'parent_id' => null, 'model' => null, 'foreign_key' => null, 'alias' => 'ROOT'],
        ];
        $this->assertEquals($expected, $result);
    }

    /**
     * testDbInherit method
     *
     * @return void
     */
    public function testInherit()
    {
        //parent doesn't have access inherit should still deny
        $this->assertFalse($this->Acl->check('Milton', 'smash', 'delete'));
        $this->Acl->inherit('Milton', 'smash', 'delete');
        $this->assertFalse($this->Acl->check('Milton', 'smash', 'delete'));

        //inherit parent
        $this->assertFalse($this->Acl->check('Milton', 'smash', 'read'));
        $this->Acl->inherit('Milton', 'smash', 'read');
        $this->assertTrue($this->Acl->check('Milton', 'smash', 'read'));
    }

    /**
     * testDbGrant method
     *
     * @expectedException PHPUnit\Framework\Error\Warning
     * @return void
     */
    public function testGrant()
    {
        $this->assertFalse($this->Acl->check('Samir', 'tpsReports', 'create'));
        $this->Acl->allow('Samir', 'tpsReports', 'create');
        $this->assertTrue($this->Acl->check('Samir', 'tpsReports', 'create'));

        $this->assertFalse($this->Acl->check('Micheal', 'view', 'read'));
        $this->Acl->allow('Micheal', 'view', ['read', 'create', 'update']);
        $this->assertTrue($this->Acl->check('Micheal', 'view', 'read'));
        $this->assertTrue($this->Acl->check('Micheal', 'view', 'create'));
        $this->assertTrue($this->Acl->check('Micheal', 'view', 'update'));
        $this->assertFalse($this->Acl->check('Micheal', 'view', 'delete'));

        $this->assertFalse($this->Acl->allow('Peter', 'ROOT/tpsReports/DoesNotExist', 'create'));
    }

    /**
     * testDbRevoke method
     *
     * @expectedException PHPUnit\Framework\Error\Warning
     * @return void
     */
    public function testRevoke()
    {
        $this->assertTrue($this->Acl->check('Bobs', 'tpsReports', 'read'));
        $this->Acl->deny('Bobs', 'tpsReports', 'read');
        $this->assertFalse($this->Acl->check('Bobs', 'tpsReports', 'read'));

        $this->assertTrue($this->Acl->check('users', 'printers', 'read'));
        $this->Acl->deny('users', 'printers', 'read');
        $this->assertFalse($this->Acl->check('users', 'printers', 'read'));
        $this->assertFalse($this->Acl->check('Samir', 'printers', 'read'));
        $this->assertFalse($this->Acl->check('Peter', 'printers', 'read'));

        $this->Acl->deny('Bobs', 'ROOT/printers/DoesNotExist', 'create');
    }

    /**
     * debug function - to help editing/creating test cases for the ACL component
     *
     * To check the overall ACL status at any time call $this->_debug();
     * Generates a list of the current aro and aco structures and a grid dump of the permissions that are defined
     * Only designed to work with the db based ACL
     *
     * @param bool $printTreesToo Set to True to output tree data to screen
     * @return void
     */
    protected function _debug($printTreesToo = false)
    {
        $this->Acl->Aro->displayField = 'alias';
        $this->Acl->Aco->displayField = 'alias';
        $aros = $this->Acl->Aro->find('list', ['order' => 'lft']);
        $acos = $this->Acl->Aco->find('list', ['order' => 'lft']);
        $rights = ['*', 'create', 'read', 'update', 'delete'];
        $permissions['Aros v Acos >'] = $acos;
        foreach ($aros as $aro) {
            $row = [];
            foreach ($acos as $aco) {
                $perms = '';
                foreach ($rights as $right) {
                    if ($this->Acl->check($aro, $aco, $right)) {
                        if ($right === '*') {
                            $perms .= '****';
                            break;
                        }
                        $perms .= $right[0];
                    } elseif ($right !== '*') {
                        $perms .= ' ';
                    }
                }
                $row[] = $perms;
            }
            $permissions[$aro] = $row;
        }
        foreach ($permissions as $key => $values) {
            array_unshift($values, $key);
            $values = array_map([&$this, '_pad'], $values);
            $permissions[$key] = implode(' ', $values);
        }
        $permissions = array_map([&$this, '_pad'], $permissions);
        array_unshift($permissions, 'Current Permissions :');
        if ($printTreesToo) {
            debug(['aros' => $this->Acl->Aro->generateTreeList(), 'acos' => $this->Acl->Aco->generateTreeList()]);
        }
        debug(implode("\r\n", $permissions));
    }

    /**
     * pad function
     * Used by debug to format strings used in the data dump
     *
     * @param string $string String for padding
     * @param int $len Padding length
     * @return void
     */
    protected function _pad($string = '', $len = 14)
    {
        return str_pad($string, $len);
    }
}
