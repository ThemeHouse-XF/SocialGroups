<?php

/**
 *
 * @see ThemeHouse_NoForo_Model_NoForo
 */
class ThemeHouse_SocialGroups_Extend_ThemeHouse_NoForo_Model_NoForo extends XFCP_ThemeHouse_SocialGroups_Extend_ThemeHouse_NoForo_Model_NoForo
{

    /**
     *
     * @see ThemeHouse_NoForo_Model_NoForo::rebuildForum()
     */
    public function rebuildForum()
    {
        $this->_rebuildPermissionsForAddOn('ThemeHouse_SocialGroups');

        parent::rebuildForum();

        // TODO: probably don't need to rebuild the entire add-on
        ThemeHouse_Install::install(array(), array(
            'addon_id' => 'ThemeHouse_SocialGroups'
        ));
    }
}