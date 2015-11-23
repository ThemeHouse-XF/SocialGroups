<?php

/**
 *
 * @see XenResource_Model_Resource
 */
class ThemeHouse_SocialGroups_Extend_XenResource_Model_Resource extends XFCP_ThemeHouse_SocialGroups_Extend_XenResource_Model_Resource
{

    /**
     *
     * @see XenResource_Model_Resource::getResourceByDiscussionId
     */
    public function getResourceByDiscussionId($discussionId, array $fetchOptions = array())
    {
        if (ThemeHouse_SocialGroups_SocialForum::hasInstance()) {
            $socialForum = ThemeHouse_SocialGroups_SocialForum::getInstance();

            if (isset($socialForum['social_forum_id'])) {
                return $this->getResourceBySocialForumId($socialForum['social_forum_id'], $fetchOptions);
            }
        }

        return parent::getResourceByDiscussionId($discussionId, $fetchOptions);
    }

    /**
     * Gets a single resource record specified by its social forum ID
     *
     * @param integer $socialForumId
     *
     * @return array
     */
    public function getResourceBySocialForumId($socialForumId, array $fetchOptions = array())
    {
        $joinOptions = $this->prepareResourceFetchOptions($fetchOptions);

        return $this->_getDb()->fetchRow(
            $this->limitQueryResults(
                '
			SELECT resource.*
				' . $joinOptions['selectFields'] . '
			FROM xf_resource AS resource
				' . $joinOptions['joinTables'] . '
			WHERE resource.social_forum_id = ?
		', 1), $socialForumId);
    }
}