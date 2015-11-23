<?php

class ThemeHouse_SocialGroups_ViewPublic_SocialForum_AvatarUpload extends XenForo_ViewPublic_Base
{

    public function prepareParams()
    {
        $this->_params['socialForum']['avatar_height'] = $this->_params['socialForum']['logo_height'];
        $this->_params['socialForum']['avatar_width'] = $this->_params['socialForum']['logo_width'];
        $this->_params['socialForum']['avatar_crop_x'] = $this->_params['socialForum']['logo_crop_x'];
        $this->_params['socialForum']['avatar_crop_y'] = $this->_params['socialForum']['logo_crop_y'];
        $this->_params['cropCss'] = XenForo_ViewPublic_Helper_User::getAvatarCropCss($this->_params['socialForum']);
    }

    public function renderJson()
    {
        $this->_params['urls'] = ThemeHouse_SocialGroups_Template_Helper_SocialForum::getAvatarUrls(
            $this->_params['socialForum']);

        $this->_params['user_id'] = "sg-" . $this->_params['social_forum_id'];

        $output = XenForo_Application::arrayFilterKeys($this->_params,
            array(
                'sizeCode',
                'maxWidth',
                'maxDimension',
                'width',
                'height',
                'cropX',
                'cropY',
                'urls',
                'user_id',
                'logo_date',
                'cropCss',
                'message'
            ));

        return XenForo_ViewRenderer_Json::jsonEncodeForOutput($output);
    }
}