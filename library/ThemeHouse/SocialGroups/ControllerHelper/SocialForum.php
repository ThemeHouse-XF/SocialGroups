<?php

class ThemeHouse_SocialGroups_ControllerHelper_SocialForum extends XenForo_ControllerHelper_Abstract
{

    public function getWrapper(XenForo_ControllerResponse_View $subView, $forceSidebar = false)
    {
        $socialForum = ThemeHouse_SocialGroups_SocialForum::getInstance()->toArray();
        $visitor = XenForo_Visitor::getInstance();

        $allMembers = ThemeHouse_SocialGroups_SocialForum::getInstance()->getSocialForumMembers();
        $socialForumModerators = array();
        $socialForumMembers = array();
        $socialForumUnapprovedMembers = array();
        $canApproveSocialForumJoinRequest = $this->_getSocialForumModel()->canApproveSocialForumJoinRequest(
            $socialForum);
        foreach ($allMembers as $member) {
            if ($member['is_invited']) {
                // do nothing
            } else
                if ($member['is_approved']) {
                    if ($member['is_social_forum_moderator']) {
                        $socialForumModerators[] = $member;
                    } else {
                        $socialForumMembers[] = $member;
                    }
                } else
                    if ($canApproveSocialForumJoinRequest) {
                        $socialForumUnapprovedMembers[] = $member;
                    }
        }

        $member = array();
        if (array_key_exists($visitor['user_id'], $allMembers)) {
            $member = $allMembers[$visitor['user_id']];
        }

        $forumId = $this->_controller->getInput()->filterSingle('node_id', XenForo_Input::UINT);

        $ftpHelper = $this->_controller->getHelper('ForumThreadPost');
        $forum = $this->_controller->getHelper('ForumThreadPost')->assertForumValidAndViewable($forumId, array());
        if (isset($subView->params['nodeBreadCrumbs'])) {
            $nodeBreadCrumbs = $subView->params['nodeBreadCrumbs'];
        } else {
            $nodeBreadCrumbs = $ftpHelper->getNodeBreadCrumbs($forum, true);
        }

        $forum['description'] = '';
        $forum['social_forum_id'] = $socialForum['social_forum_id'];
        $forum['title'] = $socialForum['title'];
        $forum['forum_is_watched'] = $socialForum['social_forum_is_watched'];

        $subView->params['forum'] = $forum;

        $canViewSocialForumMembers = $this->_getSocialForumModel()->canViewSocialForumMembers($socialForum);

        $viewParams = array(
            'socialForum' => $socialForum,

            'socialForumModerators' => $canViewSocialForumMembers ? $socialForumModerators : array(),
            'socialForumMembers' => $canViewSocialForumMembers ? $socialForumMembers : array(),
            'socialForumUnapprovedMembers' => $canViewSocialForumMembers ? $socialForumUnapprovedMembers : array(),
            'socialForumMemberCount' => (count($socialForumMembers) + count($socialForumModerators)),

            'member' => $member,

            // 'canPostEvent' => XenForo_Permission::hasPermission($visitor['permissions'], 'EWRatendo', 'canPost') && in_array($forum['node_id'], XenForo_Application::get('options')->EWRatendo_eventforums),
            'canViewSocialForumMembers' => $canViewSocialForumMembers,
            'canJoinSocialForum' => $this->_getSocialForumModel()->canJoinSocialForum($socialForum),
            'canLeaveSocialForum' => $this->_getSocialForumModel()->canLeaveSocialForum($socialForum),
            'canEditSocialForum' => $this->_getSocialForumModel()->canEditSocialForum($socialForum),
            'canMoveSocialForum' => $this->_getSocialForumModel()->canMoveSocialForum($socialForum),
            'canDeleteSocialForum' => $this->_getSocialForumModel()->canDeleteSocialForum($socialForum),

            'nodeBreadCrumbs' => $nodeBreadCrumbs
        );

        $subView->params = array_merge($subView->params, $viewParams);

        if (XenForo_Application::get('options')->th_socialGroups_showSidebar || $forceSidebar) {
            $wrapper = $this->_controller->responseView('ThemeHouse_SocialGroups_ViewPublic_SocialForum_View',
                'th_social_forum_container_socialgroups', $viewParams);
            $wrapper->containerParams['noVisitorPanel'] = true;
            $wrapper->subView = $subView;
            return $wrapper;
        }

        return $subView;
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
     * @return ThemeHouse_SocialGroups_Model_SocialForumMember
     */
    protected function _getSocialForumMemberModel()
    {
        return $this->_controller->getModelFromCache('ThemeHouse_SocialGroups_Model_SocialForumMember');
    }
}