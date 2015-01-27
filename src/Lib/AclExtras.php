<?php
/**
 * Acl Extras.
 *
 * Licensed under The MIT License
 * Redistributions of files must retain the above copyright notice.
 *
 * @copyright Copyright 2008-2013, Mark Story.
 * @link http://mark-story.com
 * @author Mark Story <mark@mark-story.com>
 * @license http://www.opensource.org/licenses/mit-license.php The MIT License
 */
namespace Acl\Lib;

use Cake\Console\ConsoleIo;
use Cake\Console\Shell;
use Acl\Controller\Component\AclComponent;
use Cake\Controller\ComponentRegistry;
use Cake\Controller\Controller;
use Cake\Network\Request;
use Cake\Core\Configure;
use Cake\Core\App;
use Cake\Utility\Inflector;
use Cake\Filesystem\Folder;


/**
 * Shell for ACO extras
 *
 * @package		acl_extras
 * @subpackage	acl_extras.Console.Command
 */
class AclExtras
{

/**
 * Contains instance of AclComponent
 *
 * @var AclComponent
 * @access public
 */
    public $Acl;

/**
 * Contains arguments parsed from the command line.
 *
 * @var array
 * @access public
 */
    public $args;

/**
 * Contains database source to use
 *
 * @var string
 * @access public
 */
    public $dataSource = 'default';

/**
 * Root node name.
 *
 * @var string
 **/
    public $rootNode = 'controllers';

/**
 * Internal Clean Actions switch
 *
 * @var boolean
 **/
    protected $_clean = false;

/**
 * Start up And load Acl Component / Aco model
 *
 * @return void
 **/
    public function startup($controller = null)
    {
        if (!$controller) {
            $controller = new Controller(new Request());
        }
        $registry = new ComponentRegistry();
        $this->Acl = new AclComponent($registry, Configure::read('Acl'));
        $this->Aco = $this->Acl->Aco;
        $this->controller = $controller;
    }

    public function out($msg)
    {
        if (!empty($this->controller->Flash)) {
            $this->controller->Flash->success($msg);
        } else {
            return $this->Shell->out($msg);
        }
    }

