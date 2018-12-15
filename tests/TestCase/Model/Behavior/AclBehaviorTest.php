<?php
/**
 * CakePHP : Rapid Development Framework (http://cakephp.org)
 * Copyright (c) Cake Software Foundation, Inc. (http://cakefoundation.org)
 *
 * Licensed under The MIT License
 * For full copyright and license information, please see the LICENSE.txt
 * Redistributions of files must retain the above copyright notice.
 *
 * @copyright     Copyright (c) Cake Software Foundation, Inc. (http://cakefoundation.org)
 * @link          http://cakephp.org CakePHP Project
 * @license       http://www.opensource.org/licenses/mit-license.php MIT License
 */
namespace Cake\Acl\Test\TestCase\Model\Behavior;

use Acl\Model\Behavior\AclBehavior;
use Acl\Model\Entity\Aco;
use Acl\Model\Entity\Aro;
use Acl\Model\Table\AclNodesTable;
use Acl\Model\Table\AcosTable;
use Acl\Model\Table\ArosTable;
use Cake\Core\App;
use Cake\Core\Configure;
use Cake\ORM\Entity;
use Cake\ORM\Table;
use Cake\ORM\TableRegistry;
use Cake\TestSuite\Fixture\TestModel;
use Cake\TestSuite\TestCase;

/**
 * Test Person class - self joined model
 *
 */
class AclPeople extends Table
{

    /**
     * initialize
     *
     * @param array $config Configuration array
     * @return void
     */
    public function initialize(array $config)
    {
        $this->setTable('people');
        $this->setEntityClass(__NAMESPACE__ . '\AclPerson');
        $this->addBehavior('Acl', ['both']);
        $this->belongsTo('Mother', [
            'className' => __NAMESPACE__ . '\AclPeople',
            'foreignKey' => 'mother_id',
        ]);
        $this->hasMany('Child', [
            'className' => __NAMESPACE__ . '\AclPeople',
            'foreignKey' => 'mother_id'
        ]);
    }
}

class AclPerson extends Entity
{

    /**
     * parentNode method
     *
     * @return void
     */
    public function parentNode()
    {
        if (!$this->id) {
            return null;
        }
        if (isset($this->mother_id)) {
            $motherId = $this->mother_id;
        } else {
            $People = TableRegistry::getTableLocator()->get('AclPeople');
            $person = $People->find('all', ['fields' => ['mother_id']])->where(['id' => $this->id])->first();
            $motherId = $person->mother_id;
        }
        if (!$motherId) {
            return null;
        }

        return ['AclPeople' => ['id' => $motherId]];
    }
}

/**
 * AclUsers class
 *
 */
class AclUsers extends Table
{

    /**
     * initialize
     *
     * @param array $config Configuration array
     * @return void
     */
    public function initialize(array $config)
    {
        $this->setTable('users');
        $this->setEntityClass(__NAMESPACE__ . '\AclUser');
        $this->addBehavior('Acl', ['type' => 'requester']);
    }
}

class AclUser extends Entity
{

    /**
     * parentNode
     *
     * @return null|string
     */
    public function parentNode()
    {
        return null;
    }
}
/**
 * AclPost class
 */
class AclPosts extends Table
{

    /**
     * initialize
     *
     * @param array $config Configuration array
     * @return void
     */
    public function initialize(array $config)
    {
        $this->setTable('posts');
        $this->setEntityClass(__NAMESPACE__ . '\AclPost');
        $this->addBehavior('Acl', ['type' => 'Controlled']);
    }
}

class AclPost extends Entity
{

    /**
     * parentNode
     *
     * @return null|string
     */
    public function parentNode()
    {
        return null;
    }
}

/**
 * AclBehaviorTest class
 */
class AclBehaviorTest extends TestCase
{

    /**
     * Aco property
     *
     * @var Aco
     */
    public $Aco;

    /**
     * Aro property
     *
     * @var Aro
     */
    public $Aro;

    /**
     * fixtures property
     *
     * @var array
     */
    public $fixtures = [
        'app.Acos',
        'app.Aros',
        'app.ArosAcos',
        'app.People',
        'core.Posts',
        'core.Users',
    ];

