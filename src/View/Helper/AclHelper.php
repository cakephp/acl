<?php
namespace Acl\View\Helper;

use Acl\Auth\ActionsAuthorize;
use Acl\Controller\Component\AclComponent;
use Cake\Controller\ComponentRegistry;
use Cake\Network\Request;
use Cake\Routing\Router;
use Cake\View\Helper;
use Cake\View\View;

class AclHelper extends Helper
{

    /**
     * Helpers used.
     *
     * @var array
     */
    public $helpers = ['Html'];

    /**
     * Acl Instance.
     *
     * @var object
     */
    public $Acl;

    /**
     * ActionsAuthorize Instance.
     *
     * @var object
     */
    public $Authorize;

    /**
     * Construct method.
     *
     * @param \Cake\View\View $view The view that was fired.
     * @param array $config The config passed to the class.
     */
    public function __construct(View $view, $config = [])
    {
        parent::__construct($view, $config);

        $collection = new ComponentRegistry();
        $this->Acl = new AclComponent($collection);


        $this->Authorize = new ActionsAuthorize($collection);
        $this->Authorize->config($this->config());
    }

    /**
     * Check if the user can access to the given URL.
     *
     * @param array $params The params to check.
     *
     * @return bool
     */
    public function check(array $params = [])
    {
        if (!$this->request->session()->read('Auth.User')) {
            return false;
        }

        $params += ['_base' => false];

        $url = Router::url($params);
        $params = Router::parse($url);

        $user = [$this->Authorize->config('userModel') => $this->request->session()->read('Auth.User')];

        $request = new Request();
        $request->addParams($params);

        $action = $this->Authorize->action($request);

        return $this->Acl->check($user, $action);
    }

    /**
     * Generate the link only if the user has access to the given url.
     *
     * @param string $title The content to be wrapped by <a> tags.
     * @param string|array|null $url Cake-relative URL or array of URL parameters, or
     * external URL (starts with http://)
     * @param array $options Array of options and HTML attributes.
     *
     * @return string
     */
    public function link($title, $url = null, array $options = [])
    {
        if (!$this->check($url)) {
            return '';
        }

        return $this->Html->link($title, $url, $options);
    }
}
