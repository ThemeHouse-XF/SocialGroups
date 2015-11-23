<?php

/**
 * Controller for handling actions on social categories.
 */
class ThemeHouse_SocialGroups_ControllerPublic_SocialCategory extends XenForo_ControllerPublic_Forum
{

    protected function _postDispatch($controllerResponse, $controllerName, $action)
    {
        parent::_postDispatch($controllerResponse, $controllerName, $action);
        
        if (class_exists('NodesAsTabs_Listen') && method_exists('NodesAsTabs_Listen', 'breadCrumbs')) {
            try {
                $optionsModel = XenForo_Model::create('NodesAsTabs_Model_Options');
                $routeMatch = $this->getRouteMatch();
                $request = $this->getRequest();
                $viewParams = (isset($controllerResponse->params) ? $controllerResponse->params : array());
                
                // DO ROUTE STUFF
                // THIS IS DONE IN CONTROLLER INSTEAD OF ROUTER
                // SO WE CAN ACCESS NODE RECORD TO SAVE A QUERY
                
                $nodeId = 0;
                if (!empty($viewParams['forum']['node_id']))
                    $nodeId = $viewParams['forum']['node_id'];
                $nodeId = ($nodeId ? $nodeId : $optionsModel->getNodeIdFromRequest($request));
                
                $nodeTabId = $optionsModel->handleRoute($nodeId, $routeMatch);
                
                // USED LATER FOR BREADCRUMBS
                $controllerResponse->containerParams['nodeTabId'] = $nodeTabId;
            } catch (Exception $e) {
                // do nothing
            }
        }
    }

    public function actionIndex()
    {
        $forumId = $this->_input->filterSingle('node_id', XenForo_Input::UINT);
        $forumName = $this->_input->filterSingle('node_name', XenForo_Input::STRING);
        if ($forumId || $forumName) {
            return $this->responseReroute(__CLASS__, 'forum');
        }
        
        if ($this->_routeMatch->getResponseType() == 'rss') {
            return $this->getGlobalForumRss();
        }
        
        $this->_routeMatch->setSections('account');
        
        $page = max(1, $this->_input->filterSingle('page', XenForo_Input::UINT));
        $socialForumsPerPage = XenForo_Application::get('options')->socialForumsPerPage;
        
        $this->canonicalizeRequestUrl(
            XenForo_Link::buildPublicLink('social-forums', array(
                'page' => $page
            )));
        
        $forum = array();
        
        $conditions = array(
            'user_id' => XenForo_Visitor::getUserId()
        );
        
        $filter = $this->_input->filterSingle('filter', XenForo_Input::STRING);
        
        if ($filter == 'creator') {
            $conditions['is_social_forum_creator'] = 1;
        } elseif ($filter == 'moderator') {
            $conditions['is_social_forum_moderator'] = 1;
        } elseif ($filter == 'member') {
            $conditions['is_approved'] = 1;
            $conditions['is_invited'] = 0;
        } elseif ($filter == 'awaiting') {
            $conditions['is_approved'] = 0;
            $conditions['is_invited'] = 0;
        } elseif ($filter == 'invited') {
            $conditions['is_invited'] = 1;
        } else {
            $filter = 'all';
        }
        
        $fetchOptions = array(
            'perPage' => $socialForumsPerPage,
            'page' => $page
        );
        unset($fetchElements);
        
        $socialForumModel = $this->_getSocialForumModel();
        
        $totalSocialForums = $socialForumModel->countSocialForums($conditions);
        
        $this->canonicalizePageNumber($page, $socialForumsPerPage, $totalSocialForums, 'social-forums');
        
        if ($totalSocialForums) {
            $socialForums = $socialForumModel->getSocialForums($conditions, $fetchOptions);
        } else {
            $socialForums = array();
        }
        
        foreach ($socialForums as &$socialForum) {
            $forum = array();
            $permissions = array();
            $socialForum = $socialForumModel->prepareSocialForum($socialForum, $forum, $permissions);
        }
        unset($socialForum);
        
        $viewParams = array(
            'socialForums' => $socialForums,
            
            'ignoredNames' => $this->_getIgnoredContentUserNames($socialForums),
            
            'page' => $page,
            'socialForumsPerPage' => $socialForumsPerPage,
            'totalSocialForums' => $totalSocialForums,
            
            'selectedTab' => $filter
        );
        
        return $this->responseView('ThemeHouse_SocialGroups_ViewPublic_SocialForum_List',
            'th_social_forum_list_socialgroups', $viewParams);
    }

