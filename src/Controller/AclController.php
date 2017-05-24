<?php

namespace Acl\Controller;

use Cake\Controller\Controller;

class AclController extends Controller {

    // show a initial empty dataTable, load all users and groups in two tabs.
    public function index(){
        // get all Users
        $users = TableRegistry::get('Users')->find('list');
        // get All groups
        $groups = TableRegistry::get('Groups')->find('list');
        $this->set(compact('users','groups'));
    }

    /** AJAX ACTIONS */
    public function getAcl($user_id = null, $id = null){
        $this->loadModel("Acos");
        $this->loadModel("Aros");
        $alias = 'Acos';
        $class = 'Acos';

        $msg = '';
        $response = array("success"=>false,"msg"=>"");
        try{
            $conn = ConnectionManager::get('default');
            if($user_id != null) {
                $user = $this->Users->get($user_id, ['contain' => 'Groups']);

                if (isset($id)) {
                    $identity = $this->parseIdentifier($this->args[1]);

                    $topNode = $this->{$class}->find('all', [
                        'conditions' => [$alias . '.id' => $this->_getNodeId($class, $identity)]
                    ])->first();

                    $nodes = $this->{$class}->find('all', [
                        'conditions' => [
                            $alias . '.lft >=' => $topNode->lft,
                            $alias . '.lft <=' => $topNode->rght
                        ],
                        'order' => $alias . '.lft ASC'
                    ]);
                } else {
                    $nodes = $this->{$class}->find('all', ['order' => $alias . '.lft ASC']);
                }

                if ($nodes->count() === 0) {
                    if (isset($id)) {
                        $this->error(__d('cake_acl', '{0} not found', [$id]), __d('cake_acl', 'No tree returned.'));
                    } elseif (isset($class)) {
                        $this->error(__d('cake_acl', '{0} not found', [$class]), __d('cake_acl', 'No tree returned.'));
                    }
                }


                $stack = [];
                $last = null;

                $rows = $nodes->hydrate(false)->toArray();

                $rows_print = [];
                $count_old = 0;

                $noh = "";
                $controller = "";
                $user_group_id = $conn->execute("SELECT id FROM aros where `foreign_key` = ". $user->id);
                $user_group_id = $user_group_id->fetch('assoc');

                foreach ($rows as $n) {
                    $stack[] = $n;
                    if (!empty($last)) {
                        $end = end($stack);
                        if ($end['rght'] > $last) {
                            foreach ($stack as $k => $v) {
                                $end = end($stack);
                                if ($v['rght'] < $end['rght']) {
                                    unset($stack[$k]);
                                }
                            }
                        }
                    }

                    $last = $n['rght'];
                    $count = count($stack);
                    switch ($count){
                        case 1:{
                            $response[$n['alias']] = ["id" =>  $n['id'] , "value" => $this->Acl->check($user_group_id['id'], $n['id'])];
                            $noh = $n['alias'];
                            break;
                        };
                        case 2:{
                            $controller = $n['alias'];
                            $response[$noh][$controller] = ["id" =>  $n['id'] , "value" => $this->Acl->check($user_group_id['id'], $n['id'])];
                            break;
                        };
                        case 3:{
                            $action = $n['alias'];
                            $response[$noh][$controller][$action] = ["id" =>  $n['id'] , "value" => $this->Acl->check($user_group_id['id'], $n['id'])];
                            break;
                        }
                    }
                }
            }
            $response['success'] = true;

        }catch(\Exception $e){
            $response["msg"] = $e->getMessage();
        }
        echo json_encode($response);
        die();
    }


}
