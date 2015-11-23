<?php

class ThemeHouse_SocialGroups_Importer_XfAddOns_Groups extends XenForo_Importer_Abstract
{

    protected $_nodeId;

    protected $_config;

    public static function getName()
    {
        return 'Social Groups for XenForo 1.3.5';
    }

    public function configure(XenForo_ControllerAdmin_Abstract $controller, array &$config)
    {
        if ($config) {
            $errors = $this->validateConfiguration($config);
            if ($errors) {
                return $controller->responseError($errors);
            }

            return true;
        } else {
            /* @var $nodeModel XenForo_Model_Node */
            $nodeModel = XenForo_Model::create('XenForo_Model_Node');

            $nodes = $nodeModel->getAllNodes();

            $nodeOptions = array();
            foreach ($nodes as $node) {
                if ($node['node_type_id'] == 'SocialCategory') {
                    $nodeOptions[] = array(
                        'value' => $node['node_id'],
                        'label' => $node['title']
                    );
                }
            }

            $viewParams = array(
                'nodeOptions' => $nodeOptions
            );
        }

        return $controller->responseView('ThemeHouse_SocialGroups_ViewAdmin_Import_XfAddOns_Groups_Config',
            'th_import_xfaddons_config_socialgroups', $viewParams);
    }

    public function validateConfiguration(array &$config)
    {
        $errors = array();

        $config['db']['dbname'] = '';

        if (!$config['node_id']) {
            $errors[] = 'Select a node';
        }

        return $errors;
    }

    public function getSteps()
    {
        return array(
            'socialForums' => array(
                'title' => new XenForo_Phrase('th_import_social_forums_socialgroups')
            ),
            'members' => array(
                'title' => new XenForo_Phrase('th_import_members_socialgroups'),
                'depends' => array(
                    'socialForums'
                )
            ),
            'joinRequests' => array(
                'title' => new XenForo_Phrase('th_import_join_requests_socialgroups'),
                'depends' => array(
                    'socialForums'
                )
            ),
            'owners' => array(
                'title' => new XenForo_Phrase('th_import_creators_socialgroups'),
                'depends' => array(
                    'members'
                )
            ),
            'secondaryGroups' => array(
                'title' => new XenForo_Phrase('th_import_secondary_groups_socialgroups'),
                'depends' => array(
                    'members'
                )
            ),
            'threads' => array(
                'title' => new XenForo_Phrase('th_import_threads_socialgroups'),
                'depends' => array(
                    'socialForums'
                )
            ),
            'posts' => array(
                'title' => new XenForo_Phrase('th_import_posts_socialgroups'),
                'depends' => array(
                    'threads'
                )
            )
        );
    }

    protected function _bootstrap(array $config)
    {
        if ($this->_nodeId) {
            // already run
            return;
        }

        $this->_config = $config;

        $this->_nodeId = $config['node_id'];
    }

