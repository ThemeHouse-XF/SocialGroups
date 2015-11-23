<?php

class ThemeHouse_SocialGroups_Listener_TemplatePostRender extends ThemeHouse_Listener_TemplatePostRender
{

    protected function _getTemplates()
    {
        return array(
            'forum_view',
            'PAGE_CONTAINER',
            'post_field_edit',
            'resource_category_edit',
            'thread_field_edit',
            'thread_prefix_edit',
            'tools_rebuild',
            'th_social_forum_container_socialgroups',
        );
    }

    public static function templatePostRender($templateName, &$content, array &$containerData,
        XenForo_Template_Abstract $template)
    {
        $templatePostRender = new ThemeHouse_SocialGroups_Listener_TemplatePostRender($templateName, $content,
            $containerData, $template);
        list ($content, $containerData) = $templatePostRender->run();
    }

    protected function _forumView()
    {
        if (ThemeHouse_SocialGroups_SocialForum::hasInstance()) {
            $socialForum = ThemeHouse_SocialGroups_SocialForum::getInstance();
            $viewParams = $this->_fetchViewParams();
            $viewParams['socialForum'] = $socialForum->toArray();
            $pattern = '#<div class="linkGroup SelectionCountContainer">#';
            $replacement = '$0' . $this->_render('th_social_forum_tools_socialgroups', $viewParams);
            $this->_patternReplace($pattern, $replacement);
        }
    }

    protected function _threadPrefixEdit()
    {
        $viewParams = $this->_fetchViewParams();

        foreach ($viewParams['nodes'] as $nodeId => $node) {
            if ($node['node_type_id'] == 'SocialCategory') {
                $pattern = '#(<option value="' . $nodeId . '"[^>]*)disabled="disabled"([^>]*>[^<]*</option>)#';
                $replacement = '${1}${2}';
                $this->_patternReplace($pattern, $replacement);
            }
        }
    }

    protected function _threadFieldEdit()
    {
        $this->_threadPrefixEdit();
    }

    protected function _postFieldEdit()
    {
        $this->_threadPrefixEdit();
    }

    protected function _pageContainer()
    {
        if (isset($GLOBALS['forum_view'])) {
            $viewParams = $this->_fetchViewParams();
            /* @var $socialForum ThemeHouse_SocialGroups_SocialForum */
            $socialForum = ThemeHouse_SocialGroups_SocialForum::getInstance();
            if (isset($socialForum['social_forum_id'])) {
                $viewParams['socialForum'] = $socialForum;
                $visitor = XenForo_Visitor::getInstance();
                if (isset($visitor['user_id'])) {
                    $member = $socialForum->getMember();
                    if (!array_key_exists('social_forum_member_id', $member) &&
                         $this->_getSocialForumModel()->canJoinSocialForum($socialForum)) {
                        $this->_appendTemplateAfterTopCtrl('th_join_social_forum_topctrl_socialgroups',
                            $viewParams);
                    } else
                        if ($member['is_invited']) {
                            $this->_appendTemplateAfterTopCtrl('th_accept_invite_topctrl_socialgroups',
                                $viewParams);
                        }
                }
            }
        }
    }

    protected function _toolsRebuild()
    {
        $this->_appendTemplate('th_tools_rebuild_socialgroups');
    }

    protected function _resourceCategoryEdit()
    {
        $viewParams = $this->_fetchViewParams();
        $nodes = $viewParams['nodes'];
        foreach ($nodes as $node) {
            if ($node['node_type_id'] == 'SocialCategory') {
                $pattern = '#(<select name="thread_node_id" class="textCtrl" id="ctrl_node_id">.*<option value="' . $node['node_id'] .'".*)disabled="disabled"(.*</select>)#Us';
                $replacement = '${1}${2}';
                $this->_patternReplace($pattern, $replacement);
            }
        }
    }

    protected function _thSocialForumContainerSocialGroups()
    {
        $viewParams = $this->_fetchViewParams();
        $resource = ThemeHouse_SocialGroups_SocialForum::getInstance()->getResource();
        if ($resource) {
            $this->_prependTemplate('resource_view_header', $viewParams + array(
                'resource' => $resource,
                'titleHtml' => (isset($containerData['h1']) ? $this->_containerData['h1'] : false),
            ));
            $this->_containerData['h1'] = '';
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