<?php

/**
 * View handling for viewing the details of a specific social category.
 */
class ThemeHouse_SocialGroups_ViewPublic_SocialCategory_View extends XenForo_ViewPublic_Base
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
        foreach ($this->_params['stickySocialForums'] as &$forum) {
            $forum['urls'] = ThemeHouse_SocialGroups_Template_Helper_SocialForum::getAvatarUrls($forum);
            $forum['description'] = XenForo_Helper_String::bbCodeStrip($forum['description'], true);
        }
        unset($forum);

        $xenOptions = XenForo_Application::get('options');
        if (!$xenOptions->th_socialGroups_showChildNodesInCategory) {
            $this->_params['renderedNodes'] = XenForo_ViewPublic_Helper_Node::renderNodeTreeFromDisplayArray($this,
                $this->_params['nodeList'], 2);
        }

        $this->_params['renderedSocialForums'] = ThemeHouse_SocialGroups_ViewPublic_Helper::renderSocialForumsListFromDisplayArray(
            $this, array_merge($this->_params['stickySocialForums'], $this->_params['socialForums']));
    }
}
