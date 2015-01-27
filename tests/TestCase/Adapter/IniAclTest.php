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
use Cake\TestSuite\TestCase;

/**
 * Test case for the IniAcl implementation
 *
 */
class IniAclTest extends TestCase
{

    /**
     * testIniCheck method
     *
     * @return void
     */
    public function testCheck()
    {
        $iniFile = TEST_APP . 'TestApp/Config/acl';

        $Ini = new IniAcl();
        $Ini->config = $Ini->readConfigFile($iniFile);

        $this->assertFalse($Ini->check('admin', 'ads'));
        $this->assertTrue($Ini->check('admin', 'posts'));

        $this->assertTrue($Ini->check('jenny', 'posts'));
        $this->assertTrue($Ini->check('jenny', 'ads'));

        $this->assertTrue($Ini->check('paul', 'posts'));
        $this->assertFalse($Ini->check('paul', 'ads'));

        $this->assertFalse($Ini->check('nobody', 'comments'));
    }

    /**
     * check should accept a user array.
     *
     * @return void
     */
    public function testCheckArray()
    {
        $iniFile = TEST_APP . 'TestApp/Config/acl';

        $Ini = new IniAcl();
        $Ini->config = $Ini->readConfigFile($iniFile);
        $Ini->userPath = 'User.username';

        $user = [
            'User' => ['username' => 'admin']
        ];
        $this->assertTrue($Ini->check($user, 'posts'));
    }
}
