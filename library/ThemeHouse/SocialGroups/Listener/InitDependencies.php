<?php

class ThemeHouse_SocialGroups_Listener_InitDependencies extends ThemeHouse_Listener_InitDependencies
{

    public function run()
    {
        XenForo_Model_Import::$extraImporters[] = "ThemeHouse_SocialGroups_Importer_XfAddOns_Groups";

        XenForo_CacheRebuilder_Abstract::$builders['SocialGroups'] = 'ThemeHouse_SocialGroups_CacheRebuilder_SocialForum';
        
        parent::run();
    }

    public static function initDependencies(XenForo_Dependencies_Abstract $dependencies, array $data)
    {
        $initDependencies = new ThemeHouse_SocialGroups_Listener_InitDependencies($dependencies, $data);
        $initDependencies->run();
    }
}