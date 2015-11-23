<?php

/**
 *
 * @see XenForo_ControllerHelper_ForumThreadPost
 */
class ThemeHouse_SocialGroups_Extend_XenForo_ControllerHelper_ForumThreadPost extends XFCP_ThemeHouse_SocialGroups_Extend_XenForo_ControllerHelper_ForumThreadPost
{

    protected static $_threads = array();

    /**
     *
     * @see XenForo_ControllerHelper_ForumThreadPost::assertForumValidAndViewable()
     */
    public function assertForumValidAndViewable($forumIdOrName, array $fetchOptions = array())
    {
        $forum = parent::assertForumValidAndViewable($forumIdOrName, $fetchOptions);
        
        if (ThemeHouse_SocialGroups_SocialForum::hasInstance()) {
            $socialForum = ThemeHouse_SocialGroups_SocialForum::getInstance()->toArray();
            if (isset($socialForum['social_forum_id'])) {
                $forum['social_forum_title'] = $socialForum['title'];
                $forum['social_forum_id'] = $socialForum['social_forum_id'];
                XenForo_Visitor::getInstance()->setNodePermissions($forum['node_id'], $socialForum['node_permissions']);
            }
        }
        
        return $forum;
    }

    /**
     *
     * @see XenForo_ControllerHelper_ForumThreadPost::assertThreadValidAndViewable()
     */
    public function assertThreadValidAndViewable($threadId, array $threadFetchOptions = array(), 
        array $forumFetchOptions = array())
    {
        $thread = $this->getThreadOrError($threadId, $threadFetchOptions);
        
        if ($thread['social_forum_id']) {
            $socialForum = ThemeHouse_SocialGroups_SocialForum::setup($thread['social_forum_id']);
            
            if ($socialForum) {
                $this->_thread['social_forum_user_id'] = $socialForum['user_id'];
            }
        }
        
        return parent::assertThreadValidAndViewable($threadId, $threadFetchOptions, $forumFetchOptions);
    }

    /**
     *
     * @see XenForo_ControllerHelper_ForumThreadPost::getThreadOrError()
     */
    public function getThreadOrError($threadId, array $fetchOptions = array())
    {
        if (empty(self::$_threads[$threadId])) {
            self::$_threads[$threadId] = parent::getThreadOrError($threadId, $fetchOptions);
        }
        return self::$_threads[$threadId];
    }

    public static function uncacheThread($threadId)
    {
        unset(self::$_threads[$threadId]);
    }

    /**
     *
     * @see XenForo_ControllerHelper_ForumThreadPost::getNodeBreadCrumbs()
     */
    public function getNodeBreadCrumbs(array $forum, $includeSelf = true)
    {
        if (ThemeHouse_SocialGroups_SocialForum::hasInstance()) {
            $socialForum = ThemeHouse_SocialGroups_SocialForum::getInstance()->toArray();
            if (isset($socialForum['social_forum_id'])) {
                return $this->_controller->getModelFromCache('ThemeHouse_SocialGroups_Model_SocialForum')->getNodeBreadCrumbs(
                    $socialForum, $forum, $includeSelf);
            }
        }
        return parent::getNodeBreadCrumbs($forum, $includeSelf);
    }
}