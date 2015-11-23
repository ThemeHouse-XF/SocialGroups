<?php

/**
 * View handling for viewing the details of a specific social category.
 */
class ThemeHouse_SocialGroups_ViewPublic_Member_SocialForums extends XenForo_ViewPublic_Base
{

    /**
     * Help render the HTML output.
     *
     * @return mixed
     */
    public function renderHtml()
    {
        foreach ($this->_params['socialForums'] as &$forum) {
            $forum['urls'] = ThemeHouse_SocialGroups_Template_Helper_SocialForum::getAvatarUrls($forum);
        }
    }
}
