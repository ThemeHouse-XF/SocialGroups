<?php

/**
 * Controller for handling actions on social forums.
 *
 * @see XenForo_ControllerPublic_Forum
 */
class ThemeHouse_SocialGroups_ControllerPublic_SocialForum extends XFCP_ThemeHouse_SocialGroups_ControllerPublic_SocialForum
{

    /**
     *
     * @see XenForo_ControllerPublic_Forum::_preDispatch()
     */
    protected function _preDispatch($action)
    {
        $socialForumId = $this->_input->filterSingle('social_forum_id', XenForo_Input::UINT);
        $urlPortion = $this->_input->filterSingle('url_portion', XenForo_Input::STRING);
        $socialForum = null;
        if ($socialForumId) {
            $socialForum = ThemeHouse_SocialGroups_SocialForum::setup($socialForumId);
        } elseif ($urlPortion) {
            $socialForum = ThemeHouse_SocialGroups_SocialForum::setup($urlPortion);
        }
        if (($socialForumId || $urlPortion) && !ThemeHouse_SocialGroups_SocialForum::hasInstance()) {
            throw new XenForo_ControllerResponse_Exception(
                $this->responseError(new XenForo_Phrase('th_social_forum_not_found_socialgroups')));
        }
        $this->_request->setParam('node_id', $socialForum['node_id']);
        $xenOptions = XenForo_Application::get('options');
        if ($socialForum['style_id'] && $xenOptions->th_socialGroups_allowStyleOverride) {
            $this->setViewStateChange('styleId', $socialForum['style_id']);
        }
        parent::_preDispatch($action);
    }

    /**
     *
     * @see XenForo_ControllerPublic_Forum::actionIndex()
     */
    public function actionIndex()
    {
        $forumId = $this->_input->filterSingle('node_id', XenForo_Input::UINT);
        $forumName = $this->_input->filterSingle('node_name', XenForo_Input::STRING);
        
        if ($forumId || $forumName) {
            if (XenForo_Application::$versionId >= 1020000) {
                return $this->responseReroute(__CLASS__, 'forum');
            } else {
                $response = parent::actionIndex();
                
                return $this->_getSocialForumResponse($response);
            }
        }
        
        if ($this->_routeMatch->getResponseType() == 'rss') {
            return $this->getGlobalForumRss();
        }
        
        return $this->responseReroute('ThemeHouse_SocialGroups_ControllerPublic_SocialCategory', 'index');
    }

    /**
     *
     * @see XenForo_ControllerPublic_Forum::actionForum()
     */
    public function actionForum()
    {
        if (XenForo_Application::$versionId < 1020000) {
            return $this->responseReroute(__CLASS__, 'index');
        }
        
        $response = parent::actionForum();
        
        return $this->_getSocialForumResponse($response);
    }

    /**
     *
     * @param XenForo_ControllerResponse_Abstract $response
     * @return XenForo_ControllerResponse_Abstract
     */
    protected function _getSocialForumResponse(XenForo_ControllerResponse_Abstract $response)
    {
        if ($this->_routeMatch->getResponseType() == 'rss') {
            return $response;
        }
        
        if ($response instanceof XenForo_ControllerResponse_View) {
            $socialForum = ThemeHouse_SocialGroups_SocialForum::getInstance()->toArray();
            $visitor = XenForo_Visitor::getInstance();
            
            $this->_assertCanViewSocialForum($socialForum);
            
            // if we've read everything on the first page of a normal sort
            // order, probably need to mark as read
            if ($visitor['user_id'] && $response->params['page'] == 1 && !$response->params['displayConditions'] &&
                 $response->params['order'] == 'last_post_date' && $response->params['orderDirection'] == 'desc' &&
                 $socialForum['social_forum_read_date'] < $socialForum['last_post_date']) {
                $hasNew = false;
                foreach ($response->params['threads'] as $thread) {
                    if ($thread['isNew'] && !$thread['isIgnored']) {
                        $hasNew = true;
                        break;
                    }
                }
                
                if (!$hasNew) {
                    // everything read, but forum not marked as read. Let's
                    // check.
                    $this->_getForumModel()->markSocialForumReadIfNeeded($socialForum);
                }
            }
            
            $response = $this->_getWrapper($response);
        }
        
        return $response;
    }

    public function actionEdit()
    {
        $socialForum = ThemeHouse_SocialGroups_SocialForum::getInstance()->toArray();
        $member = ThemeHouse_SocialGroups_SocialForum::getInstance()->getMember();
        
        $forumId = $this->_input->filterSingle('node_id', XenForo_Input::UINT);
        
        $ftpHelper = $this->getHelper('ForumThreadPost');
        $forum = $this->getHelper('ForumThreadPost')->assertForumValidAndViewable($forumId, 
            $this->_getForumFetchOptions());
        
        $this->_assertCanEditSocialForum($socialForum);
        
        $viewParams = array(
            'socialForum' => $socialForum,
            'forum' => $forum,
            'nodeBreadCrumbs' => $ftpHelper->getNodeBreadCrumbs($forum),
            'member' => $member,
            'canStickUnstickSocialForum' => $this->_getSocialCategoryModel()->canStickUnstickSocialForum($forum),
            'styles' => $this->getModelFromCache('XenForo_Model_Style')->getAllStylesAsFlattenedTree()
        );
        return $this->responseView('ThemeHouse_SocialGroups_ViewPublic_SocialForum_Edit',
            'th_social_forum_edit_socialgroups', $viewParams);
    }

