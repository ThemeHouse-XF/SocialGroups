<?php

/**
 * Alert handler for social forums.
 */
class ThemeHouse_SocialGroups_AlertHandler_SocialForum extends XenForo_AlertHandler_Abstract
{

    /**
     * Fetches the content required by alerts.
     *
     * @param array $contentIds
     * @param XenForo_Model_Alert $model Alert model invoking this
     * @param integer $userId User ID the alerts are for
     * @param array $viewingUser Information about the viewing user (keys:
     * user_id, permission_combination_id, permissions)
     *
     * @return array
     */
    public function getContentByIds(array $contentIds, $model, $userId, array $viewingUser)
    {
        $socialForumModel = ThemeHouse_SocialGroups_SocialForum::getSocialForumModel();

        return $socialForumModel->getSocialForums(array(
            'social_forum_ids' => $contentIds
        ));
    }
}