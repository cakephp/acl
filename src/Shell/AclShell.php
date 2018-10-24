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
namespace Acl\Shell;

use Acl\Controller\Component\AclComponent;
use Cake\Console\Shell;
use Cake\Controller\ComponentRegistry;
use Cake\Controller\Controller;
use Cake\Core\App;
use Cake\Core\Configure;
use Cake\ORM\TableRegistry;
use Cake\Utility\Hash;

/**
 * Shell for ACL management. This console is known to have issues with zend.ze1_compatibility_mode
 * being enabled. Be sure to turn it off when using this shell.
 *
 */
class AclShell extends Shell
{

    /**
     * Contains instance of AclComponent
     *
     * @var AclComponent
     */
    public $Acl;

    /**
     * Contains arguments parsed from the command line.
     *
     * @var array
     */
    public $args;

    /**
     * Contains database source to use
     *
     * @var string
     */
    public $connection = 'default';

    /**
     * Override startup of the Shell
     *
     * @return void
     */
    public function startup()
    {
        parent::startup();
        if (isset($this->params['connection'])) {
            $this->connection = $this->params['connection'];
        }

        $class = Configure::read('Acl.classname');
        if (strpos($class, '\\') === false && strpos($class, '.') === false) {
            $className = App::classname('Acl.' . $class, 'Adapter');
        } else {
            $className = App::classname($class, 'Adapter');
        }
        if ($class !== 'DbAcl' &&
            !is_subclass_of($className, 'Acl\Adapter\DbAcl')
        ) {
            $out = "--------------------------------------------------\n";
            $out .= __d('cake_acl', 'Error: Your current CakePHP configuration is set to an ACL implementation other than DB.') . "\n";
            $out .= __d('cake_acl', 'Please change your core config to reflect your decision to use DbAcl before attempting to use this script') . "\n";
            $out .= "--------------------------------------------------\n";
            $out .= __d('cake_acl', 'Current ACL Classname: {0}', [$class]) . "\n";
            $out .= "--------------------------------------------------\n";
            $this->err($out);
            $this->_stop();
        }

        if ($this->command) {
            try {
                TableRegistry::getTableLocator()->get('Aros')->getSchema();
                TableRegistry::getTableLocator()->remove('Aros');
            } catch (\Cake\Database\Exception $e) {
                $this->out(__d('cake_acl', 'Acl database tables not found. To create them, run:'));
                $this->out();
                $this->out('  bin/cake Migrations.migrations migrate -p Acl');
                $this->out();
                $this->_stop();

                return;
            }

            $registry = new ComponentRegistry();
            $this->Acl = new AclComponent($registry);
        }
    }

    /**
     * Override main() for help message hook
     *
     * @return void
     */
    public function main()
    {
        $this->out($this->OptionParser->help());
    }

    /**
     * Creates an ARO/ACO node
     *
     * @return void
     */
    public function create()
    {
        extract($this->_dataVars());

        $class = ucfirst($this->args[0]);
        $parent = $this->parseIdentifier($this->args[1]);

        if (!empty($parent) && $parent !== '/' && $parent !== 'root') {
            $parent = $this->_getNodeId($class, $parent);
        } else {
            $parent = null;
        }

        $data = $this->parseIdentifier($this->args[2]);
        if (is_string($data) && $data !== '/') {
            $data = ['alias' => $data];
        } elseif (is_string($data)) {
            $this->error(__d('cake_acl', '/ can not be used as an alias!') . __d('cake_acl', "	/ is the root, please supply a sub alias"));
        }

        $data['parent_id'] = $parent;
        $entity = $this->Acl->{$class}->newEntity($data);
        if ($this->Acl->{$class}->save($entity)) {
            $this->out(__d('cake_acl', "<success>New {0}</success> {1} created.", [$class, $this->args[2]]), 2);
        } else {
            $this->err(__d('cake_acl', "There was a problem creating a new {0} {1}.", [$class, $this->args[2]]));
        }
    }

