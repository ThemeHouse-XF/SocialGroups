<?php

/**
 * Route prefix handler for social categories in the admin control panel.
 */
class ThemeHouse_SocialGroups_Route_PrefixAdmin_SocialCategories extends XenForo_Route_PrefixAdmin_Nodes
{

    /**
     * Match a specific route for an already matched prefix.
     *
     * @see XenForo_Route_Interface::match()
     */
    public function match($routePath, Zend_Controller_Request_Http $request, XenForo_Router $router)
    {
        $action = $router->resolveActionWithIntegerParam($routePath, $request, 'node_id');
        if (!class_exists('XFCP_ThemeHouse_SocialGroups_ControllerAdmin_SocialCategory', false)) {
            $createClass = XenForo_Application::resolveDynamicClass('XenForo_ControllerAdmin_Forum', 'controller');
            eval('class XFCP_ThemeHouse_SocialGroups_ControllerAdmin_SocialCategory extends ' . $createClass . ' {}');
        }
        return $router->getRouteMatch('ThemeHouse_SocialGroups_ControllerAdmin_SocialCategory', $action, 'nodeTree');
    }

    /**
     * Method to build a link to the specified page/action with the provided
     * data and params.
     *
     * @see XenForo_Route_BuilderInterface
     */
    public function buildLink($originalPrefix, $outputPrefix, $action, $extension, $data, array &$extraParams)
    {
        return XenForo_Link::buildBasicLinkWithIntegerParam($outputPrefix, $action, $extension, $data, 'node_id',
            'title');
    }
}