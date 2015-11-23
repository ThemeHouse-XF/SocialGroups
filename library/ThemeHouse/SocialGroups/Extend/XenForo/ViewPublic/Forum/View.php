<?php

/**
 *
 * @see XenForo_ViewPublic_Forum_View
 */
class ThemeHouse_SocialGroups_Extend_XenForo_ViewPublic_Forum_View extends XFCP_ThemeHouse_SocialGroups_Extend_XenForo_ViewPublic_Forum_View
{

    /**
     *
     * @see XenForo_ViewPublic_Forum_View::renderHtml()
     */
    public function renderHtml()
    {
        parent::renderHtml();

        if (ThemeHouse_SocialGroups_SocialForum::hasInstance()) {
            $xenOptions = XenForo_Application::get('options');
            if (!$xenOptions->th_socialGroups_showChildNodesInSocialForums) {
                $this->_params['renderedNodes'] = array();
            }
        }
    }
}