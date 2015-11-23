<?php

/**
 *
 * @see XenForo_ControllerAdmin_Forum
 */
class ThemeHouse_SocialGroups_ControllerAdmin_SocialCategory extends XFCP_ThemeHouse_SocialGroups_ControllerAdmin_SocialCategory
{

    /**
     *
     * @see XenForo_ControllerAdmin_Forum::actionAdd()
     */
    public function actionAdd()
    {
        return $this->responseReroute('ThemeHouse_SocialGroups_ControllerAdmin_SocialCategory', 'edit');
    }

    /**
     *
     * @see XenForo_ControllerAdmin_Forum::actionEdit()
     */
    public function actionEdit()
    {
        $response = parent::actionEdit();

        if ($response instanceof XenForo_ControllerResponse_Error) {
            if ($response->errorText == new XenForo_Phrase('requested_forum_not_found')) {
                $response->errorText = new XenForo_Phrase('th_requested_social_category_not_found_socialgroups');
            }
        } elseif ($response instanceof XenForo_ControllerResponse_View) {
            $response->templateName = "th_social_category_edit_socialgroups";
        }

        return $response;
    }
}