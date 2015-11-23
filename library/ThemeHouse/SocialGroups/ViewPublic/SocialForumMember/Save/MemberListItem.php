<?php

class ThemeHouse_SocialGroups_ViewPublic_SocialForumMember_Save_MemberListItem extends XenForo_ViewPublic_Base
{

    public function renderJson()
    {
        $this->_params['noOverlay'] = true;
        $this->_params['id'] = 'member-' . $this->_params['user']['user_id'];

        if (isset($this->_params['user']['username'])) {
            return XenForo_ViewRenderer_Json::jsonEncodeForOutput(
                array(
                    'templateHtml' => $this->createTemplateObject('th_member_list_item_socialgroups',
                        $this->_params),
                    'memberId' => $this->_params['user']['user_id']
                ));
        } else {
            return XenForo_ViewRenderer_Json::jsonEncodeForOutput(
                array(
                    'memberId' => $this->_params['user']['user_id']
                ));
        }
    }
}