    public function actionSave()
    {
        $this->_assertPostOnly();
        
        $socialForum = ThemeHouse_SocialGroups_SocialForum::getInstance()->toArray();
        $member = ThemeHouse_SocialGroups_SocialForum::getInstance()->getMember();
        
        $forumId = $this->_input->filterSingle('node_id', XenForo_Input::UINT);
        
        $ftpHelper = $this->getHelper('ForumThreadPost');
        $forum = $this->getHelper('ForumThreadPost')->assertForumValidAndViewable($forumId, 
            $this->_getForumFetchOptions());
        
        $this->_assertCanEditSocialForum($socialForum);
        
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
        
        if (!$xenOptions->th_socialGroups_allowClosed) {
            $input['social_forum_open'] = 1;
        }
        
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
        
        // note: assumes that the message dw will pick up the username issues
        $writer = XenForo_DataWriter::create('ThemeHouse_SocialGroups_DataWriter_SocialForum');
        $writer->setExistingData($socialForum['social_forum_id']);
        $writer->bulkSet(
            array(
                'title' => $input['title'],
                'url_portion' => $input['new_url_portion'],
                'social_forum_open' => $input['social_forum_open'],
                'social_forum_moderated' => $input['social_forum_moderated'],
                'style_id' => $input['style_id'],
                'description' => $description
            ));
        
        if (!empty($input['_set']['sticky']) && $this->_getSocialCategoryModel()->canStickUnstickSocialForum($forum)) {
            $writer->set('sticky', $input['sticky']);
        }
        
        $writer->save();
        
        $socialForum = $writer->getMergedData();
        
        $return = XenForo_Link::buildPublicLink('social-forums', $socialForum);
        
        return $this->responseRedirect(XenForo_ControllerResponse_Redirect::SUCCESS, $return, 
            new XenForo_Phrase('th_your_social_forum_has_been_updated_socialgroups'));
    }

    /**
     * Deletes an existing social forum.
     *
     * @return XenForo_ControllerResponse_Abstract
     */
    public function actionDelete()
    {
        $socialForum = ThemeHouse_SocialGroups_SocialForum::getInstance()->toArray();
        $member = ThemeHouse_SocialGroups_SocialForum::getInstance()->getMember();
        
        $forumId = $this->_input->filterSingle('node_id', XenForo_Input::UINT);
        
        $ftpHelper = $this->getHelper('ForumThreadPost');
        $forum = $this->getHelper('ForumThreadPost')->assertForumValidAndViewable($forumId, 
            $this->_getForumFetchOptions());
        
        $fetchElements = $this->_getThreadFetchElements($forum, array());
        $threadFetchConditions = $fetchElements['conditions'];
        
        $totalThreads = $this->_getThreadModel()->countThreadsInForum($forumId, $threadFetchConditions);
        
        $this->_assertCanDeleteSocialForum($socialForum);
        
        if ($totalThreads) {
            return $this->responseError(new XenForo_Phrase('th_social_forum_not_empty_socialgroups'));
        } elseif ($this->isConfirmedPost()) {
            /* @var $dw ThemeHouse_SocialGroups_DataWriter_SocialForum */
            $dw = XenForo_DataWriter::create('ThemeHouse_SocialGroups_DataWriter_SocialForum');
            
            $dw->setExistingData($socialForum['social_forum_id']);
            
            $dw->delete();
            
            return $this->responseRedirect(XenForo_ControllerResponse_Redirect::SUCCESS, 
                XenForo_Link::buildPublicLink('social-categories', $forum));
        } else {
            $viewParams = array(
                'forum' => $forum,
                'nodeBreadCrumbs' => $ftpHelper->getNodeBreadCrumbs($forum),
                'socialForum' => $socialForum
            );
            
            return $this->responseView('ThemeHouse_SocialGroups_ViewPublic_SocialForum_Delete',
                'th_social_forum_delete_socialgroups', $viewParams);
        }
    }

    /**
     * Moves a social forum to a different social category.
     *
     * @return XenForo_ControllerResponse_Abstract
     */
    public function actionMove()
    {
        $socialForum = ThemeHouse_SocialGroups_SocialForum::getInstance()->toArray();
        
        $this->_assertCanMoveSocialForum($socialForum);
        
        $forumId = $this->_input->filterSingle('node_id', XenForo_Input::UINT);
        
        $ftpHelper = $this->getHelper('ForumThreadPost');
        $forum = $this->getHelper('ForumThreadPost')->assertForumValidAndViewable($forumId, 
            $this->_getForumFetchOptions());
        
        if ($this->isConfirmedPost()) { // move the thread
            
            $input = $this->_input->filter(
                array(
                    'move_to_node_id' => XenForo_Input::UINT
                ));
            
            $viewableNodes = $this->getModelFromCache('XenForo_Model_Node')->getViewableNodeList();
            if (isset($viewableNodes[$input['move_to_node_id']])) {
                $targetNode = $viewableNodes[$input['move_to_node_id']];
            } else {
                return $this->responseNoPermission();
            }
            
            XenForo_Db::beginTransaction();
            $dw = XenForo_DataWriter::create('ThemeHouse_SocialGroups_DataWriter_SocialForum');
            $dw->setExistingData($socialForum['social_forum_id']);
            $dw->set('node_id', $input['move_to_node_id']);
            $dw->save();
            $socialForum = $dw->getMergedData();
            $this->_getForumModel()->moveThreads($socialForum);
            XenForo_Db::commit();
            
            XenForo_Model_Log::logModeratorAction('social_forum', $socialForum, 'move', 
                array(
                    'from' => $forum['title']
                ));
            
            return $this->responseRedirect(XenForo_ControllerResponse_Redirect::SUCCESS, 
                XenForo_Link::buildPublicLink('social-forums', $socialForum));
        } else {
            return $this->responseView('ThemeHouse_SocialGroups_ViewPublic_SocialForum_Move',
                'th_social_forum_move_socialgroups',
                array(
                    'socialForum' => $socialForum,
                    'forum' => $forum,
                    'nodeBreadCrumbs' => $ftpHelper->getNodeBreadCrumbs($forum),
                    
                    'nodeOptions' => $this->getModelFromCache('XenForo_Model_Node')
                        ->getViewableNodeList()
                ));
        }
    }

