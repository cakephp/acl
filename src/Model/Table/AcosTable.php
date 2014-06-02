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
 * @since         CakePHP(tm) v 0.2.9
 * @license       http://www.opensource.org/licenses/mit-license.php MIT License
 */
namespace Acl\Model\Table;

use Acl\Model\Table\AclNodesTable;

use Cake\Core\App;

/**
 * Access Control Object
 *
 */
class AcosTable extends AclNodesTable {

/**
 * Model name
 *
 * @var string
 */
	public $name = 'Acos';

/**
 * {@inheritDoc}
 */
	public function initialize(array $config) {
		parent::initialize($config);
		$this->table('acos');
		$this->belongsToMany('ArosTable', [
			'through' => App::className('PermissionsTable', 'Model/Table'),
		]);
		$this->entityClass('Acl\Model\Entity\Aco');
	}

}
