<?php

/**
 * Helper methods for the social forum template functions/tags.
 */
class ThemeHouse_SocialGroups_Template_Helper_SocialForum
{

    /**
     * Returns an array containing the URLs for each avatar size available for
     * the given user
     *
     * @param array $socialForum
     *
     * @return array [$sizeCode => $url, $sizeCode => $url...]
     */
    public static function getAvatarUrls(array $socialForum)
    {
        $urls = array();

        foreach (ThemeHouse_SocialGroups_Model_SocialForumAvatar::getSizes() as $sizeCode => $maxDimensions) {
            $urls[$sizeCode] = self::getAvatarUrl($socialForum, $sizeCode);
        }

        return $urls;
    }

    /**
     * Returns the URL to the appropriate avatar type for the given user
     *
     * @param array $socialForum
     * @param string $size (s,m,l)
     * @param string Force 'default' or 'custom' type
     *
     * @return string
     */
    public static function getAvatarUrl(array $socialForum, $size, $forceType = '')
    {
        if (!empty($socialForum['social_forum_id']) && $forceType != 'default') {
            if (!empty($socialForum['logo_date'])) {
                return self::_getCustomAvatarUrl($socialForum, $size);
            }
        }

        return self::_getDefaultAvatarUrl($socialForum, $size);
    }

    /**
     * Returns the default gender-specific avatar URL
     *
     * @param string $size (s,m,l)
     *
     * @return string
     */
    protected static function _getDefaultAvatarUrl(array $socialForum, $size)
    {
        if (XenForo_Application::get('options')->th_socialGroups_useCreatorAvatar && $socialForum['user_id']) {
            $user = XenForo_Model::create('XenForo_Model_User')->getUserById($socialForum['user_id']);
            if ($user)
                return XenForo_Template_Helper_Core::getAvatarUrl($user, $size);
        }

        if (!$imagePath = XenForo_Template_Helper_Core::styleProperty('imagePath')) {
            $imagePath = 'styles/default';
        }

        return "{$imagePath}/xenforo/avatars/avatar_{$size}.png";
    }

    /**
     * Returns the URL to a user's custom avatar
     *
     * @param array $socialForum
     * @param string $size (s,m,l)
     *
     * @return string
     */
    protected static function _getCustomAvatarUrl(array $socialForum, $size)
    {
        $group = floor($socialForum['social_forum_id'] / 1000);
        return XenForo_Application::$externalDataUrl .
             "/social_forum_avatars/$size/$group/$socialForum[social_forum_id].jpg?$socialForum[logo_date]";
    }
}