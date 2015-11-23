<?php

class ThemeHouse_SocialGroups_ViewPublic_SocialForum_MemberListItemEdit extends XenForo_ViewPublic_Base
{

    public function renderJson()
    {
        $output = $this->_renderer->getDefaultOutputArray(get_class($this), $this->_params, $this->_templateName);

        $output['memberId'] = $this->_params['user']['user_id'];

        return XenForo_ViewRenderer_Json::jsonEncodeForOutput($output);
    }
}