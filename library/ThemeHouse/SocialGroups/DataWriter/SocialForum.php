<?php

/**
 * Data writer for Social Forums.
 */
class ThemeHouse_SocialGroups_DataWriter_SocialForum extends XenForo_DataWriter
{

    public static $socialCategoryCache = array();

    const DATA_SOCIAL_CATEGORY = 'socialCategoryInfo';

    protected function _getFields()
    {
        return array(
            'xf_social_forum' => array(
                'social_forum_id' => array(
                    'type' => self::TYPE_UINT,
                    'autoIncrement' => true
                ),
                'group_state' => array(
                    'type' => self::TYPE_STRING,
                    'allowed_values' => array(
                        'visible',
                        'moderated'
                    ),
                    'default' => 'visible'
                ),
                'node_id' => array(
                    'type' => self::TYPE_UINT,
                    'required' => true
                ),
                'title' => array(
                    'type' => self::TYPE_STRING,
                    'required' => true
                ),
                'url_portion' => array(
                    'type' => self::TYPE_STRING,
                    'default' => null,
                    'verification' => array('$this', '_verifyUrlPortion'),
                    'maxLength' => 50,
                ),
                'description' => array(
                    'type' => self::TYPE_STRING
                ),
                'user_id' => array(
                    'type' => self::TYPE_UINT,
                    'default' => 0
                ),
                'created_date' => array(
                    'type' => self::TYPE_UINT,
                    'default' => XenForo_Application::$time
                ),

                // denormalized counters
                'discussion_count' => array(
                    'type' => self::TYPE_UINT_FORCED,
                    'default' => 0
                ),
                'message_count' => array(
                    'type' => self::TYPE_UINT_FORCED,
                    'default' => 0
                ),
                'member_count' => array(
                    'type' => self::TYPE_UINT_FORCED,
                    'default' => 0
                ),

                // denormalized last post info
                'last_post_id' => array(
                    'type' => self::TYPE_UINT,
                    'default' => 0
                ),
                'last_post_date' => array(
                    'type' => self::TYPE_UINT,
                    'default' => 0
                ),
                'last_post_user_id' => array(
                    'type' => self::TYPE_UINT,
                    'default' => 0
                ),
                'last_post_username' => array(
                    'type' => self::TYPE_STRING,
                    'maxLength' => 50,
                    'default' => ''
                ),
                'last_thread_title' => array(
                    'type' => self::TYPE_STRING,
                    'maxLength' => 150,
                    'default' => ''
                ),

                // options
                'social_forum_open' => array(
                    'type' => self::TYPE_BOOLEAN,
                    'default' => 1
                ),
                'social_forum_moderated' => array(
                    'type' => self::TYPE_BOOLEAN,
                    'default' => 0
                ),
                'social_forum_type' => array(
                    'type' => self::TYPE_STRING,
                    'default' => ''
                ),
                'sticky' => array(
                    'type' => self::TYPE_BOOLEAN,
                    'default' => 0
                ),
                'moderate_messages' => array(
                    'type' => self::TYPE_BOOLEAN,
                    'default' => 0
                ),
                'allow_posting' => array(
                    'type' => self::TYPE_BOOLEAN,
                    'default' => 1
                ),
                'count_messages' => array(
                    'type' => self::TYPE_BOOLEAN,
                    'default' => 1
                ),
                'find_new' => array(
                    'type' => self::TYPE_BOOLEAN,
                    'default' => 1
                ),

                // avatar
                'logo_date' => array(
                    'type' => self::TYPE_UINT,
                    'default' => 0
                ),
                'logo_width' => array(
                    'type' => self::TYPE_UINT,
                    'max' => 65535,
                    'default' => 0
                ),
                'logo_height' => array(
                    'type' => self::TYPE_UINT,
                    'max' => 65535,
                    'default' => 0
                ),
                'logo_crop_x' => array(
                    'type' => self::TYPE_UINT,
                    'default' => 0
                ),
                'logo_crop_y' => array(
                    'type' => self::TYPE_UINT,
                    'default' => 0
                ),

                // override style
                'style_id' => array(
                    'type' => self::TYPE_UINT,
                    'default' => 0
                )
            )
        );
    }

