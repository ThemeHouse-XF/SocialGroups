<?php

/**
 *
 * @see XenForo_Model_ThreadWatch
 */
class ThemeHouse_SocialGroups_Extend_XenForo_Model_ThreadWatch extends XFCP_ThemeHouse_SocialGroups_Extend_XenForo_Model_ThreadWatch
{

    /**
     *
     * @see XenForo_Model_ThreadWatch::getUsersWatchingThread()
     */
    public function getUsersWatchingThread($threadId, $nodeId)
    {
        $users = parent::getUsersWatchingThread($threadId, $nodeId);

        /* @var $threadModel XenForo_Model_Thread */
        $threadModel = $this->getModelFromCache('XenForo_Model_Thread');

        $thread = $threadModel->getThreadById($threadId);

        if (!empty($thread['social_forum_id'])) {
            /* @var $socialForumMemberModel ThemeHouse_SocialGroups_Model_SocialForumMember */
            $socialForumMemberModel = $this->getModelFromCache('ThemeHouse_SocialGroups_Model_SocialForumMember');

            $socialForumMembers = $socialForumMemberModel->getSocialForumUsers(
                array(
                    'user_ids' => array_keys($users),
                    'social_forum_id' => $thread['social_forum_id']
                ));

            $socialForumModel = ThemeHouse_SocialGroups_SocialForum::getSocialForumModel();

            foreach ($users as $userId => &$user) {
                if (!empty($socialForumMembers[$userId])) {
                    $user = array_merge($user, $socialForumMembers[$userId]);
                } else {
                    continue;
                }

                $permissions = array();
                if (!empty($user['node_permission_cache'])) {
                    $permissions = unserialize($user['node_permission_cache']);
                }

                $nodePermissionCache = $socialForumModel->getSocialForumPermissions($user, $permissions);

                $user['node_permission_cache'] = serialize($nodePermissionCache);
            }
        }

        return $users;
    }

    /**
     *
     * @see XenForo_Model_ThreadWatch::sendNotificationToWatchUsersOnReply()
     */
    public function sendNotificationToWatchUsersOnReply(array $reply, array $thread = null, array $noAlerts = array())
    {
        $alertModel = $this->getModelFromCache('XenForo_Model_Alert');

        $xenOptions = XenForo_Application::get('options');

        $threadModel = $this->_getThreadModel();

        if (!$thread) {
            $thread = $threadModel->getThreadById($reply['thread_id'],
                array(
                    'join' => XenForo_Model_Thread::FETCH_FORUM
                ));
        }

        if ($xenOptions->th_socialGroups_alwaysAlert && !empty($thread['social_forum_id'])) {
            $alertModel->setSocialForumAlertOverride(true);
        }

        $alertArray = parent::sendNotificationToWatchUsersOnReply($reply, $thread, $noAlerts);

        $alertModel->setSocialForumAlertOverride(false);

        return $alertArray;
    }
}