    /**
     * Displays the contents of a social category.
     *
     * @return XenForo_ControllerResponse_Abstract
     */
    public function actionForum()
    {
        $forumId = $this->_input->filterSingle('node_id', XenForo_Input::UINT);
        $forumName = $this->_input->filterSingle('node_name', XenForo_Input::STRING);
        if (!$forumId && !$forumName) {
            if ($this->_routeMatch->getResponseType() == 'rss') {
                return $this->getGlobalForumRss();
            } else {
                return $this->responseRedirect(XenForo_ControllerResponse_Redirect::RESOURCE_CANONICAL_PERMANENT, 
                    XenForo_Link::buildPublicLink('index'));
            }
        }
        
        $ftpHelper = $this->getHelper('ForumThreadPost');
        $forum = $this->getHelper('ForumThreadPost')->assertForumValidAndViewable($forumId ? $forumId : $forumName, 
            $this->_getForumFetchOptions());
        $forumId = $forum['node_id'];
        
        $visitor = XenForo_Visitor::getInstance();
        $socialForumModel = $this->_getSocialForumModel();
        $forumModel = $this->_getForumModel();
        
        $page = max(1, $this->_input->filterSingle('page', XenForo_Input::UINT));
        $socialForumsPerPage = XenForo_Application::get('options')->socialForumsPerPage;
        
        $this->canonicalizeRequestUrl(
            XenForo_Link::buildPublicLink('social-categories', $forum, array(
                'page' => $page
            )));
        
        list($defaultOrder, $defaultOrderDirection) = $this->_getDefaultSocialForumSort($forum);
        
        $order = $this->_input->filterSingle('order', XenForo_Input::STRING, 
            array(
                'default' => $defaultOrder
            ));
        $orderDirection = $this->_input->filterSingle('direction', XenForo_Input::STRING, 
            array(
                'default' => $defaultOrderDirection
            ));
        
        $displayConditions = $this->_getDisplayConditions($forum);
        
        $displayConditions['group_state'] = 'visible';
        
        $fetchElements = $this->_getSocialForumFetchElements($forum, $displayConditions, $socialForumsPerPage, $page, 
            $order, $orderDirection);
        $socialForumFetchConditions = $fetchElements['conditions'];
        $socialForumFetchOptions = $fetchElements['options'] + array(
            'perPage' => $socialForumsPerPage,
            'page' => $page,
            'order' => $order,
            'orderDirection' => $orderDirection
        );
        unset($fetchElements);
        
        if (!isset($socialForumFetchConditions['social_forum_ids']) ||
             !empty($socialForumFetchConditions['social_forum_ids'])) {
            $totalSocialForums = $socialForumModel->countSocialForumsInForum($forumId, $socialForumFetchConditions);
        } else {
            $totalSocialForums = 0;
        }
        
        $this->canonicalizePageNumber($page, $socialForumsPerPage, $totalSocialForums, 'social-forums', $forum);
        
        if ($totalSocialForums) {
            $socialForums = $socialForumModel->getSocialForumsInForum($forumId, $socialForumFetchConditions, 
                $socialForumFetchOptions);
        } else {
            $socialForums = array();
        }
        
        if ($page == 1 && $totalSocialForums) {
            $stickySocialForumFetchOptions = $socialForumFetchOptions;
            unset($stickySocialForumFetchOptions['perPage'], $stickySocialForumFetchOptions['page']);
            
            $stickySocialForums = $socialForumModel->getStickySocialForumsInForum($forumId, $socialForumFetchConditions, 
                $stickySocialForumFetchOptions);
        } else {
            $stickySocialForums = array();
        }
        $totalStickySocialForums = count($stickySocialForums);
        
        $canCreateSocialForum = $forumModel->canCreateSocialForumInForum($forum);
        if (($totalSocialForums + $totalStickySocialForums) == 1 && !$canCreateSocialForum) {
            if ($totalStickySocialForums) {
                return $this->responseRedirect(XenForo_ControllerResponse_Redirect::RESOURCE_CANONICAL, 
                    XenForo_Link::buildPublicLink('social-forums', reset($stickySocialForums)));
            } elseif ($totalSocialForums) {
                return $this->responseRedirect(XenForo_ControllerResponse_Redirect::RESOURCE_CANONICAL, 
                    XenForo_Link::buildPublicLink('social-forums', reset($socialForums)));
            }
        }
        
        // prepare all social groups for the social group list
        $inlineModOptions = array();
        $permissions = $visitor->getNodePermissions($forumId);
        
        foreach ($socialForums as &$socialForum) {
            // $socialForumModOptions =
            // $socialForumModel->addInlineModOptionToSocialForum($socialForum,
            // $forum, $permissions);
            // $inlineModOptions += $socialForumModOptions;
            
            $socialForum = $socialForumModel->prepareSocialForum($socialForum, $forum, $permissions);
        }
        foreach ($stickySocialForums as &$socialForum) {
            // $socialForumModOptions =
            // $socialForumModel->addInlineModOptionToSocialForum($socialForum,
            // $forum, $permissions);
            // $inlineModOptions += $socialForumModOptions;
            
            $socialForum = $socialForumModel->prepareSocialForum($socialForum, $forum, $permissions);
        }
        unset($socialForum);
        
        // if we've read everything on the first page of a normal sort order,
        // probably need to mark as read
        if ($visitor['user_id'] && $page == 1 && !$displayConditions && $order == 'last_post_date' &&
             $orderDirection == 'desc' && $forum['forum_read_date'] < $forum['last_post_date']) {
            $hasNew = false;
            foreach ($socialForums as $socialForum) {
                if ($socialForum['hasNew']) {
                    $hasNew = true;
                    break;
                }
            }
            
            if (!$hasNew) {
                // everything read, but forum not marked as read. Let's check.
                $this->_getForumModel()->markForumReadIfNeeded($forum);
            }
        }
        
        // get the ordering params set for the header links
        $orderParams = array();
        foreach ($this->_getSocialForumSortFields($forum) as $field) {
            $orderParams[$field] = $displayConditions;
            $orderParams[$field]['order'] = ($field != $defaultOrder ? $field : false);
            if ($order == $field) {
                $orderParams[$field]['direction'] = ($orderDirection == 'desc' ? 'asc' : 'desc');
            }
        }
        
        $pageNavParams = $displayConditions;
        $pageNavParams['order'] = ($order != $defaultOrder ? $order : false);
        $pageNavParams['direction'] = ($orderDirection != $defaultOrderDirection ? $orderDirection : false);
        
        if (XenForo_Application::$versionId >= 1020000) {
            $canWatchForum = $forumModel->canWatchForum($forum);
        } else {
            $canWatchForum = false;
        }
        
        $viewParams = array(
            'nodeList' => $this->_getNodeModel()->getNodeDataForListDisplay($forum, 0),
            'forum' => $forum,
            'nodeBreadCrumbs' => $ftpHelper->getNodeBreadCrumbs($forum, false),
            
            'canCreateSocialForum' => $canCreateSocialForum,
            'canSearch' => $visitor->canSearch(),
            'canWatchForum' => $canWatchForum,
            
            'inlineModOptions' => $inlineModOptions,
            'socialForums' => $socialForums,
            'stickySocialForums' => $stickySocialForums,
            
            'ignoredNames' => $this->_getIgnoredContentUserNames($socialForums) +
                 $this->_getIgnoredContentUserNames($stickySocialForums),
                
                'order' => $order,
                'orderDirection' => $orderDirection,
                'orderParams' => $orderParams,
                'displayConditions' => $displayConditions,
                
                'pageNavParams' => $pageNavParams,
                'page' => $page,
                'socialForumStartOffset' => ($page - 1) * $socialForumsPerPage + 1,
                'socialForumEndOffset' => ($page - 1) * $socialForumsPerPage + count($socialForums),
                'socialForumsPerPage' => $socialForumsPerPage,
                'totalSocialForums' => $totalSocialForums,
                
                'showCreatedNotice' => $this->_input->filterSingle('created', XenForo_Input::UINT)
        );
        
        return $this->responseView('ThemeHouse_SocialGroups_ViewPublic_SocialCategory_View',
            'th_social_category_view_socialgroups', $viewParams);
    }