    /**
     * Gets the actual existing data out of data that was passed in.
     * See parent for explanation.
     *
     * @param mixed
     * @return array false
     */
    protected function _getExistingData($data)
    {
        if (!$socialForumId = $this->_getExistingPrimaryKey($data)) {
            return false;
        }

        $socialForum = ThemeHouse_SocialGroups_SocialForum::getSocialForumModel()->getSocialForumById($socialForumId);
        if (!$socialForum) {
            return false;
        }

        return $this->getTablesDataFromArray($socialForum);
    }

    protected function _preSave()
    {
        $xenOptions = XenForo_Application::get('options');
        if (!$this->get('url_portion')) {
            if (!empty($xenOptions->th_socialGroups_urlPortions['required'])) {
                $this->error(new XenForo_Phrase('th_please_enter_a_url_portion_socialgroups'), 'new_url_portion');
            }
        }

        if ($this->isChanged('url_portion')) {
            if ($this->get('url_portion'))
            {
                $socialForumModel = ThemeHouse_SocialGroups_SocialForum::getSocialForumModel();
                $conflict = $socialForumModel->getSocialForumById($this->get('url_portion'));
                if ($conflict && $conflict['social_forum_id'] != $this->get('social_forum_id'))
                {
                    $this->error(new XenForo_Phrase('th_url_portions_must_be_unique_socialgroups'), 'new_url_portion');
                }
            }

            if ($this->getExisting('url_portion') && empty($xenOptions->th_socialGroups_urlPortions['editable'])) {
                $this->error(new XenForo_Phrase('th_url_portion_cannot_be_changed_once_set_socialgroups'), 'new_url_portion');
            }
        }
    }

