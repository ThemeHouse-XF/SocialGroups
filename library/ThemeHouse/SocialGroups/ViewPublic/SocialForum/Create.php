<?php

class ThemeHouse_SocialGroups_ViewPublic_SocialForum_Create extends XenForo_ViewPublic_Base
{

    public function renderHtml()
    {
        $this->_params['editorTemplate'] = XenForo_ViewPublic_Helper_Editor::getEditorTemplate($this, 'description');
    }
}