    public function actionAvatar()
    {
        $socialForum = ThemeHouse_SocialGroups_SocialForum::getInstance()->toArray();
        
        $forumId = $this->_input->filterSingle('node_id', XenForo_Input::UINT);
        
        $ftpHelper = $this->getHelper('ForumThreadPost');
        $forum = $this->getHelper('ForumThreadPost')->assertForumValidAndViewable($forumId, 
            $this->_getForumFetchOptions());
        
        $this->_assertCanEditSocialForum($socialForum);
        
        $maxWidth = ThemeHouse_SocialGroups_Model_SocialForumAvatar::getSizeFromCode('m');
        
        $viewParams = array(
            'socialForum' => $socialForum,
            'sizeCode' => 'm',
            'maxWidth' => $maxWidth,
            'maxDimension' => ($socialForum['logo_width'] > $socialForum['logo_height'] ? 'height' : 'width'),
            'width' => $socialForum['logo_width'],
            'height' => $socialForum['logo_height'],
            'cropX' => $socialForum['logo_crop_x'],
            'cropY' => $socialForum['logo_crop_y']
        );
        
        return $this->responseView('ThemeHouse_SocialGroups_ViewPublic_SocialForum_Avatar',
            'th_social_forum_avatar_socialgroups', $viewParams);
    }

    public function actionAvatarUpload()
    {
        $this->_assertPostOnly();
        
        $socialForum = ThemeHouse_SocialGroups_SocialForum::getInstance()->toArray();
        
        $forumId = $this->_input->filterSingle('node_id', XenForo_Input::UINT);
        
        $ftpHelper = $this->getHelper('ForumThreadPost');
        $forum = $this->getHelper('ForumThreadPost')->assertForumValidAndViewable($forumId, 
            $this->_getForumFetchOptions());
        
        $this->_assertCanEditSocialForum($socialForum);
        
        $avatar = XenForo_Upload::getUploadedFile('avatar');
        
        /* @var $avatarModel ThemeHouse_SocialGroups_Model_SocialForumAvatar */
        $avatarModel = $this->getModelFromCache('ThemeHouse_SocialGroups_Model_SocialForumAvatar');
        
        $inputData = $this->_input->filter(array(
            'delete' => XenForo_Input::UINT
        ));
        
        $inputData['logo_crop_x'] = $this->_input->filterSingle('avatar_crop_x', XenForo_Input::UINT);
        $inputData['logo_crop_y'] = $this->_input->filterSingle('avatar_crop_y', XenForo_Input::UINT);
        
        // upload new avatar
        if ($avatar) {
            $avatarData = $avatarModel->uploadAvatar($avatar, $socialForum['social_forum_id'], 
                XenForo_Visitor::getInstance()->getNodePermissions($socialForum['node_id']));
        } elseif ($inputData['delete']) {
            $avatarData = $avatarModel->deleteAvatar($socialForum['social_forum_id']);
        } elseif ($inputData['logo_crop_x'] != $socialForum['logo_crop_x'] ||
             $inputData['logo_crop_y'] != $socialForum['logo_crop_y']) {
            $avatarData = $avatarModel->recropAvatar($socialForum['social_forum_id'], $inputData['logo_crop_x'], 
                $inputData['logo_crop_y']);
        }
        
        // merge new data into $socialForum, if there is any
        if (isset($avatarData) && is_array($avatarData)) {
            foreach ($avatarData as $key => $val) {
                $socialForum[$key] = $val;
            }
        }
        
        $message = new XenForo_Phrase('upload_completed_successfully');
        
        // return a view if noredirect has been requested and we are not
        // deleting
        if ($this->_noRedirect()) {
            return $this->responseView('ThemeHouse_SocialGroups_ViewPublic_SocialForum_AvatarUpload', '',
                array(
                    'socialForum' => $socialForum,
                    'sizeCode' => 'm',
                    'maxWidth' => ThemeHouse_SocialGroups_Model_SocialForumAvatar::getSizeFromCode('m'),
                    'maxDimension' => ($socialForum['logo_width'] > $socialForum['logo_height'] ? 'height' : 'width'),
                    'width' => $socialForum['logo_width'],
                    'height' => $socialForum['logo_height'],
                    'cropX' => $socialForum['logo_crop_x'],
                    'cropY' => $socialForum['logo_crop_y'],
                    'social_forum_id' => $socialForum['social_forum_id'],
                    'logo_date' => $socialForum['logo_date'],
                    'message' => $message
                ));
        } else {
            return $this->responseRedirect(XenForo_ControllerResponse_Redirect::SUCCESS, 
                XenForo_Link::buildPublicLink('account/personal-details'), $message);
        }
    }

    public function actionModerator()
    {
        $socialForum = ThemeHouse_SocialGroups_SocialForum::getInstance()->toArray();
        $member = ThemeHouse_SocialGroups_SocialForum::getInstance()->getMember();
        
        $forumId = $this->_input->filterSingle('node_id', XenForo_Input::UINT);
        
        $ftpHelper = $this->getHelper('ForumThreadPost');
        $forum = $this->getHelper('ForumThreadPost')->assertForumValidAndViewable($forumId, 
            $this->_getForumFetchOptions());
        
        $userId = $this->_input->filterSingle('user_id', XenForo_Input::UINT);
        
        $writer = XenForo_DataWriter::create('ThemeHouse_SocialGroups_DataWriter_SocialForumMember');
        $writer->setExtraData(ThemeHouse_SocialGroups_DataWriter_SocialForumMember::DATA_SOCIAL_FORUM, $socialForum);
        $socialForumMember = $this->_getSocialForumMemberModel()->getSocialForumMemberByUserId(
            $socialForum['social_forum_id'], $userId);
        if ($socialForumMember) {
            $writer->setExistingData($socialForumMember['social_forum_member_id']);
            if ($socialForumMember['is_social_forum_moderator']) {
                $this->_assertCanRemoveSocialForumModerator($socialForum);
                if (XenForo_Model_Alert::userReceivesAlert($socialForumMember, 'social_forum', 'remove_moderator')) {
                    $visitor = XenForo_Visitor::getInstance();
                    XenForo_Model_Alert::alert($userId, $visitor['user_id'], $visitor['username'], 'social_forum', 
                        $socialForum['social_forum_id'], 'remove_moderator');
                }
            } else {
                $this->_assertCanAddSocialForumModerator($socialForum);
                if (XenForo_Model_Alert::userReceivesAlert($socialForumMember, 'social_forum', 'moderator')) {
                    $visitor = XenForo_Visitor::getInstance();
                    XenForo_Model_Alert::alert($userId, $visitor['user_id'], $visitor['username'], 'social_forum', 
                        $socialForum['social_forum_id'], 'moderator');
                }
            }
            $writer->set('is_social_forum_moderator', !$socialForumMember['is_social_forum_moderator']);
            $writer->save();
        }
        
        return $this->responseRedirect(XenForo_ControllerResponse_Redirect::RESOURCE_CANONICAL_PERMANENT, 
            XenForo_Link::buildPublicLink('social-forums/members', $socialForum));
    }