    /**
     * Delete an ARO/ACO node.
     *
     * @return void
     */
    public function delete()
    {
        extract($this->_dataVars());

        $identifier = $this->parseIdentifier($this->args[1]);
        $nodeId = $this->_getNodeId($class, $identifier);
        $entity = $this->Acl->{$class}->newEntity(['id' => $nodeId]);
        $entity->isNew(false);

        if (!$this->Acl->{$class}->delete($entity)) {
            $this->error(__d('cake_acl', 'Node Not Deleted') . __d('cake_acl', 'There was an error deleting the {0}. Check that the node exists.', [$class]) . "\n");
        }
        $this->out(__d('cake_acl', '<success>{0} deleted.</success>', [$class]), 2);
    }

    /**
     * Set parent for an ARO/ACO node.
     *
     * @return void
     */
    public function setParent()
    {
        extract($this->_dataVars());
        $target = $this->parseIdentifier($this->args[1]);
        $parent = $this->parseIdentifier($this->args[2]);

        $data = [
            'id' => $this->_getNodeId($class, $target),
            'parent_id' => $this->_getNodeId($class, $parent)
        ];
        $entity = $this->Acl->{$class}->newEntity($data);
        if (!$this->Acl->{$class}->save($entity)) {
            $this->out(__d('cake_acl', 'Error in setting new parent. Please make sure the parent node exists, and is not a descendant of the node specified.'));
        } else {
            $this->out(__d('cake_acl', 'Node parent set to {0}', [$this->args[2]]) . "\n");
        }
    }

    /**
     * Get path to specified ARO/ACO node.
     *
     * @return void
     */
    public function getPath()
    {
        extract($this->_dataVars());
        $identifier = $this->parseIdentifier($this->args[1]);

        $id = $this->_getNodeId($class, $identifier);
        $nodes = $this->Acl->{$class}->find('path', ['for' => $id]);

        if (empty($nodes) || $nodes->count() === 0) {
            $this->error(
                __d('cake_acl', "Supplied Node {0} not found", [$this->args[1]]),
                __d('cake_acl', 'No tree returned.')
            );
        }
        $this->out(__d('cake_acl', 'Path:'));
        $this->hr();
        $rows = $nodes->enableHydration(false)->toArray();
        for ($i = 0, $len = count($rows); $i < $len; $i++) {
            $this->_outputNode($class, $rows[$i], $i);
        }
    }

    /**
     * Outputs a single node, Either using the alias or Model.key
     *
     * @param string $class Class name that is being used.
     * @param array $node Array of node information.
     * @param int $indent indent level.
     * @return void
     */
    protected function _outputNode($class, $node, $indent)
    {
        $indent = str_repeat('  ', $indent);
        if ($node['alias']) {
            $this->out($indent . "[" . $node['id'] . "] " . $node['alias']);
        } else {
            $this->out($indent . "[" . $node['id'] . "] " . $node['model'] . '.' . $node['foreign_key']);
        }
    }

    /**
     * Check permission for a given ARO to a given ACO.
     *
     * @return void
     */
    public function check()
    {
        extract($this->_getParams());

        if ($this->Acl->check($aro, $aco, $action)) {
            $this->out(__d('cake_acl', '{0} is <success>allowed</success>.', [$aroName]));
        } else {
            $this->out(__d('cake_acl', '{0} is <error>not allowed</error>.', [$aroName]));
        }
    }

    /**
     * Grant permission for a given ARO to a given ACO.
     *
     * @return void
     */
    public function grant()
    {
        extract($this->_getParams());

        if ($this->Acl->allow($aro, $aco, $action)) {
            $this->out(__d('cake_acl', 'Permission <success>granted</success>.'));
        } else {
            $this->out(__d('cake_acl', 'Permission was <error>not granted</error>.'));
        }
    }

    /**
     * Deny access for an ARO to an ACO.
     *
     * @return void
     */
    public function deny()
    {
        extract($this->_getParams());

        if ($this->Acl->deny($aro, $aco, $action)) {
            $this->out(__d('cake_acl', 'Permission denied.'));
        } else {
            $this->out(__d('cake_acl', 'Permission was not denied.'));
        }
    }

