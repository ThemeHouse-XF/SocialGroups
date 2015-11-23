<?php

/**
 * Moderation queue handler for threads.
 *
 * @package XenForo_Moderation
 */
class ThemeHouse_SocialGroups_ModerationQueueHandler_SocialForum extends XenForo_ModerationQueueHandler_Abstract
{

    /**
     * Gets visible moderation queue entries for specified user.
     *
     * @see XenForo_ModerationQueueHandler_Abstract::getVisibleModerationQueueEntriesForUser()
     */
    public function getVisibleModerationQueueEntriesForUser(array $contentIds, array $viewingUser)
    {
        $socialForumModel = ThemeHouse_SocialGroups_SocialForum::getSocialForumModel();
        $socialForums = $socialForumModel->getSocialForumsByIds($contentIds,
            array(
                'join' => ThemeHouse_SocialGroups_Model_SocialForum::FETCH_FORUM |
                     ThemeHouse_SocialGroups_Model_SocialForum::FETCH_USER,
                    'permissionCombinationId' => $viewingUser['permission_combination_id']
            ));

        $output = array();
        foreach ($socialForums as $socialForum) {
            $socialForum['permissions'] = XenForo_Permission::unserializePermissions(
                $socialForum['node_permission_cache']);

            $canManage = true;
            if (!$socialForumModel->canViewSocialForum($socialForum, $null, $socialForum['permissions'], $viewingUser)) {
                $canManage = false;
            } elseif (!XenForo_Permission::hasContentPermission($socialForum['permissions'], 'editSocialForum') ||
                 !XenForo_Permission::hasContentPermission($socialForum['permissions'], 'deleteSocialForum')) {
                $canManage = false;
            }

            if ($canManage) {
                $output[$socialForum['social_forum_id']] = array(
                    'message' => $socialForum['description'],
                    'user' => array(
                        'user_id' => $socialForum['user_id'],
                        'username' => $socialForum['username']
                    ),
                    'title' => $socialForum['title'],
                    'link' => XenForo_Link::buildPublicLink('social-forums', $socialForum),
                    'contentTypeTitle' => new XenForo_Phrase('th_social_forum_socialgroups'),
                    'titleEdit' => true
                );
            }
        }

        return $output;
    }

    /**
     * Approves the specified moderation queue entry.
     *
     * @see XenForo_ModerationQueueHandler_Abstract::approveModerationQueueEntry()
     */
    public function approveModerationQueueEntry($contentId, $message, $title)
    {
        $message = XenForo_Helper_String::autoLinkBbCode($message);

        $dw = XenForo_DataWriter::create('ThemeHouse_SocialGroups_DataWriter_SocialForum',
            XenForo_DataWriter::ERROR_SILENT);
        $dw->setExistingData($contentId);
        $dw->set('group_state', 'visible');
        $dw->set('title', $title);
        $dw->set('description', $message);

        if ($dw->save()) {
            XenForo_Model_Log::logModeratorAction('socialForum', $dw->getMergedData(), 'approve');

            return true;
        } else {
            return false;
        }
    }

    /**
     * Deletes the specified moderation queue entry.
     *
     * @see XenForo_ModerationQueueHandler_Abstract::deleteModerationQueueEntry()
     */
    public function deleteModerationQueueEntry($contentId)
    {
        $dw = XenForo_DataWriter::create('ThemeHouse_SocialGroups_DataWriter_SocialForum',
            XenForo_DataWriter::ERROR_SILENT);
        $dw->setExistingData($contentId);
        $dw->delete();

        if ($dw->save()) {
            XenForo_Model_Log::logModeratorAction('socialForum', $dw->getMergedData(), 'delete',
                array(
                    'reason' => ''
                ));
            return true;
        } else {
            return false;
        }
    }
}