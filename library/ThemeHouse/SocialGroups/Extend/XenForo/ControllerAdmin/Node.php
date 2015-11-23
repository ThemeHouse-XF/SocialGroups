<?php

/**
 *
 * @see XenForo_ControllerAdmin_Node
 */
class ThemeHouse_SocialGroups_Extend_XenForo_ControllerAdmin_Node extends XFCP_ThemeHouse_SocialGroups_Extend_XenForo_ControllerAdmin_Node
{

    /**
     *
     * @see XenForo_ControllerAdmin_Node::_postDispatch()
     */
    protected function _postDispatch($controllerResponse, $controllerName, $action)
    {
        /* @var $controllerResponse XenForo_ControllerResponse_Reroute */
        if ($controllerResponse instanceof XenForo_ControllerResponse_Reroute) {
            if ($controllerResponse->controllerName == 'ThemeHouse_SocialGroups_ControllerAdmin_SocialCategory') {
                if (!class_exists('XFCP_ThemeHouse_SocialGroups_ControllerAdmin_SocialCategory', false)) {
                    $createClass = XenForo_Application::resolveDynamicClass('XenForo_ControllerAdmin_Forum',
                        'controller');
                    eval(
                        'class XFCP_ThemeHouse_SocialGroups_ControllerAdmin_SocialCategory extends ' . $createClass . ' {}');
                }
            }
        }
    }
}