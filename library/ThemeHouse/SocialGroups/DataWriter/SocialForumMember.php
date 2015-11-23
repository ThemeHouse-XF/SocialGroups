<?php

/**
 * Data writer for Social Forum Members.
 */
class ThemeHouse_SocialGroups_DataWriter_SocialForumMember extends XenForo_DataWriter
{

    public static $socialForumCache = array();

    /**
     * Option that checks whether a user is the social forum creator when
     * deleting members.
     *
     * @var string
     */
    const OPTION_CHECK_SOCIAL_FORUM_CREATOR = 'checkSocialForumCreator';

    const DATA_SOCIAL_FORUM = 'socialForumInfo';

    protected function _getFields()
    {
        return array(
            'xf_social_forum_member' => array(
                'social_forum_member_id' => array(
                    'type' => self::TYPE_UINT,
                    'autoIncrement' => true
                ),
                'user_id' => array(
                    'type' => self::TYPE_UINT,
                    'required' => true
                ),
                'social_forum_id' => array(
                    'type' => self::TYPE_UINT,
                    'required' => true
                ),
                'is_social_forum_moderator' => array(
                    'type' => self::TYPE_BOOLEAN,
                    'default' => 0
                ),
                'is_social_forum_creator' => array(
                    'type' => self::TYPE_BOOLEAN,
                    'default' => 0
                ),
                'join_date' => array(
                    'type' => self::TYPE_UINT,
                    'default' => XenForo_Application::$time
                ),
                'is_approved' => array(
                    'type' => self::TYPE_BOOLEAN,
                    'default' => 0
                ),
                'is_invited' => array(
                    'type' => self::TYPE_BOOLEAN,
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
        if (!$socialForumMemberId = $this->_getExistingPrimaryKey($data)) {
            return false;
        }

        $socialForumMember = $this->_getSocialForumMemberModel()->getSocialForumMemberById($socialForumMemberId);
        if (!$socialForumMember) {
            return false;
        }

        return $this->getTablesDataFromArray($socialForumMember);
    }

    /**
     * Gets SQL condition to update the existing record.
     *
     * @return string
     */
    protected function _getUpdateCondition($tableName)
    {
        return 'social_forum_member_id = ' . $this->_db->quote($this->getExisting('social_forum_member_id'));
    }

    /**
     * Gets the default set of options for this data writer.
     * If in debug mode and we have a development directory config, we set the
     * dev output directory automatically.
     *
     * @return array
     */
    protected function _getDefaultOptions()
    {
        return array(
            self::OPTION_CHECK_SOCIAL_FORUM_CREATOR => true
        );
    }

    /**
     * Pre-save handling.
     */
    protected function _preSave()
    {
        $socialForum = $this->_getSocialForumData();

        $socialForumMember = $this->_getSocialForumMemberModel()->getSocialForumMemberByUserId(
            $this->get('social_forum_id'), $this->get('user_id'));

        if ($socialForumMember && $socialForumMember['social_forum_member_id'] != $this->get('social_forum_member_id')) {
            $this->error(new XenForo_Phrase('th_already_member_of_social_group_socialgroups'));
        }

        if (!$this->isChanged('is_social_forum_creator') || !$this->get('is_social_forum_creator')) {
            if ($socialForum['user_id'] == $this->get('user_id')) {
                $this->set('is_social_forum_creator', true);
            } else {
                $this->set('is_social_forum_creator', false);
            }
        }
    }

    /**
     * Post-save handling.
     */
    protected function _postSave()
    {
        if ($this->isChanged('is_social_forum_creator') && $this->get('is_social_forum_creator')) {
            $socialForum = $this->_getSocialForumData();

            if ($socialForum['user_id'] != $this->get('user_id')) {
                /* @var $dw ThemeHouse_SocialGroups_DataWriter_SocialForum */
                $dw = XenForo_DataWriter::create('ThemeHouse_SocialGroups_DataWriter_SocialForum');
                $dw->setExistingData($socialForum);
                $dw->set('user_id', $this->get('user_id'));
                $dw->save();

                $socialForum = $dw->getMergedData();

                if ($dw->getExisting('user_id')) {
                    $socialForumId = $socialForum['social_forum_id'];
                    $userId = $dw->getExisting('user_id');
                    $socialForumMember = $this->_getSocialForumMemberModel()->getSocialForumMemberByUserId(
                        $socialForumId, $userId);

                    /* @var $dw ThemeHouse_SocialGroups_DataWriter_SocialForumMember */
                    $dw = XenForo_DataWriter::create('ThemeHouse_SocialGroups_DataWriter_SocialForumMember');
                    $dw->setExistingData($socialForumMember);
                    $dw->setExtraData(self::DATA_SOCIAL_FORUM, $socialForum);
                    $dw->set('is_social_forum_creator', 0);
                    $dw->save();
                }
            }
        }

        if ($this->isChanged('is_approved') || $this->isChanged('is_invited')) {
            if (XenForo_Application::$versionId >= 1020000) {
                /* @var $forumWatchModel XenForo_Model_ForumWatch */
                $forumWatchModel = XenForo_Model::create('XenForo_Model_ForumWatch');
                if (!ThemeHouse_SocialGroups_SocialForum::hasInstance()) {
                    ThemeHouse_SocialGroups_SocialForum::setup($this->get('social_forum_id'));
                }
                $socialForum = ThemeHouse_SocialGroups_SocialForum::getInstance();
                if ($socialForum) {
                    $socialForum = $socialForum->toArray();
                } else {
                    $socialForum = array();
                }
            }

            if ($this->get('is_approved') && !$this->get('is_invited')) {
                $this->_db->query(
                    '
        			UPDATE xf_social_forum AS social_forum
        			SET social_forum.member_count = social_forum.member_count + 1
        			WHERE social_forum.social_forum_id = ?
        		', $this->get('social_forum_id'));
                $this->_db->query(
                    '
        			UPDATE xf_user_profile AS user_profile
        			SET user_profile.social_forums_joined = user_profile.social_forums_joined + 1
        			WHERE user_profile.user_id = ?
        		', $this->get('user_id'));
                $this->_getSocialForumMemberModel()->checkJoinRestrictionsForUser($this->get('user_id'),
                    $this->get('node_id'));
                $options = XenForo_Application::get('options');
                if (XenForo_Application::$versionId >= 1020000 &&
                     $options->th_socialGroups_watchForumOnJoin != 'no' && !empty($socialForum['node_id'])) {
                    $sendEmail = ($options->th_socialGroups_watchForumOnJoin == 'yes_with_email');
                    $forumWatchModel->setForumWatchState($this->get('user_id'), $socialForum['node_id'], 'thread', true,
                        $sendEmail);
                }
            } elseif (!$this->isInsert()) {
                $this->_db->query(
                    '
        			UPDATE xf_social_forum AS social_forum
        			SET social_forum.member_count = IF(social_forum.member_count > 0, social_forum.member_count - 1, 0)
        			WHERE social_forum.social_forum_id = ?
        		', $this->get('social_forum_id'));
                $this->_db->query(
                    '
        			UPDATE xf_user_profile AS user_profile
        			SET user_profile.social_forums_joined = IF(user_profile.social_forums_joined > 0, user_profile.social_forums_joined - 1, 0)
        			WHERE user_profile.user_id = ?
        		', $this->get('user_id'));
                if (XenForo_Application::$versionId >= 1020000 && !empty($socialForum['node_id'])) {
                    $forumWatchModel->setForumWatchState($this->get('user_id'), $socialForum['node_id'], 'delete');
                }
            }
        }
    }

    protected function _preDelete()
    {
        if ($this->getOption(self::OPTION_CHECK_SOCIAL_FORUM_CREATOR)) {
            if ($this->get('is_social_forum_creator')) {
                $this->error(
                    new XenForo_Phrase('th_social_forum_creator_is_not_able_to_leave_group_socialgroups'));
            }
        }
    }

    /**
     * Post-delete handling.
     */
    protected function _postDelete()
    {
        if (XenForo_Application::$versionId >= 1020000) {
            /* @var $forumWatchModel XenForo_Model_ForumWatch */
            $forumWatchModel = XenForo_Model::create('XenForo_Model_ForumWatch');
            if (!ThemeHouse_SocialGroups_SocialForum::hasInstance()) {
                ThemeHouse_SocialGroups_SocialForum::setup($this->get('social_forum_id'));
            }
            if (ThemeHouse_SocialGroups_SocialForum::hasInstance()) {
                $socialForum = ThemeHouse_SocialGroups_SocialForum::getInstance()->toArray();
                $forumWatchModel->setForumWatchState($this->get('user_id'), $socialForum['node_id'], 'delete');
            }
        }

        if ($this->getExisting('is_approved') && !$this->getExisting('is_invited')) {
            $this->_db->query(
                '
        			UPDATE xf_social_forum AS social_forum
        			SET social_forum.member_count = IF(social_forum.member_count > 0, social_forum.member_count - 1, 0)
        			WHERE social_forum.social_forum_id = ?
        		', $this->get('social_forum_id'));
            $this->_db->query(
                '
    			UPDATE xf_user_profile AS user_profile
    			SET user_profile.social_forums_joined = IF(user_profile.social_forums_joined > 0, user_profile.social_forums_joined - 1, 0)
    			WHERE user_profile.user_id = ?
    		', $this->getExisting('user_id'));
        }
    }

    /**
     *
     * @see XenForo_DataWriter::setExtraData
     */
    public function setExtraData($name, $value)
    {
        if ($name == self::DATA_SOCIAL_FORUM && is_array($value) && !empty($value['social_forum_id'])) {
            self::setSocialForumCacheItem($value);
        }

        return parent::setExtraData($name, $value);
    }

    /**
     * Get the data for the social forum the member is in
     *
     * @return array
     */
    protected function _getSocialForumData()
    {
        if (!$socialForum = $this->getExtraData(self::DATA_SOCIAL_FORUM)) {
            $socialForum = self::getSocialForumCacheItem($this->get('social_forum_id'));
        }

        return $socialForum;
    }

    public static function setSocialForumCacheItem(array $socialForum)
    {
        self::$socialForumCache[$socialForum['social_forum_id']] = $socialForum;
    }

    public static function getSocialForumCacheItem($socialForumId)
    {
        if (!self::isSocialForumCacheItem($socialForumId)) {
            $socialForum = ThemeHouse_SocialGroups_SocialForum::getSocialForumModel()->getSocialForumById($socialForumId);
            if (!$socialForum) {
                self::$socialForumCache[$socialForumId] = false;
            } else {
                self::setSocialForumCacheItem($socialForum);
            }
        }

        return self::$socialForumCache[$socialForumId];
    }

    public static function isSocialForumCacheItem($socialForumId)
    {
        return array_key_exists($socialForumId, self::$socialForumCache);
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