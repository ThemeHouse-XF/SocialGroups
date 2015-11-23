<?php

/**
 *
 * @see XenResource_DataWriter_Update
 */
class ThemeHouse_SocialGroups_Extend_XenResource_DataWriter_Update extends XFCP_ThemeHouse_SocialGroups_Extend_XenResource_DataWriter_Update
{

    /**
     *
     * @see XenResource_DataWriter_Update::_postSave()
     */
    protected function _updateThread(array $resource)
    {
        if ($resource['social_forum_id']) {
            return $this->_updateSocialForum($resource);
        }

        return parent::_updateThread($resource);
    }

    protected function _updateSocialForum($resource)
    {
        if (!$this->_isFirstVisible || !$resource || !$resource['social_forum_id']) {
            return false;
        }

        $socialForum = ThemeHouse_SocialGroups_SocialForum::setup($resource['social_forum_id'])->toArray();
        if (!$socialForum) {
            return false;
        }

        $forum = $this->getModelFromCache('XenForo_Model_Forum')->getForumById($socialForum['node_id']);
        if (!$forum) {
            return false;
        }

        $threadDw = XenForo_DataWriter::create('XenForo_DataWriter_Discussion_Thread', XenForo_DataWriter::ERROR_SILENT);
        $threadDw->setExtraData(XenForo_DataWriter_Discussion_Thread::DATA_FORUM, $forum);
        $threadDw->bulkSet(
            array(
                'node_id' => $socialForum['node_id'],
                'title' => $this->get('title'),
                'user_id' => $resource['user_id'],
                'username' => $resource['username'],
                'discussion_type' => 'resource'
            ));
        $threadDw->set('discussion_state',
            $this->getModelFromCache('XenForo_Model_Post')
                ->getPostInsertMessageState(array(), $forum));
        $threadDw->setOption(XenForo_DataWriter_Discussion::OPTION_PUBLISH_FEED, false);

        $messageText = $this->get('message');

        // note: this doesn't actually strip the BB code - it will fix the BB code in the snippet though
        $parser = new XenForo_BbCode_Parser(
            XenForo_BbCode_Formatter_Base::create('XenForo_BbCode_Formatter_BbCode_AutoLink', false));
        $snippet = $parser->render(XenForo_Helper_String::wholeWordTrim($messageText, 500));

        $message = new XenForo_Phrase('resource_message_create_update',
            array(
                'title' => $this->get('title'),
                'username' => $resource['username'],
                'userId' => $resource['user_id'],
                'snippet' => $snippet,
                'updateLink' => XenForo_Link::buildPublicLink('canonical:resources/update', $resource,
                    array(
                        'update' => $this->get('resource_update_id')
                    )),
                'resourceTitle' => $resource['title'],
                'resourceLink' => XenForo_Link::buildPublicLink('canonical:resources', $resource)
            ), false);

        $postWriter = $threadDw->getFirstMessageDw();
        $postWriter->set('message', $message->render());
        $postWriter->setExtraData(XenForo_DataWriter_DiscussionMessage_Post::DATA_FORUM, $forum);
        $postWriter->setOption(XenForo_DataWriter_DiscussionMessage::OPTION_PUBLISH_FEED, false);

        if (!$threadDw->save()) {
            return false;
        }

        $this->set('discussion_thread_id', $threadDw->get('thread_id'), '', array(
            'setAfterPreSave' => true
        ));
        $postSaveChanges['discussion_thread_id'] = $threadDw->get('thread_id');

        $this->getModelFromCache('XenForo_Model_Thread')->markThreadRead($threadDw->getMergedData(), $forum,
            XenForo_Application::$time);

        $this->getModelFromCache('XenForo_Model_ThreadWatch')->setThreadWatchStateWithUserDefault($this->get('user_id'),
            $threadDw->get('thread_id'), $this->getExtraData(XenResource_DataWriter_Resource::DATA_THREAD_WATCH_DEFAULT));

        return $postWriter->get('post_id');
    }
}