    /**
     * Set up the test
     *
     * @return void
     */
    public function setUp()
    {
        parent::setUp();
        Configure::write('Acl.database', 'test');

        TableRegistry::getTableLocator()->clear();
        $this->Aco = TableRegistry::getTableLocator()->get('Acos', [
            'className' => App::className('Acl.AcosTable', 'Model/Table'),
        ]);
        $this->Aro = TableRegistry::getTableLocator()->get('Aros', [
            'className' => App::className('Acl.ArosTable', 'Model/Table'),
        ]);

        TableRegistry::getTableLocator()->get('AclUsers', [
            'className' => __NAMESPACE__ . '\AclUsers',
        ]);
        TableRegistry::getTableLocator()->get('AclPeople', [
            'className' => __NAMESPACE__ . '\AclPeople',
        ]);
        TableRegistry::getTableLocator()->get('AclPosts', [
            'className' => __NAMESPACE__ . '\AclPosts',
        ]);
    }

    /**
     * tearDown method
     *
     * @return void
     */
    public function tearDown()
    {
        parent::tearDown();
        unset($this->Aro, $this->Aco);
    }

    /**
     * Test Setup of AclBehavior
     *
     * @return void
     */
    public function testSetup()
    {
        $User = TableRegistry::getTableLocator()->get('AclUsers');
        $this->assertEquals('requester', $User->behaviors()->Acl->getConfig('type'));
        $this->assertTrue(is_object($User->Aro));

        $Post = TableRegistry::getTableLocator()->get('AclPosts');
        $this->assertEquals('controlled', $Post->behaviors()->Acl->getConfig('type'));
        $this->assertTrue(is_object($Post->Aco));
    }

    /**
     * Test Setup of AclBehavior as both requester and controlled
     *
     * @return void
     */
    public function testSetupMulti()
    {
        TableRegistry::getTableLocator()->clear();
        $User = TableRegistry::getTableLocator()->get('AclPeople', [
            'className' => __NAMESPACE__ . '\AclPeople',
        ]);
        $this->assertEquals('both', $User->behaviors()->Acl->getConfig('type'));
        $this->assertTrue(is_object($User->Aro));
        $this->assertTrue(is_object($User->Aco));
    }

    /**
     * test After Save
     *
     * @return void
     */
    public function testAfterSave()
    {
        $Post = TableRegistry::getTableLocator()->get('AclPosts');
        $data = new AclPost([
            'author_id' => 1,
            'title' => 'Acl Post',
            'body' => 'post body',
            'published' => 1
        ]);
        $saved = $Post->save($data);
        $query = $this->Aco->find('all', [
            'conditions' => ['model' => $Post->getAlias(), 'foreign_key' => $saved->id]
        ]);
        $this->assertTrue(is_object($query));
        $result = $query->first();
        $this->assertEquals($Post->getAlias(), $result->model);
        $this->assertEquals($saved->id, $result->foreign_key);

        $Person = TableRegistry::getTableLocator()->get('AclPeople');
        $Person->deleteAll(['name' => 'person']);
        $aroData = new AclPerson([
            'model' => $Person->getAlias(),
            'foreign_key' => 2,
            'parent_id' => null
        ]);
        $this->Aro->save($aroData);

        $acoData = new AclPerson([
            'model' => $Person->getAlias(),
            'foreign_key' => 2,
            'parent_id' => null
        ]);
        $this->Aco->save($acoData);

        $data = new AclPerson([
            'name' => 'Trent',
            'mother_id' => 2,
            'father_id' => 3,
        ]);
        $saved = $Person->save($data);
        $result = $this->Aro->find('all', [
            'conditions' => ['model' => $Person->getAlias(), 'foreign_key' => $saved->id]
        ])->first();
        $this->assertEquals(5, $result->parent_id);

        $node = $Person->node(['model' => $Person->getAlias(), 'foreign_key' => 8], 'Aro');
        $this->assertEquals(2, $node->count());
        $node = $node->toArray();
        $this->assertEquals(5, $node[0]->parent_id);
        $this->assertEquals(null, $node[1]->parent_id);

        $aroData = $this->Aro->save(new AclPerson([
            'model' => $Person->getAlias(),
            'foreign_key' => 1,
            'parent_id' => null
        ]));
        $acoData = $this->Aco->save(new AclPerson([
            'model' => $Person->getAlias(),
            'foreign_key' => 1,
            'parent_id' => null
        ]));

        $person = $Person->findById(8)->first();
        $person->mother_id = 1;
        $person = $Person->save($person);
        $result = $this->Aro->find('all', [
            'conditions' => ['model' => $Person->getAlias(), 'foreign_key' => $person->id]
        ])->first();
        $this->assertEquals(7, $result->parent_id);

        $node = $Person->node(['model' => $Person->getAlias(), 'foreign_key' => 8], 'Aro')->toArray();
        $this->assertEquals(2, count($node));
        $this->assertEquals(7, $node[0]->parent_id);
        $this->assertEquals(null, $node[1]->parent_id);
    }

