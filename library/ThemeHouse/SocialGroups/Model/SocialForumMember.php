<?php

/**
 * Model for social forum members.
 */
class ThemeHouse_SocialGroups_Model_SocialForumMember extends XenForo_Model
{

    /**
     * Constants to allow joins to extra tables in certain queries
     *
     * @var integer Join user
     */
    const FETCH_USER = 0x01;

    const FETCH_USER_PROFILE = 0x02;

    const FETCH_USER_OPTION = 0x04;

    const FETCH_USER_PRIVACY = 0x08;

    const FETCH_USER_ALL = 0x0F;

    /**
     * Gets social forum member that matches the given conditions.
     *
     * @param array $conditions Conditions to apply to the fetching
     * @param array $fetchOptions Collection of options that relate to fetching
     * @return array false
     */
    public function getSocialForumMember(array $conditions = array(), array $fetchOptions = array())
    {
        $fetchOptions['limit'] = 1;

        $whereClause = $this->prepareSocialForumMemberConditions($conditions, $fetchOptions);
        $sqlClauses = $this->prepareSocialForumMemberFetchOptions($fetchOptions);
        $limitOptions = $this->prepareLimitFetchOptions($fetchOptions);

        return $this->_getDb()->fetchRow(
            $this->limitQueryResults(
                '
				SELECT social_forum_member.*
					' . $sqlClauses['selectFields'] . '
				FROM xf_social_forum_member AS social_forum_member
				' . $sqlClauses['joinTables'] . '
				WHERE ' . $whereClause . '
			', $limitOptions['limit'], $limitOptions['offset']));
    }

    /**
     * Gets the specified social_forum_member if it exists.
     *
     * @param string $socialForumMemberId
     *
     * @return array false
     */
    public function getSocialForumMemberById($id)
    {
        $fetchOptions = array();
        $conditions = array(
            'social_forum_member_id' => $id
        );
        return $this->getSocialForumMember($conditions, $fetchOptions);
    }

    /**
     * Gets the specified social forum member if it exists.
     *
     * @param int $socialForumId
     * @param int $userId
     *
     * @return array false
     */
    public function getSocialForumMemberByUserId($socialForumId, $userId)
    {
        $fetchOptions = array();
        $conditions = array(
            'social_forum_id' => $socialForumId,
            'user_id' => $userId
        );
        return $this->getSocialForumMember($conditions, $fetchOptions);
    }

    /**
     * Gets social forum members that match the given conditions.
     *
     * @param array $conditions Conditions to apply to the fetching
     * @param array $fetchOptions Collection of options that relate to fetching
     * @return array Format: [social_forum_member id] => info
     */
    public function getSocialForumMembers(array $conditions = array(), array $fetchOptions = array())
    {
        $whereClause = $this->prepareSocialForumMemberConditions($conditions, $fetchOptions);
        $sqlClauses = $this->prepareSocialForumMemberFetchOptions($fetchOptions);
        $limitOptions = $this->prepareLimitFetchOptions($fetchOptions);

        return $this->fetchAllKeyed(
            $this->limitQueryResults(
                '
				SELECT social_forum_member.*
					' . $sqlClauses['selectFields'] . '
				FROM xf_social_forum_member AS social_forum_member
				' . $sqlClauses['joinTables'] . '
				WHERE ' . $whereClause . '
				' . $sqlClauses['orderClause'] . '
			', $limitOptions['limit'],
                $limitOptions['offset']), 'social_forum_member_id');
    }

    /**
     * Gets social forum users that match the given conditions.
     *
     * @param array $conditions Conditions to apply to the fetching
     * @param array $fetchOptions Collection of options that relate to fetching
     * @return array Format: [user_id] => info
     */
    public function getSocialForumUsers(array $conditions = array(), array $fetchOptions = array())
    {
        if (!isset($fetchOptions['join'])) {
            $fetchOptions['join'] = 0;
        }
        $fetchOptions['join'] |= self::FETCH_USER;

        $whereClause = $this->prepareSocialForumMemberConditions($conditions, $fetchOptions);
        $sqlClauses = $this->prepareSocialForumMemberFetchOptions($fetchOptions);
        $limitOptions = $this->prepareLimitFetchOptions($fetchOptions);

        return $this->fetchAllKeyed(
            $this->limitQueryResults(
                '
					SELECT social_forum_member.*
						' . $sqlClauses['selectFields'] . '
					FROM xf_social_forum_member AS social_forum_member
					' . $sqlClauses['joinTables'] . '
					WHERE ' . $whereClause . '
					' . $sqlClauses['orderClause'] . '
				', $limitOptions['limit'], $limitOptions['offset']),
            'user_id');
    }

    /**
     * Gets the count of social forum members with the specified criteria.
     *
     * @param array $conditions Conditions to apply to the fetching
     * @return integer
     */
    public function countSocialForumMembers(array $conditions)
    {
        $fetchOptions = array();
        $whereConditions = $this->prepareSocialForumMemberConditions($conditions, $fetchOptions);

        $sqlClauses = $this->prepareSocialForumMemberFetchOptions($fetchOptions);

        return $this->_getDb()->fetchOne(
            '
				SELECT COUNT(*)
				FROM xf_social_forum_member AS social_forum_member
				' . $sqlClauses['joinTables'] . '
				WHERE ' . $whereConditions . '
			');
    }

    /**
     * Prepares join-related fetch options.
     *
     * @param array $fetchOptions
     *
     * @return array Containing 'selectFields' and 'joinTables' keys.
     */
    public function prepareSocialForumMemberFetchOptions(array $fetchOptions)
    {
        $selectFields = '';
        $joinTables = '';
        $orderClause = 'ORDER BY social_forum_member.is_social_forum_moderator DESC,
				social_forum_member.is_approved ASC ';

        if (!empty($fetchOptions['join'])) {
            if ($fetchOptions['join'] & self::FETCH_USER) {
                $selectFields .= ',
								user.*';
                $joinTables .= '
					INNER JOIN xf_user AS user ON
						(user.user_id = social_forum_member.user_id)';
                $orderClause .= ', user.username ASC';
            }

            if ($fetchOptions['join'] & self::FETCH_USER_PROFILE) {
                $selectFields .= ',
					user_profile.*';
                $joinTables .= '
					INNER JOIN xf_user_profile AS user_profile ON
						(user_profile.user_id = user.user_id)';
            }

            if ($fetchOptions['join'] & self::FETCH_USER_OPTION) {
                $selectFields .= ',
					user_option.*';
                $joinTables .= '
					INNER JOIN xf_user_option AS user_option ON
						(user_option.user_id = user.user_id)';
            }

            if ($fetchOptions['join'] & self::FETCH_USER_PRIVACY) {
                $selectFields .= ',
					user_privacy.*';
                $joinTables .= '
					INNER JOIN xf_user_privacy AS user_privacy ON
						(user_privacy.user_id = user.user_id)';
            }
        }

        return array(
            'selectFields' => $selectFields,
            'joinTables' => $joinTables,
            'orderClause' => $orderClause
        );
    }

    /**
     * Prepares a set of conditions to select social_forum_members against.
     *
     * @param array $conditions List of conditions.
     * @param array $fetchOptions The fetch options that have been provided. May
     * be edited if criteria requires.
     * @return string Criteria as SQL for where clause
     */
    public function prepareSocialForumMemberConditions(array $conditions, array &$fetchOptions)
    {
        $db = $this->_getDb();
        $sqlConditions = array();

        if (isset($conditions['social_forum_member_id'])) {
            $sqlConditions[] = 'social_forum_member.social_forum_member_id = ' .
                 $db->quote($conditions['social_forum_member_id']);
        }

        if (isset($conditions['user_ids']) && !empty($conditions['user_ids'])) {
            $sqlConditions[] = 'social_forum_member.user_id IN (' . $db->quote($conditions['user_ids']) . ')';
        } elseif (isset($conditions['user_id'])) {
            $sqlConditions[] = 'social_forum_member.user_id = ' . $db->quote($conditions['user_id']);
        }

        if (isset($conditions['social_forum_id'])) {
            $sqlConditions[] = 'social_forum_member.social_forum_id = ' . $db->quote($conditions['social_forum_id']);
        }

        if (isset($conditions['is_social_forum_creator'])) {
            $sqlConditions[] = 'social_forum_member.is_social_forum_creator = ' .
                 $db->quote($conditions['is_social_forum_creator']);
        }

        if (isset($conditions['is_social_forum_moderator'])) {
            $sqlConditions[] = 'social_forum_member.is_social_forum_moderator = ' .
                 $db->quote($conditions['is_social_forum_moderator']);
        }

        if (isset($conditions['is_approved'])) {
            $sqlConditions[] = 'social_forum_member.is_approved = ' . $db->quote($conditions['is_approved']);
        }

        if (isset($conditions['is_invited'])) {
            $sqlConditions[] = 'social_forum_member.is_invited = ' . $db->quote($conditions['is_invited']);
        }

        return $this->getConditionsForClause($sqlConditions);
    }

    /**
     *
     * @param integer $userId
     * @return integer
     */
    public function getMaximumMembershipForUserId($userId)
    {
        return $this->_getDb()->fetchRow(
            '
				SELECT (max(social_forum_member.is_social_forum_moderator)*max(social_forum_member.is_approved) + max(social_forum_member.is_approved)) AS level
				FROM xf_social_forum_member AS social_forum_member
				WHERE user_id = ?
			', $userId);
    }

    /**
     *
     * @param array $socialForumMember
     * @return boolean
     */
    public function approve(array $socialForumMember)
    {
        if (!$socialForumMember['is_approved']) {
            $writer = XenForo_DataWriter::create('ThemeHouse_SocialGroups_DataWriter_SocialForumMember');
            $writer->setExistingData($socialForumMember);
            $writer->set('is_approved', true);
            $writer->save();

            if (XenForo_Model_Alert::userReceivesAlert($socialForumMember, 'social_forum', 'approve')) {
                $visitor = XenForo_Visitor::getInstance();
                XenForo_Model_Alert::alert($socialForumMember['user_id'], $visitor['user_id'], $visitor['username'],
                    'social_forum', $socialForumMember['social_forum_id'], 'approve');
            }
            return true;
        }
        return false;
    }

    /**
     *
     * @param array $user
     * @param array $socialForum
     * @return boolean
     */
    public function invite(array $user, array $socialForum)
    {
        $socialForumMember = $this->getSocialForumMemberByUserId($socialForum['social_forum_id'], $user['user_id']);
        if ($socialForumMember) {
            if ($this->_getSocialForumModel()->canApproveSocialForumJoinRequest($socialForum)) {
                return $this->approve($socialForumMember);
            }
            return false;
        }

        $writer = XenForo_DataWriter::create('ThemeHouse_SocialGroups_DataWriter_SocialForumMember');
        $writer->set('user_id', $user['user_id']);
        $writer->set('social_forum_id', $socialForum['social_forum_id']);
        if (!$socialForum['social_forum_moderated'] ||
             $this->_getSocialForumModel()->canApproveSocialForumJoinRequest($socialForum)) {
            $writer->set('is_approved', true);
        }
        $writer->set('is_invited', true);
        $writer->save();

        $visitor = XenForo_Visitor::getInstance();

        if (XenForo_Model_Alert::userReceivesAlert($user, 'social_forum', 'invite')) {
            XenForo_Model_Alert::alert($user['user_id'], $visitor['user_id'], $visitor['username'], 'social_forum',
                $socialForum['social_forum_id'], 'invite');
        }

        $conversation = array(
            'title' => new XenForo_Phrase('th_you_have_been_invited_to_x_socialgroups',
                array(
                    'title' => $socialForum['title']
                ))
        );
        $message = new XenForo_Phrase('th_invite_conversation_message_socialgroups',
            array(
                'to' => $user['username'],
                'from' => $visitor['username'],
                'title' => $socialForum['title'],
                'url' => XenForo_Link::buildPublicLink('canonical:social-forums', $socialForum)
            ));
        $recipients = array(
            $user['username']
        );
        $this->startConversation($conversation, $message, $recipients, $visitor);

        return true;
    }

    /**
     *
     * @param int $userId
     * @param int $nodeId
     * @return int false
     */
    public function checkJoinRestrictionsForUser($userId, $nodeId)
    {
        $user = $this->_getUserModel()->getFullUserById($userId,
            array(
                'join' => XenForo_Model_User::FETCH_USER_PERMISSIONS
            ));
        $permissions = XenForo_Permission::unserializePermissions($user['global_permission_cache']);
        $maxSocialForumCount = XenForo_Permission::hasPermission($permissions, 'forum', 'joinSocialForum');

        if ($maxSocialForumCount > 0) {
            if ($maxSocialForumCount <= $user['social_forums_joined']) {
                return $this->_getDb()->delete('xf_social_forum_member',
                    '
                    user_id = ' . $this->_getDb()
                        ->quote($userId) . '
                    AND (is_approved = 0 OR is_invited = 1)
                ');
            }
        }

        $nodePermissions = $this->_getPermissionCacheModel()->getContentPermissionsForItem(
            $user['permission_combination_id'], 'node', $nodeId);
        $maxSocialForumCountForNode = XenForo_Permission::hasContentPermission($nodePermissions, 'joinSocialForum');

        if ($maxSocialForumCountForNode > 0) {
            $socialForumCount = $this->_getSocialForumModel()->countJoinedSocialForumsForUser($userId,
                array(
                    'node_id' => $nodeId
                ));

            if ($maxSocialForumCountForNode <= $socialForumCount) {
                return $this->_getDb()->delete('xf_social_forum_member',
                    '
                    user_id = ' . $this->_getDb()
                        ->quote($userId) . '
                    AND (is_approved = 0 OR is_invited = 1)
                    AND node_id = ' . $this->_getDb()
                        ->quote($nodeId) . '
                ');
            }
        }

        return false;
    }

    public function startConversation(array $conversation, $message, array $recipients,
        XenForo_Visitor $visitor = null)
    {
        if (!$visitor) {
            $visitor = XenForo_Visitor::getInstance();
        }

        $message = $message->__toString();

        $conversationModel = $this->_getConversationModel();
        if ($conversationModel->canStartConversations()) {
            if (in_array($visitor['username'], $recipients)) {
                unset($recipients[array_search($visitor['username'], $recipients)]);
            }
            $users = array();
            if (!empty($recipients)) {
                $users = $this->_getUserModel()->getUsersByNames($recipients,
                    array(
                        'join' => XenForo_Model_User::FETCH_USER_PRIVACY + XenForo_Model_User::FETCH_USER_OPTION,
                        'followingUserId' => $visitor['user_id']
                    ));
            }
            $recipients = array();
            foreach ($users as $user) {
                $errorPhraseKey = '';
                if ($conversationModel->canStartConversationWithUser($user, $errorPhraseKey, $visitor->toArray())) {
                    $recipients[] = $user['username'];
                }
            }

            if (!empty($recipients)) {
                $conversationDw = XenForo_DataWriter::create('XenForo_DataWriter_ConversationMaster');
                $conversationDw->setExtraData(XenForo_DataWriter_ConversationMaster::DATA_ACTION_USER,
                    $visitor->toArray());
                $conversationDw->setExtraData(XenForo_DataWriter_ConversationMaster::DATA_MESSAGE, $message);
                $conversationDw->set('user_id', $visitor['user_id']);
                $conversationDw->set('username', $visitor['username']);
                $conversationDw->bulkSet($conversation);
                $conversationDw->set('open_invite', true);
                $conversationDw->set('conversation_open', true);

                $conversationDw->addRecipientUserNames($recipients); // checks permissions


                $messageDw = $conversationDw->getFirstMessageDw();
                $messageDw->set('message', $message);

                $conversationDw->preSave();

                $conversationDw->save();
                $conversation = $conversationDw->getMergedData();

                $this->_getConversationModel()->markConversationAsRead($conversation['conversation_id'],
                    XenForo_Visitor::getUserId(), XenForo_Application::$time);
                return $conversation;
            }
        }
        return false;
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
     * @return XenForo_Model_Thread
     */
    protected function _getThreadModel()
    {
        return $this->getModelFromCache('XenForo_Model_Thread');
    }

    /**
     *
     * @return XenForo_Model_User
     */
    protected function _getUserModel()
    {
        return $this->getModelFromCache('XenForo_Model_User');
    }

    /**
     *
     * @return XenForo_Model_PermissionCache
     */
    protected function _getPermissionCacheModel()
    {
        return $this->getModelFromCache('XenForo_Model_PermissionCache');
    }

    /**
     *
     * @return XenForo_Model_Conversation
     */
    protected function _getConversationModel()
    {
        return $this->getModelFromCache('XenForo_Model_Conversation');
    }
}