    public function actionAcceptInvite()
    {
        $this->_checkCsrfFromToken($this->_request->getParam('_xfToken'));
        
        $socialForum = ThemeHouse_SocialGroups_SocialForum::getInstance()->toArray();
        $member = ThemeHouse_SocialGroups_SocialForum::getInstance()->getMember();
        
        if (isset($member['social_forum_member_id']) && $member['is_invited']) {
            $writer = XenForo_DataWriter::create('ThemeHouse_SocialGroups_DataWriter_SocialForumMember');
            $writer->setExtraData(ThemeHouse_SocialGroups_DataWriter_SocialForumMember::DATA_SOCIAL_FORUM, $socialForum);
            $writer->setExistingData($member['social_forum_member_id']);
            $writer->set('is_invited', false);
            $writer->set('join_date', XenForo_Application::$time);
            $writer->save();
        }
        
        return $this->responseRedirect(XenForo_ControllerResponse_Redirect::SUCCESS, 
            XenForo_Link::buildPublicLink('social-forums', $socialForum), 
            new XenForo_Phrase('th_invite_accepted_socialgroups'));
    }

    public function actionJoin()
    {
        $this->_checkCsrfFromToken($this->_request->getParam('_xfToken'));
        
        $socialForum = ThemeHouse_SocialGroups_SocialForum::getInstance()->toArray();
        $member = ThemeHouse_SocialGroups_SocialForum::getInstance()->getMember();
        
        $forumModel = $this->_getForumModel();
        
        $forumId = $this->_input->filterSingle('node_id', XenForo_Input::UINT);
        
        $ftpHelper = $this->getHelper('ForumThreadPost');
        $forum = $this->getHelper('ForumThreadPost')->assertForumValidAndViewable($forumId, 
            $this->_getForumFetchOptions());
        
        $visitor = XenForo_Visitor::getInstance();
        $userId = $visitor['user_id'];
        
        if ($forumModel->canApproveSocialForumJoinRequest($socialForum) ||
             $forumModel->canRejectSocialForumJoinRequest($socialForum) ||
             $forumModel->canRevokeSocialForumMembership($socialForum)) {
            if ($this->_input->filterSingle('user_id', XenForo_Input::UINT)) {
                $userId = $this->_input->filterSingle('user_id', XenForo_Input::UINT);
            }
        }
        
        $user = $this->_getUserModel()->getUserById($userId);
        
        $writer = XenForo_DataWriter::create('ThemeHouse_SocialGroups_DataWriter_SocialForumMember');
        $writer->setExtraData(ThemeHouse_SocialGroups_DataWriter_SocialForumMember::DATA_SOCIAL_FORUM, $socialForum);
        $socialForumMember = $this->_getSocialForumMemberModel()->getSocialForumMemberByUserId(
            $socialForum['social_forum_id'], $userId);
        if ($socialForumMember) {
            $writer->setExistingData($socialForumMember['social_forum_member_id']);
            if ($this->_input->filterSingle('approved', XenForo_Input::UINT)) {
                $this->_assertCanApproveSocialForumJoinRequest($socialForum);
                $writer->set('is_approved', true);
                $writer->save();
                if (XenForo_Model_Alert::userReceivesAlert($user, 'social_forum', 'approve')) {
                    $visitor = XenForo_Visitor::getInstance();
                    XenForo_Model_Alert::alert($userId, $visitor['user_id'], $visitor['username'], 'social_forum', 
                        $socialForum['social_forum_id'], 'approve');
                }
            } else {
                if (isset($member['user_id']) && $member['user_id'] == $userId) {
                    $this->_assertCanLeaveSocialForum($socialForum);
                } elseif ($socialForumMember['is_approved']) {
                    $this->_assertCanRevokeSocialForumMembership($socialForum);
                    if (XenForo_Model_Alert::userReceivesAlert($user, 'social_forum', 'revoke')) {
                        $visitor = XenForo_Visitor::getInstance();
                        XenForo_Model_Alert::alert($userId, $visitor['user_id'], $visitor['username'], 'social_forum', 
                            $socialForum['social_forum_id'], 'revoke');
                    }
                } else {
                    if ($socialForumMember['is_social_forum_moderator']) {
                        $this->_assertCanRemoveSocialForumModerator($socialForum);
                        if (XenForo_Model_Alert::userReceivesAlert($user, 'social_forum', 'remove_moderator')) {
                            $visitor = XenForo_Visitor::getInstance();
                            XenForo_Model_Alert::alert($userId, $visitor['user_id'], $visitor['username'], 
                                'social_forum', $socialForum['social_forum_id'], 'remove_moderator');
                        }
                    }
                    $this->_assertCanRejectSocialForumJoinRequest($socialForum);
                    if (XenForo_Model_Alert::userReceivesAlert($user, 'social_forum', 'reject')) {
                        $visitor = XenForo_Visitor::getInstance();
                        XenForo_Model_Alert::alert($userId, $visitor['user_id'], $visitor['username'], 'social_forum', 
                            $socialForum['social_forum_id'], 'reject');
                    }
                }
                $writer->delete();
            }
            if (!isset($member['user_id']) || $member['user_id'] != $userId) {
                return $this->responseRedirect(XenForo_ControllerResponse_Redirect::RESOURCE_CANONICAL_PERMANENT, 
                    XenForo_Link::buildPublicLink('social-forums/members', $socialForum));
            }
        } else {
            $this->_assertCanJoinSocialForum($socialForum);
            
            $writer->bulkSet(
                array(
                    'social_forum_id' => $socialForum['social_forum_id'],
                    'user_id' => $userId
                ));
            if (!$socialForum['social_forum_moderated']) {
                $writer->set('is_approved', true);
            } else {
                if (XenForo_Model_Alert::userReceivesAlert($user, 'social_forum', 'request')) {
                    $visitor = XenForo_Visitor::getInstance();
                    XenForo_Model_Alert::alert($socialForum['user_id'], $visitor['user_id'], $visitor['username'], 
                        'social_forum', $socialForum['social_forum_id'], 'request');
                }
            }
            $writer->save();
        }
        
        return $this->responseRedirect(XenForo_ControllerResponse_Redirect::RESOURCE_CANONICAL_PERMANENT, 
            XenForo_Link::buildPublicLink('social-forums', $socialForum));
    }

