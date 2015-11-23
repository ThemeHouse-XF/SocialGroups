<?php

/**
 * Route prefix handler for social forums in the public system.
 */
class ThemeHouse_SocialGroups_Route_Prefix_SocialForums implements XenForo_Route_Interface
{

    /**
     * Match a specific route for an already matched prefix.
     *
     * @see XenForo_Route_Interface::match()
     */
    public function match($routePath, Zend_Controller_Request_Http $request, XenForo_Router $router)
    {
        $action = $router->resolveActionWithIntegerOrStringParam($routePath, $request, 'social_forum_id', 'url_portion');
        $action = $router->resolveActionAsPageNumber($action, $request);
        if (!class_exists('XFCP_ThemeHouse_SocialGroups_ControllerPublic_SocialForum', false)) {
            $createClass = XenForo_Application::resolveDynamicClass('XenForo_ControllerPublic_Forum', 'controller');
            eval('class XFCP_ThemeHouse_SocialGroups_ControllerPublic_SocialForum extends ' . $createClass . ' {}');
        }
        return $router->getRouteMatch('ThemeHouse_SocialGroups_ControllerPublic_SocialForum', $action, 'forums');
    }

    /**
     * Method to build a link to the specified page/action with the provided
     * data and params.
     *
     * @see XenForo_Route_BuilderInterface
     */
    public function buildLink($originalPrefix, $outputPrefix, $action, $extension, $data, array &$extraParams)
    {
        if (is_array($data) && !empty($data['url_portion'])) {
            return XenForo_Link::buildBasicLinkWithStringParam($outputPrefix, $action, $extension, $data, 'url_portion');
        } else {
            if (isset($data['social_forum_title'])) {
                $data['title'] = $data['social_forum_title'];
            }
            return XenForo_Link::buildBasicLinkWithIntegerParam($outputPrefix, $action, $extension, $data,
                'social_forum_id', 'title');
        }
    }
}