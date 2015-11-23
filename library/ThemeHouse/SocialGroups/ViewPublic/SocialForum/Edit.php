<?php

class ThemeHouse_SocialGroups_ViewPublic_SocialForum_Edit extends XenForo_ViewPublic_Base
{

    public function renderHtml()
    {
        $this->_params['editorTemplate'] = XenForo_ViewPublic_Helper_Editor::getEditorTemplate($this, 'description',
            $this->_params['socialForum']['description']);
    }
}
