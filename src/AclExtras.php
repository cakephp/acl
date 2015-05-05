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
use Cake\Controller\ComponentRegistry;
use Cake\Controller\Controller;
use Cake\Core\App;
use Cake\Core\Configure;
use Cake\Core\Plugin;
use Cake\Filesystem\Folder;
use Cake\Network\Request;
use Cake\Routing\Router;
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
    * App routes.
    *
    * @var array
    */
    protected $routes = [];

    /**
    * App Plugins.
    *
    * @var array
    */
    protected $plugins = [];

    /**
    * App Prefixes.
    *
    * @var array
    */
    protected $prefixes = [];

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
        if (isset($params['plugin'])) {
            $plugin = $params['plugin'];
            $this->plugins[] = $plugin;
            $pluginPath = Plugin::path($plugin);
            $routesFile = $pluginPath  . 'config' . DS . 'routes.php';
            if (is_file($routesFile)) {
                include $routesFile;
            }
        } else {
            $plugin = null;
            $this->plugins = Plugin::loaded();
        }
        $this->routes = $this->getRoutes(Router::routes(), $plugin);
        $controllersNames = [];
        foreach ($this->routes as $route) {
            if ($plugin && $route['plugin'] != $plugin) {
                continue;
            }
            $controllersNames[$route['template']] = $this->_updateControllers($route);
            $this->_checkMethods($route);
        }
        if ($this->_clean) {
            $plugins = [];
            foreach ($this->plugins as $pluginName) {
                $plugins[] = $this->_pluginAlias($pluginName);
            }
            foreach ($this->routes as $key => $route) {
                if ($plugin && $route['plugin'] != $plugin) {
                    continue;
                }
                $node = $this->getNode($route);
                $controllers = $controllersNames[$route['template']];
                if (empty($route['plugin'])) {
                    $controllers = array_merge($controllers, $plugins);
                }
                if (empty($route['prefix'])) {
                    $controllers = array_merge($controllers, $this->prefixes);
                }
                $this->_cleaner($node->id, $controllers);
            }
        }
        return true;
    }

    /**
     * Updates a collection of controllers.
     *
     * @param array $route Array
     * @return array
     */
    protected function _updateControllers($route)
    {
        $rootNode = $this->getNode($route);
        $controllers = $this->getControllerList($route['plugin'], $route['prefix']);
        $controllersNames = [];
        foreach ($controllers as $controller) {
            $tmp = explode('/', $controller);
            $controllerName = str_replace('Controller.php', '', array_pop($tmp));
            if($controllerName == 'App'){
                continue;
            }
            $controllersNames[] = $controllerName;
            $path = $this->rootNode . $route['template'] . '/' . $controllerName;
            $controllerNode = $this->_checkNode($path, $controllerName, $rootNode->id);
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
        $path = App::path('Controller' . (empty($prefix) ? '' : DS . Inflector::camelize($prefix)), $plugin);
        $dir = new Folder($path[0]);
        $controllers = $dir->find('.*Controller\.php');
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
     * @param array $route
     * @return void
     */
    protected function _checkMethods($route)
    {
        $prefixPath = $pluginPath = false;
        if ($route['prefix']) {
            $prefixPath = $route['prefix'] . '/';
        }
        if ($route['plugin']) {
            $pluginPath = $route['plugin'] . '/';
        }
        $controllers = $this->getControllerList($route['plugin'], $route['prefix']);
        foreach ($controllers as $controller) {
            $tmp = explode('/', $controller);
            $controllerName = str_replace('Controller.php', '', array_pop($tmp));
            if ($controllerName == 'App') {
                continue;
            }
            $namespace = $this->_getNamespace($controller, $pluginPath, $prefixPath);
            $excludes = $this->_getCallbacks($controller, $pluginPath, $prefixPath);
            $baseMethods = get_class_methods(new Controller);
            $namespace = $this->_getNamespace($controller, $pluginPath, $prefixPath);
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
                $path = $this->rootNode . $route['template'] . '/' . $controllerName;
                $node = $this->Aco->node($path);
                $node = $node->first();
                $path = $path . '/' . $action;
                $this->_checkNode($path, $action, $node->id);
                $methods[$key] = $action;
            }
            if ($this->_clean) {
                $this->_cleaner($node->id, $methods);
            }
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
     * Get all app and plugins routes
     *
     * @param Cake\Routing\Route\Route $routes List of all loaded routes.
     * @param string $plugin if param plugin was passed.
     * @return array
     */
    protected function getRoutes($routes = [], $plugin = null)
    {
        $once = [];
        $returnRoutes = [];
        $i=0;
        foreach ($routes as $route) {
            if (strpos($route->template, ':controller') !== false) {
                $template = $route->template;
                if (isset($route->defaults['plugin']) && !empty($route->defaults['plugin'])) {
                    $pluginSearch = '/' . Inflector::underscore($route->defaults['plugin']) . '/';
                    $pluginReplace = '/' . Inflector::camelize($route->defaults['plugin']) . '/';
                    $template = str_replace($pluginSearch, $pluginReplace, $template);
                }
                if (isset($route->defaults['prefix']) && !empty($route->defaults['prefix'])) {
                    $prefixSearch = '/' . Inflector::underscore($route->defaults['prefix']) . '/';
                    $prefixReplace = '/' . Inflector::camelize($route->defaults['prefix']) . '/';
                    $template = str_replace($prefixSearch, $prefixReplace, $template);
                }
                $template = str_replace(['/:controller', '/:action', '/*'], '', $template);
                if (isset($route->defaults['prefix'])) {
                    $this->prefixes[] = Inflector::camelize($route->defaults['prefix']);
                }
                if (!isset($once[$template])) {
                    if ($plugin) {
                        if ($route->defaults['plugin'] != $plugin) {
                            continue;
                        }
                        $returnRoutes[$i]['template'] = $template;
                        $returnRoutes[$i]['prefix'] = isset($route->defaults['prefix']) ? $route->defaults['prefix'] : null;
                        $returnRoutes[$i++]['plugin'] = isset($route->defaults['plugin']) ? $route->defaults['plugin'] : null;
                        $once[$template] = true;
                    } else {
                        $returnRoutes[$i]['template'] = $template;
                        $returnRoutes[$i]['prefix'] = isset($route->defaults['prefix']) ? $route->defaults['prefix'] : null;
                        $returnRoutes[$i++]['plugin'] = isset($route->defaults['plugin']) ? $route->defaults['plugin'] : null;
                        $once[$template] = true;
                    }
                }
            }
        }
        /* generating default routes for loaded Plugins */
        foreach ($this->plugins as $plugin) {
            $template = '/'. $this->_pluginAlias($plugin);
            if (!isset($once[$template])) {
                $returnRoutes[$i]['template'] = $template;
                $returnRoutes[$i]['prefix'] = null;
                $returnRoutes[$i++]['plugin'] = $plugin;
                $once[$template] = true;
            }
        }
        return $returnRoutes;
    }

    /**
     * Returns the aliased name for the plugin (Needed in order to correctly handle nested plugins)
     *
     * @param string $plugin The name of the plugin to alias
     * @return string
     */
    protected function _pluginAlias($plugin)
    {
        return preg_replace('/\//', '\\', Inflector::camelize($plugin));
    }

    /**
     *  Return node for the path and create all necessary nodes
     *
     * @param array $route
     * @return array Aco Node array
     */
    protected function getNode($route) {
        if(is_array($route)) {
            $template = $route['template'];
        } else {
            $template = $route;
        }
        $pathArray = explode('/', $template);
        $path = $this->rootNode;
        foreach ($pathArray as $part) {
            $path .= $part . '/';
            if(!isset($lastNode)){
                $lastNode = $this->_checkNode(substr($path, 0, -1), substr($path, 0, -1));
            } else {
                $lastNode = $this->_checkNode(substr($path, 0, -1), $part, $lastNode->id);
            }
        }
        return $lastNode;
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
        $preservedItemsFlip = array_flip($preservedItems);
        foreach ($nodes as $node) {
            if (!isset($preservedItemsFlip[$node->alias])) {
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
