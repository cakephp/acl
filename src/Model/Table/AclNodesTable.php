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
namespace Acl\Model\Table;

use Cake\Core\Configure;
use Cake\Core\Exception;
use Cake\ORM\Entity;
use Cake\ORM\Table;
use Cake\ORM\TableRegistry;

/**
 * ACL Nodes
 *
 */
class AclNodesTable extends Table
{

    /**
     * {@inheritDoc}
     *
     * @return void
     */
    public static function defaultConnectionName()
    {
        return Configure::read('Acl.database');
    }

    /**
     * Retrieves the Aro/Aco node for this model
     *
     * @param string|array|Model $ref Array with 'model' and 'foreign_key', model object, or string value
     * @return array Node found in database
     * @throws \Cake\Core\Exception\Exception when binding to a model that doesn't exist.
     */
    public function node($ref = null)
    {
        $db = $this->connection();
        $type = $this->alias();
        $table = $this->table();
        $result = null;

        if (empty($ref)) {
            return null;
        } elseif (is_string($ref)) {
            $path = explode('/', $ref);
            $start = $path[0];
            unset($path[0]);

            $queryData = [
                'conditions' => [
                    "{$type}.lft" . ' <= ' . "{$type}0.lft",
                    "{$type}.rght" . ' >= ' . "{$type}0.rght",
                ],
                'fields' => ['id', 'parent_id', 'model', 'foreign_key', 'alias'],
                'join' => [[
                        'table' => $table,
                        'alias' => "{$type}0",
                        'type' => 'INNER',
                        'conditions' => ["{$type}0.alias" => $start]
                ]],
                'order' => "{$type}.lft" . ' DESC'
            ];

            foreach ($path as $i => $alias) {
                $j = $i - 1;

                $queryData['join'][] = [
                    'table' => $table,
                    'alias' => "{$type}{$i}",
                    'type' => 'INNER',
                    'conditions' => [
                        "{$type}{$i}.lft" . ' > ' . "{$type}{$j}.lft",
                        "{$type}{$i}.rght" . ' < ' . "{$type}{$j}.rght",
                        "{$type}{$i}.alias" => $alias,
                        "{$type}{$j}.id" . ' = ' . "{$type}{$i}.parent_id"
                    ]
                ];

                $queryData['conditions'] = [
                    'or' => [
                        "{$type}.lft" . ' <= ' . "{$type}0.lft" . ' AND ' . "{$type}.rght" . ' >= ' . "{$type}0.rght",
                        "{$type}.lft" . ' <= ' . "{$type}{$i}.lft" . ' AND ' . "{$type}.rght" . ' >= ' . "{$type}{$i}.rght"
                    ]
                ];
            }
            $query = $this->find('all', $queryData);
            $result = $query->toArray();
            $path = array_values($path);

            if (
                !isset($result[0]) ||
                (!empty($path) && $result[0]->alias != $path[count($path) - 1]) ||
                (empty($path) && $result[0]->alias != $start)
            ) {
                return false;
            }
        } elseif (is_object($ref) && $ref instanceof Entity) {
            $ref = ['model' => $ref->source(), 'foreign_key' => $ref->id];
        } elseif (is_array($ref) && !(isset($ref['model']) && isset($ref['foreign_key']))) {
            $name = key($ref);
            list(, $alias) = pluginSplit($name);

            $bindTable = TableRegistry::get($name);
            $entityClass = $bindTable->entityClass();

            if ($entityClass) {
                $entity = new $entityClass();
            }

            if (empty($entity)) {
                throw new Exception\Exception(__d('cake_dev', "Entity class {0} not found in AclNode::node() when trying to bind {1} object", [$type, $this->alias()]));
            }

            $tmpRef = null;
            if (method_exists($entity, 'bindNode')) {
                $tmpRef = $entity->bindNode($ref);
            }
            if (empty($tmpRef)) {
                $ref = [
                    'model' => $alias,
                    'foreign_key' => $ref[$name][$this->primaryKey()]
                ];
            } else {
                if (is_string($tmpRef)) {
                    return $this->node($tmpRef);
                }
                $ref = $tmpRef;
            }
        }
        if (is_array($ref)) {
            if (is_array(current($ref)) && is_string(key($ref))) {
                $name = key($ref);
                $ref = current($ref);
            }
            foreach ($ref as $key => $val) {
                if (strpos($key, $type) !== 0 && strpos($key, '.') === false) {
                    unset($ref[$key]);
                    $ref["{$type}0.{$key}"] = $val;
                }
            }
            $queryData = [
                'conditions' => $ref,
                'fields' => ['id', 'parent_id', 'model', 'foreign_key', 'alias'],
                'join' => [
                    [
                        'table' => $table,
                        'alias' => "{$type}0",
                        'type' => 'INNER',
                        'conditions' => [
                            "{$type}.lft" . ' <= ' . "{$type}0.lft",
                            "{$type}.rght" . ' >= ' . "{$type}0.rght",
                        ]
                    ]
                ],
                'order' => "{$type}.lft" . ' DESC',
            ];
            $query = $this->find('all', $queryData);

            if ($query->count() == 0) {
                throw new Exception\Exception(__d('cake_dev', "AclNode::node() - Couldn't find {0} node identified by \"{1}\"", [$type, print_r($ref, true)]));
            }
        }
        return $query;
    }
}
