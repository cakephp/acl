<?php
/**
 * Acl Extras Shell.
 *
 * Enhances the existing Acl Shell with a few handy functions
 *
 * Copyright 2008, Mark Story.
 * 694B The Queensway
 * toronto, ontario M8Y 1K9
 *
 * Licensed under The MIT License
 * Redistributions of files must retain the above copyright notice.
 *
 * @copyright Copyright 2008-2009, Mark Story.
 * @link http://mark-story.com
 * @author Mark Story <mark@mark-story.com>
 * @license http://www.opensource.org/licenses/mit-license.php The MIT License
 */
namespace Acl\Test\TestCase;

use Cake\Core\Configure;
use Cake\Core\Plugin;
use Cake\Datasource\ConnectionManager;
use Cake\TestSuite\TestCase;

//import test controller class names.
include dirname(__FILE__) . DS . 'test_controllers.php';
include dirname(__FILE__) . DS . 'test_admin_controllers.php';
include dirname(__FILE__) . DS . 'test_plugin_controllers.php';
include dirname(__FILE__) . DS . 'test_nested_plugin_controllers.php';
include dirname(__FILE__) . DS . 'test_plugin_admin_controllers.php';

/**
 * AclExtras Shell Test case
 *
 */
class AclExtrasTestCase extends TestCase
{

    public $fixtures = ['app.acos', 'app.aros', 'app.aros_acos'];

    /**
     * setUp
     *
     * @return void
     */
    public function setUp()
    {
        parent::setUp();
        Configure::write('Acl.classname', 'DbAcl');
        Configure::write('Acl.database', 'test');

        $this->Task = $this->getMock(
            'Acl\AclExtras',
            ['in', 'out', 'hr', 'createFile', 'error', 'err', 'clear', 'getControllerList']
        );
    }

    /**
     * tearDown
     *
     * @return void
     */
    public function tearDown()
    {
        parent::tearDown();
        unset($this->Task);
    }

    /**
     * test recover
     *
     * @return void
     */
    public function testRecover()
    {
        $this->Task->startup();
        $this->Task->args = ['Aco'];
        $this->Task->Acl->Aco = $this->getMock('Aco', ['recover']);
        $this->Task->Acl->Aco->expects($this->once())
            ->method('recover')
            ->will($this->returnValue(true));

        $this->Task->expects($this->once())
            ->method('out')
            ->with($this->matchesRegularExpression('/recovered/'));

        $this->Task->recover();
    }

    /**
     * test startup
     *
     * @return void
     */
    public function testStartup()
    {
        $this->assertEquals($this->Task->Acl, null);
        $this->Task->startup();
        $this->assertInstanceOf('Acl\Controller\Component\AclComponent', $this->Task->Acl);
    }

    /**
     * clean fixtures and setup mock
     *
     * @return void
     */
    protected function _clean()
    {
        $tableName = 'acos';
        $db = ConnectionManager::get('test');
        $db->execute('DELETE FROM ' . $tableName);
    }

    protected function _setup()
    {
        $this->Task->expects($this->any())
            ->method('getControllerList')
            ->with(null)
            ->will($this->returnCallback(function ($plugin, $prefix) {
                if ($prefix === null) {
                    return ['CommentsController.php', 'PostsController.php', 'BigLongNamesController.php'];
                } else {
                    return ['PostsController.php', 'BigLongNamesController.php'];
                }
            }));

        $this->Task->startup();
    }

