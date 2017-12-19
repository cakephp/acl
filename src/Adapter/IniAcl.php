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
namespace Acl\Adapter;

use Acl\AclInterface;
use Cake\Controller\Component;
use Cake\Core\Configure\Engine\IniConfig;
use Cake\Core\InstanceConfigTrait;
use Cake\Utility\Hash;

/**
 * IniAcl implements an access control system using an INI file. An example
 * of the ini file used can be found in /config/acl.ini.
 *
 */
class IniAcl implements AclInterface
{

    /**
     * The Hash::extract() path to the user/aro identifier in the
     * acl.ini file. This path will be used to extract the string
     * representation of a user used in the ini file.
     *
     * @var string
     */
    public $userPath = 'User.username';

    /**
     * Default config for this class
     *
     * @var array
     */
    protected $_defaultConfig = [];

    /**
     * Constructor
     *
     * Sets a few default settings up.
     */
    public function __construct()
    {
        $this->options = [
            'config' => ROOT . DS . 'config/acl',
        ];
    }

    /**
     * Initialize method
     *
     * @param Component $component Component instance.
     * @return void
     */
    public function initialize(Component $component)
    {
        $adapter = $component->getConfig('adapter');
        if (is_array($adapter)) {
            $this->options = $adapter + $this->options;
        }

        $engine = new IniConfig(dirname($this->options['config']) . DS);
        $this->options = $engine->read(basename($this->options['config']));
    }

    /**
     * No op method, allow cannot be done with IniAcl
     *
     * @param string $aro ARO The requesting object identifier.
     * @param string $aco ACO The controlled object identifier.
     * @param string $action Action (defaults to *)
     * @return void
     */
    public function allow($aro, $aco, $action = "*")
    {
    }

    /**
     * No op method, deny cannot be done with IniAcl
     *
     * @param string $aro ARO The requesting object identifier.
     * @param string $aco ACO The controlled object identifier.
     * @param string $action Action (defaults to *)
     * @return void
     */
    public function deny($aro, $aco, $action = "*")
    {
    }

    /**
     * No op method, inherit cannot be done with IniAcl
     *
     * @param string $aro ARO The requesting object identifier.
     * @param string $aco ACO The controlled object identifier.
     * @param string $action Action (defaults to *)
     * @return void
     */
    public function inherit($aro, $aco, $action = "*")
    {
    }

    /**
     * Main ACL check function. Checks to see if the ARO (access request object) has access to the
     * ACO (access control object).Looks at the acl.ini file for permissions
     * (see instructions in /config/acl.ini).
     *
     * @param string $aro ARO
     * @param string $aco ACO
     * @param string $action Action
     * @return bool Success
     */
    public function check($aro, $aco, $action = null)
    {
        $aclConfig = $this->options;

        if (is_array($aro)) {
            $aro = Hash::get($aro, $this->userPath);
        }

        if (isset($aclConfig[$aro]['deny'])) {
            $userDenies = $this->arrayTrim(explode(",", $aclConfig[$aro]['deny']));

            if (array_search($aco, $userDenies)) {
                return false;
            }
        }

        if (isset($aclConfig[$aro]['allow'])) {
            $userAllows = $this->arrayTrim(explode(",", $aclConfig[$aro]['allow']));

            if (array_search($aco, $userAllows)) {
                return true;
            }
        }

        if (isset($aclConfig[$aro]['groups'])) {
            $userGroups = $this->arrayTrim(explode(",", $aclConfig[$aro]['groups']));

            foreach ($userGroups as $group) {
                if (array_key_exists($group, $aclConfig)) {
                    if (isset($aclConfig[$group]['deny'])) {
                        $groupDenies = $this->arrayTrim(explode(",", $aclConfig[$group]['deny']));

                        if (array_search($aco, $groupDenies)) {
                            return false;
                        }
                    }

                    if (isset($aclConfig[$group]['allow'])) {
                        $groupAllows = $this->arrayTrim(explode(",", $aclConfig[$group]['allow']));

                        if (array_search($aco, $groupAllows)) {
                            return true;
                        }
                    }
                }
            }
        }

        return false;
    }

    /**
     * Parses an INI file and returns an array that reflects the
     * INI file's section structure. Double-quote friendly.
     *
     * @param string $filename File
     * @return array INI section structure
     */
    public function readConfigFile($filename)
    {
        $iniFile = new IniConfig(dirname($filename) . DS);

        return $iniFile->read(basename($filename));
    }

    /**
     * Removes trailing spaces on all array elements (to prepare for searching)
     *
     * @param array $array Array to trim
     * @return array Trimmed array
     */
    public function arrayTrim($array)
    {
        foreach ($array as $key => $value) {
            $array[$key] = trim($value);
        }
        array_unshift($array, "");

        return $array;
    }
}
