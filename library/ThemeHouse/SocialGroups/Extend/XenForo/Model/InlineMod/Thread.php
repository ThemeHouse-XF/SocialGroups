<?php

/**
 *
 * @see XenForo_Model_InlineMod_Thread
 */
class ThemeHouse_SocialGroups_Extend_XenForo_Model_InlineMod_Thread extends XFCP_ThemeHouse_SocialGroups_Extend_XenForo_Model_InlineMod_Thread
{

    protected $_socialForumMembers = null;

    /**
     *
     * @see XenForo_Model_InlineMod_Thread::_getForumFromThread()
     */
    protected function _getForumFromThread(array $thread, array $forums)
    {
        $forum = parent::_getForumFromThread($thread, $forums);

        if ($thread['social_forum_id']) {
            $socialForumMember = $this->_getSocialForumMember($thread['social_forum_id']);
            $socialForumModel = $this->_getSocialForumModel();
            $forum['nodePermissions'] = $socialForumModel->getNodePermissions($thread, array(
                $socialForumMember
            ));
        }

        return $forum;
    }

    /**
     *
     * @param int $socialForumId
     * @return array $socialForumMember
     */
    protected function _getSocialForumMember($socialForumId)
    {
        if (!$this->_socialForumMembers) {
            $visitor = XenForo_Visitor::getInstance();
            $this->_socialForumMembers = $this->_getSocialForumMemberModel()->getSocialForumMembers(
                array(
                    'user_id' => $visitor['user_id']
                ));
        }
        foreach ($this->_socialForumMembers as $socialForumMember) {
            if ($socialForumMember['social_forum_id'] == $socialForumId) {
                return $socialForumMember;
            }
        }
        return array();
    }

    /**
     *
     * @return ThemeHouse_SocialGroups_Model_SocialForum
     */
    protected function _getSocialForumModel()
    {
        return ThemeHouse_SocialGroups_SocialForum::getSocialForumModel();
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