    /**
     * Set an ARO to inherit permission to an ACO.
     *
     * @return void
     */
    public function inherit()
    {
        extract($this->_getParams());

        if ($this->Acl->inherit($aro, $aco, $action)) {
            $this->out(__d('cake_acl', 'Permission inherited.'));
        } else {
            $this->out(__d('cake_acl', 'Permission was not inherited.'));
        }
    }

    /**
     * Show a specific ARO/ACO node.
     *
     * @return void
     */
    public function view()
    {
        extract($this->_dataVars());

        $alias = $this->Acl->{$class}->getAlias();
        if (isset($this->args[1])) {
            $identity = $this->parseIdentifier($this->args[1]);

            $topNode = $this->Acl->{$class}->find('all', [
                'conditions' => [$alias . '.id' => $this->_getNodeId($class, $identity)]
            ])->first();

            $nodes = $this->Acl->{$class}->find('all', [
                'conditions' => [
                    $alias . '.lft >=' => $topNode->lft,
                    $alias . '.lft <=' => $topNode->rght
                ],
                'order' => $alias . '.lft ASC'
            ]);
        } else {
            $nodes = $this->Acl->{$class}->find('all', ['order' => $alias . '.lft ASC']);
        }

        if ($nodes->count() === 0) {
            if (isset($this->args[1])) {
                $this->abort(__d('cake_acl', '{0} not found', [$this->args[1]]));
            } elseif (isset($this->args[0])) {
                $this->abort(__d('cake_acl', '{0} not found', [$this->args[0]]));
            }
        }
        $this->out($class . ' tree:');
        $this->hr();

        $stack = [];
        $last = null;

        $rows = $nodes->enableHydration(false)->toArray();
        foreach ($rows as $n) {
            $stack[] = $n;
            if (!empty($last)) {
                $end = end($stack);
                if ($end['rght'] > $last) {
                    foreach ($stack as $k => $v) {
                        $end = end($stack);
                        if ($v['rght'] < $end['rght']) {
                            unset($stack[$k]);
                        }
                    }
                }
            }
            $last = $n['rght'];
            $count = count($stack);

            $this->_outputNode($class, $n, $count);
        }
        $this->hr();
    }

