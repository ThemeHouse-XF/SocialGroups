<?php

/**
 *
 * @see XenForo_Model_ForumWatch
 */
class ThemeHouse_SocialGroups_Extend_XenForo_Model_ForumWatch extends XFCP_ThemeHouse_SocialGroups_Extend_XenForo_Model_ForumWatch
{

    /**
     *
     * @see XenForo_Model_ForumWatch::getUserForumWatchByForumId()
     */
    public function getUserForumWatchByForumId($userId, $nodeId)
    {
        if (ThemeHouse_SocialGroups_SocialForum::hasInstance()) {
            $socialForum = ThemeHouse_SocialGroups_SocialForum::getInstance();
            if ($socialForum['node_id'] == $nodeId) {
                return $this->getUserSocialForumWatchBySocialForumId($userId, $socialForum['social_forum_id']);
            }
        }

        return parent::getUserForumWatchByForumId($userId, $nodeId);
    }

    /**
     * Get the social forum watch records for a user, across many social forum
     * IDs.
     *
     * @param integer $userId
     * @param array $socialForumIds
     *
     * @return array Format: [social_forum_id] => watch info
     */
    public function getUserSocialForumWatchBySocialForumIds($userId, array $socialForumIds)
    {
        if (!$socialForumIds) {
            return array();
        }

        return $this->fetchAllKeyed(
            '
			SELECT *
			FROM xf_social_forum_watch
			WHERE user_id = ?
				AND social_forum_id IN (' . $this->_getDb()
                ->quote($socialForumIds) . ')
		', 'social_forum_id', $userId);
    }

    /**
     * Gets a user's social forum watch record for the specified social forum
     * ID.
     *
     * @param integer $userId
     * @param integer $socialForumId
     *
     * @return array bool
     */
    public function getUserSocialForumWatchBySocialForumId($userId, $socialForumId)
    {
        return $this->_getDb()->fetchRow(
            '
			SELECT *
			FROM xf_social_forum_watch
			WHERE user_id = ?
				AND social_forum_id = ?
		', array(
                $userId,
                $socialForumId
            ));
    }

    /**
     *
     * @param integer $userId
     *
     * @return array
     */
    public function getUserSocialForumWatchByUser($userId)
    {
        return $this->fetchAllKeyed('
			SELECT *
			FROM xf_social_forum_watch
			WHERE user_id = ?
		', 'social_forum_id', $userId);
    }

    /**
     *
     * @see XenForo_Model_ForumWatch::getUsersWatchingForum()
     */
    public function getUsersWatchingForum($nodeId, $threadId, $isReply = false)
    {
        $users = parent::getUsersWatchingForum($nodeId, $threadId, $isReply);

        $autoReadDate = XenForo_Application::$time -
             (XenForo_Application::get('options')->readMarkingDataLifetime * 86400);

        if ($isReply) {
            $notificationLimit = "AND social_forum_watch.notify_on = 'message'";
        } else {
            $notificationLimit = "AND social_forum_watch.notify_on IN ('thread', 'message')";
        }

        $activeLimitOption = XenForo_Application::getOptions()->watchAlertActiveOnly;
        if (!empty($activeLimitOption['enabled'])) {
            $activeLimit = ' AND user.last_activity >= ' .
                 (XenForo_Application::$time - 86400 * $activeLimitOption['days']);
        } else {
            $activeLimit = '';
        }

        if (ThemeHouse_SocialGroups_SocialForum::hasInstance()) {
            $socialForum = ThemeHouse_SocialGroups_SocialForum::getInstance();
            if ($socialForum['node_id'] == $nodeId) {
                $socialForumUsers = $this->fetchAllKeyed(
                    '
        			SELECT user.*,
        				user_option.*,
        				user_profile.*,
        				social_forum_watch.notify_on,
        				social_forum_watch.send_alert,
        				social_forum_watch.send_email,
        				permission.cache_value AS node_permission_cache,
        				GREATEST(COALESCE(thread_read.thread_read_date, 0), COALESCE(social_forum_read.social_forum_read_date, 0), ' .
                         $autoReadDate .
                         ') AS read_date
        			FROM xf_social_forum_watch AS social_forum_watch
        			INNER JOIN xf_user AS user ON
				        (user.user_id = social_forum_watch.user_id AND user.user_state = \'valid\' AND user.is_banned = 0' .
                         $activeLimit . ')
        			INNER JOIN xf_user_option AS user_option ON
        				(user_option.user_id = user.user_id)
        			INNER JOIN xf_user_profile AS user_profile ON
        				(user_profile.user_id = user.user_id)
        			LEFT JOIN xf_permission_cache_content AS permission
        				ON (permission.permission_combination_id = user.permission_combination_id
        					AND permission.content_type = \'node\'
        					AND permission.content_id = ?)
        			LEFT JOIN xf_social_forum_read AS social_forum_read
        				ON (social_forum_read.social_forum_id = social_forum_watch.social_forum_id AND social_forum_read.user_id = user.user_id)
        			LEFT JOIN xf_thread_read AS thread_read
        				ON (thread_read.thread_id = ? AND thread_read.user_id = user.user_id)
        			WHERE social_forum_watch.social_forum_id = ?
        				' . $notificationLimit . '
        				AND (social_forum_watch.send_alert <> 0 OR social_forum_watch.send_email <> 0)
        		', 'user_id',
                        array(
                            $nodeId,
                            $threadId,
                            $socialForum['social_forum_id']
                        ));
                $users = $users + $socialForumUsers;

                if (!empty($socialForum['social_forum_id'])) {
                    /* @var $socialForumMemberModel ThemeHouse_SocialGroups_Model_SocialForumMember */
                    $socialForumMemberModel = $this->getModelFromCache('ThemeHouse_SocialGroups_Model_SocialForumMember');

                    $socialForumMembers = $socialForumMemberModel->getSocialForumUsers(
                        array(
                            'user_ids' => array_keys($users),
                            'social_forum_id' => $socialForum['social_forum_id']
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
            }
        }

        return $users;
    }

    /**
     *
     * @see XenForo_Model_ForumWatch::setForumWatchState()
     */
    public function setForumWatchState($userId, $forumId, $notifyOn = null, $sendAlert = null, $sendEmail = null)
    {
        if (!$userId) {
            return false;
        }

        if (ThemeHouse_SocialGroups_SocialForum::hasInstance()) {
            $socialForum = ThemeHouse_SocialGroups_SocialForum::getInstance();
            if ($socialForum['node_id'] == $forumId) {
                $forumWatch = $this->getUserForumWatchByForumId($userId, $forumId);

                if ($notifyOn === 'delete') {
                    if ($forumWatch) {
                        $dw = XenForo_DataWriter::create('ThemeHouse_SocialGroups_DataWriter_SocialForumWatch');
                        $dw->setExistingData($forumWatch, true);
                        $dw->delete();
                    }
                    return true;
                }

                $dw = XenForo_DataWriter::create('ThemeHouse_SocialGroups_DataWriter_SocialForumWatch');
                if ($forumWatch) {
                    $dw->setExistingData($forumWatch, true);
                } else {
                    $dw->set('user_id', $userId);
                    $dw->set('social_forum_id', $socialForum['social_forum_id']);
                }
                if ($notifyOn !== null) {
                    $dw->set('notify_on', $notifyOn);
                }
                if ($sendAlert !== null) {
                    $dw->set('send_alert', $sendAlert ? 1 : 0);
                }
                if ($sendEmail !== null) {
                    $dw->set('send_email', $sendEmail ? 1 : 0);
                }
                $dw->save();
                return true;
            }
        }

        return parent::setForumWatchState($userId, $forumId, $notifyOn, $sendAlert, $sendEmail);
    }
}