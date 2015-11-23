<?php

class ThemeHouse_SocialGroups_Extend_ThemeHouse_ForumWatchMore_Install_Controller extends XFCP_ThemeHouse_SocialGroups_Extend_ThemeHouse_ForumWatchMore_Install_Controller
{
    /**
     *
     * @see ThemeHouse_ForumWatchMore_Install_Controller::_getTables()
     */
    protected function _getTables()
    {
        $tables = parent::_getTables();
        
        unset($tables['xf_social_forum_watch']);
        
        return $tables;
    }
    
    /**
     *
     * @see ThemeHouse_ForumWatchMore_Install_Controller::_getPrimaryKeys()
     */
    protected function _getPrimaryKeys()
    {
        $tables = parent::_getPrimaryKeys();
    
        unset($tables['xf_social_forum_watch']);
    
        return $tables;
    }
    
    /**
     *
     * @see ThemeHouse_ForumWatchMore_Install_Controller::_getKeys()
     */
    protected function _getKeys()
    {
        $tables = parent::_getKeys();
    
        unset($tables['xf_social_forum_watch']);
    
        return $tables;
    }
}