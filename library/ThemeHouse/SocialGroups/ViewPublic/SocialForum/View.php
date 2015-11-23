<?php

/**
 * View handling for viewing the details of a specific social forum.
 */
class ThemeHouse_SocialGroups_ViewPublic_SocialForum_View extends XenForo_ViewPublic_Base
{

    /**
     * Help render the HTML output.
     *
     * @return mixed
     */
    public function renderHtml()
    {
        $this->_params['urls'] = ThemeHouse_SocialGroups_Template_Helper_SocialForum::getAvatarUrls(
            $this->_params['socialForum']);

        $bbCodeParser = new XenForo_BbCode_Parser(XenForo_BbCode_Formatter_Base::create('Base', array(
            'view' => $this
        )));
        $this->_params['socialForum']['description'] = new XenForo_BbCode_TextWrapper(
            $this->_params['socialForum']['description'], $bbCodeParser);
    }
}
