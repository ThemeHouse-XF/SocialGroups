<?php

class ThemeHouse_SocialGroups_ViewPublic_Helper extends XenForo_ViewPublic_Base
{

    public static function renderSocialForumsListFromDisplayArray(XenForo_View $view, array $socialForums)
    {
        $renderedSocialForums = array();
        foreach ($socialForums as $socialForum) {
            $template = $view->createTemplateObject('th_social_forum_list_item_socialgroups');
            $template->setParam('forum', $socialForum);
            $renderedSocialForums[] = $template->render();
        }
        return $renderedSocialForums;
    }
}