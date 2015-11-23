<?php

class ThemeHouse_SocialGroups_Listener_LoadClass extends ThemeHouse_Listener_LoadClass
{

    protected function _getExtendedClasses()
    {
        return array(
            'ThemeHouse_SocialGroups' => array(
                'controller' => array(
                    'XenForo_ControllerAdmin_Node',
                    'XenForo_ControllerAdmin_Tools',
                    'XenForo_ControllerPublic_Account',
                    'XenForo_ControllerPublic_Forum',
                    'XenForo_ControllerPublic_Member',
                    'XenForo_ControllerPublic_Post',
                    'XenForo_ControllerPublic_Thread',
                    'XenForo_ControllerPublic_Watched',
                    'XenResource_ControllerPublic_Resource'
                ),
                'datawriter' => array(
                    'XenForo_DataWriter_Discussion_Thread',
                    'XenForo_DataWriter_User',
                    'XenResource_DataWriter_Resource',
                    'XenResource_DataWriter_Update'
                ),
                'deferred' => array(
                    'XenForo_Deferred_User'
                ),
                'installer_th' => array(
                ),
                'model' => array(
                    'EWRporta_Model_Blocks',
                    'ThemeHouse_NoForo_Model_NoForo',
                    'XenForo_Model_InlineMod_Post',
                    'XenForo_Model_InlineMod_Thread',
                    'XenForo_Model_Forum',
                    'XenForo_Model_ForumWatch',
                    'XenForo_Model_Permission',
                    'XenForo_Model_Post',
                    'XenForo_Model_Session',
                    'XenForo_Model_Thread',
                    'XenForo_Model_User',
                    'XenResource_Model_Resource',
                    'XenForo_Model_ThreadWatch',
                    'XenForo_Model_Alert'
                ),
                'route_prefix' => array(
                    'XenForo_Route_Prefix_Forums'
                ),
                'view' => array(
                    'XenForo_ViewPublic_Forum_View'
                ),
                'helper' => array(
                    'XenForo_ControllerHelper_ForumThreadPost'
                ),
            ),
        );
    }

    public static function loadClassController($class, array &$extend)
    {
        $extend = self::createAndRun('ThemeHouse_SocialGroups_Listener_LoadClass', $class, $extend, 'controller');
    }

    public static function loadClassDataWriter($class, array &$extend)
    {
        $extend = self::createAndRun('ThemeHouse_SocialGroups_Listener_LoadClass', $class, $extend, 'datawriter');
    }

    public static function loadClassDeferred($class, array &$extend)
    {
        $extend = self::createAndRun('ThemeHouse_SocialGroups_Listener_LoadClass', $class, $extend, 'deferred');
    }

    public static function loadClassInstallerThemeHouse($class, array &$extend)
    {
        $extend = self::createAndRun('ThemeHouse_SocialGroups_Listener_LoadClass', $class, $extend, 'installer_th');
    }

    public static function loadClassModel($class, array &$extend)
    {
        $extend = self::createAndRun('ThemeHouse_SocialGroups_Listener_LoadClass', $class, $extend, 'model');
    }

    public static function loadClassRoutePrefix($class, array &$extend)
    {
        $extend = self::createAndRun('ThemeHouse_SocialGroups_Listener_LoadClass', $class, $extend, 'route_prefix');
    }

    public static function loadClassView($class, array &$extend)
    {
        $extend = self::createAndRun('ThemeHouse_SocialGroups_Listener_LoadClass', $class, $extend, 'view');
    }

    public static function loadClassHelper($class, array &$extend)
    {
        $extend = self::createAndRun('ThemeHouse_SocialGroups_Listener_LoadClass', $class, $extend, 'helper');
    }
}