    public function actionWatch()
    {
        $response = parent::actionWatch();
        
        if ($response instanceof XenForo_ControllerResponse_Redirect) {
            $stop = $this->_input->filterSingle('stop', XenForo_Input::STRING);
            /* @var $response XenForo_ControllerResponse_Redirect */
            $response->redirectParams['linkPhrase'] = (!$stop ? new XenForo_Phrase(
                'th_unwatch_social_category_socialgroups') : new XenForo_Phrase(
                'th_watch_social_category_socialgroups'));
        } elseif ($response instanceof XenForo_ControllerResponse_View) {
            /* @var $response XenForo_ControllerResponse_View */
            $response->templateName = 'th_social_category_watch_socialgroups';
        }
        
        return $response;
    }

    public function actionCreateSocialForum()
    {
        $forumId = $this->_input->filterSingle('node_id', XenForo_Input::UINT);
        $forumName = $this->_input->filterSingle('node_name', XenForo_Input::STRING);
        
        $ftpHelper = $this->getHelper('ForumThreadPost');
        $forum = $ftpHelper->assertForumValidAndViewable($forumId ? $forumId : $forumName);
        
        $forumId = $forum['node_id'];
        
        $this->_assertCanCreateSocialForumInForum($forum);
        
        $viewParams = array(
            'socialForum' => array(
                'social_forum_open' => 1
            ),
            'forum' => $forum,
            'nodeBreadCrumbs' => $ftpHelper->getNodeBreadCrumbs($forum),
            'member' => array(
                'is_social_forum_moderator' => 1
            ),
            'canStickUnstickSocialForum' => $this->_getForumModel()->canStickUnstickSocialForum($forum),
            'styles' => $this->getModelFromCache('XenForo_Model_Style')->getAllStylesAsFlattenedTree()
        );
        return $this->responseView('ThemeHouse_SocialGroups_ViewPublic_SocialForum_Create',
            'th_social_forum_create_socialgroups', $viewParams);
    }

