<?php
/**
 * PHP configuration based Access Control Object
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

/**
 * Access Control Object
 *
 */
class PhpAco
{

    /**
     * holds internal ACO representation
     *
     * @var array
     */
    protected $_tree = [];

    /**
     * map modifiers for ACO paths to their respective PCRE pattern
     *
     * @var array
     */
    public static $modifiers = [
        '*' => '.*',
    ];

    /**
     * Constructor
     *
     * @param array $rules Rules array
     */
    public function __construct(array $rules = [])
    {
        foreach (['allow', 'deny'] as $type) {
            if (empty($rules[$type])) {
                $rules[$type] = [];
            }
        }

        $this->build($rules['allow'], $rules['deny']);
    }

    /**
     * return path to the requested ACO with allow and deny rules attached on each level
     *
     * @param string $aco ACO string
     * @return array
     */
    public function path($aco)
    {
        $aco = $this->resolve($aco);
        $path = [];
        $level = 0;
        $root = $this->_tree;
        $stack = [[$root, 0]];

        while (!empty($stack)) {
            list($root, $level) = array_pop($stack);

            if (empty($path[$level])) {
                $path[$level] = [];
            }

            foreach ($root as $node => $elements) {
                $pattern = '/^' . str_replace(array_keys(static::$modifiers), array_values(static::$modifiers), $node) . '$/';

                if ($node == $aco[$level] || preg_match($pattern, $aco[$level])) {
                    // merge allow/denies with $path of current level
                    foreach (['allow', 'deny'] as $policy) {
                        if (!empty($elements[$policy])) {
                            if (empty($path[$level][$policy])) {
                                $path[$level][$policy] = [];
                            }
                            $path[$level][$policy] = array_merge($path[$level][$policy], $elements[$policy]);
                        }
                    }

                    // traverse
                    if (!empty($elements['children']) && isset($aco[$level + 1])) {
                        array_push($stack, [$elements['children'], $level + 1]);
                    }
                }
            }
        }

        return $path;
    }

    /**
     * allow/deny ARO access to ARO
     *
     * @param string $aro ARO string
     * @param string $aco ACO string
     * @param string $action Action string
     * @param string $type access type
     * @return void
     */
    public function access($aro, $aco, $action, $type = 'deny')
    {
        $aco = $this->resolve($aco);
        $depth = count($aco);
        $root = $this->_tree;
        $tree = &$root;

        foreach ($aco as $i => $node) {
            if (!isset($tree[$node])) {
                $tree[$node] = [
                    'children' => [],
                ];
            }

            if ($i < $depth - 1) {
                $tree = &$tree[$node]['children'];
            } else {
                if (empty($tree[$node][$type])) {
                    $tree[$node][$type] = [];
                }

                $tree[$node][$type] = array_merge(is_array($aro) ? $aro : [$aro], $tree[$node][$type]);
            }
        }

        $this->_tree = &$root;
    }

    /**
     * resolve given ACO string to a path
     *
     * @param string $aco ACO string
     * @return array path
     */
    public function resolve($aco)
    {
        if (is_array($aco)) {
            return array_map('strtolower', $aco);
        }

        // strip multiple occurrences of '/'
        $aco = preg_replace('#/+#', '/', $aco);
        // make case insensitive
        $aco = ltrim(strtolower($aco), '/');

        return array_filter(array_map('trim', explode('/', $aco)));
    }

    /**
     * build a tree representation from the given allow/deny informations for ACO paths
     *
     * @param array $allow ACO allow rules
     * @param array $deny ACO deny rules
     * @return void
     */
    public function build(array $allow, array $deny = [])
    {
        $this->_tree = [];

        foreach ($allow as $dotPath => $aros) {
            if (is_string($aros)) {
                $aros = array_map('trim', explode(',', $aros));
            }

            $this->access($aros, $dotPath, null, 'allow');
        }

        foreach ($deny as $dotPath => $aros) {
            if (is_string($aros)) {
                $aros = array_map('trim', explode(',', $aros));
            }

            $this->access($aros, $dotPath, null, 'deny');
        }
    }
}
