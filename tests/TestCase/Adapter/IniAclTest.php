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

use Acl\Adapter\IniAcl;
use Acl\Controller\Component\AclComponent;
use Cake\Controller\ComponentRegistry;
use Cake\Core\Configure;
use Cake\TestSuite\TestCase;

/**
 * Test case for the IniAcl implementation
 *
 */
class IniAclTest extends TestCase
{

    /**
     * Setup
     *
     * @return void
     */
    public function setUp()
    {
        parent::setUp();
        Configure::write('Acl.classname', 'IniAcl');
        $Collection = new ComponentRegistry();
        $this->IniAcl = new IniAcl();
        $this->Acl = new AclComponent($Collection, [
            'adapter' => [
                'config' => TEST_APP . 'config/acl',
            ],
        ]);
    }

    /**
     * testIniCheck method
     *
     * @return void
     */
    public function testCheck()
    {
        $this->assertFalse($this->Acl->check('admin', 'ads'));
        $this->assertTrue($this->Acl->check('admin', 'posts'));

        $this->assertTrue($this->Acl->check('jenny', 'posts'));
        $this->assertTrue($this->Acl->check('jenny', 'ads'));

        $this->assertTrue($this->Acl->check('paul', 'posts'));
        $this->assertFalse($this->Acl->check('paul', 'ads'));

        $this->assertFalse($this->Acl->check('nobody', 'comments'));
    }

    /**
     * check should accept a user array.
     *
     * @return void
     */
    public function testCheckArray()
    {
        $user = [
            'User' => ['username' => 'admin']
        ];
        $this->assertTrue($this->Acl->check($user, 'posts'));
    }
}
