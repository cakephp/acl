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
namespace Acl\Test\TestCase\Auth;

use Acl\Auth\ActionsAuthorize;
use Cake\Http\ServerRequest;
use Cake\TestSuite\TestCase;

/**
 * Class ActionsAuthorizeTest
 *
 */
class ActionsAuthorizeTest extends TestCase
{

    /**
     * setUp
     *
     * @return void
     */
    public function setUp()
    {
        parent::setUp();
        $this->controller = $this->getMockBuilder('Cake\Controller\Controller')
            ->disableOriginalConstructor()
            ->getMock();
        $this->Acl = $this->getMockBuilder('Acl\Controller\Component\AclComponent')
            ->disableOriginalConstructor()
            ->getMock();
        $this->Collection = $this->getMockBuilder('Cake\Controller\ComponentRegistry')
            ->getMock();

        $this->auth = new ActionsAuthorize($this->Collection);
        $this->auth->setConfig('actionPath', '/controllers');
    }

    /**
     * setup the mock acl.
     *
     * @return void
     */
    protected function _mockAcl()
    {
        $this->Collection->expects($this->any())
            ->method('load')
            ->with('Acl')
            ->will($this->returnValue($this->Acl));
    }

    /**
     * test failure
     *
     * @return void
     */
    public function testAuthorizeFailure()
    {
        $user = [
            'Users' => [
                'id' => 1,
                'user' => 'mariano'
            ]
        ];
        $request = new ServerRequest('/posts/index');
        $request = $request->withAttribute('params', [
            'plugin' => null,
            'controller' => 'posts',
            'action' => 'index'
        ]);

        $this->_mockAcl();

        $this->Acl->expects($this->once())
            ->method('check')
            ->with($user, 'controllers/Posts/index')
            ->will($this->returnValue(false));

        $this->assertFalse($this->auth->authorize($user['Users'], $request));
    }

    /**
     * test isAuthorized working.
     *
     * @return void
     */
    public function testAuthorizeSuccess()
    {
        $user = [
            'Users' => [
                'id' => 1,
                'user' => 'mariano'
            ]
        ];
        $request = new ServerRequest('/posts/index');
        $request = $request->withAttribute('params', [
            'plugin' => null,
            'controller' => 'posts',
            'action' => 'index'
        ]);

        $this->_mockAcl();

        $this->Acl->expects($this->once())
            ->method('check')
            ->with($user, 'controllers/Posts/index')
            ->will($this->returnValue(true));

        $this->assertTrue($this->auth->authorize($user['Users'], $request));
    }

    /**
     * testAuthorizeSettings
     *
     * @return void
     */
    public function testAuthorizeSettings()
    {
        $request = new ServerRequest('/posts/index');
        $request = $request->withAttribute('params', [
            'plugin' => null,
            'controller' => 'posts',
            'action' => 'index'
        ]);

        $this->_mockAcl();

        $this->auth->setConfig('userModel', 'TestPlugin.AuthUser');
        $user = [
            'id' => 1,
            'username' => 'mariano'
        ];

        $expected = ['TestPlugin.AuthUser' => ['id' => 1, 'username' => 'mariano']];
        $this->Acl->expects($this->once())
            ->method('check')
            ->with($expected, 'controllers/Posts/index')
            ->will($this->returnValue(true));

        $this->assertTrue($this->auth->authorize($user, $request));
    }

    /**
     * test action()
     *
     * @return void
     */
    public function testActionMethod()
    {
        $request = new ServerRequest('/posts/index');
        $request = $request->withAttribute('params', [
            'plugin' => null,
            'controller' => 'posts',
            'action' => 'index'
        ]);

        $result = $this->auth->action($request);
        $this->assertEquals('controllers/Posts/index', $result);
    }

    /**
     * Make sure that action() doesn't create double slashes anywhere.
     *
     * @return void
     */
    public function testActionNoDoubleSlash()
    {
        $this->auth->setConfig('actionPath', '/controllers/');
        $request = new ServerRequest('/posts/index', false);
        $request = $request->withAttribute('params', [
            'plugin' => null,
            'controller' => 'posts',
            'action' => 'index'
        ]);
        $result = $this->auth->action($request);
        $this->assertEquals('controllers/Posts/index', $result);
    }

    /**
     * test action() and plugins
     *
     * @return void
     */
    public function testActionWithPlugin()
    {
        $request = new ServerRequest('/debug_kit/posts/index');
        $request = $request->withAttribute('params', [
            'plugin' => 'debug_kit',
            'controller' => 'posts',
            'action' => 'index'
        ]);

        $result = $this->auth->action($request);
        $this->assertEquals('controllers/DebugKit/Posts/index', $result);
    }

    public function testActionWithPluginAndPrefix()
    {
        $request = new ServerRequest('/debug_kit/admin/posts/index');
        $request = $request->withAttribute('params', [
            'plugin' => 'debug_kit',
            'prefix' => 'admin',
            'controller' => 'posts',
            'action' => 'index'
        ]);

        $result = $this->auth->action($request);
        $this->assertEquals('controllers/DebugKit/Admin/Posts/index', $result);
    }

    public function testActionWithPrefix()
    {
        $request = new ServerRequest('/admin/posts/index');
        $request = $request->withAttribute('params', [
            'plugin' => null,
            'prefix' => 'admin',
            'controller' => 'posts',
            'action' => 'index'
        ]);

        $result = $this->auth->action($request);
        $this->assertEquals('controllers/Admin/Posts/index', $result);
    }
}
