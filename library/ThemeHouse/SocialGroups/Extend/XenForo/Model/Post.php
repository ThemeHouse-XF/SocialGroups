<?php

/**
 *
 * @see XenForo_Model_Post
 */
class ThemeHouse_SocialGroups_Extend_XenForo_Model_Post extends XFCP_ThemeHouse_SocialGroups_Extend_XenForo_Model_Post
{

    /**
     *
     * @see XenForo_Model_Post::preparePostJoinOptions()
     */
    public function preparePostJoinOptions(array $fetchOptions)
    {
        $userFetchOptions = parent::preparePostJoinOptions($fetchOptions);

        $selectFields = $userFetchOptions['selectFields'];
        $joinTables = $userFetchOptions['joinTables'];

        if (!empty($fetchOptions['join'])) {
            if ($fetchOptions['join'] & self::FETCH_USER_PROFILE) {
                $selectFields .= ',
					social_forum.logo_date,
					social_forum.logo_width,
					social_forum.logo_height,
					social_forum.logo_crop_x,
					social_forum.logo_crop_y,
					social_forum.title AS social_forum_title,
					social_forum_combination.cache_value AS secondary_social_forums';
                $joinTables .= '
					LEFT JOIN xf_social_forum AS social_forum ON
    					(social_forum.social_forum_id = user_profile.primary_social_forum_id)
					LEFT JOIN xf_social_forum_combination AS social_forum_combination ON
						(social_forum_combination.social_forum_combination_id = user_profile.social_forum_combination_id)';
            } elseif ($fetchOptions['join'] & self::FETCH_FORUM) {
                $selectFields .= ',
				   IF(thread.social_forum_id, social_forum.title, node.title) AS node_title';
                $joinTables .= '
				   LEFT JOIN xf_social_forum AS social_forum ON
				       (social_forum.social_forum_id = thread.social_forum_id)';
            }
        }

        return array(
            'selectFields' => $selectFields,
            'joinTables' => $joinTables
        );
    }

    /**
     *
     * @see XenForo_Model_Post::preparePost()
     */
    public function preparePost(array $post, array $thread, array $forum, array $nodePermissions = null,
        array $viewingUser = null)
    {
        $socialForums = array();

        $xenOptions = XenForo_Application::get('options');

        if ($xenOptions->th_socialGroups_primarySignature && !empty($post['primary_social_forum_id'])) {
            $socialForums[$post['primary_social_forum_id']] = array(
                'social_forum_id' => $post['primary_social_forum_id'],
                'title' => $post['social_forum_title']
            );
        }
        if ($xenOptions->th_socialGroups_secondarySignature && !empty($post['secondary_social_forums'])) {
            $secondarySocialForums = unserialize($post['secondary_social_forums']);
            foreach ($secondarySocialForums as $socialForumId => $socialForum) {
                $socialForums[$socialForumId] = $socialForum;
            }
        }

        if (!empty($socialForums)) {
            $socialForumUrls = array();
            foreach ($socialForums as $socialForumId => $socialForum) {
                $socialForumUrls[] = '[URL=' . XenForo_Link::buildPublicLink('full:social-forums', $socialForum) . ']' .
                     $socialForum['title'] . '[/URL]';
            }
            $post['signature'] = implode(' - ', $socialForumUrls) .
                 ($post['signature'] ? "\n\n" . $post['signature'] : '');
        }

        return parent::preparePost($post, $thread, $forum, $nodePermissions, $viewingUser);
    }

    /**
     *
     * @see XenForo_Model_Post::canViewPostAndContainer()
     */
    public function canViewPostAndContainer(array $post, array $thread, array $forum, &$errorPhraseKey = '',
        array $nodePermissions = null, array $viewingUser = null)
    {
        if (isset($thread['social_forum_id']) && $thread['social_forum_id']) {
            $socialForumModel = ThemeHouse_SocialGroups_SocialForum::getSocialForumModel();

            $fetchOptions = array(
                'join' => ThemeHouse_SocialGroups_Model_SocialForum::FETCH_SOCIAL_MEMBER
            );

            $socialForum = $socialForumModel->getCurrentSocialForumById($thread['social_forum_id'],
                $fetchOptions);

            if (isset($socialForum['social_forum_id'])) {
                $socialForumMembers = XenForo_Model::create('ThemeHouse_SocialGroups_Model_SocialForumMember')->getSocialForumUsers(
                    array(
                        'social_forum_id' => $socialForum['social_forum_id']
                    ));

                $resetNodePermissions = XenForo_Visitor::getInstance()->getNodePermissions($forum['node_id']);

                $nodePermissions = $socialForumModel->getNodePermissions($socialForum,
                    $socialForumMembers);
                XenForo_Visitor::getInstance()->setNodePermissions($forum['node_id'], $nodePermissions);
            }
        }

        $return = parent::canViewPostAndContainer($post, $thread, $forum, $errorPhraseKey, $nodePermissions, $viewingUser);

        if (isset($resetNodePermissions)) {
            XenForo_Visitor::getInstance()->setNodePermissions($forum['node_id'], $resetNodePermissions);
        }

        return $return;
    }

    /**
     *
     * @see XenForo_Model_Post::canViewAttachmentOnPost()
     */
    public function canViewAttachmentOnPost(array $post, array $thread, array $forum, &$errorPhraseKey = '',
        array $nodePermissions = null, array $viewingUser = null)
    {
        if (isset($thread['social_forum_id']) && $thread['social_forum_id']) {
            $socialForum = ThemeHouse_SocialGroups_SocialForum::setup($thread['social_forum_id']);

            /* @var $socialForumModel ThemeHouse_SocialGroups_Model_SocialForum */
            $socialForumModel = XenForo_Model::create('ThemeHouse_SocialGroups_Model_SocialForum');

            $nodePermissions = $socialForumModel->getNodePermissions($socialForum->toArray(),
                $socialForum->getSocialForumMembers());
            XenForo_Visitor::getInstance()->setNodePermissions($forum['node_id'], $nodePermissions);
        }

        return parent::canViewAttachmentOnPost($post, $thread, $forum, $errorPhraseKey, $nodePermissions, $viewingUser);
    }
}