    public function actionWatch()
    {
        $response = parent::actionWatch();
        
        if ($response instanceof XenForo_ControllerResponse_View) {
            $socialForum = ThemeHouse_SocialGroups_SocialForum::getInstance()->toArray();
            
            /* @var $response XenForo_ControllerResponse_View */
            $response->templateName = 'th_social_forum_watch_socialgroups';
            $response->params['socialForum'] = $socialForum;
        }
        
        return $response;
    }

    public function actionCreateThread()
    {
        $response = parent::actionCreateThread();
        
        $socialForum = ThemeHouse_SocialGroups_SocialForum::getInstance()->toArray();
        
        if ($response instanceof XenForo_ControllerResponse_View) {
            $response->params['forum']['social_forum_id'] = $socialForum['social_forum_id'];
            $response->params['forum']['description'] = '';
        }
        
        return $response;
    }

    /**
     * Member list
     *
     * @return XenForo_ControllerResponse_View
     */
    public function actionMembers()
    {
        $socialForum = ThemeHouse_SocialGroups_SocialForum::getInstance()->toArray();
        if ($socialForum) {
            $socialForumMemberModel = $this->_getSocialForumMemberModel();
            
            $this->_assertCanViewSocialForumMembers($socialForum);
            
            $page = $this->_input->filterSingle('page', XenForo_Input::UINT);
            $usersPerPage = XenForo_Application::get('options')->membersPerPage;
            
            $member = ThemeHouse_SocialGroups_SocialForum::getInstance()->getMember();
            
            $filter = $this->_input->filterSingle('filter', XenForo_Input::STRING);
            
            $criteria = array(
                'social_forum_id' => $socialForum['social_forum_id'],
                'user_state' => 'valid',
                'is_banned' => 0
            );
            
            if ($filter == 'creator') {
                $criteria['is_social_forum_creator'] = 1;
            } elseif ($filter == 'moderator') {
                $criteria['is_social_forum_moderator'] = 1;
            } elseif ($filter == 'member') {
                $criteria['is_approved'] = 1;
                $criteria['is_invited'] = 0;
            } elseif ($filter == 'awaiting') {
                $criteria['is_approved'] = 0;
                $criteria['is_invited'] = 0;
            } elseif ($filter == 'invited') {
                $criteria['is_invited'] = 1;
            } else {
                $filter = 'all';
            }
            
            $canApproveSocialForumJoinRequest = $this->_getForumModel()->canApproveSocialForumJoinRequest($socialForum);
            
            if (!$canApproveSocialForumJoinRequest) {
                $criteria['is_approved'] = true;
            }
            
            // users for the member list
            $users = $socialForumMemberModel->getSocialForumUsers($criteria, 
                array(
                    'join' => XenForo_Model_User::FETCH_USER_FULL,
                    'perPage' => $usersPerPage,
                    'page' => $page
                ));
            
            $viewParams = array(
                'users' => $users,
                'totalUsers' => $socialForumMemberModel->countSocialForumMembers($criteria),
                'page' => $page,
                'usersPerPage' => $usersPerPage,
                'socialForum' => $socialForum,
                'member' => $member,
                
                'canEditInline' => $this->_getCanEditInline($socialForum),
                'canApproveSocialForumJoinRequest' => $canApproveSocialForumJoinRequest,
                
                'selectedTab' => $filter
            );
            
            $subView = $this->responseView('ThemeHouse_SocialGroups_ViewPublic_SocialForumMember_List',
                'th_member_list_socialgroups', $viewParams);
            $response = $this->_getWrapper($subView, true);
            $response->params['canInviteMembers'] = true;
            return $response;
        }
        
        return $this->responseRedirect(XenForo_ControllerResponse_Redirect::RESOURCE_CANONICAL_PERMANENT, 
            XenForo_Link::buildPublicLink('index'));
    }

    protected function _getCanEditInline(array $socialForum)
    {
        if ($this->_getForumModel()->canAddSocialForumModerator($socialForum)) {
            return true;
        }
        if ($this->_getForumModel()->canRemoveSocialForumModerator($socialForum)) {
            return true;
        }
        if ($this->_getForumModel()->canApproveSocialForumJoinRequest($socialForum)) {
            return true;
        }
        if ($this->_getForumModel()->canRejectSocialForumJoinRequest($socialForum)) {
            return true;
        }
        if ($this->_getForumModel()->canRevokeSocialForumMembership($socialForum)) {
            return true;
        }
    }

