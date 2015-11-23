<?php

/**
 *
 * @see XenForo_ControllerPublic_Member
 */
class ThemeHouse_SocialGroups_Extend_XenForo_ControllerPublic_Member extends XFCP_ThemeHouse_SocialGroups_Extend_XenForo_ControllerPublic_Member
{

    /**
     * Gets social forums for the specified member
     *
     * @return XenForo_ControllerResponse_Abstract
     */
    public function actionSocialForums()
    {
        $userId = $this->_input->filterSingle('user_id', XenForo_Input::UINT);
        $user = $this->getHelper('UserProfile')->assertUserProfileValidAndViewable($userId);

        $socialForumModel = $this->_getSocialForumModel();

        $socialForums = $socialForumModel->getSocialForums(array(
            'user_id' => $userId
        ));

        foreach ($socialForums as &$socialForum) {
            $socialForum = $socialForumModel->prepareSocialForum($socialForum);
        }

        $viewParams = array(
            'socialForums' => $socialForums,
            'user' => $user
        );

        return $this->responseView('ThemeHouse_SocialGroups_ViewPublic_Member_SocialForums',
            'th_member_social_forums_socialgroups', $viewParams);
    }

    /**
     *
     * @return ThemeHouse_SocialGroups_Model_SocialForum
     */
    protected function _getSocialForumModel()
    {
        return ThemeHouse_SocialGroups_SocialForum::getSocialForumModel();
    }
}