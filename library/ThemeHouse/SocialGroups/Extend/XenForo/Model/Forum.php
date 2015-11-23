<?php

/**
 *
 * @see XenForo_Model_Forum
 */
class ThemeHouse_SocialGroups_Extend_XenForo_Model_Forum extends XFCP_ThemeHouse_SocialGroups_Extend_XenForo_Model_Forum
{

    /**
     *
     * @see XenForo_Model_Forum::canUploadAndManageAttachment()
     */
    public function canUploadAndManageAttachment(array $forum, &$errorPhraseKey = '', array $nodePermissions = null,
        array $viewingUser = null)
    {
        $socialForum = ThemeHouse_SocialGroups_SocialForum::getInstance();
        if ($forum['node_type_id'] == 'SocialCategory' && !isset($socialForum['social_forum_id'])) {
            $socialForumMember = $this->_getSocialForumMemberModel()->getMaximumMembershipForUserId(
                XenForo_Visitor::getUserId());
            // TODO: Need to check whether a user can upload an attachment for a specific group
            if ($socialForumMember['level']) {
                $nodePermissions = XenForo_Application::get('options')->th_socialGroups_permissions[$socialForumMember['level']];
            }
        }

        return parent::canUploadAndManageAttachment($forum, $errorPhraseKey, $nodePermissions, $viewingUser);
    }

    /**
     *
     * @return ThemeHouse_SocialGroups_Model_SocialForumMember
     */
    protected function _getSocialForumMemberModel()
    {
        return $this->getModelFromCache('ThemeHouse_SocialGroups_Model_SocialForumMember');
    }
}