    /**
     * test that an afterSave on an update does not cause parent_id to become null.
     *
     * @return void
     */
    public function testAfterSaveUpdateParentIdNotNull()
    {
        $Person = TableRegistry::getTableLocator()->get('AclPeople');
        $Person->deleteAll(['name' => 'person']);
        $this->Aro->save(new Aro([
            'model' => $Person->getAlias(),
            'foreign_key' => 2,
            'parent_id' => null,
        ]));

        $this->Aco->save(new Aco([
            'model' => $Person->getAlias(),
            'foreign_key' => 2,
            'parent_id' => null,
        ]));

        $person = $Person->save(new AclPerson([
            'name' => 'Trent',
            'mother_id' => 2,
            'father_id' => 3,
        ]));
        $result = $this->Aro->find('all', [
            'conditions' => ['model' => $Person->getAlias(), 'foreign_key' => $person->id]
        ])->first();
        $this->assertEquals(5, $result->parent_id);

        $person = $Person->save(new AclPerson([
            'id' => $person->id,
            'name' => 'Bruce',
        ], [
            'source' => $Person->getAlias(),
        ]));
        $result = $this->Aro->find('all', [
            'conditions' => ['model' => $Person->getAlias(), 'foreign_key' => $person->id]
        ])->first();
        $this->assertEquals(5, $result->parent_id);
    }

    /**
     * Test After Delete
     *
     * @return void
     */
    public function testAfterDelete()
    {
        $Person = TableRegistry::getTableLocator()->get('AclPeople');

        $this->Aro->save(new Aro([
            'model' => $Person->getAlias(),
            'foreign_key' => 2,
            'parent_id' => null
        ]));

        $this->Aco->save(new Aco([
            'model' => $Person->getAlias(),
            'foreign_key' => 2,
            'parent_id' => null
        ]));

        $Person->deleteAll(['name' => 'person']);
        $person = $Person->save(new AclPerson([
            'name' => 'Trent',
            'mother_id' => 2,
            'father_id' => 3,
        ]));
        $node = $Person->node($person, 'Aro')->toArray();

        $this->assertEquals(2, count($node));
        $this->assertEquals(5, $node[0]->parent_id);
        $this->assertEquals(null, $node[1]->parent_id);

        $Person->delete($person);
        $result = $this->Aro->find('all', [
            'conditions' => ['model' => $Person->getAlias(), 'foreign_key' => $person->id]
        ]);
        $this->assertTrue($result->count() === 0);
        $result = $this->Aro->find('all', [
            'conditions' => ['model' => $Person->getAlias(), 'foreign_key' => 2]
        ]);
        $this->assertTrue($result->count() > 0);

        $person = $Person->save(new AclPerson([
            'name' => 'Trent',
            'mother_id' => 2,
            'father_id' => 3,
        ]));

        $person = new AclPerson(['id' => 2], ['source' => $Person->getAlias(), 'markNew' => false]);

        $Person->delete($person);
        $result = $this->Aro->find('all', [
            'conditions' => ['model' => $Person->getAlias(), 'foreign_key' => $person->id]
        ]);
        $this->assertTrue($result->count() === 0);

        $result = $this->Aro->find('all', [
            'conditions' => ['model' => $Person->getAlias(), 'foreign_key' => 2]
        ]);
        $this->assertTrue($result->count() === 0);
    }

    /**
     * Test Node()
     *
     * @return void
     */
    public function testNode()
    {
        $Person = TableRegistry::getTableLocator()->get('AclPeople');
        $this->Aro->save(new Aro([
            'model' => $Person->getAlias(),
            'foreign_key' => 2,
            'parent_id' => null
        ]));

        $person = new AclPerson(['id' => 2], ['source' => $Person->getAlias()]);
        $result = $Person->node($person, 'Aro');
        $this->assertEquals(1, $result->count());
    }
}
