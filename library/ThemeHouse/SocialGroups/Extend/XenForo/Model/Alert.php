<?php

/**
 *
 * @see XenForo_Model_Alert
 */
class ThemeHouse_SocialGroups_Extend_XenForo_Model_Alert extends XFCP_ThemeHouse_SocialGroups_Extend_XenForo_Model_Alert
{

    protected static $_socialForumAlertOverride = false;

    /**
     *
     * @param boolean $value
     */
    public function setSocialForumAlertOverride($value)
    {
        self::$_socialForumAlertOverride = $value;
    }

    /**
     *
     * @see XenForo_Model_Alert::getAlertOptOuts()
     */
    public function getAlertOptOuts(array $user = null, $useDenormalized = true)
	{
        $optOuts = parent::getAlertOptOuts($user, $useDenormalized);

        if (self::$_socialForumAlertOverride) {
            unset($optOuts["post_insert"]);
            unset($optOuts["post_insert_attachment"]);
        }

        return $optOuts;
    }
}