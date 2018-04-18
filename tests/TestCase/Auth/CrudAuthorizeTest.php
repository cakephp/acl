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

use Acl\Auth\CrudAuthorize;
use Cake\Core\Configure;
use Cake\Http\ServerRequest;
use Cake\Routing\Router;
use Cake\TestSuite\TestCase;

/**
 * Class CrudAuthorizeTest
 *
 */
class CrudAuthorizeTest extends TestCase
{

    /**
     * setup
     *
     * @return void
     */
    public function setUp()
    {
        parent::setUp();
        Configure::write('Routing.prefixes', []);
        Router::reload();

        $this->Acl = $this->getMockBuilder('Acl\Controller\Component\AclComponent')
            ->disableOriginalConstructor()
            ->getMock();
        $this->Components = $this->getMockBuilder('Cake\Controller\ComponentRegistry')
            ->getMock();

        $this->auth = new CrudAuthorize($this->Components);
    }

    /**
     * setup the mock acl.
     *
     * @return void
     */
    protected function _mockAcl()
    {
        $this->Components->expects($this->any())
            ->method('load')
            ->with('Acl')
            ->will($this->returnValue($this->Acl));
    }

    /**
     * test authorize() without a mapped action, ensure an error is generated.
     *
     * @expectedException PHPUnit\Framework\Error\Warning
     * @return void
     */
    public function testAuthorizeNoMappedAction()
    {
        $request = new ServerRequest('/posts/foobar');
        $request = $request->withAttribute('params', [
            'controller' => 'posts',
            'action' => 'foobar'
        ]);
        $user = ['User' => ['username' => 'mark']];

        $this->auth->authorize($user, $request);
    }

    /**
     * test check() passing
     *
     * @return void
     */
    public function testAuthorizeCheckSuccess()
    {
        $request = new ServerRequest('posts/index');
        $request = $request->withAttribute('params', [
            'controller' => 'posts',
            'action' => 'index'
        ]);
        $user = ['Users' => ['username' => 'mark']];

        $this->_mockAcl();
        $this->Acl->expects($this->once())
            ->method('check')
            ->with($user, 'Posts', 'read')
            ->will($this->returnValue(true));

        $this->assertTrue($this->auth->authorize($user['Users'], $request));
    }

    /**
     * test check() failing
     *
     * @return void
     */
    public function testAuthorizeCheckFailure()
    {
        $request = new ServerRequest('posts/index');
        $request = $request->withAttribute('params', [
            'controller' => 'posts',
            'action' => 'index'
        ]);
        $user = ['Users' => ['username' => 'mark']];

        $this->_mockAcl();
        $this->Acl->expects($this->once())
            ->method('check')
            ->with($user, 'Posts', 'read')
            ->will($this->returnValue(false));

        $this->assertFalse($this->auth->authorize($user['Users'], $request));
    }

    /**
     * test getting actionMap
     *
     * @return void
     */
    public function testMapActionsGet()
    {
        $result = $this->auth->mapActions();
        $expected = [
            'delete' => 'delete',
            'index' => 'read',
            'add' => 'create',
            'edit' => 'update',
            'view' => 'read',
            'remove' => 'delete'
        ];
        $this->assertEquals($expected, $result);
    }

    /**
     * test adding into mapActions
     *
     * @return void
     */
    public function testMapActionsSet()
    {
        $map = [
            'create' => ['generate'],
            'read' => ['listing', 'show'],
            'update' => ['update'],
            'random' => 'custom'
        ];
        $result = $this->auth->mapActions($map);
        $this->assertNull($result);

        $result = $this->auth->mapActions();
        $expected = [
            'add' => 'create',
            'index' => 'read',
            'edit' => 'update',
            'view' => 'read',
            'delete' => 'delete',
            'remove' => 'delete',
            'generate' => 'create',
            'listing' => 'read',
            'show' => 'read',
            'update' => 'update',
            'random' => 'custom',
        ];
        $this->assertEquals($expected, $result);
    }
}
