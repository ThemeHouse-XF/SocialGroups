<?php

/**
 * View handling for viewing the details of a viewer's social forums.
 */
class ThemeHouse_SocialGroups_ViewPublic_SocialForum_List extends XenForo_ViewPublic_Base
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
            $forum['description'] = XenForo_Helper_String::bbCodeStrip($forum['description'], true);
        }
        unset($forum);

        $this->_params['renderedSocialForums'] = ThemeHouse_SocialGroups_ViewPublic_Helper::renderSocialForumsListFromDisplayArray(
            $this, $this->_params['socialForums']);
    }
}