    protected function _postSave()
    {
        $this->_updateModerationQueue();

        ThemeHouse_SocialGroups_SocialForum::setup($this->getMergedData());

        if ($this->isInsert()) {
            $this->addCreatorAsMember();

            $xenOptions = XenForo_Application::get('options');
            if ($xenOptions->th_socialGroups_createToSecondary) {
                $createToSecondary = $xenOptions->th_socialGroups_createToSecondary;
                if (!empty($createToSecondary['type']) &&
                     ($createToSecondary == '_all' ||
                     ($createToSecondary == '_some' && !empty($createToSecondary[$this->get('node_id')])))) {
                    $this->addToSecondarySocialForumIds();
                }
            }
        }

        if ($this->isChanged('title') || $this->isChanged('logo_date') || $this->isChanged('logo_width') ||
             $this->isChanged('logo_height') || $this->isChanged('logo_crop_x') || $this->isChanged('logo_crop_y')) {
            /* @var $socialForumCombinationModel ThemeHouse_SocialGroups_Model_SocialForumCombination */
            $socialForumCombinationModel = $this->getModelFromCache(
                'ThemeHouse_SocialGroups_Model_SocialForumCombination');
            $socialForumCombinationModel->rebuildExistingSocialForumCombinationForSocialForumId(
                $this->get('social_forum_id'));
        }

        if ($this->isChanged('user_id')) {
            if ($this->get('user_id')) {
                $this->_db->query(
                    '
        			UPDATE xf_user_profile AS user_profile
        			SET user_profile.social_forums_created = user_profile.social_forums_created + 1
        			WHERE user_profile.user_id = ?
        		', $this->get('user_id'));
                $this->_getSocialForumMemberModel()->checkJoinRestrictionsForUser($this->get('user_id'),
                    $this->get('node_id'));
            }
            if ($this->getExisting('user_id')) {
                $this->_db->query(
                    '
        			UPDATE xf_user_profile AS user_profile
        			SET user_profile.social_forums_created = IF(user_profile.social_forums_created > 0, user_profile.social_forums_created - 1, 0)
        			WHERE user_profile.user_id = ?
        		', $this->getExisting('user_id'));
            }
        }
    }

    /**
     * Updates the moderation queue if necessary.
     */
    protected function _updateModerationQueue()
    {
        if (!$this->isChanged('group_state')) {
            return;
        }

        if ($this->get('group_state') == 'moderated') {
            $this->getModelFromCache('XenForo_Model_ModerationQueue')->insertIntoModerationQueue('social_forum',
                $this->get('social_forum_id'), $this->get('created_date'));
        } else
            if ($this->getExisting('group_state') == 'moderated') {
                $this->getModelFromCache('XenForo_Model_ModerationQueue')->deleteFromModerationQueue('social_forum',
                    $this->get('social_forum_id'));
            }
    }

    protected function _postDelete()
    {
        $members = $this->_getSocialForumMemberModel()->getSocialForumMembers(
            array(
                'social_forum_id' => $this->getExisting('social_forum_id')
            ));
        foreach ($members as $member) {
            $dw = XenForo_DataWriter::create('ThemeHouse_SocialGroups_DataWriter_SocialForumMember');
            $dw->setOption(ThemeHouse_SocialGroups_DataWriter_SocialForumMember::OPTION_CHECK_SOCIAL_FORUM_CREATOR, false);
            $dw->setExistingData($member);
            $dw->delete();
        }

        if ($this->getExisting('user_id')) {
            $this->_db->query(
                '
    			UPDATE xf_user_profile AS user_profile
    			SET user_profile.social_forums_created = IF(user_profile.social_forums_created > 0, user_profile.social_forums_created - 1, 0)
    			WHERE user_profile.user_id = ?
    		', $this->getExisting('user_id'));
        }

        /* @var $socialForumCombinationModel ThemeHouse_SocialGroups_Model_SocialForumCombination */
        $socialForumCombinationModel = $this->getModelFromCache('ThemeHouse_SocialGroups_Model_SocialForumCombination');
        $socialForumCombinationModel->deleteSocialForumCombinationsForSocialForum($this->get('social_forum_id'));
    }

    public function addCreatorAsMember()
    {
        /* @var $writer ThemeHouse_SocialGroups_DataWriter_SocialForumMember */
        $writer = XenForo_DataWriter::create('ThemeHouse_SocialGroups_DataWriter_SocialForumMember');
        $writer->setExtraData(ThemeHouse_SocialGroups_DataWriter_SocialForumMember::DATA_SOCIAL_FORUM, $this->getMergedData());
        $writer->bulkSet(
            array(
                'social_forum_id' => $this->get('social_forum_id'),
                'user_id' => $this->get('user_id'),
                'is_social_forum_moderator' => true,
                'is_social_forum_creator' => true,
                'is_approved' => true
            ));
        $writer->save();
    }

    public function addToSecondarySocialForumIds()
    {
        /* @var $writer XenForo_DataWriter_User */
        $writer = XenForo_DataWriter::create('XenForo_DataWriter_User');
        $writer->setExistingData($this->get('user_id'));
        $primarySocialForumId = $writer->get('primary_social_forum_id');
        $secondarySocialForumIds = $writer->get('secondary_social_forum_ids');
        $secondarySocialForumIds = explode(',', $secondarySocialForumIds);
        $secondarySocialForumIds = array_filter($secondarySocialForumIds);
        if (!in_array($this->get('social_forum_id'), $secondarySocialForumIds) &&
             $this->get('social_forum_id') != $primarySocialForumId) {
            $secondarySocialForumIds[] = $this->get('social_forum_id');
            $secondarySocialForumIds = implode(',', $secondarySocialForumIds);
            $writer->set('secondary_social_forum_ids', $secondarySocialForumIds);
            $writer->save();
        }
    }

    /**
     * Gets SQL condition to update the existing record.
     *
     * @return string
     */
    protected function _getUpdateCondition($tableName)
    {
        return 'social_forum_id = ' . $this->_db->quote($this->getExisting('social_forum_id'));
    }

    public function updateCountersAfterDiscussionSave(XenForo_DataWriter_Discussion $discussionDw,
        $forceInsert = false)
    {
        if ($discussionDw->get('discussion_type') == 'redirect') {
            // note: this assumes the discussion type will never change to/from
            // this except at creation
            return;
        }

        if ($discussionDw->get('discussion_state') == 'visible' &&
             ($discussionDw->getExisting('discussion_state') != 'visible' || $forceInsert)) {
            $this->set('discussion_count', $this->get('discussion_count') + 1);
            $this->set('message_count', $this->get('message_count') + $discussionDw->get('reply_count') + 1);
        } elseif ($discussionDw->getExisting('discussion_state') == 'visible' &&
             $discussionDw->get('discussion_state') != 'visible') {
            $this->set('discussion_count', $this->get('discussion_count') - 1);
            $this->set('message_count', $this->get('message_count') - $discussionDw->get('reply_count') - 1);

            if ($discussionDw->get('last_post_id') == $this->get('last_post_id')) {
                $this->updateLastPost();
            }
        } elseif ($discussionDw->get('discussion_state') == 'visible' &&
             $discussionDw->getExisting('discussion_state') == 'visible') {
            // no state change, probably just a reply
            $messageChange = $discussionDw->get('reply_count') - $discussionDw->getExisting('reply_count');
            $this->set('message_count', $this->get('message_count') + $messageChange);
        }

        if ($discussionDw->get('discussion_state') == 'visible' &&
             $discussionDw->get('last_post_date') >= $this->get('last_post_date')) {
            $this->set('last_post_date', $discussionDw->get('last_post_date'));
            $this->set('last_post_id', $discussionDw->get('last_post_id'));
            $this->set('last_post_user_id', $discussionDw->get('last_post_user_id'));
            $this->set('last_post_username', $discussionDw->get('last_post_username'));
            $this->set('last_thread_title', $discussionDw->get('title'));
        } elseif ($discussionDw->get('discussion_state') == 'visible' &&
             $discussionDw->getExisting('discussion_state') == 'visible' &&
             $discussionDw->getExisting('last_post_id') == $this->get('last_post_id') &&
             ($discussionDw->isChanged('last_post_id') || $discussionDw->isChanged('title'))) {
            $this->updateLastPost();
        }

        $categoryDw = $this->_getSocialCategoryDataWriter($this->get('node_id'), $this->_errorHandler);
        $categoryDw->updateCountersAfterDiscussionSave($discussionDw);
        if ($categoryDw->hasChanges()) {
            $categoryDw->save();
        }
    }

    /**
     * Implemented for {@see XenForo_DataWriter_DiscussionContainerInterface}.
     */
    public function updateCountersAfterDiscussionDelete(XenForo_DataWriter_Discussion $discussionDw)
    {
        if ($discussionDw->get('discussion_type') == 'redirect') {
            // note: this assumes the discussion type will never change to/from
            // this except at creation
            return;
        }

        if ($discussionDw->get('discussion_state') == 'visible') {
            $this->set('discussion_count', $this->get('discussion_count') - 1);
            $this->set('message_count', $this->get('message_count') - $discussionDw->get('reply_count') - 1);

            if ($discussionDw->get('last_post_id') == $this->get('last_post_id')) {
                $this->updateLastPost();
            }
        }

        $categoryDw = $this->_getSocialCategoryDataWriter($this->get('node_id'), $this->_errorHandler);
        $categoryDw->updateCountersAfterDiscussionDelete($discussionDw);
        if ($categoryDw->hasChanges()) {
            $categoryDw->save();
        }
    }

    /**
     * Updates the last post information for this forum.
     */
    public function updateLastPost()
    {
        $lastPost = $this->getModelFromCache('XenForo_Model_Thread')->getLastUpdatedThreadInSocialForum(
            $this->get('social_forum_id'));
        if ($lastPost) {
            $this->set('last_post_id', $lastPost['last_post_id']);
            $this->set('last_post_date', $lastPost['last_post_date']);
            $this->set('last_post_user_id', $lastPost['last_post_user_id']);
            $this->set('last_post_username', $lastPost['last_post_username']);
            $this->set('last_thread_title', $lastPost['title']);
        } else {
            $this->set('last_post_id', 0);
            $this->set('last_post_date', 0);
            $this->set('last_post_user_id', 0);
            $this->set('last_post_username', '');
            $this->set('last_thread_title', '');
        }
    }

    /**
     * Updates the member count information for this forum.
     */
    public function updateMemberCount()
    {
        $memberCount = $this->_getSocialForumMemberModel()->countSocialForumMembers(array('social_forum_id' => $this->get('social_forum_id')));
        $this->set('member_count', $memberCount);
    }

    /**
     * Rebuilds the counters for this forum.
     */
    public function rebuildCounters()
    {
        $this->updateLastPost();
        $this->updateMemberCount();
        $this->bulkSet(
            ThemeHouse_SocialGroups_SocialForum::getSocialForumModel()->getSocialForumCounters(
                $this->get('social_forum_id')));
    }

    /**
     * Get the data for the socialCategory the thread is in
     *
     * @return array
     */
    protected function _getSocialCategoryData()
    {
        if (!$socialCategory = $this->getExtraData(self::DATA_SOCIAL_CATEGORY)) {
            $socialCategory = self::getSocialCategoryCacheItem($this->get('node_id'));
        }

        return $socialCategory;
    }

    public static function setSocialCategoryCacheItem(array $socialCategory)
    {
        self::$socialCategoryCache[$socialCategory['node_id']] = $socialCategory;
    }

    public static function getSocialCategoryCacheItem($nodeId)
    {
        if (!self::isSocialCategoryCacheItem($nodeId)) {
            $socialCategory = XenForo_Model::create('ThemeHouse_SocialGroups_Model_SocialCategory')->getForumById($nodeId);
            if (!$socialCategory) {
                self::$socialCategoryCache[$nodeId] = false;
            } else {
                self::setSocialCategoryCacheItem($socialCategory);
            }
        }

        return self::$socialCategoryCache[$nodeId];
    }

    public static function isSocialCategoryCacheItem($socialCategoryId)
    {
        return array_key_exists($socialCategoryId, self::$socialCategoryCache);
    }

    /**
     * Verifies that a URL portion is valid - a-z0-9_-+ valid characters
     *
     * @param string $data
     *
     * @return boolean
     */
    protected function _verifyUrlPortion(&$data)
    {
        if (!$data) {
            $data = null;
            return true;
        }

        if (!preg_match('/^[a-z0-9_\-]+$/i', $data)) {
            $this->error(new XenForo_Phrase('th_please_enter_url_portion_using_alphanumeric_socialgroups'),
                'new_url_portion');
            return false;
        }

        if ($data === strval(intval($data)) || $data == '-') {
            $this->error(new XenForo_Phrase('th_url_portions_contain_more_numbers_hyphen_socialgroups'),
                'new_url_portion');
            return false;
        }

        return true;
    }

    /**
     *
     * @return XenForo_DataWriter_Forum
     */
    protected function _getSocialCategoryDataWriter($socialCategoryId, $errorHandler)
    {
        $dw = XenForo_DataWriter::create('XenForo_DataWriter_Forum', $errorHandler);
        $dw->setExistingData($socialCategoryId);
        return $dw;
    }

    /**
     *
     * @return ThemeHouse_SocialGroups_Model_SocialForumMember
     */
    protected function _getSocialForumMemberModel()
    {
        return $this->getModelFromCache('ThemeHouse_SocialGroups_Model_SocialForumMember');
    }
}