    /**
     * Gets the option parser instance and configures it.
     *
     * @return ConsoleOptionParser
     */
    public function getOptionParser()
    {
        $parser = parent::getOptionParser();

        $type = [
            'choices' => ['aro', 'aco'],
            'required' => true,
            'help' => __d('cake_acl', 'Type of node to create.')
        ];

        $parser->setDescription(
            __d('cake_acl', 'A console tool for managing the DbAcl')
        )->addSubcommand('create', [
            'help' => __d('cake_acl', 'Create a new ACL node'),
            'parser' => [
                'description' => __d('cake_acl', 'Creates a new ACL object <node> under the parent'),
                'epilog' => __d('cake_acl', 'You can use `root` as the parent when creating nodes to create top level nodes.'),
                'arguments' => [
                    'type' => $type,
                    'parent' => [
                        'help' => __d('cake_acl', 'The node selector for the parent.'),
                        'required' => true
                    ],
                    'alias' => [
                        'help' => __d('cake_acl', 'The alias to use for the newly created node.'),
                        'required' => true
                    ]
                ]
            ]
        ])->addSubcommand('delete', [
            'help' => __d('cake_acl', 'Deletes the ACL object with the given <node> reference'),
            'parser' => [
                'description' => __d('cake_acl', 'Delete an ACL node.'),
                'arguments' => [
                    'type' => $type,
                    'node' => [
                        'help' => __d('cake_acl', 'The node identifier to delete.'),
                        'required' => true,
                    ]
                ]
            ]
        ])->addSubcommand('setparent', [
            'help' => __d('cake_acl', 'Moves the ACL node under a new parent.'),
            'parser' => [
                'description' => __d('cake_acl', 'Moves the ACL object specified by <node> beneath <parent>'),
                'arguments' => [
                    'type' => $type,
                    'node' => [
                        'help' => __d('cake_acl', 'The node to move'),
                        'required' => true,
                    ],
                    'parent' => [
                        'help' => __d('cake_acl', 'The new parent for <node>.'),
                        'required' => true
                    ]
                ]
            ]
        ])->addSubcommand('getpath', [
            'help' => __d('cake_acl', 'Print out the path to an ACL node.'),
            'parser' => [
                'description' => [
                    __d('cake_acl', "Returns the path to the ACL object specified by <node>."),
                    __d('cake_acl', "This command is useful in determining the inheritance of permissions for a certain object in the tree.")
                ],
                'arguments' => [
                    'type' => $type,
                    'node' => [
                        'help' => __d('cake_acl', 'The node to get the path of'),
                        'required' => true,
                    ]
                ]
            ]
        ])->addSubcommand('check', [
            'help' => __d('cake_acl', 'Check the permissions between an ACO and ARO.'),
            'parser' => [
                'description' => [
                    __d('cake_acl', 'Use this command to check ACL permissions.')
                ],
                'arguments' => [
                    'aro' => ['help' => __d('cake_acl', 'ARO to check.'), 'required' => true],
                    'aco' => ['help' => __d('cake_acl', 'ACO to check.'), 'required' => true],
                    'action' => ['help' => __d('cake_acl', 'Action to check'), 'default' => 'all']
                ]
            ]
        ])->addSubcommand('grant', [
            'help' => __d('cake_acl', 'Grant an ARO permissions to an ACO.'),
            'parser' => [
                'description' => [
                    __d('cake_acl', 'Use this command to grant ACL permissions. Once executed, the ARO specified (and its children, if any) will have ALLOW access to the specified ACO action (and the ACO\'s children, if any).')
                ],
                'arguments' => [
                    'aro' => ['help' => __d('cake_acl', 'ARO to grant permission to.'), 'required' => true],
                    'aco' => ['help' => __d('cake_acl', 'ACO to grant access to.'), 'required' => true],
                    'action' => ['help' => __d('cake_acl', 'Action to grant'), 'default' => 'all']
                ]
            ]
        ])->addSubcommand('deny', [
            'help' => __d('cake_acl', 'Deny an ARO permissions to an ACO.'),
            'parser' => [
                'description' => [
                    __d('cake_acl', 'Use this command to deny ACL permissions. Once executed, the ARO specified (and its children, if any) will have DENY access to the specified ACO action (and the ACO\'s children, if any).')
                ],
                'arguments' => [
                    'aro' => ['help' => __d('cake_acl', 'ARO to deny.'), 'required' => true],
                    'aco' => ['help' => __d('cake_acl', 'ACO to deny.'), 'required' => true],
                    'action' => ['help' => __d('cake_acl', 'Action to deny'), 'default' => 'all']
                ]
            ]
        ])->addSubcommand('inherit', [
            'help' => __d('cake_acl', 'Inherit an ARO\'s parent permissions.'),
            'parser' => [
                'description' => [
                    __d('cake_acl', "Use this command to force a child ARO object to inherit its permissions settings from its parent.")
                ],
                'arguments' => [
                    'aro' => ['help' => __d('cake_acl', 'ARO to have permissions inherit.'), 'required' => true],
                    'aco' => ['help' => __d('cake_acl', 'ACO to inherit permissions on.'), 'required' => true],
                    'action' => ['help' => __d('cake_acl', 'Action to inherit'), 'default' => 'all']
                ]
            ]
        ])->addSubcommand('view', [
            'help' => __d('cake_acl', 'View a tree or a single node\'s subtree.'),
            'parser' => [
                'description' => [
                    __d('cake_acl', "The view command will return the ARO or ACO tree."),
                    __d('cake_acl', "The optional node parameter allows you to return"),
                    __d('cake_acl', "only a portion of the requested tree.")
                ],
                'arguments' => [
                    'type' => $type,
                    'node' => ['help' => __d('cake_acl', 'The optional node to view the subtree of.')]
                ]
            ]
        ])->setEpilog(
            [
                'Node and parent arguments can be in one of the following formats:',
                '',
                ' - <model>.<id> - The node will be bound to a specific record of the given model.',
                '',
                ' - <alias> - The node will be given a string alias (or path, in the case of <parent>)',
                "   i.e. 'John'. When used with <parent>, this takes the form of an alias path,",
                "   i.e. <group>/<subgroup>/<parent>.",
                '',
                "To add a node at the root level, enter 'root' or '/' as the <parent> parameter."
            ]
        );

        return $parser;
    }

