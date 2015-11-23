<?php

/**
 *
 * @see XenForo_Model_Session
 */
class ThemeHouse_SocialGroups_Extend_XenForo_Model_Session extends XFCP_ThemeHouse_SocialGroups_Extend_XenForo_Model_Session
{

    /**
     *
     * @see XenForo_Model_Session::addSessionActivityDetailsToList()
     */
    public function addSessionActivityDetailsToList(array $activities)
    {
        foreach ($activities as $key => $activity) {
            if ($activity['controller_name'] == 'ThemeHouse_SocialGroups_ControllerPublic_SocialForum') {
                if (!class_exists('XFCP_ThemeHouse_SocialGroups_ControllerPublic_SocialForum', false)) {
                    $createClass = XenForo_Application::resolveDynamicClass('XenForo_ControllerPublic_Forum',
                        'controller');
                    eval(
                        'class XFCP_ThemeHouse_SocialGroups_ControllerPublic_SocialForum extends ' . $createClass . ' {}');
                    break;
                }
            }
        }
        return parent::addSessionActivityDetailsToList($activities);
    }
}