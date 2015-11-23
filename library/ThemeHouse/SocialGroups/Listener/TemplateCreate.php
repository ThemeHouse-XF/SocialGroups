<?php

class ThemeHouse_SocialGroups_Listener_TemplateCreate extends ThemeHouse_Listener_TemplateCreate
{

    protected function _getTemplates()
    {
        return array(
            'forum_view',
            'thread_view',
        );
    }

    public static function templateCreate(&$templateName, array &$params, XenForo_Template_Abstract $template)
    {
        $templateCreate = new ThemeHouse_SocialGroups_Listener_TemplateCreate($templateName, $params, $template);
        list ($templateName, $params) = $templateCreate->run();
    }

    protected function _forumView()
    {
        $this->_preloadTemplate('th_social_forum_tools_socialgroups');
    }

    protected function _threadView()
    {
        $this->_preloadTemplate('th_message_user_info_socialgroups');
    }
}