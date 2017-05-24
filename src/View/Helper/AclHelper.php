<?php

namespace Acl\View\Helper;

use Acl\Auth\ActionsAuthorize;
use Acl\Controller\Component\AclComponent;
use Cake\Controller\ComponentRegistry;
use Cake\Core\Configure;
use Cake\Network\Request;
use Cake\Routing\Router;
use Cake\View\Helper;

/**
 * Acl helper library.
 *
 * Automatic generation of links based on ACL.
 *
 * @property FormHelper $Form
 */
class AclHelper extends Helper
{
    /**
     * List of helpers used by this helper
     *
     * @var array
     */
    public $helpers = ['Form'];

    /**
     * Default config for the helper.
     *
     * @var array
     */
    protected $_defaultConfig = [
        'userModel' => 'Users'
    ];

    /**
     *  Check if the current user have access to the aco
     *
     * @param string|array $url
     *
     * @return bool
     */
    public function _check($url)
    {
        $registry  = new ComponentRegistry();
        $Acl = new AclComponent($registry, Configure::read('Acl'));
        $authorize = new ActionsAuthorize($registry, Configure::read('Acl'));

        $user = [$this->_config['userModel'] => $this->request->session()->read('Auth.User')];
        $request = new Request(['params' => Router::parse(Router::normalize($url))]);
        return $Acl->check($user, $authorize->action($request));
    }

    /**
     * Creates an HTML link.
     *
     * @link http://book.cakephp.org/3.0/en/views/helpers/html.html#creating-links
     */
    public function link($title, $url = null, array $options = [])
    {
        if (!$this->_check($url)) {
            return '';
        }
        return $this->Form->Html->link($title, $url, $options);
    }

    /**
     * Creates an HTML link, but access the URL using the method you specify
     * (defaults to POST). Requires javascript to be enabled in browser.
     *
     * @link http://book.cakephp.org/3.0/en/views/helpers/form.html#creating-standalone-buttons-and-post-links
     */
    public function postLink($title, $url = null, array $options = [])
    {
        if (!$this->_check($url)) {
            return '';
        }
        return $this->Form->postLink($title, $url = null, $options);
    }
}
