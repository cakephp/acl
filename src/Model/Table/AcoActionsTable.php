<?php

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

use Cake\Core\App;
use Cake\ORM\Table;

/**
 * Action for Access Control Object
 *
 */
class AcoActionsTable extends Table
{

    /**
     * {@inheritDoc}
     *
     * @param array $config Configuration
     * @return void
     */
    public function initialize(array $config) :void
    {
        $this->belongsTo('Acos', [
            'className' => 'Acl.Acos'
        ]);
    }
}
