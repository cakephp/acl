<?php
/**
 * PHP configuration based Access Request Object
 *
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
namespace Acl\Adapter\Utility;

use Cake\Utility\Hash;
use Cake\Utility\Inflector;

/**
 * Access Request Object
 *
 */
class PhpAro
{

    /**
     * role to resolve to when a provided ARO is not listed in
     * the internal tree
     *
     * @var string
     */
    const DEFAULT_ROLE = 'Role/default';

    /**
     * map external identifiers. E.g. if
     *
     * ['Users' => ['username' => 'jeff', 'role' => 'editor']]
     *
     * is passed as an ARO to one of the methods of AclComponent, PhpAcl
     * will check if it can be resolved to an User or a Role defined in the
     * configuration file.
     *
     * @var array
     * @see app/Config/acl.php
     */
    public $map = [
        'User' => 'Users/username',
        'Role' => 'Users/role',
    ];

    /**
     * aliases to map
     *
     * @var array
     */
    public $aliases = [];

    /**
     * internal ARO representation
     *
     * @var array
     */
    protected $_tree = [];

    /**
     * Constructor
     *
     * @param array $aro Aro instance
     * @param array $map Map
     * @param array $aliases Aliases
     */
    public function __construct(array $aro = [], array $map = [], array $aliases = [])
    {
        if (!empty($map)) {
            $this->map = $map;
        }

        $this->aliases = $aliases;
        $this->build($aro);
    }

    /**
     * From the perspective of the given ARO, walk down the tree and
     * collect all inherited AROs levelwise such that AROs from different
     * branches with equal distance to the requested ARO will be collected at the same
     * index. The resulting array will contain a prioritized list of (list of) roles ordered from
     * the most distant AROs to the requested one itself.
     *
     * @param string|array $aro An ARO identifier
     * @return array prioritized AROs
     */
    public function roles($aro)
    {
        $aros = [];
        $aro = $this->resolve($aro);
        $stack = [[$aro, 0]];

        while (!empty($stack)) {
            list($element, $depth) = array_pop($stack);
            $aros[$depth][] = $element;

            foreach ($this->_tree as $node => $children) {
                if (in_array($element, $children)) {
                    array_push($stack, [$node, $depth + 1]);
                }
            }
        }

        return array_reverse($aros);
    }

    /**
     * resolve an ARO identifier to an internal ARO string using
     * the internal mapping information.
     *
     * @param string|array $aro ARO identifier (Users.jeff, ['Users' => ...], etc)
     * @return string internal aro string (e.g. Users/jeff, Role/default)
     */
    public function resolve($aro)
    {
        foreach ($this->map as $aroGroup => $map) {
            list($model, $field) = explode('/', $map, 2);
            $mapped = '';

            if (is_array($aro)) {
                if (isset($aro['model']) && isset($aro['foreign_key']) && $aro['model'] === $aroGroup) {
                    $mapped = $aroGroup . '/' . $aro['foreign_key'];
                } elseif (isset($aro[$model][$field])) {
                    $mapped = $aroGroup . '/' . $aro[$model][$field];
                } elseif (isset($aro[$field])) {
                    $mapped = $aroGroup . '/' . $aro[$field];
                }
            } elseif (is_string($aro)) {
                $aro = ltrim($aro, '/');

                if (strpos($aro, '/') === false) {
                    $mapped = $aroGroup . '/' . $aro;
                } else {
                    list($aroModel, $aroValue) = explode('/', $aro, 2);

                    $aroModel = Inflector::camelize($aroModel);

                    if ($aroModel === $model || $aroModel === $aroGroup) {
                        $mapped = $aroGroup . '/' . $aroValue;
                    }
                }
            }

            if (isset($this->_tree[$mapped])) {
                return $mapped;
            }

            // is there a matching alias defined (e.g. Role/1 => Role/admin)?
            if (!empty($this->aliases[$mapped])) {
                return $this->aliases[$mapped];
            }
        }

        return static::DEFAULT_ROLE;
    }

    /**
     * adds a new ARO to the tree
     *
     * @param array $aro one or more ARO records
     * @return void
     */
    public function addRole(array $aro)
    {
        foreach ($aro as $role => $inheritedRoles) {
            if (!isset($this->_tree[$role])) {
                $this->_tree[$role] = [];
            }

            if (!empty($inheritedRoles)) {
                if (is_string($inheritedRoles)) {
                    $inheritedRoles = array_map('trim', explode(',', $inheritedRoles));
                }

                foreach ($inheritedRoles as $dependency) {
                    // detect cycles
                    $roles = $this->roles($dependency);

                    if (in_array($role, Hash::flatten($roles))) {
                        $path = '';

                        foreach ($roles as $roleDependencies) {
                            $path .= implode('|', (array)$roleDependencies) . ' -> ';
                        }

                        trigger_error(sprintf('cycle detected when inheriting %s from %s. Path: %s', $role, $dependency, $path . $role));
                        continue;
                    }

                    if (!isset($this->_tree[$dependency])) {
                        $this->_tree[$dependency] = [];
                    }

                    $this->_tree[$dependency][] = $role;
                }
            }
        }
    }

    /**
     * adds one or more aliases to the internal map. Overwrites existing entries.
     *
     * @param array $alias alias from => to (e.g. Role/13 -> Role/editor)
     * @return void
     */
    public function addAlias(array $alias)
    {
        $this->aliases = $alias + $this->aliases;
    }

    /**
     * build an ARO tree structure for internal processing
     *
     * @param array $aros array of AROs as key and their inherited AROs as values
     * @return void
     */
    public function build(array $aros)
    {
        $this->_tree = [];
        $this->addRole($aros);
    }
}
