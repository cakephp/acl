<?php
/**
 * ACL behavior class.
 *
 * Enables objects to easily tie into an ACL system
 *
 * CakePHP :  Rapid Development Framework (http://cakephp.org)
 * Copyright (c) Cake Software Foundation, Inc. (http://cakefoundation.org)
 *
 * Licensed under The MIT License
 * For full copyright and license information, please see the LICENSE.txt
 * Redistributions of files must retain the above copyright notice.
 *
 * @copyright     Copyright (c) Cake Software Foundation, Inc. (http://cakefoundation.org)
 * @link          http://cakephp.org CakePHP Project
 * @since         CakePHP v 1.2.0.4487
 * @license       http://www.opensource.org/licenses/mit-license.php MIT License
 */
namespace Acl\Model\Behavior;

use Cake\Core\App;
use Cake\Error;
use Cake\Event\Event;
use Cake\ORM\Behavior;
use Cake\ORM\Entity;
use Cake\ORM\Table;
use Cake\ORM\TableRegistry;
use Cake\Utility\ClassRegistry;
use Cake\Utility\Hash;
use Cake\Utility\Inflector;

/**
 * ACL behavior
 *
 * Enables objects to easily tie into an ACL system
 *
 * @link http://book.cakephp.org/2.0/en/core-libraries/behaviors/acl.html
 */
class AclBehavior extends Behavior {

	protected $_table = null;

/**
 * Maps ACL type options to ACL models
 *
 * @var array
 */
	protected $_typeMaps = array('requester' => 'Aro', 'controlled' => 'Aco', 'both' => array('Aro', 'Aco'));

/**
 * Sets up the configuration for the model, and loads ACL models if they haven't been already
 *
 * @param Model $model
 * @param array $config
 * @return void
 */
	public function __construct(Table $model, array $config = []) {
		$this->_table = $model;
		if (isset($config[0])) {
			$config['type'] = $config[0];
			unset($config[0]);
		}
		if (isset($config['type'])) {
			$config['type'] = strtolower($config['type']);
		}
		parent::__construct($model, $config);

		$types = $this->_typeMaps[$this->config()['type']];

		if (!is_array($types)) {
			$types = array($types);
		}
		foreach ($types as $type) {
			$alias = Inflector::pluralize($type);
			$className = App::className($alias . 'Table', 'Model/Table');
			$config = [];
			if (!TableRegistry::exists($alias)) {
				$config = ['className' => $className];
			}
			$model->{$type} = TableRegistry::get($alias, $config);
		}

		if (!method_exists($model->entityClass(), 'parentNode')) {
			trigger_error(__d('cake_dev', 'Callback %s not defined in %s', 'parentNode()', $model->entityClass()), E_USER_WARNING);
		}
	}

/**
 * Retrieves the Aro/Aco node for this model
 *
 * @param Model $model
 * @param string|array|Model $ref Array with 'model' and 'foreign_key', model object, or string value
 * @param string $type Only needed when Acl is set up as 'both', specify 'Aro' or 'Aco' to get the correct node
 * @return array
 * @link http://book.cakephp.org/2.0/en/core-libraries/behaviors/acl.html#node
 */
	public function node($ref = null, $type = null) {
		if (empty($type)) {
			$type = $this->_typeMaps[$this->config('type')];
			if (is_array($type)) {
				trigger_error(__d('cake_dev', 'AclBehavior is setup with more then one type, please specify type parameter for node()'), E_USER_WARNING);
				return null;
			}
		}
		if (empty($ref)) {
			throw new Error\Exception(__d('cake_dev', 'ref parameter must be a string or an Entity'));
		}
		return $this->_table->{$type}->node($ref);
	}

/**
 * Creates a new ARO/ACO node bound to this record
 *
 * @param Model $model
 * @param boolean $created True if this is a new record
 * @param array $options Options passed from Model::save().
 * @return void
 */
	public function afterSave(Event $event, Entity $entity) {
		$model = $event->subject();
		$types = $this->_typeMaps[$this->config('type')];
		if (!is_array($types)) {
			$types = array($types);
		}
		foreach ($types as $type) {
			$parent = $entity->parentNode();
			if (!empty($parent)) {
				$parent = $this->node($parent, $type)->first();
			}
			$data = array(
				'parent_id' => isset($parent->id) ? $parent->id : null,
				'model' => $model->alias(),
				'foreign_key' => $entity->id,
			);

			if (!$entity->isNew()) {
				$node = $this->node($entity, $type)->first();
				$data['id'] = isset($node->id) ? $node->id : null;
			}
			$newData = $model->{$type}->newEntity($data);
			$saved = $model->{$type}->save($newData);
		}
	}

/**
 * Destroys the ARO/ACO node bound to the deleted record
 *
 * @param Model $model
 * @return void
 */
	public function afterDelete(Event $event, Entity $entity) {
		$types = $this->_typeMaps[$this->config('type')];
		if (!is_array($types)) {
			$types = array($types);
		}
		foreach ($types as $type) {
			$node = $this->node($entity, $type)->toArray();
			if (!empty($node)) {
				$event->subject()->{$type}->delete($node[0]);
			}
		}
	}

}