    /**
     * Test acoUpdate method.
     *
     * @return void
     */
    public function testAcoUpdate()
    {
        $this->_clean();
        $this->_setup();
        $this->Task->acoUpdate();

        $Aco = $this->Task->Acl->Aco;

        $result = $Aco->node('controllers/Comments')->toArray();
        $this->assertEquals($result[0]['alias'], 'Comments');

        $result = $Aco->find('children', ['for' => $result[0]['id']])->toArray();
        $this->assertEquals(count($result), 3);
        $this->assertEquals($result[0]['alias'], 'add');
        $this->assertEquals($result[1]['alias'], 'index');
        $this->assertEquals($result[2]['alias'], 'delete');

        $result = $Aco->node('controllers/Posts')->toArray();
        $this->assertEquals($result[0]['alias'], 'Posts');
        $result = $Aco->find('children', ['for' => $result[0]['id']])->toArray();
        $this->assertEquals(count($result), 3);

        $result = $Aco->node('controllers/Admin/Posts')->toArray();
        $this->assertEquals($result[0]['alias'], 'Posts');
        $result = $Aco->find('children', ['for' => $result[0]['id']])->toArray();
        $this->assertEquals(count($result), 3);

        $result = $Aco->node('controllers/Admin/BigLongNames')->toArray();
        $this->assertEquals($result[0]['alias'], 'BigLongNames');
        $result = $Aco->find('children', ['for' => $result[0]['id']])->toArray();
        $this->assertEquals(count($result), 4);

        $result = $Aco->node('controllers/BigLongNames')->toArray();
        $this->assertEquals($result[0]['alias'], 'BigLongNames');
        $result = $Aco->find('children', ['for' => $result[0]['id']])->toArray();
        $this->assertEquals(count($result), 4);
    }

    protected function _createNode($parent, $expected)
    {
        $Aco = $this->Task->Acl->Aco;
        $Aco->cacheQueries = false;

        $result = $Aco->node($parent)->toArray();
        $new = [
            'parent_id' => $result[0]['id'],
            'alias' => 'someMethod'
        ];
        $new = $Aco->newEntity($new);
        $Aco->save($new);

        $children = $Aco->find('children', ['for' => $result[0]['id']])->toArray();
        $this->assertEquals(count($children), $expected);

        return $result;
    }

    /**
     * test syncing of Aco records
     *
     * @return void
     */
    public function testAcoSyncRemoveMethods()
    {
        $this->_clean();
        $this->_setup();
        $this->Task->acoUpdate();

        $Aco = $this->Task->Acl->Aco;
        $Aco->cacheQueries = false;

        $basic = $this->_createNode('controllers/Comments', 4);
        $adminPosts = $this->_createNode('controllers/Admin/Posts', 4);

        $this->Task->acoSync();
        $children = $Aco->find('children', ['for' => $basic[0]['id']])->toArray();
        $this->assertEquals(count($children), 3);
        $children = $Aco->find('children', ['for' => $adminPosts[0]['id']])->toArray();
        $this->assertEquals(count($children), 3);

        $method = $Aco->node('controllers/Comments/someMethod');
        $this->assertFalse($method);
        $method = $Aco->node('controllers/Admin/Posts/otherMethod');
        $this->assertFalse($method);
    }

    /**
     * test adding methods with acoUpdate
     *
     * @return void
     */
    public function testAcoUpdateAddingMethods()
    {
        $this->_clean();
        $this->_setup();
        $this->Task->acoUpdate();

        $Aco = $this->Task->Acl->Aco;
        $Aco->cacheQueries = false;

        $result = $Aco->node('controllers/Comments')->toArray();
        $children = $Aco->find('children', ['for' => $result[0]['id']])->toArray();
        $this->assertEquals(count($children), 3);

        $Aco->delete($children[0]);
        $Aco->delete($children[1]);
        $this->Task->acoUpdate();

        $children = $Aco->find('children', ['for' => $result[0]['id']])->toArray();
        $this->assertEquals(count($children), 3);
    }

    /**
     * test adding controllers on sync
     *
     * @return void
     */
    public function testAddingControllers()
    {
        $this->_clean();
        $this->_setup();
        $this->Task->acoUpdate();

        $Aco = $this->Task->Acl->Aco;
        $Aco->cacheQueries = false;

        $result = $Aco->node('controllers/Comments')->toArray();
        $Aco->delete($result[0]);

        $this->Task->acoUpdate();
        $newResult = $Aco->node('controllers/Comments')->toArray();
        $this->assertNotEquals($newResult[0]['id'], $result[0]['id']);
        $this->assertEquals($newResult[0]['alias'], $result[0]['alias']);
    }

