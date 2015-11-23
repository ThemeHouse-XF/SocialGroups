<?php

/**
 *
 * @see XenForo_DataWriter_Discussion_Thread
 */
class ThemeHouse_SocialGroups_Extend_XenForo_DataWriter_Discussion_Thread_Base extends XFCP_ThemeHouse_SocialGroups_Extend_XenForo_DataWriter_Discussion_Thread
{

    /**
     *
     * @see XenForo_DataWriter_Discussion_Thread::_getFields()
     */
    protected function _getFields()
    {
        $fields = parent::_getFields();
        $fields['xf_thread']['social_forum_id'] = array(
            'type' => self::TYPE_UINT,
            'default' => '0'
        );

        return $fields;
    }

    /**
     *
     * @see XenForo_DataWriter_Discussion_Thread::_discussionPreSave()
     */
    protected function _discussionPreSave()
    {
        parent::_discussionPreSave();

        if (ThemeHouse_SocialGroups_SocialForum::hasInstance()) {
            $socialForum = ThemeHouse_SocialGroups_SocialForum::getInstance()->toArray();

            if ($this->get('social_forum_id') && $this->isUpdate() && $this->isChanged('node_id')) {
                $this->set('social_forum_id', 0);
            } else {
                $this->set('social_forum_id', $socialForum['social_forum_id']);
            }
        }
    }

    /**
     *
     * @see XenForo_DataWriter_Discussion_Thread::_updateContainerPostSave()
     */
    protected function _updateContainerPostSave()
    {
        if ($this->isUpdate() && $this->isChanged('social_forum_id')) {
            // this is a move. move is like: inserting into new container...
            if ($this->get('social_forum_id')) {
                $newContainerDw = $this->_getSocialForumDataWriter($this->get('social_forum_id'), $this->_errorHandler);
                if ($newContainerDw) {
                    $newContainerDw->updateCountersAfterDiscussionSave($this, true);
                    if ($newContainerDw->hasChanges()) {
                        $newContainerDw->save();
                    }
                }
            }

            // ...and deleting from old container
            if ($this->getExisting('social_forum_id')) {
                $oldContainerDw = $this->_getSocialForumDataWriter($this->getExisting('social_forum_id'),
                    $this->_errorHandler);
                if ($oldContainerDw) {
                    $oldContainerDw->updateCountersAfterDiscussionDelete($this);
                    if ($oldContainerDw->hasChanges()) {
                        $oldContainerDw->save();
                    }
                }
            }
        } else {
            if ($this->get('social_forum_id')) {
                $containerDw = $this->_getSocialForumDataWriter($this->get('social_forum_id'), $this->_errorHandler);
                if ($containerDw) {
                    $containerDw->updateCountersAfterDiscussionSave($this);
                    if ($containerDw->hasChanges()) {
                        $containerDw->save();
                    }
                }
            } else {
                parent::_updateContainerPostSave();
            }
        }
    }

    /**
     *
     * @see XenForo_DataWriter_Discussion_Thread::_updateContainerPostDelete()
     */
    protected function _updateContainerPostDelete()
    {
        if ($this->get('social_forum_id')) {
            $containerDw = $this->_getSocialForumDataWriter($this->get('social_forum_id'), $this->_errorHandler);
            if ($containerDw) {
                $containerDw->updateCountersAfterDiscussionDelete($this);
                if ($containerDw->hasChanges()) {
                    $containerDw->save();
                }
            } else {
                parent::_updateContainerPostDelete();
            }
        }
    }

    /**
     *
     * @param int $socialForumId
     * @param constant $errorHandler
     * @return ThemeHouse_SocialGroups_DataWriter_SocialForum
     */
    protected function _getSocialForumDataWriter($socialForumId, $errorHandler)
    {
        $dw = XenForo_DataWriter::create('ThemeHouse_SocialGroups_DataWriter_SocialForum', $errorHandler);
        $dw->setExistingData($socialForumId);
        return $dw;
    }

    protected function _discussionPostSaveSocialGroups(array $messages = array())
    {
        if (XenForo_Application::$versionId < 1020000) {
            parent::_discussionPostSave($messages);
        } else {
            parent::_discussionPostSave();
        }

        ThemeHouse_SocialGroups_ControllerHelper_SocialForumThreadPost::uncacheThread($this->get('thread_id'));
    }
}

if (XenForo_Application::$versionId < 1020000) {

    class ThemeHouse_SocialGroups_Extend_XenForo_DataWriter_Discussion_Thread extends ThemeHouse_SocialGroups_Extend_XenForo_DataWriter_Discussion_Thread_Base
    {

        /**
         *
         * @see XenForo_DataWriter_Discussion_Thread::_discussionPostSave()
         */
        protected function _discussionPostSave(array $messages)
        {
            return $this->_discussionPostSaveSocialGroups($messages);
        }
    }
} else {

    class ThemeHouse_SocialGroups_Extend_XenForo_DataWriter_Discussion_Thread extends ThemeHouse_SocialGroups_Extend_XenForo_DataWriter_Discussion_Thread_Base
    {

        /**
         *
         * @see XenForo_DataWriter_Discussion_Thread::_discussionPostSave()
         */
        protected function _discussionPostSave()
        {
            return $this->_discussionPostSaveSocialGroups();
        }
    }
}