    /**
     * Checks that given node exists
     *
     * @return bool Success
     */
    public function nodeExists()
    {
        if (!isset($this->args[0]) || !isset($this->args[1])) {
            return false;
        }
        $dataVars = $this->_dataVars($this->args[0]);
        extract($dataVars);
        $key = is_numeric($this->args[1]) ? $dataVars['secondary_id'] : 'alias';
        $conditions = [$class . '.' . $key => $this->args[1]];
        $possibility = $this->Acl->{$class}->find('all', compact('conditions'));
        if (empty($possibility)) {
            $this->error(__d('cake_acl', '{0} not found', [$this->args[1]]), __d('cake_acl', 'No tree returned.'));
        }

        return $possibility;
    }

    /**
     * Parse an identifier into Model.foreignKey or an alias.
     * Takes an identifier determines its type and returns the result as used by other methods.
     *
     * @param string $identifier Identifier to parse
     * @return mixed a string for aliases, and an array for model.foreignKey
     */
    public function parseIdentifier($identifier)
    {
        if (preg_match('/^([\w]+)\.(.*)$/', $identifier, $matches)) {
            return [
                'model' => $matches[1],
                'foreign_key' => $matches[2],
            ];
        }

        return $identifier;
    }

    /**
     * Get the node for a given identifier. $identifier can either be a string alias
     * or an array of properties to use in AcoNode::node()
     *
     * @param string $class Class type you want (Aro/Aco)
     * @param string|array $identifier A mixed identifier for finding the node.
     * @return int|null Integer of NodeId. Will trigger an error if nothing is found.
     */
    protected function _getNodeId($class, $identifier)
    {
        $node = $this->Acl->{$class}->node($identifier);
        if (empty($node) || $node->count() === 0) {
            if (is_array($identifier)) {
                $identifier = var_export($identifier, true);
            }
            $this->error(__d('cake_acl', 'Could not find node using reference "{0}"', [$identifier]));

            return null;
        }

        return $node->first()->id;
    }

    /**
     * get params for standard Acl methods
     *
     * @return array aro, aco, action
     */
    protected function _getParams()
    {
        $aro = is_numeric($this->args[0]) ? intval($this->args[0]) : $this->args[0];
        $aco = is_numeric($this->args[1]) ? intval($this->args[1]) : $this->args[1];
        $aroName = $aro;
        $acoName = $aco;

        if (is_string($aro)) {
            $aro = $this->parseIdentifier($aro);
        }
        if (is_string($aco)) {
            $aco = $this->parseIdentifier($aco);
        }
        $action = '*';
        if (isset($this->args[2]) && !in_array($this->args[2], ['', 'all'])) {
            $action = $this->args[2];
        }

        return compact('aro', 'aco', 'action', 'aroName', 'acoName');
    }

    /**
     * Build data parameters based on node type
     *
     * @param string $type Node type  (ARO/ACO)
     * @return array Variables
     */
    protected function _dataVars($type = null)
    {
        if (!$type) {
            $type = $this->args[0];
        }
        $vars = [];
        $class = ucwords($type);
        $vars['secondary_id'] = (strtolower($class) === 'aro') ? 'foreign_key' : 'object_id';
        $vars['data_name'] = $type;
        $vars['table_name'] = $type . 's';
        $vars['class'] = $class;

        return $vars;
    }
}
