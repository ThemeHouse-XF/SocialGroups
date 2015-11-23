<?php

class ThemeHouse_SocialGroups_ViewPublic_SocialForum_CreatePreview extends XenForo_ViewPublic_Base
{

    public function renderHtml()
    {
        $bbCodeParser = XenForo_BbCode_Parser::create(
            XenForo_BbCode_Formatter_Base::create('Base', array(
                'view' => $this
            )));
        $this->_params['descriptionParsed'] = new XenForo_BbCode_TextWrapper($this->_params['description'],
            $bbCodeParser);
    }
}