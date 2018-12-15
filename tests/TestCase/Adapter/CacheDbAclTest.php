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

use Acl\Adapter\CachedDbAcl;
use Acl\Controller\Component\AclComponent;
use Cake\Cache\Cache;
use Cake\Controller\ComponentRegistry;
use Cake\Core\Configure;
use Cake\ORM\Entity;
use Cake\ORM\TableRegistry;
use Cake\TestSuite\TestCase;

/**
 * CachedDbAclTwoTest class
 *
 */
class CachedDbAclTwoTest extends CachedDbAcl
{

    public $Permission = null;

    /**
     * construct method
     *
     */
    public function __construct()
    {
        $this->_cacheConfig = 'tests';
    }

    /**
     * Pass through for cache keys
     *
     * @param string|array|Entity $aro The requesting object identifier.
     * @param string|array|Entity $aco The controlled object identifier.
     * @param string $action Action
     *
     * @return string
     */
    public function getCacheKey($aro, $aco, $action)
    {
        return $this->_getCacheKey($aro, $aco, $action);
    }
}

/**
 * Test case for AclComponent using the CachedDbAcl implementation.
 *
 */
class CacheDbAclTest extends TestCase
{
    /**
     * fixtures property
     *
     * @var array
     */
    public $fixtures = [
        'core.Users',
    ];

    /**
     * setUp method
     *
     * @return void
     */
    public function setUp()
    {
        parent::setUp();
        Configure::write('Acl.classname', __NAMESPACE__ . '\CachedDbAclTwoTest');

        $this->CachedDb = new CachedDbAclTwoTest();

        Cache::setConfig('tests', [
            'engine' => 'File',
            'path' => TMP . 'test_acl',
            'prefix' => 'test_'
        ]);
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
        Cache::clear(false, 'tests');
        Cache::drop('tests');
    }

    /**
     * Test check
     *
     * @return void
     */
    public function testCacheKeys()
    {
        $this->assertSame('samir_print_read', $this->CachedDb->getCacheKey('Samir', 'print', 'read'));
        $this->assertSame('samir_root_tpsreports_update', $this->CachedDb->getCacheKey('Samir', 'ROOT/tpsReports/update', '*'));
        $this->assertSame('users_1_print', $this->CachedDb->getCacheKey(['Users' => ['id' => 1]], 'print', '*'));
        $this->assertSame('users_1_print', $this->CachedDb->getCacheKey(['model' => 'Users', 'foreign_key' => 1], 'print', '*'));

        $entity = new Entity([
            'id' => '1'
        ], ['source' => 'Users']);
        $this->assertSame('users_1_print', $this->CachedDb->getCacheKey($entity, 'print', '*'));
    }

    /**
     * Tests that permissions are cached
     *
     * @return void
     */
    public function testCaching()
    {
        $this->CachedDb->Permission = $this
            ->getMockBuilder('Acl\Model\Table\PermissionsTable')
            ->getMock();

        $this->CachedDb->Permission
            ->expects($this->once())
            ->method('check')
            ->with('Samir', 'print', '*')
            ->will($this->returnValue(true));

        $this->assertTrue($this->CachedDb->check('Samir', 'print'));
        $this->assertTrue($this->CachedDb->check('Samir', 'print'));
    }

    /**
     * Tests that permissions are cached for false permissions
     *
     * @return void
     */
    public function testCacheFalse()
    {
        $this->CachedDb->Permission = $this
            ->getMockBuilder('Acl\Model\Table\PermissionsTable')
            ->getMock();

        $this->CachedDb->Permission
            ->expects($this->once())
            ->method('check')
            ->with('Samir', 'view', 'create')
            ->will($this->returnValue(false));

        $this->assertFalse($this->CachedDb->check('Samir', 'view', 'create'));
        $this->assertFalse($this->CachedDb->check('Samir', 'view', 'create'));
    }

    /**
     * Tests that permissions cache is cleared when updated
     *
     * @return void
     */
    public function testCacheCleared()
    {
        $this->CachedDb->Permission = $this
            ->getMockBuilder('Acl\Model\Table\PermissionsTable')
            ->getMock();

        $this->CachedDb->Permission
            ->expects($this->exactly(2))
            ->method('check')
            ->with('Samir', 'view', '*')
            ->will($this->returnValue(true));

        $this->CachedDb->Permission
            ->expects($this->once())
            ->method('allow')
            ->with('Samir', 'view', '*', 1)
            ->will($this->returnValue(true));

        $this->assertTrue($this->CachedDb->check('Samir', 'view'));

        $this->CachedDb->allow('Samir', 'view');

        $this->assertTrue($this->CachedDb->check('Samir', 'view'));
    }
}