    public function err($msg)
    {
        if (!empty($this->controller->Flash)) {
            $this->controller->Flash->error($msg);
        } else {
            return $this->Shell->err($msg);
        }
    }

/**
 * Sync the ACO table
 *
 * @return void
 **/
    public function aco_sync($params = [])
    {
        $this->_clean = true;
        $this->aco_update($params);
    }

/**
 * Updates the Aco Tree with new controller actions.
 *
 * @return void
 **/
    public function aco_update($params = [])
    {
        $root = $this->_checkNode($this->rootNode, $this->rootNode, null);
        if (empty($params['plugin'])) {
            $controllers = $this->getControllerList();
            $this->_updateControllers($root, $controllers);
            $plugins = $this->get_plugin_list();
        } else {
            $plugin = $params['plugin'];
            if (!in_array($plugin, App::objects('plugin')) || !CakePlugin::loaded($plugin)) {
                $this->err(__d('cake_acl', "<error>Plugin {0} not found or not activated.</error>", [$plugin]));
                return false;
            }
            $plugins = array($params['plugin']);
        }
        foreach ($plugins as $plugin) {
            $controllers = $this->getControllerList($plugin);
            $path = $this->rootNode . '/' . $plugin;
            $pluginRoot = $this->_checkNode($path, $plugin, $root->id);
            $this->_updateControllers($pluginRoot, $controllers, $plugin);
        }
        $this->out(__d('cake_acl', '<success>Aco Update Complete</success>'));
        return true;
    }

/**
 * Updates a collection of controllers.
 *
 * @param array $root Array or ACO information for root node.
 * @param array $controllers Array of Controllers
 * @param string $plugin Name of the plugin you are making controllers for.
 * @return void
 */
    protected function _updateControllers($root, $controllers, $plugin = null)
    {
        $dotPlugin = $pluginPath = $plugin;
        if ($plugin) {
            $dotPlugin .= '.';
            $pluginPath .= '/';
        }
        $appIndex = array_search($plugin . 'AppController', $controllers);
        // look at each controller
        $controllersNames=array();
        foreach ($controllers as $controller) {
            $tmp = explode('/', $controller);
            $controllerName = str_replace('Controller.php', '', array_pop($tmp));
            $controllersNames[] = $controllerName;
            $path = $this->rootNode . '/' . $pluginPath . $controllerName;
            $controllerNode = $this->_checkNode($path, $controllerName, $root->id);
            $this->_checkMethods($controller, $controllerName, $controllerNode, $pluginPath);
        }
        if ($this->_clean) {
            if (!$plugin) {
                $controllers = array_merge($controllersNames, $this->get_plugin_list());
            } else {
                $controllers = $controllersNames;
            }
            $controllerFlip = array_flip($controllers);
            $this->Aco->id = $root->id;
            $controllerNodes = $this->Aco->find()->where(['parent_id'=>$root->id]);
            foreach ($controllerNodes as $ctrlNode) {
                $alias = $ctrlNode->alias;
                $name = $alias . 'Controller';
                if (!isset($controllerFlip[$name]) && !isset($controllerFlip[$alias])) {
                    $entity = $this->Aco->get($ctrlNode->id);
                    if ($this->Aco->delete($entity)) {
                        $this->out(__d('cake_acl',
                            'Deleted <warning>{0}</warning> and all children',
                            $this->rootNode . '/' .$plugin.'/'. $ctrlNode->alias
                        ));
                    }
                }
            }
        }
    }

/**
 * Get a list of controllers in the app and plugins.
 *
 * Returns an array of path => import notation.
 *
 * @param string $plugin Name of plugin to get controllers for
 * @return array
 **/
    public function getControllerList($plugin = null)
    {
        if (!$plugin) {
            $path = App::path('Controller');
            $dir = new Folder($path[0]);
            $controllers = $dir->findRecursive('.*Controller\.php');
            unset($controllers[0]);
        } else {
            $path = App::path('Controller', $plugin);
            $dir = new Folder($path[0]);
            $controllers = $dir->findRecursive('.*Controller\.php');
        }
        return $controllers;
    }

/**
 * Check a node for existance, create it if it doesn't exist.
 *
 * @param string $path
 * @param string $alias
 * @param int $parentId
 * @return array Aco Node array
 */
    protected function _checkNode($path, $alias, $parentId = null)
    {
        $node = $this->Aco->node($path);
        if (!$node) {
            $data = [
                'parent_id' => $parentId,
                'model' => null,
                'alias' => $alias,
            ];
            $entity = $this->Aco->newEntity($data);
            $node = $this->Aco->save($entity);
            $this->out(__d('cake_acl', 'Created Aco node: <success>{0}</success>', $path));
        } else {
            $node = $node->first();
        }
        return $node;
    }

/**
 * Get a list of registered callback methods
 */
    protected function _getCallbacks($className, $pluginPath = false)
    {
        $callbacks = array();
        $namespace = $this->get_namesapce($className, $pluginPath);
        $reflection = new \ReflectionClass($namespace);
        if ($reflection->isAbstract()) {
            return $callbacks;
        }
        try {
            $method = $reflection->getMethod('implementedEvents');
        } catch (ReflectionException $e) {
            return $callbacks;
        }
        if (version_compare(phpversion(), '5.4', '>=')) {
            $object = $reflection->newInstanceWithoutConstructor();
        } else {
            $object = unserialize(
                sprintf('O:%d:"%s":0:{}', strlen($className), $className)
            );
        }
        $implementedEvents = $method->invoke($object);
        foreach ($implementedEvents as $event => $callable) {
            if (is_string($callable)) {
                $callbacks[] = $callable;
            }
            if (is_array($callable) && isset($callable['callable'])) {
                $callbacks[] = $callable['callable'];
            }
        }
        return $callbacks;
    }

/**
 * Check and Add/delete controller Methods
 *
 * @param string $controller
 * @param array $node
 * @param string $plugin Name of plugin
 * @return void
 */
    protected function _checkMethods($className, $controllerName, $node, $pluginPath = false)
    {
        $excludes = $this->_getCallbacks($className, $pluginPath);
        $baseMethods = get_class_methods(new Controller);
        $namespace = $this->get_namesapce($className, $pluginPath);
        $actions = get_class_methods(new $namespace);
        $prefix = $this->get_prefix($namespace, $pluginPath);
        if ($actions == null) {
            $this->err(__d('cake_acl', 'Unable to get methods for {0}', $className));
            return false;
        }
        $methods = array_diff($actions, $baseMethods);
        $methods = array_diff($methods, $excludes);
        foreach ($methods as $key=>$action) {
            if (strpos($action, '_', 0) === 0) {
                continue;
            }
            $path = $this->rootNode . '/' . $pluginPath . $controllerName . '/' . $prefix.$action;
            $this->_checkNode($path, $prefix.$action, $node->id);
            $methods[$key]=$prefix.$action;
        }
        if ($this->_clean) {
            $actionNodes = $this->Aco->find('children', ['for'=>$node->id]);
            $methodFlip = array_flip($methods);
            foreach ($actionNodes as $action) {
                if (!isset($methodFlip[$action->alias])) {
                    $entity = $this->Aco->get($action->id);
                    if ($this->Aco->delete($entity)) {
                        $path = $this->rootNode . '/' . $controllerName . '/' . $action->alias;
                        $this->out(__d('cake_acl', 'Deleted Aco node: <warning>{0}</warning>', $path));
                    }
                }
            }
        }
        return true;
    }

/**
 * Verify a Acl Tree
 *
 * @param string $type The type of Acl Node to verify
 * @access public
 * @return void
 */
    public function verify()
    {
        $type = Inflector::camelize($this->args[0]);
        $return = $this->Acl->{$type}->verify();
        if ($return === true) {
            $this->out(__('<success>Tree is valid and strong</success>'));
        } else {
            $this->err(print_r($return, true));
            return false;
        }
    }

/**
 * Recover an Acl Tree
 *
 * @param string $type The Type of Acl Node to recover
 * @access public
 * @return void
 */
    public function recover()
    {
        $type = Inflector::camelize($this->args[0]);
        $this->Acl->{$type}->recover();
        $this->out(__('Tree has been recovered, or tree did not need recovery.'));
    }

    protected function get_namesapce($className, $pluginPath = false)
    {
        $namespace = preg_replace('/(.*)Controller\//', '', $className);
        $namespace = preg_replace('/\//', '\\', $namespace);
        $namespace = preg_replace('/\.php/', '', $namespace);
        if (!$pluginPath) {
            $namespace = '\App\Controller\\'.$namespace;
        } else {
            $pluginPath = preg_replace('/\//', '\\', $pluginPath);
            $namespace = '\\'.$pluginPath.'Controller\\'.$namespace;
        }
        return $namespace;
    }
    protected function get_prefix($namespace = null)
    {
        if (empty($namespace)) {
            return null;
        }
        $path_array = explode('\\', $namespace);
        if (count($path_array)>=5) {
            return Inflector::dasherize($path_array[3]).'_';
        }
        return null;
    }
    protected function get_plugin_list()
    {
        $path = App::path('Plugin');
        $dir = new Folder($path[0]);
        $plugins = $dir->read();
        return $plugins[0];
    }
}
