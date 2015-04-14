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
use Cake\Routing\Router;

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
     * Contains app route prefixes
     *
     * @var array
     */
    protected $prefixes = [];

    /**
     * Contains plugins route prefixes
     *
     * @var array
     */
    protected $pluginPrefixes = [];

    /**
     * List of ACOs found during synchronization
     *
     * @var array
     */
    protected $foundACOs = [];

    /**
     * Start up And load Acl Component / Aco model
     *
     * @return void
     */
    public function startup($controller = null)
    {
        if (!$controller) {
            $controller = new Controller(new Request());
            include CONFIG . 'routes.php';
        }
        $registry = new ComponentRegistry();
        $this->Acl = new AclComponent($registry, Configure::read('Acl'));
        $this->Aco = $this->Acl->Aco;
        $this->controller = $controller;
        $this->_buildPrefixes();
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
            $plugins = Plugin::loaded();
            $this->_processControllers($root);
            $this->_processPrefixes($root);
            $this->_processPlugins($root, $plugins);
        } else {
            $plugin = $params['plugin'];
            if (!Plugin::loaded($plugin)) {
                $this->err(__d('cake_acl', "<error>Plugin {0} not found or not activated.</error>", [$plugin]));
                return false;
            }
            $plugins = [$params['plugin']];
            $this->_processPlugins($root, $plugins);
            $this->foundACOs = array_slice($this->foundACOs, 1, null, true);
        }
        if ($this->_clean) {
            foreach ($this->foundACOs as $parentId => $acosList) {
                $this->_cleaner($parentId, $acosList);
            }
        }
        $this->out(__d('cake_acl', '<success>Aco Update Complete</success>'));
        return true;
    }

    /**
     * Updates the Aco Tree with all App controllers.
     *
     * @param \Acl\Model\Entity\Aco $root The root note of Aco Tree
     * @return void
     */
    protected function _processControllers($root)
    {
        $controllers = $this->getControllerList();
        $this->foundACOs[$root->id] = $this->_updateControllers($root, $controllers, '/:controller');
    }

    /**
     * Updates the Aco Tree with all App route prefixes.
     *
     * @param \Acl\Model\Entity\Aco $root The root note of Aco Tree
     * @return void
     */
    protected function _processPrefixes($root)
    {
        foreach ($this->prefixes as $prefix) {
            $controllers = [];
            if (strpos($prefix['template'], ':action') === false) {
                $controllers = $this->getControllerList(null, $prefix['prefix']);
                $path = str_replace('/:controller', '', $prefix['template']);
                $path = $this->rootNode . $path;
                $pathNode = $this->_checkNode($path, $prefix['prefix'], $root->id);
                $this->foundACOs[$root->id][] = $prefix['prefix'];
                if (isset($this->foundACOs[$pathNode->id])) {
                    $this->foundACOs[$pathNode->id] += $this->_updateControllers($pathNode, $controllers, $prefix['template'], null, $prefix['prefix']);
                } else {
                    $this->foundACOs[$pathNode->id] = $this->_updateControllers($pathNode, $controllers, $prefix['template'], null, $prefix['prefix']);
                }
            }
        }
    }

    /**
     * Updates the Aco Tree with all Plugins.
     *
     * @param \Acl\Model\Entity\Aco $root The root note of Aco Tree
     * @param array $plugins list of App plugins
     * @return void
     */
    protected function _processPlugins($root, array $plugins = [])
    {
        foreach ($plugins as $plugin) {
            $controllers = [];
            $controllers = $this->getControllerList($plugin);
            $path = $this->rootNode . '/' . $plugin;
            $pathNode = $this->_checkNode($path, $plugin, $root->id);
            $this->foundACOs[$root->id][] = $plugin;
            $template = '/' . $plugin . '/:controller';
            if (isset($this->foundACOs[$pathNode->id])) {
                $this->foundACOs[$pathNode->id] += $this->_updateControllers($pathNode, $controllers, $template, $plugin);
            } else {
                $this->foundACOs[$pathNode->id] = $this->_updateControllers($pathNode, $controllers, $template, $plugin);
            }
            if (isset($this->pluginPrefixes[$plugin])) {
                foreach ($this->pluginPrefixes[$plugin] as $prefix) {
                    if (strpos($prefix['template'], ':action') === false) {
                        $parts = explode('/', $prefix['template']);
                        //Case of controllers/:Plugin/:Prefix/:Controller
                        if ($parts[1] == $plugin) {
                            $path = $this->rootNode . '/' . $plugin;
                            $pathNode = $this->_checkNode($path, $plugin, $root->id);
                            $path = $this->rootNode . '/' . $plugin . '/' . $prefix['prefix'];
                            $this->foundACOs[$pathNode->id][] = $prefix['prefix'];
                            $pathNode = $this->_checkNode($path, $prefix['prefix'], $pathNode->id);
                            $controllers = $this->getControllerList($plugin, $prefix['prefix']);
                            if (isset($this->foundACOs[$pathNode->id])) {
                                $this->foundACOs[$pathNode->id] += $this->_updateControllers($pathNode, $controllers, $prefix['template'], $plugin, $prefix['prefix']);
                            } else {
                                $this->foundACOs[$pathNode->id] = $this->_updateControllers($pathNode, $controllers, $prefix['template'], $plugin, $prefix['prefix']);
                            }
                        //Case of controllers/:Prefix/:Plugin/:Controller
                        } else {
                            $path = $this->rootNode . '/' . $prefix['prefix'];
                            $pathNode = $this->_checkNode($path, $prefix['prefix'], $root->id);
                            $this->foundACOs[$pathNode->id][] = $plugin;
                            $path = $this->rootNode . '/' . $prefix['prefix'] . '/' . $plugin;
                            $pathNode = $this->_checkNode($path, $plugin, $pathNode->id);
                            $controllers = $this->getControllerList($plugin, $prefix['prefix']);
                            if (isset($this->foundACOs[$pathNode->id])) {
                                $this->foundACOs[$pathNode->id] += $this->_updateControllers($pathNode, $controllers, $prefix['template'], $plugin, $prefix['prefix']);
                            } else {
                                $this->foundACOs[$pathNode->id] = $this->_updateControllers($pathNode, $controllers, $prefix['template'], $plugin, $prefix['prefix']);
                            }
                        }
                    }
                }
            }
        }
    }

    /**
     * Updates a collection of controllers.
     *
     * @param array $root Array or ACO information for root node.
     * @param array $controllers Array of Controllers
     * @param string $template Template to generate the path of ACO
     * @param string $plugin Name of the plugin you are making controllers for.
     * @param string $prefix Name of the prefix you are making controllers for.
     * @return array
     */
    protected function _updateControllers($root, $controllers, $template, $plugin = null, $prefix = null)
    {
        $dotPlugin = $pluginPath = $plugin;
        if ($plugin) {
            $dotPlugin .= '.';
            $pluginPath .= '/';
        }
        $prefixPath = $prefix;
        if ($prefix) {
            $prefixPath .= '/';
        }
        $appIndex = array_search($plugin . 'AppController', $controllers);
        // look at each controller
        $controllersNames = [];
        foreach ($controllers as $controller) {
            $tmp = explode('/', $controller);
            $controllerName = str_replace('Controller.php', '', array_pop($tmp));
            if ($controllerName == 'App') {
                continue;
            }
            $controllersNames[] = $controllerName;
            $path = str_replace(':controller', $controllerName, $template);
            $path = $this->rootNode . $path;
            $controllerNode = $this->_checkNode($path, $controllerName, $root->id);
            $this->_checkMethods($controller, $controllerName, $controllerNode, $template . '/:action', $pluginPath, $prefixPath);
        }
        return $controllersNames;
    }

    /**
     * Get a list of controllers in the app and plugins.
     *
     * Returns an array of path => import notation.
     *
     * @param string $plugin Name of plugin to get controllers for
     * @param string $prefix Name of prefix to get controllers for
     * @return array
     */
    public function getControllerList($plugin = null, $prefix = null)
    {
        if (!$plugin) {
            $path = App::path('Controller' . (empty($prefix) ? '' : DS . Inflector::camelize($prefix)));
            $dir = new Folder($path[0]);
            $controllers = $dir->find('.*Controller\.php');
        } else {
            $path = App::path('Controller' . (empty($prefix) ? '' : DS . Inflector::camelize($prefix)), $plugin);
            $dir = new Folder($path[0]);
            $controllers = $dir->find('.*Controller\.php');
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
     * @param string $prefixPath The prefix path.
     * @return array
     */
    protected function _getCallbacks($className, $pluginPath = false, $prefixPath = false)
    {
        $callbacks = [];
        $namespace = $this->_getNamespace($className, $pluginPath, $prefixPath);
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
     * @param array $node The node to check.
     * @param string $pluginPath The plugin path to use.
     * @return void
     */
    protected function _checkMethods($className, $controllerName, $node, $template, $pluginPath = false, $prefixPath = null)
    {
        $excludes = $this->_getCallbacks($className, $pluginPath, $prefixPath);
        $baseMethods = get_class_methods(new Controller);
        $namespace = $this->_getNamespace($className, $pluginPath, $prefixPath);
        $actions = get_class_methods(new $namespace);
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
            $path = str_replace(':controller', $controllerName, $template);
            $path = str_replace(':action', $action, $path);
            $path = $this->rootNode . $path;
            $this->_checkNode($path, $action, $node->id);
            $methods[$key] = $action;
        }
        if ($this->_clean) {
            $this->_cleaner($node->id, $methods);
        }
        return true;
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
     * @param string $prefixPath The prefix path.
     * @return string
     */
    protected function _getNamespace($className, $pluginPath = null, $prefixPath = null)
    {
        $namespace = preg_replace('/(.*)Controller\//', '', $className);
        $namespace = preg_replace('/\//', '\\', $namespace);
        $namespace = preg_replace('/\.php/', '', $namespace);
        $prefixPath = preg_replace('/\//', '\\', Inflector::camelize($prefixPath));
        if (!$pluginPath) {
            $appNamespace = Configure::read('App.namespace');
            $namespace = '\\' . $appNamespace . '\\Controller\\' . $prefixPath . $namespace;
        } else {
            $pluginPath = preg_replace('/\//', '\\', $pluginPath);
            $namespace = '\\' . $pluginPath . 'Controller\\' . $prefixPath . $namespace;
        }
        return $namespace;
    }

    /**
     * Build prefixes for App and Plugins based on configured routes
     *
     * @return void
     */
    protected function _buildPrefixes()
    {
        $routes = Router::routes();
        $routesList = [];
        foreach ($routes as $key => $route) {
            if (isset($route->defaults['prefix'])) {
                if (!isset($route->defaults['plugin'])) {
                    $this->prefixes[] = [
                        'prefix' => $route->defaults['prefix'],
                        'template' => str_replace('/*', '', $route->template)
                    ];
                } else {
                    $template = str_replace('/*', '', $route->template);
                    $template = str_replace('/' . strtolower($route->defaults['plugin']) . '/', '/' . $route->defaults['plugin'] . '/', $template);
                    $this->pluginPrefixes[$route->defaults['plugin']][] = [
                        'prefix' => $route->defaults['prefix'],
                        'template' => $template
                    ];
                }
            }
        }
    }

    /**
     * Delete unused ACOs.
     *
     * @param int $parentId Id of the parent node.
     * @param array $preservedItems list of items that will not be erased.
     * @return void
     */
    protected function _cleaner($parentId, $preservedItems = [])
    {
        $nodes = $this->Aco->find()->where(['parent_id' => $parentId]);
        $methodFlip = array_flip($preservedItems);
        foreach ($nodes as $node) {
            if (!isset($methodFlip[$node->alias])) {
                $crumbs = $this->Aco->find('path', ['for' => $node->id, 'order' => 'lft']);
                $path = null;
                foreach ($crumbs as $crumb) {
                    $path .= '/' . $crumb->alias;
                }
                $entity = $this->Aco->get($node->id);
                if ($this->Aco->delete($entity)) {
                    $this->out(__d('cake_acl', 'Deleted Aco node: <warning>{0}</warning> and all children', $path));
                }
            }
        }
    }
}
