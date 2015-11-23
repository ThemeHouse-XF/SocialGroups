<?php

class ThemeHouse_SocialGroups_Listener_TemplateHook extends ThemeHouse_Listener_TemplateHook
{

    protected function _getHooks()
    {
        return array(
            'account_wrapper_sidebar_your_account',
            'thread_create_fields_extra',
            'account_personal_details_biometrics',
            'message_user_info_text',
            'member_view_tabs_heading',
            'member_view_tabs_content',
            'navigation_visitor_tab_links2',
            'resource_view_tabs',
            'th_social_forum_description_above_socialgroups'
        );
    }

    public static function templateHook($hookName, &$contents, array $hookParams, XenForo_Template_Abstract $template)
    {
        $templateHook = new ThemeHouse_SocialGroups_Listener_TemplateHook($hookName, $contents, $hookParams, $template);
        $contents = $templateHook->run();
    }

    protected function _accountWrapperSidebarYourAccount()
    {
        $this->_appendTemplate('th_account_sidebar_your_account_socialgroups');
    }

    protected function _threadCreateFieldsExtra()
    {
        if (isset($this->_hookParams['forum']['social_forum_id'])) {
            $this->_appendTemplate('th_create_fields_extra_socialgroups');
        }
    }

    protected function _accountPersonalDetailsBiometrics()
    {
        $xenOptions = XenForo_Application::get('options');
        if ($xenOptions->th_socialGroups_primaryPostBit || $xenOptions->th_socialGroups_secondaryPostBit) {
            $viewParams = $this->_fetchViewParams();
            $conditions = array(
                'user_id' => XenForo_Visitor::getUserId()
            );
            $viewParams['socialForums'] = $this->_getSocialForumModel()->getSocialForums($conditions);
            if ($xenOptions->th_socialGroups_secondaryPostBit) {
                $viewParams['visitor']['secondary_social_forum_ids'] = array_fill_keys(
                    explode(",", $viewParams['visitor']['secondary_social_forum_ids']), 1);
            }
            $this->_prependTemplate('th_account_social_forums_socialgroups', $viewParams);
        }
    }

    protected function _messageUserInfoText()
    {
        $viewParams = $this->_fetchViewParams();
        if (!isset($viewParams['user']['post_id']))
            return;
        if ($viewParams['user']['primary_social_forum_id']) {
            $socialForum = array(
                'social_forum_id' => $viewParams['user']['primary_social_forum_id'],
                'logo_date' => $viewParams['user']['logo_date'],
                'logo_width' => $viewParams['user']['logo_width'],
                'logo_height' => $viewParams['user']['logo_height'],
                'logo_crop_x' => $viewParams['user']['logo_crop_x'],
                'logo_crop_y' => $viewParams['user']['logo_crop_y']
            );
            $viewParams['user']['primary_social_forum_urls'] = ThemeHouse_SocialGroups_Template_Helper_SocialForum::getAvatarUrls(
                $socialForum);
            $viewParams['user']['primarySocialForum'] = array(
                'social_forum_id' => $viewParams['user']['primary_social_forum_id'],
                'title' => $viewParams['user']['social_forum_title']
            );
        }
        if (isset($viewParams['user']['secondary_social_forums']) && $viewParams['user']['secondary_social_forums']) {
            $viewParams['user']['secondary_social_forums'] = unserialize($viewParams['user']['secondary_social_forums']);
            if (XenForo_Application::get('options')->th_socialGroups_secondaryPostBit) {
                foreach ($viewParams['user']['secondary_social_forums'] as &$socialForum) {
                    $socialForum['urls'] = ThemeHouse_SocialGroups_Template_Helper_SocialForum::getAvatarUrls(
                        $socialForum);
                }
            }
        }
        $this->_appendTemplateAtSlot('message_user_info_text', 'th_message_user_info_socialgroups', $viewParams);
    }

    protected function _memberViewTabsHeading()
    {
        $this->_appendTemplate('th_member_view_tabs_heading_socialgroups');
    }

    protected function _memberViewTabsContent()
    {
        $this->_appendTemplate('th_member_view_tabs_content_socialgroups');
    }

    protected function _navigationVisitorTabLinks2()
    {
        $this->_appendTemplate('th_navigation_visitor_tab_links_socialgroups');
    }

    protected function _resourceViewTabs()
    {
        $viewParams = $this->_fetchViewParams();
        $resource = $viewParams['resource'];
        if ($resource['social_forum_id'] && empty($viewParams['thread'])) {
            $this->_appendTemplate('th_resource_view_tabs_socialgroups', $viewParams);
        }
    }

    protected function _thSocialForumDescriptionAboveSocialgroups()
    {
        $viewParams = $this->_fetchViewParams();
        if ($viewParams['socialForum']['social_forum_type'] == 'resource') {
            /* @var $resourceModel XenResource_Model_Resource */
            $resourceModel = XenForo_Model::create('XenResource_Model_Resource');

            $fetchOptions = array(
                'join' => XenResource_Model_Resource::FETCH_CATEGORY | XenResource_Model_Resource::FETCH_USER |
                     XenResource_Model_Resource::FETCH_ATTACHMENT | XenResource_Model_Resource::FETCH_VERSION,
                    'watchUserId' => XenForo_Visitor::getUserId()
            );

            if (XenForo_Visitor::getInstance()->hasPermission('resource', 'viewDeleted')) {
                $fetchOptions['join'] |= XenResource_Model_Resource::FETCH_DELETION_LOG;
            }

            $resource = $resourceModel->getResourceBySocialForumId($viewParams['socialForum']['social_forum_id'], $fetchOptions);
            if ($resource && $resourceModel->canViewResourceAndContainer($resource, $resource)) {
                /* @var $categoryModel XenResource_Model_Category */
                $categoryModel = XenForo_Model::create('XenResource_Model_Category');

                $updateConditions = $categoryModel->getPermissionBasedFetchConditions($resource);
                if ($updateConditions['deleted'] === true || $updateConditions['moderated'] === true ||
                     $updateConditions['moderated'] == $resource['user_id']) {
                    /* @var $updateModel XenResource_Model_Update */
                    $updateModel = XenForo_Model::create('XenResource_Model_Update');

                    $resourceUpdateCount = $updateModel->countUpdates(
                        $updateConditions + array(
                            'resource_id' => $resource['resource_id'],
                            'resource_update_id_not' => $resource['description_update_id']
                        ));
                } else {
                    $resourceUpdateCount = $resource['update_count'];
                }

                $resource = $resourceModel->prepareResource($resource, $resource);
                $this->_appendTemplate('resource_view_tabs',
                    $viewParams + array(
                        'resource' => $resource,
                        'resourceUpdateCount' => $resourceUpdateCount,
                        'selectedTab' => 'discussion'
                    ));

                ThemeHouse_SocialGroups_SocialForum::getInstance()->setResource($resource);
            }
        }
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