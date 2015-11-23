<?php

/**
 *
 * @see XenForo_Model_Thread
 */
class ThemeHouse_SocialGroups_Extend_XenForo_Model_Thread extends XFCP_ThemeHouse_SocialGroups_Extend_XenForo_Model_Thread
{

    const FETCH_SOCIAL_FORUM_CREATOR = 0x02;

    /**
     *
     * @see XenForo_Model_Thread::prepareThreadFetchOptions()
     */
    public function prepareThreadFetchOptions(array $fetchOptions)
    {
        $threadFetchOptions = parent::prepareThreadFetchOptions($fetchOptions);

        $selectFields = $threadFetchOptions['selectFields'];
        $joinTables = $threadFetchOptions['joinTables'];

        if (!empty($fetchOptions)) {
            $selectFields .= ',
    			social_forum.title AS social_forum_title, social_forum.user_id AS social_forum_user_id, social_forum.style_id AS social_forum_style_id';
            $joinTables .= '
    			LEFT JOIN xf_social_forum AS social_forum ON
    				(social_forum.social_forum_id = thread.social_forum_id)';
        }

        if (!empty($fetchOptions['join'])) {
            if ($fetchOptions['join'] & self::FETCH_FORUM) {
                $selectFields .= ',
					IF (social_forum.title, social_forum.title, node.title) AS node_title';
            }
        }

        if (!empty($fetchOptions['th_join'])) {
            if ($fetchOptions['th_join'] & self::FETCH_SOCIAL_FORUM_CREATOR) {
                $selectFields .= ',
					social_forum_user.username AS social_forum_username';
                $joinTables .= '
					LEFT JOIN xf_user AS social_forum_user ON
						(social_forum.user_id = social_forum_user.user_id)';
            }
        }

        return array(
            'selectFields' => $selectFields,
            'joinTables' => $joinTables,
            'orderClause' => $threadFetchOptions['orderClause']
        );
    }

    /**
     *
     * @see XenForo_Model_Thread::prepareThreadConditions()
     */
    public function prepareThreadConditions(array $conditions, array &$fetchOptions)
    {
        $sqlConditions = array();
        $db = $this->_getDb();

        $sqlConditions[] = parent::prepareThreadConditions($conditions, $fetchOptions);

        if (isset($conditions['social_forum_id'])) {
            $sqlConditions[] = 'thread.social_forum_id = ' . ($conditions['social_forum_id']);
        }

        return $this->getConditionsForClause($sqlConditions);
    }

    /**
     *
     * @see XenForo_Model_Thread::countThreadsInForum()
     */
    public function countThreadsInForum($forumId, array $conditions = array())
    {
        $socialForum = ThemeHouse_SocialGroups_SocialForum::getInstance();
        if (!empty($socialForum)) {
            if (isset($socialForum['social_forum_id'])) {
                $conditions['social_forum_id'] = $socialForum['social_forum_id'];
            }
        }
        return parent::countThreadsInForum($forumId, $conditions);
    }

    /**
     *
     * @see XenForo_Model_Thread::prepareThread()
     */
    public function prepareThread(array $thread, array $forum, array $nodePermissions = null, array $viewingUser = null)
    {
        $this->standardizeViewingUserReferenceForNode($thread['node_id'], $viewingUser, $nodePermissions);

        if (isset($thread['social_forum_title'])) {
            $thread['socialForum'] = array(
                'social_forum_id' => $thread['social_forum_id'],
                'title' => $thread['social_forum_title']
            );
        }

        return parent::prepareThread($thread, $forum, $nodePermissions, $viewingUser);
    }

    /**
     *
     * @see XenForo_Model_Thread::getLastUpdatedThreadInSocialForum()
     */
    public function getLastUpdatedThreadInSocialForum($socialForumId, array $fetchOptions = array())
    {
        $db = $this->_getDb();

        $stateLimit = $this->prepareStateLimitFromConditions($fetchOptions, '', 'discussion_state');

        return $db->fetchRow(
            $db->limit(
                '
				SELECT *
				FROM xf_thread
				WHERE social_forum_id = ?
				AND discussion_type <> \'redirect\'
				AND (' . $stateLimit . ')
				ORDER BY last_post_date DESC
				', 1), $socialForumId);
    }

    /**
     *
     * @see XenForo_Model_Thread::getThreadsInForum()
     */
    public function getThreadsInForum($forumId, array $conditions = array(), array $fetchOptions = array())
    {
        $socialForum = ThemeHouse_SocialGroups_SocialForum::getInstance();
        if (!empty($socialForum)) {
            if (isset($socialForum['social_forum_id'])) {
                $conditions['social_forum_id'] = $socialForum['social_forum_id'];
            }
        }
        return parent::getThreadsInForum($forumId, $conditions, $fetchOptions);
    }

    /**
     *
     * @see XenForo_Model_Thread::getStickyThreadsInForum()
     */
    public function getStickyThreadsInForum($forumId, array $conditions = array(), array $fetchOptions = array())
    {
        $socialForum = ThemeHouse_SocialGroups_SocialForum::getInstance();
        if (!empty($socialForum)) {
            if (isset($socialForum['social_forum_id'])) {
                $conditions['social_forum_id'] = $socialForum['social_forum_id'];
            }
        }
        return parent::getStickyThreadsInForum($forumId, $conditions, $fetchOptions);
    }

    /**
     *
     * @return ThemeHouse_SocialGroups_Model_SocialForum
     */
    protected function _getForumModel()
    {
        return ThemeHouse_SocialGroups_SocialForum::getSocialForumModel();
    }
}