    public function actionAddSocialForum()
    {
        $this->_assertPostOnly();
        
        $forumId = $this->_input->filterSingle('node_id', XenForo_Input::UINT);
        $forumName = $this->_input->filterSingle('node_name', XenForo_Input::STRING);
        
        $ftpHelper = $this->getHelper('ForumThreadPost');
        $forum = $ftpHelper->assertForumValidAndViewable($forumId ? $forumId : $forumName);
        
        $forumId = $forum['node_id'];
        
        $this->_assertCanCreateSocialForumInForum($forum);
        
        $visitor = XenForo_Visitor::getInstance();
        
        $input = $this->_input->filter(
            array(
                'title' => XenForo_Input::STRING,
                'new_url_portion' => XenForo_Input::STRING,
                'social_forum_open' => XenForo_Input::UINT,
                'social_forum_moderated' => XenForo_Input::UINT,
                'style_id' => XenForo_Input::UINT,
                
                '_set' => array(
                    XenForo_Input::UINT,
                    'array' => true
                ),
                'sticky' => XenForo_Input::UINT
            ));
        
        $xenOptions = XenForo_Application::get('options');
        if (!$xenOptions->th_socialGroups_allowStyleOverride) {
            $input['style_id'] = 0;
        } elseif (!$this->_input->filterSingle('style_override', XenForo_Input::UINT)) {
            $input['style_id'] = 0;
        }
        
        if (empty($xenOptions->th_socialGroups_urlPortions['allow'])) {
            $input['new_url_portion'] = null;
        }
        
        $description = $this->getHelper('Editor')->getMessageText('description', $this->_input);
        $description = XenForo_Helper_String::autoLinkBbCode($description);
        
        $writer = XenForo_DataWriter::create('ThemeHouse_SocialGroups_DataWriter_SocialForum');
        $writer->bulkSet(
            array(
                'title' => $input['title'],
                'url_portion' => $input['new_url_portion'],
                'social_forum_open' => $input['social_forum_open'],
                'social_forum_moderated' => $input['social_forum_moderated'],
                'style_id' => $input['style_id'],
                'description' => $description
            ));
        $writer->set('node_id', $forumId);
        $writer->set('user_id', $visitor['user_id']);
        
        if (!empty($input['_set']['sticky']) && $this->_getForumModel()->canStickUnstickSocialForum($forum)) {
            $writer->set('sticky', $input['sticky']);
        }
        
        if ($xenOptions->th_socialGroups_moderateSocialForums) {
            if (!$this->_getForumModel()->canBypassSocialForumModeration($forum)) {
                $writer->set('group_state', 'moderated');
            }
        }
        
        $writer->save();
        
        $socialForum = $writer->getMergedData();
        $return = XenForo_Link::buildPublicLink('social-forums', $socialForum);
        
        return $this->responseRedirect(XenForo_ControllerResponse_Redirect::SUCCESS, $return, 
            new XenForo_Phrase('th_your_social_forum_has_been_created_socialgroups'));
    }

