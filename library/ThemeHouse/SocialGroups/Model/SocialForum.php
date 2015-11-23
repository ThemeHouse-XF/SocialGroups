<?php

/**
 *
 * @see XenForo_Model_Forum
 */
class ThemeHouse_SocialGroups_Model_SocialForum extends XFCP_ThemeHouse_SocialGroups_Model_SocialForum
{

    /**
     * Constants to allow joins to extra tables in certain queries
     *
     * @var integer Join user table
     * @var integer Join node table
     * @var integer Join post table
     * @var integer Join user table to fetch avatar info of first poster
     * @var integer Join forum table to fetch forum options
     */
    const FETCH_USER = 0x01;

    const FETCH_FORUM = 0x02;

    const FETCH_FIRSTPOST = 0x04;

    const FETCH_AVATAR = 0x08;

    const FETCH_DELETION_LOG = 0x10;

    const FETCH_FORUM_OPTIONS = 0x20;

    const FETCH_SOCIAL_FORUM = 0x40;

    const FETCH_SOCIAL_MEMBER = 0x80;

    const FETCH_SOCIAL_MEMBER_OTHER = 0x100;

    /**
     * Returns a social forum record
     *
     * @param integer $socialForumId
     * @param array $fetchOptions Collection of options related to fetching
     * @return array false
     */
    public function getSocialForumById($socialForumId, array $fetchOptions = array())
    {
        $joinOptions = $this->prepareSocialForumFetchOptions($fetchOptions);

        return $this->_getDb()->fetchRow(
            '
				SELECT social_forum.*
					' . $joinOptions['selectFields'] . '
				FROM xf_social_forum AS social_forum
				' . $joinOptions['joinTables'] . '
				WHERE ' . (is_numeric($socialForumId) ? 'social_forum.social_forum_id = ?' : 'social_forum.url_portion = ?') . '
			', $socialForumId);
    }

    public function getCurrentSocialForumById($socialForumId, array $fetchOptions = array())
    {
        return $this->getSocialForumById($socialForumId, $fetchOptions);
    }

    /**
     * Returns social forum records
     *
     * @param integer $socialForumId
     * @param array $fetchOptions Collection of options related to fetching
     * @return array false
     */
    public function getSocialForumsByIds($socialForumIds, array $fetchOptions = array())
    {
        if (empty($socialForumIds)) {
            return array();
        }

        $joinOptions = $this->prepareSocialForumFetchOptions($fetchOptions);

        return $this->fetchAllKeyed(
            '
				SELECT social_forum.*
					' . $joinOptions['selectFields'] . '
				FROM xf_social_forum AS social_forum
				' . $joinOptions['joinTables'] . '
				WHERE social_forum.social_forum_id IN (' . $this->_getDb()
                ->quote($socialForumIds) . ')
			', 'social_forum_id');
    }

    public function prepareSocialForumFetchOptions(array $fetchOptions)
    {
        $db = $this->_getDb();

        $selectFields = '';
        $joinTables = '';
        $orderBy = '';

        if (!empty($fetchOptions['order'])) {
            $orderBySecondary = '';

            switch ($fetchOptions['order']) {
                case 'member_count':
                case 'discussion_count':
                case 'title':
                case 'created_date':
                    $orderBy = 'social_forum.' . $fetchOptions['order'];
                    break;
                case 'last_post_date':
                default:
                    $orderBy = 'social_forum.last_post_date';
            }
            if (!isset($fetchOptions['orderDirection']) || $fetchOptions['orderDirection'] == 'desc') {
                $orderBy .= ' DESC';
            } else {
                $orderBy .= ' ASC';
            }

            $orderBy .= $orderBySecondary;
        } else {
            $orderBy = 'social_forum.title ASC';
        }

        if (!empty($fetchOptions['join'])) {
            if ($fetchOptions['join'] & self::FETCH_USER) {
                $selectFields .= ',
					user.username';
                $joinTables .= '
					INNER JOIN xf_user AS user ON
						(user.user_id = social_forum.user_id)';
            }

            if ($fetchOptions['join'] & self::FETCH_FORUM) {
                $selectFields .= ',
					node.title AS node_title';
                $joinTables .= '
					INNER JOIN xf_node AS node ON
						(node.node_id = social_forum.node_id)';
            }

            if ($fetchOptions['join'] & self::FETCH_SOCIAL_MEMBER) {
                $selectFields .= ',
					social_forum_member.is_approved, social_forum_member.is_social_forum_moderator, social_forum_member.is_social_forum_creator, social_forum_member.is_invited';
                $joinTables .= '
					LEFT JOIN xf_social_forum_member AS social_forum_member ON
						(social_forum.social_forum_id = social_forum_member.social_forum_id
						AND social_forum_member.user_id = ' .
                     $db->quote(XenForo_Visitor::getUserId()) . ')';
            }

            if ($fetchOptions['join'] & self::FETCH_SOCIAL_MEMBER_OTHER) {
                $selectFields .= ',
				social_forum_member.is_approved, social_forum_member.is_social_forum_moderator, social_forum_member.is_social_forum_creator, social_forum_member.is_invited';
                $joinTables .= '
				LEFT JOIN xf_social_forum_member AS social_forum_member ON
				(social_forum.social_forum_id = social_forum_member.social_forum_id)';
            }

            if ($fetchOptions['join'] & self::FETCH_FORUM_OPTIONS) {
                $selectFields .= ',
					forum.*';
                $joinTables .= '
					INNER JOIN xf_forum AS forum ON
						(forum.node_id = social_forum.node_id)';
            }
        }

        if (isset($fetchOptions['readUserId'])) {
            if (!empty($fetchOptions['readUserId'])) {
                $autoReadDate = XenForo_Application::$time -
                     (XenForo_Application::get('options')->readMarkingDataLifetime * 86400);

                $joinTables .= '
					LEFT JOIN xf_social_forum_read AS social_forum_read ON
						(social_forum_read.social_forum_id = social_forum.social_forum_id
						AND social_forum_read.user_id = ' .
                     $this->_getDb()->quote($fetchOptions['readUserId']) . ')';

                $joinForumRead = (!empty($fetchOptions['includeForumReadDate']) ||
                     (!empty($fetchOptions['join']) && $fetchOptions['join'] & self::FETCH_FORUM));
                if ($joinForumRead) {
                    $joinTables .= '
						LEFT JOIN xf_forum_read AS forum_read ON
							(forum_read.node_id = social_forum.node_id
							AND forum_read.user_id = ' .
                         $this->_getDb()->quote($fetchOptions['readUserId']) . ')';

                    $selectFields .= ",
						GREATEST(COALESCE(social_forum_read.social_forum_read_date, 0), COALESCE(forum_read.forum_read_date, 0), $autoReadDate) AS social_forum_read_date";
                } else {
                    $selectFields .= ",
						IF(social_forum_read.social_forum_read_date > $autoReadDate, social_forum_read.social_forum_read_date, $autoReadDate) AS social_forum_read_date";
                }
            } else {
                $selectFields .= ',
					NULL AS social_forum_read_date';
            }
        }

        if (isset($fetchOptions['watchUserId'])) {
            if (!empty($fetchOptions['watchUserId']) && XenForo_Application::$versionId >= 1020000) {
                $selectFields .= ',
					IF(social_forum_watch.user_id IS NULL, 0, 1) AS social_forum_is_watched';
                $joinTables .= '
					LEFT JOIN xf_social_forum_watch AS social_forum_watch
						ON (social_forum_watch.social_forum_id = social_forum.social_forum_id
						AND social_forum_watch.user_id = ' .
                     $this->_getDb()->quote($fetchOptions['watchUserId']) . ')';
            } else {
                $selectFields .= ',
					0 AS social_forum_is_watched';
            }
        }

        if (!empty($fetchOptions['permissionCombinationId'])) {
            $selectFields .= ',
				permission.cache_value AS node_permission_cache';
            $joinTables .= '
				LEFT JOIN xf_permission_cache_content AS permission
					ON (permission.permission_combination_id = ' . $this->_getDb()->quote($fetchOptions['permissionCombinationId']) . '
						AND permission.content_type = \'node\'
						AND permission.content_id = social_forum.node_id)';
        }

        $this->_prepareSocialForumFetchOptions($fetchOptions, $selectFields, $joinTables, $orderBy);

        return array(
            'selectFields' => $selectFields,
            'joinTables' => $joinTables,
            'orderClause' => ($orderBy ? "ORDER BY $orderBy" : '')
        );
    }

    /**
     * Method designed to be overridden by child classes to add to SQL snippets.
     *
     * @param array $fetchOptions containing a 'join' integer key built from
     * this class's FETCH_x bitfields.
     * @param string $selectFields = ', user.*, foo.title'
     * @param string $joinTables = ' INNER JOIN foo ON (foo.id = other.id) '
     * @param string $orderBy = 'x.y ASC, x.z DESC'
     */
    protected function _prepareSocialForumFetchOptions(array &$fetchOptions, &$selectFields, &$joinTables, &$orderBy)
    {
    }

    /**
     * Prepares a collection of social forum fetching related conditions into an
     * SQL clause
     *
     * @param array $conditions List of conditions
     * @param array $fetchOptions Modifiable set of fetch options (may have
     * joins pushed on to it)
     * @return string SQL clause (at least 1=1)
     */
    public function prepareSocialForumConditions(array $conditions, array &$fetchOptions)
    {
        $sqlConditions = array();
        $db = $this->_getDb();

        if (!empty($conditions['forum_id']) && empty($conditions['node_id'])) {
            $conditions['node_id'] = $conditions['forum_id'];
        }

        if (!empty($conditions['node_id'])) {
            if (is_array($conditions['node_id'])) {
                $sqlConditions[] = 'social_forum.node_id IN (' . $db->quote($conditions['node_id']) . ')';
            } else {
                $sqlConditions[] = 'social_forum.node_id = ' . $db->quote($conditions['node_id']);
            }
        }

        if (isset($conditions['group_states']) && !empty($conditions['group_states'])) {
            $sqlConditions[] = 'social_forum.group_state IN (' . $db->quote($conditions['group_states']) . ')';
        } else
            if (isset($conditions['group_state'])) {
                $sqlConditions[] = 'social_forum.group_state = ' . $db->quote($conditions['group_state']);
            }

        if (isset($conditions['sticky'])) {
            $sqlConditions[] = 'social_forum.sticky = ' . ($conditions['sticky'] ? 1 : 0);
        }

        if (isset($conditions['social_forum_ids'])) {
            $sqlConditions[] = 'social_forum.social_forum_id IN (' . $db->quote($conditions['social_forum_ids']) . ')';
        }

        if (isset($conditions['creator_id'])) {
            $sqlConditions[] = 'social_forum.user_id = ' . $db->quote($conditions['creator_id']);
        }

        if (isset($conditions['user_id'])) {
            $sqlConditions[] = 'social_forum_member.user_id = ' . $db->quote($conditions['user_id']);
            if ($conditions['user_id'] == XenForo_Visitor::getUserId()) {
                if (isset($fetchOptions['join'])) {
                    $fetchOptions['join'] |= self::FETCH_SOCIAL_MEMBER;
                } else {
                    $fetchOptions['join'] = self::FETCH_SOCIAL_MEMBER;
                }
            } else {
                if (isset($fetchOptions['join'])) {
                    $fetchOptions['join'] |= self::FETCH_SOCIAL_MEMBER_OTHER;
                } else {
                    $fetchOptions['join'] = self::FETCH_SOCIAL_MEMBER_OTHER;
                }
            }
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

        $this->_prepareSocialForumConditions($conditions, $fetchOptions, $sqlConditions);

        return $this->getConditionsForClause($sqlConditions);
    }

    /**
     * Method designed to be overridden by child classes to add to set of
     * conditions.
     *
     * @param array $conditions List of conditions.
     * @param array $fetchOptions The fetch options that have been provided. May
     * be edited if criteria requires.
     * @param array $sqlConditions List of conditions as SQL snippets. May be
     * edited if criteria requires.
     */
    protected function _prepareSocialForumConditions(array $conditions, array &$fetchOptions, array &$sqlConditions)
    {
    }

    /**
     * Gets threads that match the given conditions.
     *
     * @param array $conditions Conditions to apply to the fetching
     * @param array $fetchOptions Collection of options that relate to fetching
     * @return array Format: [thread id] => info
     */
    public function getSocialForums(array $conditions = array(), array $fetchOptions = array())
    {
        $whereConditions = $this->prepareSocialForumConditions($conditions, $fetchOptions);

        $sqlClauses = $this->prepareSocialForumFetchOptions($fetchOptions);
        $limitOptions = $this->prepareLimitFetchOptions($fetchOptions);

        return $this->fetchAllKeyed(
            $this->limitQueryResults(
                '
				SELECT social_forum.*
					' . $sqlClauses['selectFields'] . '
				FROM xf_social_forum AS social_forum
				' . $sqlClauses['joinTables'] . '
				WHERE ' . $whereConditions . '
				' . $sqlClauses['orderClause'] . '
			', $limitOptions['limit'],
                $limitOptions['offset']), 'social_forum_id');
    }

    /**
     * Prepares a social forum for display.
     *
     * @param array $socialForum Unprepared social forum
     * @return array Prepared social forum
     */
    public function prepareSocialForum(array $socialForum, array $forum = array(), array $permissions = array())
    {
        $socialForum['hasNew'] = (isset($socialForum['social_forum_read_date']) &&
             $socialForum['social_forum_read_date'] < $socialForum['last_post_date']);

        $socialForum['lastPost'] = array(
            'post_id' => $socialForum['last_post_id'],
            'date' => $socialForum['last_post_date'],
            'user_id' => $socialForum['last_post_user_id'],
            'username' => $socialForum['last_post_username'],
            'title' => $socialForum['last_thread_title']
        );

        $permissions = $this->getSocialForumPermissions($socialForum, $permissions);

        if (!isset($permissions['viewOthers']) || !$permissions['viewOthers']) {
            $socialForum['privateInfo'] = true;
        }

        $socialForum['description'] = XenForo_Helper_String::wholeWordTrim($socialForum['description'], 200);

        return $socialForum;
    }

    /**
     * Gets the count of social forums with the specified criteria.
     *
     * @param array $conditions Conditions to apply to the fetching
     * @return integer
     */
    public function countSocialForums(array $conditions)
    {
        $fetchOptions = array();
        $whereConditions = $this->prepareSocialForumConditions($conditions, $fetchOptions);

        $sqlClauses = $this->prepareSocialForumFetchOptions($fetchOptions);

        return $this->_getDb()->fetchOne(
            '
				SELECT COUNT(*)
				FROM xf_social_forum AS social_forum
				' . $sqlClauses['joinTables'] . '
				WHERE ' . $whereConditions . '
			');
    }

    /**
     * Gets social forums that belong to the specified forum.
     *
     * @param integer $forumId
     * @param array $conditions Conditions to apply to the fetching
     * @param array $fetchOptions Collection of options that relate to fetching
     * @return array Format: [thread id] => info
     */
    public function getSocialForumsInForum($forumId, array $conditions = array(), array $fetchOptions = array())
    {
        $conditions['forum_id'] = $forumId;
        return $this->getSocialForums($conditions, $fetchOptions);
    }

    /**
     * Gets sticky social forums that belong to the specified forum.
     *
     * @param integer $forumId
     * @param array $conditions Conditions to apply to the fetching
     * @param array $fetchOptions Collection of options that relate to fetching
     * @return array Format: [thread id] => info
     */
    public function getStickySocialForumsInForum($forumId, array $conditions = array(), array $fetchOptions = array())
    {
        $conditions['forum_id'] = $forumId;
        $conditions['sticky'] = true;
        return $this->getSocialForums($conditions, $fetchOptions);
    }

    /**
     * Gets the count of social forums in the specified forum.
     *
     * @param integer $forumId
     * @param array $conditions Conditions to apply to the fetching
     * @return integer
     */
    public function countSocialForumsInForum($forumId, array $conditions = array())
    {
        $conditions['forum_id'] = $forumId;
        return $this->countSocialForums($conditions);
    }

    /**
     * Gets the total number of social groups that a user has joined.
     *
     * @param integer $userId
     * @param array $conditions
     *
     * @return integer
     */
    public function countJoinedSocialForumsForUser($userId, array $conditions = array())
    {
        $fetchOptions = array();
        $whereConditions = $this->prepareSocialForumConditions($conditions, $fetchOptions);

        $sqlClauses = $this->prepareSocialForumFetchOptions($fetchOptions);

        return $this->_getDb()->fetchOne(
            '
            SELECT COUNT(*)
            FROM xf_social_forum_member AS social_forum_member
            INNER JOIN xf_social_forum AS social_forum ON (social_forum_member.social_forum_id = social_forum.social_forum_id)
			' . $sqlClauses['joinTables'] . '
            WHERE ' . $whereConditions . '
                AND social_forum_member.user_id = ?
                AND social_forum_member.is_approved = 1
                AND social_forum_member.is_invited = 0
        ', $userId);
    }

    public function getUnreadThreadCountInSocialForum($socialForumId, $userId, $socialForumReadDate = 0,
        $ignored = false)
    {
        if (!$userId) {
            return false;
        }

        if ($ignored && is_string($ignored)) {
            $ignored = unserialize($ignored);
            $ignored = array_keys($ignored);
        }

        $db = $this->_getDb();

        return $db->fetchOne(
            '
			SELECT COUNT(*)
			FROM xf_thread AS thread
			LEFT JOIN xf_thread_read AS thread_read ON
				(thread_read.thread_id = thread.thread_id AND thread_read.user_id = ?)
			WHERE thread.social_forum_id = ?
				AND thread.last_post_date > ?
				AND (thread_read.thread_id IS NULL OR thread.last_post_date > thread_read.thread_read_date)
				' .
                 ($ignored ? 'AND thread.user_id NOT IN (' . $db->quote($ignored) . ')' : '') . '
				AND thread.discussion_state = \'visible\'
				AND thread.discussion_type <> \'redirect\'
		',
                array(
                    $userId,
                    $socialForumId,
                    $socialForumReadDate
                ));
    }

    /**
     * Marks the specified social forum as read up to a specific time.
     * Social forum must have the
     * social_forum_read_date key.
     *
     * @param array $socialForum Forum info
     * @param integer $readDate Timestamp to mark as read until
     * @param array|null $viewingUser
     *
     * @return boolean True if marked as read
     */
    public function markSocialForumRead(array $socialForum, $readDate, array $viewingUser = null)
    {
        $this->standardizeViewingUserReference($viewingUser);

        $userId = $viewingUser['user_id'];
        if (!$userId) {
            return false;
        }

        if (!array_key_exists('social_forum_read_date', $socialForum)) {
            $socialForum['social_forum_read_date'] = $this->getUserSocialForumReadDate($userId,
                $socialForum['social_forum_id']);
        }

        if ($readDate <= $socialForum['social_forum_read_date']) {
            return false;
        }

        $this->_getDb()->query(
            '
				INSERT INTO xf_social_forum_read
					(user_id, social_forum_id, social_forum_read_date)
				VALUES
					(?, ?, ?)
				ON DUPLICATE KEY UPDATE social_forum_read_date = VALUES(social_forum_read_date)
			',
            array(
                $userId,
                $socialForum['social_forum_id'],
                $readDate
            ));

        return true;
    }

    /**
     * Determine if the social forum should be marked as read and do so if
     * needed.
     *
     * @param array $socialForum
     * @param integer $userId
     * @param array|null $viewingUser
     *
     * @return boolean
     */
    public function markSocialForumReadIfNeeded(array $socialForum, array $viewingUser = null)
    {
        $this->standardizeViewingUserReference($viewingUser);

        $userId = $viewingUser['user_id'];
        if (!$userId) {
            return false;
        }

        if (!array_key_exists('social_forum_read_date', $socialForum)) {
            $socialForum['social_forum_read_date'] = $this->getUserSocialForumReadDate($userId,
                $socialForum['social_forum_id']);
        }

        $unreadThreadCount = $this->getUnreadThreadCountInSocialForum($socialForum['social_forum_id'], $userId,
            $socialForum['social_forum_read_date'], $viewingUser['ignored']);

        if (!$unreadThreadCount) {
            return $this->markSocialForumRead($socialForum, XenForo_Application::$time, $viewingUser);
        } else {
            return false;
        }
    }

    /**
     * Get the time when a user has marked the given social forum as read.
     *
     * @param integer $userId
     * @param integer $socialForumId
     *
     * @return integer null if guest; timestamp otherwise
     */
    public function getUserSocialForumReadDate($userId, $socialForumId)
    {
        if (!$userId) {
            return null;
        }

        $readDate = $this->_getDb()->fetchOne(
            '
			SELECT social_forum_read_date
			FROM xf_social_forum_read
			WHERE user_id = ?
			AND social_forum_id = ?
		', array(
                $userId,
                $socialForumId
            ));

        $autoReadDate = XenForo_Application::$time -
             (XenForo_Application::get('options')->readMarkingDataLifetime * 86400);
        return max($readDate, $autoReadDate);
    }

    protected static function _member($var)
    {
        return (isset($var['user_id']) ? $var['user_id'] == XenForo_Visitor::getUserId() : false);
    }

    public function getNodePermissions(array $socialForum, array $members)
    {
        $visitor = XenForo_Visitor::getInstance();

        $nodePermissions = array();
        $member = array_filter($members, array(
            'self',
            '_member'
        ));
        if (!empty($member)) {
            $nodePermissions = $this->getSocialForumPermissions(array_pop($member),
                $visitor->getNodePermissions($socialForum['node_id']));
        } else {
            $nodePermissions = $this->getSocialForumPermissions(array(),
                $visitor->getNodePermissions($socialForum['node_id']));
        }

        return $nodePermissions;
    }

    public function getSocialForumPermissions(array $user, $permissions)
    {
        $nodePermissions = array();
        if (!empty($user) && $user['is_approved'] && !$user['is_invited']) {
            if ($user['is_social_forum_creator']) {
                if (isset(XenForo_Application::get('options')->th_socialGroups_permissions[3])) {
                    $nodePermissions = XenForo_Application::get('options')->th_socialGroups_permissions[3];
                }
            } elseif ($user['is_social_forum_moderator']) {
                if (isset(XenForo_Application::get('options')->th_socialGroups_permissions[2])) {
                    $nodePermissions = XenForo_Application::get('options')->th_socialGroups_permissions[2];
                }
            } else {
                if (isset(XenForo_Application::get('options')->th_socialGroups_permissions[1])) {
                    $nodePermissions = XenForo_Application::get('options')->th_socialGroups_permissions[1];
                }
            }
        }
        foreach ($nodePermissions as $nodePermission => $permissionValue) {
            if ($permissionValue) {
                $permissions[$nodePermission] = 1;
            }
        }
        if (!empty($user) && ($user['is_approved'] || $user['is_invited'])) {
            $permissions['viewSocialForum'] = 1;
        }
        return $permissions;
    }

    /**
     * Gets the forum counters for the specified forum.
     *
     * @param integer $forumId
     *
     * @return array Keys: discussion_count, message_count
     */
    public function getSocialForumCounters($socialForumId)
    {
        return $this->_getDb()->fetchRow(
            '
				SELECT
				COUNT(*) AS discussion_count,
				COUNT(*) + SUM(reply_count) AS message_count
				FROM xf_thread
				WHERE social_forum_id = ?
				AND discussion_state = \'visible\'
				AND discussion_type <> \'redirect\'
				', $socialForumId);
    }

    public function unlinkMovedThreads()
    {
        $threadIds = $this->_getDb()->fetchCol(
            '
				SELECT thread_id
                FROM xf_thread AS thread
                INNER JOIN xf_social_forum AS social_forum ON (thread.social_forum_id = social_forum.social_forum_id)
                WHERE thread.node_id != social_forum.node_id
				');
        if ($threadIds) {
            $this->_getDb()->query(
                '
                    UPDATE xf_thread SET social_forum_id = 0
                    WHERE thread_id IN (' . $this->_getDb()
                    ->quote($threadIds) . ')
                ');
        }
    }

    public function moveThreads(array $socialForum)
    {
        $this->_getDb()->query('
			UPDATE xf_thread SET node_id = ? WHERE social_forum_id = ?
		', array(
            $socialForum['node_id'],
            $socialForum['social_forum_id']
        ));
    }

    /**
     * Determines if the specified social forum can be viewed with the given
     * permissions.
     * If no permissions are specified, permissions are retrieved from the
     * currently visiting user.
     * This does not check viewing permissions.
     *
     * @param array $socialForum Info about the social forum posting in
     * @param string $errorPhraseKey Returned phrase key for a specific error
     * @param array|null $nodePermissions
     * @param array|null $viewingUser
     *
     * @return boolean
     */
    public function canViewSocialForum(array $socialForum, &$errorPhraseKey = '', array $nodePermissions = null,
        array $viewingUser = null)
    {
        $this->standardizeViewingUserReferenceForNode($socialForum['node_id'], $viewingUser, $nodePermissions);

        if ($socialForum['group_state'] != 'visible' && $socialForum['user_id'] != $viewingUser['user_id']) {
            return XenForo_Permission::hasContentPermission($nodePermissions, 'editSocialForum') &&
                 XenForo_Permission::hasContentPermission($nodePermissions, 'deleteSocialForum');
        }

        return XenForo_Permission::hasContentPermission($nodePermissions, 'viewSocialForum');
    }

    /**
     * Determines if members of the specified social forum can be viewed with
     * the given permissions.
     * If no permissions are specified, permissions are retrieved from the
     * currently visiting user.
     * This does not check viewing permissions.
     *
     * @param array $socialForum Info about the social forum posting in
     * @param string $errorPhraseKey Returned phrase key for a specific error
     * @param array|null $nodePermissions
     * @param array|null $viewingUser
     *
     * @return boolean
     */
    public function canViewSocialForumMembers(array $socialForum, &$errorPhraseKey = '', array $nodePermissions = null,
        array $viewingUser = null)
    {
        $this->standardizeViewingUserReferenceForNode($socialForum['node_id'], $viewingUser, $nodePermissions);

        return XenForo_Permission::hasContentPermission($nodePermissions, 'viewSocialForumMembers');
    }

    /**
     * Determines if the specified social forum can be joined with the given
     * permissions.
     * If no permissions are specified, permissions are retrieved from the
     * currently visiting user.
     * This does not check viewing permissions.
     *
     * @param array $socialForum Info about the social forum posting in
     * @param string $errorPhraseKey Returned phrase key for a specific error
     * @param array|null $nodePermissions
     * @param array|null $viewingUser
     *
     * @return boolean
     */
    public function canJoinSocialForum(array $socialForum, &$errorPhraseKey = '', array $nodePermissions = null,
        array $viewingUser = null)
    {
        $this->standardizeViewingUserReferenceForNode($socialForum['node_id'], $viewingUser, $nodePermissions);

        if ($socialForum['group_state'] != 'visible') {
            return false;
        }

        if (empty($viewingUser['user_id'])) {
            $errorPhraseKey = 'must_be_registered';
            return false;
        }

        if (empty($socialForum['social_forum_open'])) {
            $errorPhraseKey = 'th_social_forum_is_closed_socialgroups';
            return false;
        }

        if (!XenForo_Permission::hasPermission($viewingUser['permissions'], 'forum', 'joinSocialForum')) {
            return false;
        }

        $maxSocialForumCount = XenForo_Permission::hasPermission($viewingUser['permissions'], 'forum',
            'maxSocialForums');
        $maxSocialForumCountForNode = XenForo_Permission::hasContentPermission($nodePermissions, 'maxSocialForums');

        if (!$maxSocialForumCount && !$maxSocialForumCountForNode) {
            return false;
        }

        if ($maxSocialForumCount > 0) {
            if ($maxSocialForumCount <= $viewingUser['social_forums_joined']) {
                return false;
            }
        }

        $socialForumCount = $this->countJoinedSocialForumsForUser($viewingUser['user_id'],
            array(
                'node_id' => $socialForum['node_id']
            ));

        if ($maxSocialForumCountForNode > 0) {
            if ($maxSocialForumCountForNode <= $socialForumCount) {
                return false;
            }
        }

        return true;
    }

    /**
     * Determines if the specified social forum can be left with the given
     * permissions.
     * If no permissions are specified, permissions are retrieved from the
     * currently visiting user.
     * This does not check viewing permissions.
     *
     * @param array $socialForum Info about the social forum
     * @param string $errorPhraseKey Returned phrase key for a specific error
     * @param array|null $nodePermissions
     * @param array|null $viewingUser
     *
     * @return boolean
     */
    public function canLeaveSocialForum(array $socialForum, &$errorPhraseKey = '', array $nodePermissions = null,
        array $viewingUser = null)
    {
        $this->standardizeViewingUserReferenceForNode($socialForum['node_id'], $viewingUser, $nodePermissions);

        if ($socialForum['user_id'] == $viewingUser['user_id']) {
            return false;
        }

        return XenForo_Permission::hasContentPermission($nodePermissions, 'leaveSocialForum');
    }

    /**
     * Determines if the user request to join can be approved in the specified
     * social forum with the given permissions.
     * If no permissions are specified, permissions are retrieved from the
     * currently visiting user.
     * This does not check viewing permissions.
     *
     * @param array $socialForum Info about the social forum
     * @param string $errorPhraseKey Returned phrase key for a specific error
     * @param array|null $nodePermissions
     * @param array|null $viewingUser
     *
     * @return boolean
     */
    public function canApproveSocialForumJoinRequest(array $socialForum, &$errorPhraseKey = '',
        array $nodePermissions = null, array $viewingUser = null)
    {
        $this->standardizeViewingUserReferenceForNode($socialForum['node_id'], $viewingUser, $nodePermissions);

        return XenForo_Permission::hasContentPermission($nodePermissions, 'approveSocialForumJoin');
    }

    /**
     * Determines if the user request to join can be rejected in the specified
     * social forum with the given permissions.
     * If no permissions are specified, permissions are retrieved from the
     * currently visiting user.
     * This does not check viewing permissions.
     *
     * @param array $socialForum Info about the social forum
     * @param string $errorPhraseKey Returned phrase key for a specific error
     * @param array|null $nodePermissions
     * @param array|null $viewingUser
     *
     * @return boolean
     */
    public function canRejectSocialForumJoinRequest(array $socialForum, &$errorPhraseKey = '',
        array $nodePermissions = null, array $viewingUser = null)
    {
        $this->standardizeViewingUserReferenceForNode($socialForum['node_id'], $viewingUser, $nodePermissions);

        return XenForo_Permission::hasContentPermission($nodePermissions, 'rejectSocialForumJoin');
    }

    /**
     * Determines if a membership can be revoked in the specified social forum
     * with the given permissions.
     * If no permissions are specified, permissions are retrieved from the
     * currently visiting user.
     * This does not check viewing permissions.
     *
     * @param array $socialForum Info about the social forum
     * @param string $errorPhraseKey Returned phrase key for a specific error
     * @param array|null $nodePermissions
     * @param array|null $viewingUser
     *
     * @return boolean
     */
    public function canRevokeSocialForumMembership(array $socialForum, &$errorPhraseKey = '',
        array $nodePermissions = null, array $viewingUser = null)
    {
        $this->standardizeViewingUserReferenceForNode($socialForum['node_id'], $viewingUser, $nodePermissions);

        return XenForo_Permission::hasContentPermission($nodePermissions, 'killSocialForumMember');
    }

    /**
     * Determines if the specified social forum can be edited with the given
     * permissions.
     * If no permissions are specified, permissions are retrieved from the
     * currently visiting user.
     * This does not check viewing permissions.
     *
     * @param array $socialForum Info about the social forum
     * @param string $errorPhraseKey Returned phrase key for a specific error
     * @param array|null $nodePermissions
     * @param array|null $viewingUser
     *
     * @return boolean
     */
    public function canEditSocialForum(array $socialForum, &$errorPhraseKey = '', array $nodePermissions = null,
        array $viewingUser = null)
    {
        $this->standardizeViewingUserReferenceForNode($socialForum['node_id'], $viewingUser, $nodePermissions);

        return XenForo_Permission::hasContentPermission($nodePermissions, 'editSocialForum');
    }

    /**
     * Determines if the specified social forum can be moved with the given
     * permissions.
     * If no permissions are specified, permissions are retrieved from the
     * currently visiting user.
     * This does not check viewing permissions.
     *
     * @param array $socialForum Info about the social forum
     * @param string $errorPhraseKey Returned phrase key for a specific error
     * @param array|null $nodePermissions
     * @param array|null $viewingUser
     *
     * @return boolean
     */
    public function canMoveSocialForum(array $socialForum, &$errorPhraseKey = '', array $nodePermissions = null,
        array $viewingUser = null)
    {
        $this->standardizeViewingUserReferenceForNode($socialForum['node_id'], $viewingUser, $nodePermissions);

        return XenForo_Permission::hasContentPermission($nodePermissions, 'moveSocialForum');
    }

    /**
     * Determines if the specified social forum can be deleted with the given
     * permissions.
     * If no permissions are specified, permissions are retrieved from the
     * currently visiting user.
     * This does not check viewing permissions.
     *
     * @param array $socialForum Info about the social forum
     * @param string $errorPhraseKey Returned phrase key for a specific error
     * @param array|null $nodePermissions
     * @param array|null $viewingUser
     *
     * @return boolean
     */
    public function canDeleteSocialForum(array $socialForum, &$errorPhraseKey = '', array $nodePermissions = null,
        array $viewingUser = null)
    {
        $this->standardizeViewingUserReferenceForNode($socialForum['node_id'], $viewingUser, $nodePermissions);

        return XenForo_Permission::hasContentPermission($nodePermissions, 'deleteSocialForum');
    }

    /**
     * Determines if a moderator can be added to the specified social forum can
     * be edited with the given permissions.
     * If no permissions are specified, permissions are retrieved from the
     * currently visiting user.
     * This does not check viewing permissions.
     *
     * @param array $socialForum Info about the social forum
     * @param string $errorPhraseKey Returned phrase key for a specific error
     * @param array|null $nodePermissions
     * @param array|null $viewingUser
     *
     * @return boolean
     */
    public function canAddSocialForumModerator(array $socialForum, &$errorPhraseKey = '', array $nodePermissions = null,
        array $viewingUser = null)
    {
        $this->standardizeViewingUserReferenceForNode($socialForum['node_id'], $viewingUser, $nodePermissions);

        return XenForo_Permission::hasContentPermission($nodePermissions, 'addSocialForumModerator');
    }

    /**
     * Determines if a moderator can be removed from the specified social forum
     * with the given permissions.
     * If no permissions are specified, permissions are retrieved from the
     * currently visiting user.
     * This does not check viewing permissions.
     *
     * @param array $socialForum Info about the social forum
     * @param string $errorPhraseKey Returned phrase key for a specific error
     * @param array|null $nodePermissions
     * @param array|null $viewingUser
     *
     * @return boolean
     */
    public function canRemoveSocialForumModerator(array $socialForum, &$errorPhraseKey = '', array $nodePermissions = null,
        array $viewingUser = null)
    {
        $this->standardizeViewingUserReferenceForNode($socialForum['node_id'], $viewingUser, $nodePermissions);

        return XenForo_Permission::hasContentPermission($nodePermissions, 'killSocialForumModerator');
    }

    /**
     * Determines if a creator can be assigned for the specified social forum
     * with the given permissions.
     * If no permissions are specified, permissions are retrieved from the
     * currently visiting user.
     * This does not check viewing permissions.
     *
     * @param array $socialForum Info about the social forum
     * @param string $errorPhraseKey Returned phrase key for a specific error
     * @param array|null $nodePermissions
     * @param array|null $viewingUser
     *
     * @return boolean
     */
    public function canAssignSocialForumCreator(array $socialForum, &$errorPhraseKey = '', array $nodePermissions = null,
        array $viewingUser = null)
    {
        $this->standardizeViewingUserReferenceForNode($socialForum['node_id'], $viewingUser, $nodePermissions);

        return XenForo_Permission::hasContentPermission($nodePermissions, 'assignSocialForumCreator');
    }

    public function canWatchForum(array $forum, &$errorPhraseKey = '', array $nodePermissions = null,
        array $viewingUser = null)
    {
        $this->standardizeViewingUserReferenceForNode($forum['node_id'], $viewingUser, $nodePermissions);
        return ($viewingUser['user_id'] ? true : false);
    }

    public function getNodeBreadCrumbs(array $socialForum, array $forum, $includeSelf = true)
    {
        $nodeBreadCrumbs = $this->getModelFromCache('XenForo_Model_Node')->getNodeBreadCrumbs($forum, true);
        if ($includeSelf) {
            $nodeBreadCrumbs[] = array(
                'href' => XenForo_Link::buildPublicLink('social-forums', $socialForum),
                'value' => $socialForum['title']
            );
        }
        return $nodeBreadCrumbs;
    }
}