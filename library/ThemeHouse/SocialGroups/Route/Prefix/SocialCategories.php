<?php

/**
 * Route prefix handler for social categories in the public system.
 */
class ThemeHouse_SocialGroups_Route_Prefix_SocialCategories extends XenForo_Route_Prefix_Forums
{

    /**
     * Match a specific route for an already matched prefix.
     *
     * @see XenForo_Route_Interface::match()
     */
    public function match($routePath, Zend_Controller_Request_Http $request, XenForo_Router $router)
    {
        $action = $router->resolveActionWithIntegerOrStringParam($routePath, $request, 'node_id', 'node_name');
        $action = $router->resolveActionAsPageNumber($action, $request);
        return $router->getRouteMatch('ThemeHouse_SocialGroups_ControllerPublic_SocialCategory', $action, 'forums');
    }
}