    /**
     * Shows a preview of the social forum creation.
     *
     * @return XenForo_ControllerResponse_Abstract
     */
    public function actionCreateSocialForumPreview()
    {
        $this->_assertPostOnly();
        
        $forumId = $this->_input->filterSingle('node_id', XenForo_Input::UINT);
        $forumName = $this->_input->filterSingle('node_name', XenForo_Input::STRING);
        
        $ftpHelper = $this->getHelper('ForumThreadPost');
        $forum = $ftpHelper->assertForumValidAndViewable($forumId ? $forumId : $forumName);
        
        $forumId = $forum['node_id'];
        
        $this->_assertCanCreateSocialForumInForum($forum);
        
        $description = $this->getHelper('Editor')->getMessageText('description', $this->_input);
        $description = XenForo_Helper_String::autoLinkBbCode($description);
        
        $viewParams = array(
            'forum' => $forum,
            'description' => $description
        );
        
        return $this->responseView('ThemeHouse_SocialGroups_ViewPublic_SocialForum_CreatePreview',
            'th_social_forum_create_preview_socialgroups', $viewParams);
    }

    protected function _getSocialForumFetchElements(array $forum, array $displayConditions)
    {
        $socialForumModel = $this->_getSocialForumModel();
        $visitor = XenForo_Visitor::getInstance();
        
        $socialForumFetchConditions = $displayConditions;
        
        if ($this->_routeMatch->getResponseType() != 'rss') {
            $socialForumFetchConditions += array(
                'sticky' => 0
            );
        }
        
        $socialForumFetchOptions = array(
            'join' => ThemeHouse_SocialGroups_Model_SocialForum::FETCH_SOCIAL_MEMBER,
            'readUserId' => $visitor['user_id']
        );
        if (!empty($socialForumFetchConditions['deleted'])) {
            $socialForumFetchOptions['join'] |= ThemeHouse_SocialGroups_Model_SocialForum::FETCH_DELETION_LOG;
        }
        
        /*
         * @var $socialCategoryModel ThemeHouse_SocialGroups_Model_SocialCategory
         */
        $socialCategoryModel = XenForo_Model::create('ThemeHouse_SocialGroups_Model_SocialCategory');
        
        // if a user can't view all forums, then we find which ones they can
        if (!$socialCategoryModel->canViewSocialForums($forum)) {
            /*
             * @var $socialForumMemberModel
             * ThemeHouse_SocialGroups_Model_SocialForumMember
             */
            $socialForumMemberModel = XenForo_Model::create('ThemeHouse_SocialGroups_Model_SocialForumMember');
            
            $socialForumMembers = $socialForumMemberModel->getSocialForumMembers(
                array(
                    'user_id' => $visitor['user_id']
                ));
            
            $socialForumIds = array();
            /* @var $options XenForo_Options */
            $options = XenForo_Application::get('options');
            foreach ($socialForumMembers as $socialForumMember) {
                if (isset($options->th_socialGroups_permissions[1]['viewSocialForum']) &&
                     ($options->th_socialGroups_permissions[1]['viewSocialForum'] ||
                     ($socialForumMember['is_social_forum_moderator'] &&
                     $options->th_socialGroups_permissions[2]['viewSocialForum']) ||
                     ($socialForumMember['is_social_forum_creator'] &&
                     $options->th_socialGroups_permissions[3]['viewSocialForum']))) {
                    $socialForumIds[] = $socialForumMember['social_forum_id'];
                }
            }
            
            $socialForumFetchConditions['social_forum_ids'] = $socialForumIds;
        }
        
        return array(
            'conditions' => $socialForumFetchConditions,
            'options' => $socialForumFetchOptions
        );
    }

