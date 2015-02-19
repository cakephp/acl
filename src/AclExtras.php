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
namespace Acl;

use Acl\Controller\Component\AclComponent;
use Cake\Console\ConsoleIo;
use Cake\Console\Shell;
use Cake\Controller\ComponentRegistry;
use Cake\Controller\Controller;
use Cake\Core\App;
use Cake\Core\Configure;
use Cake\Core\Plugin;
use Cake\Filesystem\Folder;
use Cake\Network\Request;
use Cake\Utility\Inflector;

/**
 * Provides features for additional ACL operations.
 * Can be used in either a CLI or Web context.
 */
class AclExtras
{

    /**
     * Contains instance of AclComponent
     *
     * @var \Acl\Controller\Component\AclComponent
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
    public $dataSource = 'default';

    /**
     * Root node name.
     *
     * @var string
     */
    public $rootNode = 'controllers';

    /**
     * Internal Clean Actions switch
     *
     * @var bool
     */
    protected $_clean = false;

    /**
     * Start up And load Acl Component / Aco model
     *
     * @return void
     */
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

    /**
     * Output a message.
     *
     * Will either use shell->out, or controller->Flash->success()
     *
     * @param string $msg The message to output.
     * @return void
     */
    public function out($msg)
    {
        if (!empty($this->controller->Flash)) {
            $this->controller->Flash->success($msg);
        } else {
            return $this->Shell->out($msg);
        }
    }

    /**
     * Output an error message.
     *
     * Will either use shell->err, or controller->Flash->error()
     *
     * @param string $msg The message to output.
     * @return void
     */
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
     * @param array $params An array of parameters
     * @return void
     */
    public function acoSync($params = [])
    {
        $this->_clean = true;
        $this->acoUpdate($params);
    }

    /**
     * Updates the Aco Tree with new controller actions.
     *
     * @param array $params An array of parameters
     * @return void
     */
    public function acoUpdate($params = [])
    {
        $root = $this->_checkNode($this->rootNode, $this->rootNode, null);
        if (empty($params['plugin'])) {
            $controllers = $this->getControllerList();
            $this->_updateControllers($root, $controllers);
            $plugins = $this->_getPluginList();
        } else {
            $plugin = $params['plugin'];
            if (!Plugin::loaded($plugin)) {
                $this->err(__d('cake_acl', "<error>Plugin {0} not found or not activated.</error>", [$plugin]));
                return false;
            }
            $plugins = [$params['plugin']];
        }
        foreach ($plugins as $plugin) {
            $controllers = $this->getControllerList($plugin);
            $path = $this->rootNode . '/' . $plugin;
            $pluginRoot = $this->_checkNode($path, preg_replace('/\//', '\\', Inflector::camelize($plugin)), $root->id);
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
        $controllersNames = [];
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
                $controllers = array_merge($controllersNames, $this->_getPluginList());
            } else {
                $controllers = $controllersNames;
            }
            $controllerFlip = array_flip($controllers);
            $this->Aco->id = $root->id;
            $controllerNodes = $this->Aco->find()->where(['parent_id' => $root->id]);
            foreach ($controllerNodes as $ctrlNode) {
                $alias = $ctrlNode->alias;
                $name = $alias . 'Controller';
                if (!isset($controllerFlip[$name]) && !isset($controllerFlip[$alias])) {
                    $entity = $this->Aco->get($ctrlNode->id);
                    if ($this->Aco->delete($entity)) {
                        $this->out(__d(
                            'cake_acl',
                            'Deleted <warning>{0}</warning> and all children',
                            $this->rootNode . '/' . $plugin . '/' . $ctrlNode->alias
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
     */
    public function getControllerList($plugin = null)
    {
        if (!$plugin) {
            $path = App::path('Controller');
            $dir = new Folder($path[0]);
            $controllers = $dir->findRecursive('.*Controller\.php');
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
     * @param string $path The path to check
     * @param string $alias The alias to create
     * @param int $parentId The parent id to use when creating.
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
     *
     * @param string $className The class to reflect on.
     * @param string $pluginPath The plugin path.
     * @return array
     */
    protected function _getCallbacks($className, $pluginPath = false)
    {
        $callbacks = [];
        $namespace = $this->_getNamespace($className, $pluginPath);
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
     * @param string $className The classname to check
     * @param string $controllerName The controller name
     * @param array $node
     * @param string $pluginPath
     * @return void
     */
    protected function _checkMethods($className, $controllerName, $node, $pluginPath = false)
    {
        $excludes = $this->_getCallbacks($className, $pluginPath);
        $baseMethods = get_class_methods(new Controller);
        $namespace = $this->_getNamespace($className, $pluginPath);
        $actions = get_class_methods(new $namespace);
        $prefix = $this->_getPrefix($namespace, $pluginPath);
        if ($actions == null) {
            $this->err(__d('cake_acl', 'Unable to get methods for {0}', $className));
            return false;
        }
        $methods = array_diff($actions, $baseMethods);
        $methods = array_diff($methods, $excludes);
        foreach ($methods as $key => $action) {
            if (strpos($action, '_', 0) === 0) {
                continue;
            }
            $path = $this->rootNode . '/' . $pluginPath . $controllerName . '/' . $prefix . $action;
            $this->_checkNode($path, $prefix . $action, $node->id);
            $methods[$key] = $prefix . $action;
        }
        if ($this->_clean) {
            $actionNodes = $this->Aco->find('children', ['for' => $node->id]);
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
     * @return void
     */
    public function recover()
    {
        $type = Inflector::camelize($this->args[0]);
        $this->Acl->{$type}->recover();
        $this->out(__('Tree has been recovered, or tree did not need recovery.'));
    }

    /**
     * Get the namespace for a given class.
     *
     * @param string $className The class you want a namespace for.
     * @param string $pluginPath The plugin path.
     * @return string
     */
    protected function _getNamespace($className, $pluginPath = false)
    {
        $namespace = preg_replace('/(.*)Controller\//', '', $className);
        $namespace = preg_replace('/\//', '\\', $namespace);
        $namespace = preg_replace('/\.php/', '', $namespace);
        if (!$pluginPath) {
            $appNamespace = Configure::read('App.namespace');
            $namespace = '\\' . $appNamespace . '\\Controller\\' . $namespace;
        } else {
            $pluginPath = preg_replace('/\//', '\\', $pluginPath);
            $namespace = '\\' . $pluginPath . 'Controller\\' . $namespace;
        }
        return $namespace;
    }


    /**
     * Get the prefix for a namespace.
     *
     * @param string|null $namespace The namespace to get a prefix from.
     * @return string|null
     */
    protected function _getPrefix($namespace = null)
    {
        if (empty($namespace)) {
            return null;
        }
        $pathArray = explode('\\', $namespace);
        if (count($pathArray) >= 5 && $pathArray[3] !== 'Controller') {
            return Inflector::dasherize($pathArray[3]) . '_';
        }
        return null;
    }

    /**
     * Get the list of plugins in the application.
     *
     * @return array
     */
    protected function _getPluginList()
    {
        return Plugin::loaded();
    }
}