    public function actionMemberSave()
    {
        $userId = $this->_input->filterSingle('user_id', XenForo_Input::UINT);
        
        $socialForum = ThemeHouse_SocialGroups_SocialForum::getInstance()->toArray();
        if ($socialForum) {
            $socialForumMemberModel = $this->_getSocialForumMemberModel();
            
            $this->_assertCanViewSocialForumMembers($socialForum);
            
            $user = $socialForumMemberModel->getSocialForumMember(
                array(
                    'social_forum_id' => $socialForum['social_forum_id'],
                    'user_id' => $userId
                ), array(
                    'join' => XenForo_Model_User::FETCH_USER_FULL
                ));
            
            $input = $this->_input->filter(
                array(
                    'is_social_forum_creator' => XenForo_Input::UINT,
                    'is_social_forum_moderator' => XenForo_Input::UINT,
                    'is_social_forum_member' => XenForo_Input::UINT
                ));
            
            $canAddSocialForumModerator = $this->_getForumModel()->canAddSocialForumModerator($socialForum);
            $canRemoveSocialForumModerator = $this->_getForumModel()->canRemoveSocialForumModerator($socialForum);
            
            if ($user['is_social_forum_moderator'] && !$canRemoveSocialForumModerator) {
                unset($input['is_social_forum_moderator']);
            } elseif (!$user['is_social_forum_moderator'] && !$canAddSocialForumModerator) {
                unset($input['is_social_forum_moderator']);
            }
            
            $canApproveSocialForumJoinRequest = $this->_getForumModel()->canApproveSocialForumJoinRequest($socialForum);
            $canRejectSocialForumJoinRequest = $this->_getForumModel()->canRejectSocialForumJoinRequest($socialForum);
            $canRevokeSocialForumMembership = $this->_getForumModel()->canRevokeSocialForumMembership($socialForum);
            
            if (!$input['is_social_forum_member'] && (($user['is_approved'] && $canRevokeSocialForumMembership) ||
                 (!$user['is_approved'] && !$user['is_invited'] && $canRejectSocialForumJoinRequest))) {
                /*
                 * @var $dw ThemeHouse_SocialGroups_DataWriter_SocialForumMember
                 */
                $dw = XenForo_DataWriter::create('ThemeHouse_SocialGroups_DataWriter_SocialForumMember');
                $dw->setExtraData(ThemeHouse_SocialGroups_DataWriter_SocialForumMember::DATA_SOCIAL_FORUM, $socialForum);
                $dw->setExistingData($user);
                $dw->delete();
                $user = array(
                    'user_id' => $user['user_id']
                );
            } else {
                if ($input['is_social_forum_member'] &&
                     (!$user['is_approved'] && !$user['is_invited'] && $canApproveSocialForumJoinRequest)) {
                    $input['is_approved'] = true;
                }
                unset($input['is_social_forum_member']);
                
                /*
                 * @var $dw ThemeHouse_SocialGroups_DataWriter_SocialForumMember
                 */
                $dw = XenForo_DataWriter::create('ThemeHouse_SocialGroups_DataWriter_SocialForumMember');
                $dw->setExtraData(ThemeHouse_SocialGroups_DataWriter_SocialForumMember::DATA_SOCIAL_FORUM, $socialForum);
                $dw->setExistingData($user);
                $dw->bulkSet($input);
                $dw->save();
                $user = array_merge($user, $dw->getMergedData());
            }
            
            $viewParams = array(
                'user' => $user,
                'socialForum' => $socialForum,
                
                'canEditInline' => $this->_getCanEditInline($socialForum)
            );
            
            return $this->responseView('ThemeHouse_SocialGroups_ViewPublic_SocialForumMember_Save_MemberListItem',
                'th_member_list_item_socialgroups', $viewParams);
        }
        
        return $this->responseRedirect(XenForo_ControllerResponse_Redirect::RESOURCE_CANONICAL_PERMANENT, 
            XenForo_Link::buildPublicLink('index'));
    }

    public function actionMemberListItemEdit()
    {
        $userId = $this->_input->filterSingle('user_id', XenForo_Input::UINT);
        
        $socialForum = ThemeHouse_SocialGroups_SocialForum::getInstance()->toArray();
        if ($socialForum) {
            $socialForumMemberModel = $this->_getSocialForumMemberModel();
            
            $this->_assertCanViewSocialForumMembers($socialForum);
            
            $user = $socialForumMemberModel->getSocialForumMember(
                array(
                    'social_forum_id' => $socialForum['social_forum_id'],
                    'user_id' => $userId
                ), array(
                    'join' => XenForo_Model_User::FETCH_USER_FULL
                ));
            
            $viewParams = array(
                'user' => $user,
                
                'socialForum' => $socialForum,
                
                'canAddSocialForumModerator' => $this->_getForumModel()->canAddSocialForumModerator($socialForum),
                'canRemoveSocialForumModerator' => $this->_getForumModel()->canRemoveSocialForumModerator($socialForum),
                'canAssignSocialForumCreator' => $this->_getForumModel()->canAssignSocialForumCreator($socialForum),
                'canApproveSocialForumJoinRequest' => $this->_getForumModel()->canApproveSocialForumJoinRequest(
                    $socialForum),
                'canRejectSocialForumJoinRequest' => $this->_getForumModel()->canRejectSocialForumJoinRequest(
                    $socialForum),
                'canRevokeSocialForumMembership' => $this->_getForumModel()->canRevokeSocialForumMembership(
                    $socialForum)
            );
            
            return $this->responseView('ThemeHouse_SocialGroups_ViewPublic_SocialForum_MemberListItemEdit',
                'th_member_list_item_edit_socialgroups', $viewParams);
        }
        
        return $this->responseRedirect(XenForo_ControllerResponse_Redirect::RESOURCE_CANONICAL_PERMANENT, 
            XenForo_Link::buildPublicLink('index'));
    }