    protected function _getDefaultSocialForumSort(array $forum)
    {
        $xenOptions = XenForo_Application::get('options');
        
        $defaultSortOrder = $xenOptions->th_socialGroups_defaultSortOrder;
        
        if ($defaultSortOrder) {
            return $defaultSortOrder;
        }
        
        return array(
            'title',
            'asc'
        );
    }

    protected function _getSocialForumSortFields(array $forum)
    {
        return array(
            'title',
            'last_post_date',
            'discussion_count',
            'social_forum_members'
        );
    }

    /**
     * Asserts that the currently browsing user can create a social forum in
     * the specified forum.
     *
     * @param array $forum
     */
    protected function _assertCanCreateSocialForumInForum(array $forum)
    {
        if (!$this->_getForumModel()->canCreateSocialForumInForum($forum, $errorPhraseKey)) {
            throw $this->getErrorOrNoPermissionResponseException($errorPhraseKey);
        }
    }

    /**
     * Creates the specified helper class.
     * If no underscore is present in the class
     * name, "XenForo_ControllerHelper_" is prefixed. Otherwise, a full class
     * name
     * is assumed.
     *
     * @param string $class Full class name, or partial suffix (if no
     * underscore)
     *
     * @return XenForo_ControllerHelper_Abstract
     */
    public function getHelper($class)
    {
        if ($class == "ForumThreadPost") {
            $class = 'ThemeHouse_SocialGroups_ControllerHelper_SocialCategory';
        }
        
        return parent::getHelper($class);
    }

    /**
     *
     * @return ThemeHouse_SocialGroups_Model_SocialForum
     */
    protected function _getSocialForumModel()
    {
        return ThemeHouse_SocialGroups_SocialForum::getSocialForumModel();
    }

    /**
     *
     * @return ThemeHouse_SocialGroups_Model_SocialCategory
     */
    protected function _getForumModel()
    {
        return $this->getModelFromCache('ThemeHouse_SocialGroups_Model_SocialCategory');
    }
}