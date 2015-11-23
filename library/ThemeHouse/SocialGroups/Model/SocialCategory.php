<?php

/**
 * Model for social categories
 */
class ThemeHouse_SocialGroups_Model_SocialCategory extends XenForo_Model_Forum
{

    /**
     * Fetches the combined node-forum record for the specified node name
     *
     * @param string $name Node name
     * @param array $fetchOptions Options that affect what is fetched
     * @return array
     */
    public function getForumByNodeName($name, array $fetchOptions = array())
    {
        $joinOptions = $this->prepareForumJoinOptions($fetchOptions);

        return $this->_getDb()->fetchRow(
            '
			SELECT node.*, forum.*
				' . $joinOptions['selectFields'] . '
			FROM xf_forum AS forum
			INNER JOIN xf_node AS node ON (node.node_id = forum.node_id)
			' . $joinOptions['joinTables'] . '
			WHERE node.node_name = ?
				AND node.node_type_id = \'SocialCategory\'
		', $name);
    }

    /**
     * Determines if a new thread can be posted in the specified forum,
     * with the given permissions.
     * If no permissions are specified, permissions
     * are retrieved from the currently visiting user. This does not check
     * viewing permissions.
     *
     * @param array $forum Info about the forum posting in
     * @param string $errorPhraseKey Returned phrase key for a specific error
     * @param array|null $nodePermissions
     * @param array|null $viewingUser
     *
     * @return boolean
     */
    public function canCreateSocialForumInForum(array $forum, &$errorPhraseKey = '', array $nodePermissions = null,
        array $viewingUser = null)
    {
        $this->standardizeViewingUserReferenceForNode($forum['node_id'], $viewingUser, $nodePermissions);

        $maxSocialForumCount = XenForo_Permission::hasPermission($viewingUser['permissions'], 'forum',
            'createSocialForum');
        $maxSocialForumCountForNode = XenForo_Permission::hasContentPermission($nodePermissions, 'createSocialForum');

        if (!$maxSocialForumCount && !$maxSocialForumCountForNode) {
            return false;
        }

        if ($maxSocialForumCount > 0) {
            if ($maxSocialForumCount <= $viewingUser['social_forums_created']) {
                return false;
            }
        }

        $socialForumCount = $this->_getSocialForumModel()->countSocialForums(
            array(
                'creator_id' => $viewingUser['user_id'],
                'node_id' => $forum['node_id']
            ));

        if ($maxSocialForumCountForNode > 0) {
            if ($maxSocialForumCountForNode <= $socialForumCount) {
                return false;
            }
        }

        return true;
    }

    /**
     * Determines if a social forum in the specified category can be made
     * sticky/unsticky with the given permissions.
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
    public function canStickUnstickSocialForum(array $forum, &$errorPhraseKey = '', array $nodePermissions = null,
        array $viewingUser = null)
    {
        $this->standardizeViewingUserReferenceForNode($forum['node_id'], $viewingUser, $nodePermissions);

        return XenForo_Permission::hasContentPermission($nodePermissions, 'stickUnstickSocialForum');
    }

    /**
     * Determines if members of the specified social category can view social
     * forums with the given permissions.
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
    public function canViewSocialForums(array $forum, &$errorPhraseKey = '', array $nodePermissions = null,
        array $viewingUser = null)
    {
        $this->standardizeViewingUserReferenceForNode($forum['node_id'], $viewingUser, $nodePermissions);

        return XenForo_Permission::hasContentPermission($nodePermissions, 'viewSocialForum');
    }

    /**
     * Determines if members of the specified social category can bypass
     * moderation when creating social forums with the given permissions.
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
    public function canBypassSocialForumModeration(array $forum, &$errorPhraseKey = '', array $nodePermissions = null,
        array $viewingUser = null)
    {
        $this->standardizeViewingUserReferenceForNode($forum['node_id'], $viewingUser, $nodePermissions);

        return XenForo_Permission::hasContentPermission($nodePermissions, 'editSocialForum') &&
             XenForo_Permission::hasContentPermission($nodePermissions, 'deleteSocialForum');
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