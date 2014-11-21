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

use Cake\Cache\Cache;
use Cake\Controller\Component;
use Cake\Core\Configure;
use Cake\ORM\Entity;
use Cake\Utility\Inflector;

/**
 * DbAcl implements an ACL control system in the database. ARO's and ACO's are
 * structured into trees and a linking table is used to define permissions. You
 * can install the schema for DbAcl with the Schema Shell.
 *
 * `$aco` and `$aro` parameters can be slash delimited paths to tree nodes.
 *
 * eg. `controllers/Users/edit`
 *
 * Would point to a tree structure like
 *
 * {{{
 *	controllers
 *		Users
 *			edit
 * }}}
 *
 */
class CachedDbAcl extends DbAcl implements AclInterface {

	private $__cacheConfig = 'default';

/**
 * Constructor
 *
 */
	public function __construct() {
		parent::__construct();

		if (Configure::check('Acl.cacheConfig')) {
			$this->__cacheConfig = Configure::read('Acl.cacheConfig');
		}
	}

/**
 * Checks if the given $aro has access to action $action in $aco
 *
 * @param string $aro ARO The requesting object identifier.
 * @param string $aco ACO The controlled object identifier.
 * @param string $action Action (defaults to *)
 * @return bool Success (true if ARO has access to action in ACO, false otherwise)
 */
	public function check($aro, $aco, $action = "*") {
		$key = $this->_getCacheKey($aro) . '_' . $this->_getCacheKey($aco) . ($action == '*' ? '' : '_' . $action);

		$permission = Cache::remember($key, function () use ($aro, $aco, $action) {
			return $this->Permission->check($aro, $aco, $action) === true ? 'true' : 'false';
		}, $this->__cacheConfig);

		return $permission === 'true';
	}

/**
 * Generates a key string to use for the cache
 *
 * @param string|array|Entity $ref Array with 'model' and 'foreign_key', model object, or string value
 * @return string
 */
	protected function _getCacheKey($ref) {
		if (empty($ref)) {
			return '';
		} elseif (is_string($ref)) {
			return Inflector::slug($ref, '_');
		} elseif (is_object($ref) && $ref instanceof Entity) {
			return $ref->source() . '_' . $ref->id;
		} elseif (is_array($ref) && !(isset($ref['model']) && isset($ref['foreign_key']))) {
			$name = key($ref);
			list(, $alias) = pluginSplit($name);
			return $alias . '_' . $ref[$name]['id'];
		} elseif (is_array($ref)) {
			return $ref['model'] . '_' . $ref['foreign_key'];
		}

		return '';
	}

}