    public function actionInvite()
    {
        $this->_assertPostOnly();
        
        $socialForum = ThemeHouse_SocialGroups_SocialForum::getInstance()->toArray();
        
        $username = $this->_input->filterSingle('username', XenForo_Input::STRING);
        $user = $this->_getUserModel()->getUserByName($username);
        
        if (!$user) {
            return $this->responseError(new XenForo_Phrase('requested_user_not_found'));
        }
        
        $this->_getSocialForumMemberModel()->invite($user, $socialForum);
        
        return $this->responseRedirect(XenForo_ControllerResponse_Redirect::SUCCESS, 
            XenForo_Link::buildPublicLink('social-forums/members', $socialForum), 
            new XenForo_Phrase('th_invite_sent_socialgroups'));
    }

    public function getSocialForumId()
    {
        $socialForumId = $this->_input->filterSingle('social_forum_id', XenForo_Input::UINT);
        return ($socialForumId ? $socialForumId : false);
    }

    /**
     * Creates the specified helper class.
     * If no underscore is present in the class name,
     * "XenForo_ControllerHelper_" is prefixed. Otherwise, a full class name is
     * assumed.
     *
     * @param string $class Full class name, or partial suffix (if no
     * underscore)
     *
     * @return XenForo_ControllerHelper_Abstract
     */
    public function getHelper($class)
    {
        if (XenForo_Application::$versionId < 1020000 && $class == "ForumThreadPost") {
            $class = 'ThemeHouse_SocialGroups_ControllerHelper_SocialForumThreadPost';
        }
        
        return parent::getHelper($class);
    }

    /**
     * Session activity details.
     *
     * @see XenForo_Controller::getSessionActivityDetailsForList()
     */
    public static function getSessionActivityDetailsForList(array $activities)
    {
        // TODO: check for $activity['params']['social_forum_id']
        $forumIds = array();
        $nodeNames = array();
        foreach ($activities as $activity) {
            if (!empty($activity['params']['node_id'])) {
                $forumIds[$activity['params']['node_id']] = intval($activity['params']['node_id']);
            } else 
                if (!empty($activity['params']['node_name'])) {
                    $nodeNames[$activity['params']['node_name']] = $activity['params']['node_name'];
                }
        }
        
        if ($nodeNames) {
            $nodeNames = XenForo_Model::create('XenForo_Model_Node')->getNodeIdsFromNames($nodeNames);
            
            foreach ($nodeNames as $nodeName => $nodeId) {
                $forumIds[$nodeName] = $nodeId;
            }
        }
        
        $forumData = array();
        
        if ($forumIds) {
            /* @var $forumModel XenForo_Model_Forum */
            $forumModel = XenForo_Model::create('XenForo_Model_Forum');
            
            $visitor = XenForo_Visitor::getInstance();
            $permissionCombinationId = $visitor['permission_combination_id'];
            
            $forums = $forumModel->getForumsByIds($forumIds, 
                array(
                    'permissionCombinationId' => $permissionCombinationId
                ));
            foreach ($forums as $forum) {
                $visitor->setNodePermissions($forum['node_id'], $forum['node_permission_cache']);
                if ($forumModel->canViewForum($forum)) {
                    $forumData[$forum['node_id']] = array(
                        'title' => $forum['title'],
                        'url' => XenForo_Link::buildPublicLink('social-categories', $forum)
                    );
                }
            }
        }
        
        $output = array();
        foreach ($activities as $key => $activity) {
            $forum = false;
            if (!empty($activity['params']['node_id'])) {
                $nodeId = $activity['params']['node_id'];
                if (isset($forumData[$nodeId])) {
                    $forum = $forumData[$nodeId];
                }
            } else 
                if (!empty($activity['params']['node_name'])) {
                    $nodeName = $activity['params']['node_name'];
                    if (isset($nodeNames[$nodeName])) {
                        $nodeId = $nodeNames[$nodeName];
                        if (isset($forumData[$nodeId])) {
                            $forum = $forumData[$nodeId];
                        }
                    }
                }
            
            if ($forum) {
                $output[$key] = array(
                    new XenForo_Phrase('th_viewing_social_category_socialgroups'),
                    $forum['title'],
                    $forum['url'],
                    false
                );
            } else {
                $output[$key] = new XenForo_Phrase('th_viewing_social_category_socialgroups');
            }
        }
        
        return $output;
    }

    /**
     * Asserts that the currently browsing user can view the specified social
     * forum.
     *
     * @param ThemeHouse_SocialGroups_SocialForum $socialForum
     */
    protected function _assertCanViewSocialForum(array $socialForum)
    {
        $errorPhraseKey = '';
        if (!$this->_getForumModel()->canViewSocialForum($socialForum, $errorPhraseKey)) {
            throw $this->getErrorOrNoPermissionResponseException($errorPhraseKey);
        }
    }

    /**
     * Asserts that the currently browsing user can view members of the
     * specified social forum.
     *
     * @param ThemeHouse_SocialGroups_SocialForum $socialForum
     */
    protected function _assertCanViewSocialForumMembers(array $socialForum)
    {
        $errorPhraseKey = '';
        if (!$this->_getForumModel()->canViewSocialForumMembers($socialForum, $errorPhraseKey)) {
            throw $this->getErrorOrNoPermissionResponseException($errorPhraseKey);
        }
    }

    /**
     * Asserts that the currently browsing user can join the specified social
     * forum.
     *
     * @param ThemeHouse_SocialGroups_SocialForum $socialForum
     */
    protected function _assertCanJoinSocialForum(array $socialForum)
    {
        $errorPhraseKey = '';
        if (!$this->_getForumModel()->canJoinSocialForum($socialForum, $errorPhraseKey)) {
            throw $this->getErrorOrNoPermissionResponseException($errorPhraseKey);
        }
    }