    /**
     * Ensures that nested plugins are correctly created
     *
     * @return void
     */
    public function testUpdateWithPlugins()
    {
        Plugin::unload();
        Plugin::load('TestPlugin', ['routes' => true]);
        Plugin::load('Nested/TestPluginTwo');
        Plugin::routes();
        $this->_clean();

        $this->Task->expects($this->atLeast(3))
            ->method('getControllerList')
            ->will($this->returnCallback(function ($plugin, $prefix) {
                switch ($plugin) {
                    case 'TestPlugin':
                        return ['PluginController.php'];
                    case 'Nested/TestPluginTwo':
                        if ($prefix !== null) {
                            return [];
                        }
                        return ['PluginTwoController.php'];
                    default:
                        if ($prefix !== null) {
                            return ['PostsController.php', 'BigLongNamesController.php'];
                        }
                        return ['CommentsController.php', 'PostsController.php', 'BigLongNamesController.php'];
                }
            }));

        $this->Task->startup();

        $this->Task->acoUpdate();

        $Aco = $this->Task->Acl->Aco;

        $result = $Aco->node('controllers/TestPlugin/Plugin');
        $this->assertNotFalse($result);
        $this->assertEquals($result->toArray()[0]['alias'], 'Plugin');

        $result = $Aco->node('controllers/TestPlugin/Admin/Plugin');
        $this->assertNotFalse($result);
        $this->assertEquals($result->toArray()[0]['alias'], 'Plugin');

        $result = $Aco->node('controllers/Nested\TestPluginTwo/PluginTwo');
        $this->assertNotFalse($result);
        $result = $result->toArray();
        $this->assertEquals($result[0]['alias'], 'PluginTwo');
        $result = $Aco->find('children', ['for' => $result[0]['id']])->toArray();
        $this->assertEquals(count($result), 3);
        $this->assertEquals($result[0]['alias'], 'index');
        $this->assertEquals($result[1]['alias'], 'add');
        $this->assertEquals($result[2]['alias'], 'edit');
    }

    /**
     * Tests that aco sync works correctly with nested plugins
     *
     * @return void
     */
    public function testSyncWithNestedPlugin()
    {
        Plugin::unload();
        Plugin::load('Nested/TestPluginTwo');
        $this->_clean();

        $this->Task->expects($this->atLeast(2))
            ->method('getControllerList')
            ->will($this->returnCallback(function ($plugin, $prefix) {
                if ($prefix !== null) {
                    return [];
                }

                switch ($plugin) {
                    case 'Nested/TestPluginTwo':
                        return ['PluginTwoController.php'];
                    default:
                        return ['CommentsController.php', 'PostsController.php', 'BigLongNamesController.php'];
                }
            }));

        $this->Task->startup();
        $this->Task->acoUpdate();

        $Aco = $this->Task->Acl->Aco;
        $originalNode = $Aco->node('controllers/Nested\TestPluginTwo/PluginTwo')->first();

        $cleanTask = $this->getMock(
            'Acl\AclExtras',
            ['in', 'out', 'hr', 'createFile', 'error', 'err', 'clear', 'getControllerList']
        );

        $cleanTask->expects($this->atLeast(2))
            ->method('getControllerList')
            ->will($this->returnCallback(function ($plugin, $prefix) {
                if ($prefix !== null) {
                    return [];
                }

                switch ($plugin) {
                    case 'Nested/TestPluginTwo':
                        return ['PluginTwoController.php'];
                    default:
                        return ['CommentsController.php', 'PostsController.php', 'BigLongNamesController.php'];
                }
            }));

        $cleanTask->startup();
        $cleanTask->acoSync();

        $updatedNode = $Aco->node('controllers/Nested\TestPluginTwo/PluginTwo')->first();

        $this->assertSame($originalNode->id, $updatedNode->id);
    }
}
