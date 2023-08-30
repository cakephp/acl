<?php
declare(strict_types=1);

/**
 * CakePHP(tm) : Rapid Development Framework (https://cakephp.org)
 * Copyright (c) Cake Software Foundation, Inc. (https://cakefoundation.org)
 *
 * Licensed under The MIT License
 * For full copyright and license information, please see the LICENSE.txt
 * Redistributions of files must retain the above copyright notice.
 *
 * @copyright     Copyright (c) Cake Software Foundation, Inc. (https://cakefoundation.org)
 * @link          https://cakephp.org CakePHP(tm) Project
 * @license       https://www.opensource.org/licenses/mit-license.php MIT License
 */

namespace Acl\Model\Table;

/**
 * Access Control Object
 */
class AcosTable extends AclNodesTable
{
    /**
     * {@inheritDoc}
     *
     * @param array $config Config
     * @return void
     */
    public function initialize(array $config): void
    {
        parent::initialize($config);
        $this->setAlias('Acos');
        $this->setTable('acos');
        $this->addBehavior('Tree', ['type' => 'nested']);

        $this->belongsToMany('Aros', [
            'through' => 'Acl.Permissions',
            'className' => 'Acl.Aros',
        ]);
        $this->hasMany('AcoChildren', [
            'className' => 'Acl.Acos',
            'foreignKey' => 'parent_id',
        ]);
        $this->setEntityClass('Acl.Aco');
    }
}
