<?php
/**
 *
 * CakePHP(tm) : Rapid Development Framework (http://cakephp.org)
 * Copyright (c) Cake Software Foundation, Inc. (http://cakefoundation.org)
 *
 * Licensed under The MIT License
 * For full copyright and license information, please see the LICENSE.txt
 * Redistributions of files must retain the above copyright notice.
 *
 * @copyright     Copyright (c) Cake Software Foundation, Inc. (http://cakefoundation.org)
 * @link          http://cakephp.org CakePHP(tm) Project
 * @license       http://www.opensource.org/licenses/mit-license.php MIT License
 */
namespace Acl\Auth;

use Cake\Controller\ComponentRegistry;
use Cake\Http\ServerRequest;

/**
 * An authorization adapter for AuthComponent. Provides the ability to authorize using CRUD mappings.
 * CRUD mappings allow you to translate controller actions into *C*reate *R*ead *U*pdate *D*elete actions.
 * This is then checked in the AclComponent as specific permissions.
 *
 * For example, taking `/posts/index` as the current request. The default mapping for `index`, is a `read` permission
 * check. The Acl check would then be for the `posts` controller with the `read` permission. This allows you
 * to create permission systems that focus more on what is being done to resources, rather than the specific actions
 * being visited.
 *
 * @see AuthComponent::$authenticate
 * @see AclComponent::check()
 */
class CrudAuthorize extends BaseAuthorize
{

    /**
     * Sets up additional actionMap values that match the configured `Routing.prefixes`.
     *
     * @param ComponentRegistry $registry The component registry from the controller.
     * @param array $config An array of config. This class does not use any config.
     */
    public function __construct(ComponentRegistry $registry, $config = [])
    {
        parent::__construct($registry, $config);
    }

    /**
     * Authorize a user using the mapped actions and the AclComponent.
     *
     * @param array $user The user to authorize
     * @param \Cake\Network\Request $request The request needing authorization.
     * @return bool
     */
    public function authorize($user, ServerRequest $request)
    {
        $mapped = $this->getConfig('actionMap.' . $request->getParam('action'));

        if (!$mapped) {
            trigger_error(
                sprintf(
                    'CrudAuthorize::authorize() - Attempted access of un-mapped action "%1$s" in controller "%2$s"',
                    $request->getParam('action'),
                    $request->getParam('controller')
                ),
                E_USER_WARNING
            );

            return false;
        }
        $user = [$this->_config['userModel'] => $user];
        $Acl = $this->_registry->load('Acl');

        return $Acl->check(
            $user,
            $this->action($request, ':controller'),
            $mapped
        );
    }
}
