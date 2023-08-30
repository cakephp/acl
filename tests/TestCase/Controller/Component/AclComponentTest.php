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

namespace Acl\Test\TestCase\Controller\Component;

use Acl\Controller\Component\AclComponent;
use Cake\Controller\ComponentRegistry;
use Cake\Core\Configure;
use Cake\TestSuite\TestCase;

/**
 * Test Case for AclComponent
 *
 */
class AclComponentTest extends TestCase
{

    /**
     * setUp method
     *
     * @return void
     */
    public function setUp() :void
    {
        parent::setUp();
        if (!class_exists('MockAclImplementation', false)) {
            $this->getMockBuilder('Acl\AclInterface')
                ->setMockClassName('MockAclImplementation')
                ->getMock();
        }
        Configure::write('Acl.classname', '\MockAclImplementation');
        $Collection = new ComponentRegistry();
        $this->Acl = new AclComponent($Collection);
    }

    /**
     * tearDown method
     *
     * @return void
     */
    public function tearDown() :void
    {
        parent::tearDown();
        unset($this->Acl);
    }

    /**
     * test that constructor throws an exception when Acl.classname is a
     * non-existent class
     *
     * @return void
     */
    public function testConstrutorException()
    {
        $this->expectException(\Cake\Core\Exception\Exception::class);
        Configure::write('Acl.classname', 'AclClassNameThatDoesNotExist');
        $Collection = new ComponentRegistry();
        new AclComponent($Collection);
    }

    /**
     * test that adapter() allows control of the internal implementation AclComponent uses.
     *
     * @return void
     */
    public function testAdapter()
    {
        $Adapter = $this->getMockBuilder('Acl\AclInterface')->getMock();
        $Adapter->expects($this->once())->method('initialize')->with($this->Acl);

        $this->assertNull($this->Acl->adapter($Adapter));
        $this->assertEquals($this->Acl->adapter(), $Adapter, 'Returned object is different %s');
    }

    /**
     * test that adapter() whines when the class does not implement AclInterface
     *
     * @return void
     */
    public function testAdapterException()
    {
        $this->expectException(\Cake\Core\Exception\Exception::class);
        $thing = new \StdClass();
        $this->Acl->adapter($thing);
    }
}
