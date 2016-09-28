<?php

namespace Acl\View\Helper;

use Acl\Auth\ActionsAuthorize;
use Acl\Controller\Component\AclComponent;
use Cake\Controller\ComponentRegistry;
use Cake\Core\Configure;
use Cake\Network\Request;
use Cake\Routing\Router;
use Cake\View\Helper;

class AclHelper extends Helper
{
    public $helpers = ['Form'];

    protected $_defaultConfig = [
        'userModel' => 'Users'
    ];

    public function __construct(View $View, array $config = []) {
        parent::__construct($View, $config + $defaults);
    }

    public function _check($url)
    {
        $registry  = new ComponentRegistry();
        $Acl = new AclComponent($registry, Configure::read('Acl'));
        $authorize = new ActionsAuthorize($registry, Configure::read('Acl'));

        $user = [$this->_config['userModel'] => $this->request->session()->read('Auth.User')];
        $request = new Request(['params' => Router::parse(Router::normalize($url))]);
        return $Acl->check($user, $authorize->action($request));
    }

    public function link($title, $url = null, array $options = [])
    {
        if (!$this->_check($url)) {
            return '';
        }
        return $this->Form->Html->link($title, $url, $options);
    }

    public function postLink($title, $url = null, array $options = [])
    {
        if (!$this->_check($url)) {
            return '';
        }
        return $this->Form->postLink($title, $url = null, $options);
    }
}