    public function stepSocialForums($start, array $options)
    {
        $groups = $this->_db->fetchAll('
			SELECT *
			FROM xf_cz_group
			ORDER BY group_id
		');

        $total = 0;

        XenForo_Db::beginTransaction();

        foreach ($groups as $group) {
            if ($group['group_state'] == 'visible') {
                $writer = XenForo_DataWriter::create('ThemeHouse_SocialGroups_DataWriter_SocialForum');
                $writer->set('title', $group['group_name']);
                $writer->set('description', $group['group_description']);
                switch ($group['group_type']) {
                    case 'public':
                        $writer->set('social_forum_open', 1);
                        $writer->set('social_forum_moderated', 0);
                        break;
                    case 'moderated':
                        $writer->set('social_forum_open', 1);
                        $writer->set('social_forum_moderated', 1);
                        break;
                    case 'invite_only':
                        $writer->set('social_forum_open', 0);
                        $writer->set('social_forum_moderated', 0);
                        break;
                }
                $writer->set('node_id', $this->_nodeId);
                $writer->set('discussion_count', $group['group_thread_count']);
                $writer->set('message_count', $group['group_reply_count']);
                $writer->save();

                $oldId = $group['group_id'];
                $newId = $writer->get('social_forum_id');
                $this->_importModel->logImportData('socialForum', $oldId, $newId);
            }
            $total++;
        }

        XenForo_Db::commit();

        $this->_session->incrementStepImportTotal($total);

        return true;
    }

    public function stepMembers($start, array $options)
    {
        $model = $this->_importModel;

        $socialForumMap = $model->getImportContentMap('socialForum');

        $options = array_merge(array(
            'limit' => 100,
            'max' => false
        ), $options);

        if ($options['max'] === false) {
            $options['max'] = $this->_db->fetchOne('
				SELECT MAX(member_id)
				FROM xf_cz_group_member
			');
        }

        $members = $this->_db->fetchAll(
            $this->_db->limit(
                '
			SELECT *
			FROM xf_cz_group_member
			WHERE member_id > ' . $this->_db->quote($start) . '
			ORDER BY member_id
			', $options['limit']));

        if (!$members) {
            return true;
        }

        $next = 0;
        $total = 0;

        XenForo_Db::beginTransaction();

        foreach ($members as $member) {
            $next = $member['member_id'];
            $socialForumId = $this->_mapLookUp($socialForumMap, $member['group_id']);
            if ($socialForumId) {
                if ($member['user_id']) {
                    $socialForum = ThemeHouse_SocialGroups_SocialForum::setup($socialForumId);
                    $writer = XenForo_DataWriter::create('ThemeHouse_SocialGroups_DataWriter_SocialForumMember');
                    $writer->set('social_forum_id', $socialForumId);
                    $writer->set('user_id', $member['user_id']);
                    $writer->set('join_date', $member['join_date']);
                    $writer->set('is_approved', 1);
                    $writer->save();
                    $total++;
                }
            }
        }

        XenForo_Db::commit();

        $this->_session->incrementStepImportTotal($total);

        return array(
            $next,
            $options,
            $this->_getProgressOutput($next, $options['max'])
        );
    }

    public function stepJoinRequests($start, array $options)
    {
        $model = $this->_importModel;

        $socialForumMap = $model->getImportContentMap('socialForum');

        $options = array_merge(array(
            'limit' => 100,
            'max' => false
        ), $options);

        if ($options['max'] === false) {
            $options['max'] = $this->_db->fetchOne('
				SELECT MAX(request_id)
				FROM xf_cz_join_request
			');
        }

        $requests = $this->_db->fetchAll(
            $this->_db->limit(
                '
			SELECT *
			FROM xf_cz_join_request
			WHERE request_id > ' . $this->_db->quote($start) . '
			ORDER BY request_id
			', $options['limit']));
        if (!$requests) {
            return true;
        }

        $next = 0;
        $total = 0;

        XenForo_Db::beginTransaction();

        foreach ($requests as $request) {
            $next = $request['request_id'];
            $socialForumId = $this->_mapLookUp($socialForumMap, $request['group_id']);
            if ($request['user_id']) {
                $writer = XenForo_DataWriter::create('ThemeHouse_SocialGroups_DataWriter_SocialForumMember');
                $writer->set('social_forum_id', $socialForumId);
                $writer->set('user_id', $request['user_id']);
                $writer->save();
                $total++;
            }
        }

        XenForo_Db::commit();

        $this->_session->incrementStepImportTotal($total);

        return array(
            $next,
            $options,
            $this->_getProgressOutput($next, $options['max'])
        );
    }

    public function stepOwners($start, array $options)
    {
        $model = $this->_importModel;
        /* @var $socialForumMemberModel ThemeHouse_SocialGroups_Model_SocialForumMember */
        $socialForumMemberModel = XenForo_Model::create('ThemeHouse_SocialGroups_Model_SocialForumMember');

        $socialForumMap = $model->getImportContentMap('socialForum');

        $owners = $this->_db->fetchAll('
				SELECT *
				FROM xf_cz_group_owner
				ORDER BY owner_id
			');

        $total = 0;

        XenForo_Db::beginTransaction();

        foreach ($owners as $owner) {
            $socialForumId = $this->_mapLookUp($socialForumMap, $owner['group_id']);
            $member = $socialForumMemberModel->getSocialForumMemberByUserId($socialForumId, $owner['user_id']);
            $writer = XenForo_DataWriter::create('ThemeHouse_SocialGroups_DataWriter_SocialForum');
            $writer->setExistingData($socialForumId);
            $writer->set('user_id', $owner['user_id']);
            $writer->save();
            if ($member) {
                $writer = XenForo_DataWriter::create('ThemeHouse_SocialGroups_DataWriter_SocialForumMember');
                $writer->setExistingData($member);
                $writer->set('is_social_forum_moderator', true);
                $writer->set('is_social_forum_creator', true);
                $writer->save();
                $total++;
            }
        }

        XenForo_Db::commit();

        $this->_session->incrementStepImportTotal($total);

        return true;
    }

    public function stepSecondaryGroups($start, array $options)
    {
        $model = $this->_importModel;

        $socialForumMap = $model->getImportContentMap('socialForum');

        $options = array_merge(array(
            'limit' => 100,
            'max' => false
        ), $options);

        if ($options['max'] === false) {
            $options['max'] = $this->_db->fetchOne('
				SELECT MAX(user_id)
				FROM xf_user_profile
			');
        }

        $secondaryGroups = $this->_db->fetchPairs(
            $this->_db->limit(
                '
			SELECT user_id, xfa_groups_display
			FROM xf_user_profile
			WHERE user_id > ' . $this->_db->quote($start) . '
			ORDER BY user_id
			', $options['limit']));

        if (!$secondaryGroups) {
            return true;
        }

        $next = 0;
        $total = 0;

        XenForo_Db::beginTransaction();

        foreach ($secondaryGroups as $userId => $groupsDisplay) {
            $next = $userId;
            if ($groupsDisplay) {
                $groupsDisplay = unserialize($groupsDisplay);
                $secondarySocialForumIds = array();
                foreach ($groupsDisplay as $groupId) {
                    $secondarySocialForumIds[] = $this->_mapLookUp($socialForumMap, $groupId);
                }
                array_filter($secondarySocialForumIds);
                if (!empty($secondarySocialForumIds)) {
                    /* @var $writer XenForo_DataWriter_User */
                    $writer = XenForo_DataWriter::create('XenForo_DataWriter_User');
                    $writer->setExistingData($userId);
                    $writer->set('secondary_social_forum_ids', implode(',', $secondarySocialForumIds));
                    $writer->save();
                    $total++;
                }
            }
        }

        XenForo_Db::commit();

        $this->_session->incrementStepImportTotal($total);

        return array(
            $next,
            $options,
            $this->_getProgressOutput($next, $options['max'])
        );
    }

    public function stepThreads($start, array $options)
    {
        $model = $this->_importModel;

        $socialForumMap = $model->getImportContentMap('socialForum');

        $options = array_merge(array(
            'limit' => 300,
            'max' => false
        ), $options);

        if ($options['max'] === false) {
            $options['max'] = $this->_db->fetchOne('
				SELECT MAX(thread_id)
				FROM xf_cz_group_thread
			');
        }

        $threads = $this->_db->fetchAll(
            $this->_db->limit(
                '
				SELECT *
				FROM xf_cz_group_thread
				WHERE thread_id > ' . $this->_db->quote($start) . '
				ORDER BY thread_id
			', $options['limit']));
        if (!$threads) {
            return true;
        }

        $next = 0;
        $total = 0;

        XenForo_Db::beginTransaction();

        foreach ($threads as $thread) {
            $next = $thread['thread_id'];

            $socialForumId = $this->_mapLookUp($socialForumMap, $thread['group_id']);

            if (!$socialForumId)
                continue;

            $import = array(
                'title' => $thread['title'],
                'node_id' => $this->_nodeId,
                'user_id' => $thread['user_id'],
                'username' => $thread['username'],
                'discussion_state' => $thread['discussion_state'],
                'social_forum_id' => $socialForumId
            );

            $threadId = $model->importThread($thread['thread_id'], $import);
            if (!$threadId) {
                continue;
            }

            $total++;
        }

        XenForo_Db::commit();

        $this->_session->incrementStepImportTotal($total);

        return array(
            $next,
            $options,
            $this->_getProgressOutput($next, $options['max'])
        );
    }

    public function stepPosts($start, array $options)
    {
        $model = $this->_importModel;

        $threadMap = $model->getImportContentMap('thread');

        /* @var $userModel XenForo_Model_User */
        $userModel = XenForo_Model::create('XenForo_Model_User');

        $options = array_merge(array(
            'limit' => 200,
            'max' => false
        ), $options);

        if ($options['max'] === false) {
            $options['max'] = $this->_db->fetchOne('
				SELECT MAX(post_id)
				FROM xf_cz_group_post
			');
        }

        $posts = $this->_db->fetchAll(
            $this->_db->limit(
                '
			SELECT *
			FROM xf_cz_group_post
			WHERE post_id > ' . $this->_db->quote($start) . '
			ORDER BY post_id
			', $options['limit']));
        if (!$posts) {
            return true;
        }

        $next = 0;
        $total = 0;

        XenForo_Db::beginTransaction();

        foreach ($posts as $post) {
            $next = $post['post_id'];

            $threadId = $this->_mapLookUp($threadMap, $post['thread_id']);
            $thread = XenForo_Model::create('XenForo_Model_Thread')->getThreadById($threadId);

            if (!isset($thread['thread_id'])) {
                continue;
            }

            $import = array(
                'thread_id' => $thread['thread_id'],
                'user_id' => $post['user_id'],
                'post_date' => $post['post_date'],
                'message' => $post['message'],
                'ip_id' => $post['ip_id'],
                'message_state' => $post['message_state'],
                'position' => $post['position'],
                'likes' => $post['likes'],
                'like_users' => $post['like_users']
            );

            $user = $userModel->getUserById($post['user_id']);
            if (isset($user['username'])) {
                $import['username'] = $user['username'];
            } else {
                continue;
            }

            $postId = $model->importPost($post['post_id'], $import);
            /*
			if (!$postId)
			{
				continue;
			}
			$writer = XenForo_DataWriter::create('XenForo_DataWriter_Discussion_Thread');
			$writer->setExistingData($import['thread_id']);
			$socialForumId = $writer->getExisting('social_forum_id');
			$socialForum = ThemeHouse_SocialGroups_SocialForum::create($socialForumId);
			if (!$writer->getExisting('first_post_id'))
			{
				$writer->set('first_post_id', $postId);
				$writer->set('first_post_likes', $import['likes']);
				$writer->set('post_date', $import['post_date']);
			}
			if ($writer->get('last_post_date') < $import['post_date'])
			{
				$writer->set('last_post_date', $import['post_date']);
				$writer->set('last_post_id', $postId);
				$writer->set('last_post_user_id', $import['user_id']);
				$writer->set('last_post_username', $import['username']);
				$writer->save();

				if ($socialForumId)
				{
					$writer = XenForo_DataWriter::create('ThemeHouse_SocialGroups_DataWriter_SocialForum');
					$writer->setExistingData($socialForumId);
					$writer->set('last_post_date', $import['post_date']);
					$writer->set('last_post_id', $postId);
					$writer->set('last_post_user_id', $import['user_id']);
					$writer->set('last_post_username', $import['username']);
					$writer->save();
				}
			}
			*/
            $total++;
        }

        XenForo_Db::commit();

        $this->_session->incrementStepImportTotal($total);

        return array(
            $next,
            $options,
            $this->_getProgressOutput($next, $options['max'])
        );
    }
}