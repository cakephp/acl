<?php

namespace Acl\Test\TestCase\View\Helper;

use Acl\Controller\Component\AclComponent;
use Acl\Model\Entity\Aco;
use Acl\Model\Entity\Aro;
use Acl\Model\Table\AclNodesTable;
use Acl\Model\Table\AcosTable;
use Acl\Model\Table\ArosTable;
use Acl\Model\Table\PermissionsTable;
use Acl\View\Helper\AclHelper;
use Cake\Controller\ComponentRegistry;
use Cake\Core\Configure;
use Cake\ORM\TableRegistry;
use Cake\TestSuite\IntegrationTestCase;
use Cake\View\View;

/**
 * Class AclHelperTest
 *
 */
class AclHelperTest extends IntegrationTestCase
{
    /**
     * setUp
     *
     * @return void
     */
    public function setUp()
    {
        parent::setUp();

        Configure::write('Acl.classname', 'DbAcl');
        Configure::write('Acl.database', 'test');

        $Collection = new ComponentRegistry();
        $this->Acl = new AclComponent($Collection);

        $View = new View();
        $View->request->session()->write([
            'Auth' => [
                'User' => [
                    'id' => 7,
                    'name' => 'Samir'
                ]
            ]
        ]);
        $this->helper = new AclHelper($View);

        $aro = new Aro([
            'id' => 1,
            'model' => 'Users',
            'foreign_key' => 7,
            'alias' => 'Samir'
        ]);
        $aro = $this->Acl->Aro->save($aro);

        $aco = new Aco([
            'id' => 1,
            'alias' => 'controller'
        ]);
        $aco = $this->Acl->Aco->save($aco);

        $aco = new Aco([
            'id' => 2,
            'parent_id' => 1,
            'alias' => 'Posts'
        ]);
        $aco = $this->Acl->Aco->save($aco);

        $aco = new Aco([
            'id' => 3,
            'parent_id' => 2,
            'alias' => 'add'
        ]);
        $aco = $this->Acl->Aco->save($aco);

        $aco = new Aco([
            'id' => 4,
            'parent_id' => 2,
            'alias' => 'view'
        ]);
        $aco = $this->Acl->Aco->save($aco);

        $this->Acl->allow('Samir', 'controller/Posts/view');
        $this->Acl->deny('Samir', 'controller/Posts/add');
    }

    /**
     * tearDown method
     *
     * @return void
     */
    public function tearDown()
    {
        parent::tearDown();
        unset($this->Acl);
        unset($this->helper);
    }

    /**
     * test failure
     *
     * @return void
     */
    public function testFailure()
    {
        $this->assertFalse((bool)$this->helper->_check('/posts/add'));
    }

    /**
     * test success
     *
     * @return void
     */
    public function testSuccess()
    {
        $this->assertTrue((bool)$this->helper->_check('/posts/view/1'));
    }
}