    /**
     * Asserts that the currently browsing user can leave the specified social
     * forum.
     *
     * @param ThemeHouse_SocialGroups_SocialForum $socialForum
     */
    protected function _assertCanLeaveSocialForum(array $socialForum)
    {
        $errorPhraseKey = '';
        if (!$this->_getForumModel()->canLeaveSocialForum($socialForum, $errorPhraseKey)) {
            throw $this->getErrorOrNoPermissionResponseException($errorPhraseKey);
        }
    }

    /**
     * Asserts that the currently browsing user can approve a join request in
     * the specified social forum.
     *
     * @param ThemeHouse_SocialGroups_SocialForum $socialForum
     */
    protected function _assertCanApproveSocialForumJoinRequest(ThemeHouse_SocialGroups_SocialForum $socialForum)
    {
        $errorPhraseKey = '';
        if (!$this->_getForumModel()->canApproveSocialForumJoinRequest($socialForum, $errorPhraseKey)) {
            throw $this->getErrorOrNoPermissionResponseException($errorPhraseKey);
        }
    }

    /**
     * Asserts that the currently browsing user can reject a join request in the
     * specified social forum.
     *
     * @param ThemeHouse_SocialGroups_SocialForum $socialForum
     */
    protected function _assertCanRejectSocialForumJoinRequest(array $socialForum)
    {
        $errorPhraseKey = '';
        if (!$this->_getForumModel()->canRejectSocialForumJoinRequest($socialForum, $errorPhraseKey)) {
            throw $this->getErrorOrNoPermissionResponseException($errorPhraseKey);
        }
    }

    /**
     * Asserts that the currently browsing user can revoke membership of the
     * specified social forum.
     *
     * @param ThemeHouse_SocialGroups_SocialForum $socialForum
     */
    protected function _assertCanRevokeSocialForumMembership(array $socialForum)
    {
        $errorPhraseKey = '';
        if (!$this->_getForumModel()->canRevokeSocialForumMembership($socialForum, $errorPhraseKey)) {
            throw $this->getErrorOrNoPermissionResponseException($errorPhraseKey);
        }
    }

    /**
     * Asserts that the currently browsing user can edit the specified social
     * forum.
     *
     * @param ThemeHouse_SocialGroups_SocialForum $socialForum
     */
    protected function _assertCanEditSocialForum(array $socialForum)
    {
        $errorPhraseKey = '';
        if (!$this->_getForumModel()->canEditSocialForum($socialForum, $errorPhraseKey)) {
            throw $this->getErrorOrNoPermissionResponseException($errorPhraseKey);
        }
    }

    /**
     * Asserts that the currently browsing user can move the specified social
     * forum.
     *
     * @param ThemeHouse_SocialGroups_SocialForum $socialForum
     */
    protected function _assertCanMoveSocialForum(array $socialForum)
    {
        $errorPhraseKey = '';
        if (!$this->_getForumModel()->canMoveSocialForum($socialForum, $errorPhraseKey)) {
            throw $this->getErrorOrNoPermissionResponseException($errorPhraseKey);
        }
    }

    /**
     * Asserts that the currently browsing user can delete the specified social
     * forum.
     *
     * @param ThemeHouse_SocialGroups_SocialForum $socialForum
     */
    protected function _assertCanDeleteSocialForum(array $socialForum)
    {
        $errorPhraseKey = '';
        if (!$this->_getForumModel()->canDeleteSocialForum($socialForum, $errorPhraseKey)) {
            throw $this->getErrorOrNoPermissionResponseException($errorPhraseKey);
        }
    }

    /**
     * Asserts that the currently browsing user can add a moderator to the
     * specified social forum.
     *
     * @param ThemeHouse_SocialGroups_SocialForum $socialForum
     */
    protected function _assertCanAddSocialForumModerator(array $socialForum)
    {
        $errorPhraseKey = '';
        if (!$this->_getForumModel()->canAddSocialForumModerator($socialForum, $errorPhraseKey)) {
            throw $this->getErrorOrNoPermissionResponseException($errorPhraseKey);
        }
    }

    /**
     * Asserts that the currently browsing user can remove a moderator from the
     * specified social forum.
     *
     * @param ThemeHouse_SocialGroups_SocialForum $socialForum
     */
    protected function _assertCanRemoveSocialForumModerator(array $socialForum)
    {
        $errorPhraseKey = '';
        if (!$this->_getForumModel()->canRemoveSocialForumModerator($socialForum, $errorPhraseKey)) {
            throw $this->getErrorOrNoPermissionResponseException($errorPhraseKey);
        }
    }

    /**
     *
     * @return ThemeHouse_SocialGroups_Model_SocialForumMember
     */
    protected function _getSocialForumMemberModel()
    {
        return $this->getModelFromCache('ThemeHouse_SocialGroups_Model_SocialForumMember');
    }

    /**
     *
     * @return XenForo_Model_User
     */
    protected function _getUserModel()
    {
        return $this->getModelFromCache('XenForo_Model_User');
    }

    /**
     *
     * @return ThemeHouse_SocialGroups_Model_SocialForum
     */
    protected function _getForumModel()
    {
        return ThemeHouse_SocialGroups_SocialForum::getSocialForumModel();
    }

    /**
     *
     * @return ThemeHouse_SocialGroups_Model_SocialCategory
     */
    protected function _getSocialCategoryModel()
    {
        return $this->getModelFromCache('ThemeHouse_SocialGroups_Model_SocialCategory');
    }

    /**
     * Gets the social forum pages wrapper.
     *
     * @param XenForo_ControllerResponse_View $subView
     *
     * @return XenForo_ControllerResponse_View
     */
    protected function _getWrapper(XenForo_ControllerResponse_View $subView, $forceSidebar = false)
    {
        return $this->getHelper('ThemeHouse_SocialGroups_ControllerHelper_SocialForum')->getWrapper($subView,
            $forceSidebar);
    }
}