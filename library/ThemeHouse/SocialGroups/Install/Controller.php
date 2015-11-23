<?php

class ThemeHouse_SocialGroups_Install_Controller extends ThemeHouse_Install
{

    protected $_resourceManagerUrl = 'http://xenforo.com/community/resources/social-groups.891/';

    protected function _preInstall()
    {
        $this->_upgradeFromBdForumWatchMore();
    }

    protected function _upgradeFromBdForumWatchMore()
    {
        if ($this->_isTableExists('xf_social_forum_watch')) {
            $columns = $this->_db->describeTable('xf_social_forum_watch');
            if (isset($columns['reply_subscribe']) && !isset($columns['notify_on'])) {
                $this->_db->query(
                    '
                    ALTER TABLE xf_social_forum_watch
                    ADD notify_on enum(\'\',\'thread\',\'message\') NOT NULL
                ');
                $this->_db->query(
                    '
                    UPDATE xf_social_forum_watch SET notify_on = \'thread\' WHERE reply_subscribe = 0
                ');
                $this->_db->query(
                    '
                    UPDATE xf_social_forum_watch SET notify_on = \'message\' WHERE reply_subscribe = 1
                ');
                $this->_db->query(
                    '
                    ALTER TABLE xf_social_forum_watch
                    DROP reply_subscribe
                ');
            }
            if (isset($columns['notification_method']) && !isset($columns['send_email']) &&
                 !isset($columns['send_alert'])) {
                $this->_db->query(
                    '
                    ALTER TABLE xf_social_forum_watch
                    CHANGE notification_method send_alert tinyint UNSIGNED NOT NULL,
                    ADD send_email tinyint UNSIGNED NOT NULL
                ');
                $this->_db->query(
                    '
                    UPDATE xf_social_forum_watch SET send_email = 1 WHERE send_alert = 0
                ');
                $this->_db->query(
                    '
                    UPDATE xf_social_forum_watch SET send_email = 1, send_alert = 1 WHERE send_alert = 2
                ');
            }
        }
    }

    protected function _getTables()
    {
        return array(
            'xf_social_forum' => array(
                'social_forum_id' => 'int UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY', 
                'group_state' => 'enum(\'visible\',\'moderated\') NOT NULL DEFAULT \'visible\'', 
                'node_id' => 'int UNSIGNED NOT NULL', 
                'title' => 'varchar(150) NOT NULL DEFAULT \'\'', 
                'url_portion' => 'varchar(50) NULL', 
                'description' => 'text NOT NULL', 
                'discussion_count' => 'int UNSIGNED NOT NULL DEFAULT \'0\'', 
                'message_count' => 'int UNSIGNED NOT NULL DEFAULT \'0\'', 
                'member_count' => 'int UNSIGNED NOT NULL DEFAULT \'0\'', 
                'sticky' => 'tinyint UNSIGNED NOT NULL DEFAULT \'0\'', 
                'last_post_id' => 'int UNSIGNED NOT NULL DEFAULT \'0\'', 
                'last_post_date' => 'int UNSIGNED NOT NULL DEFAULT \'0\'', 
                'last_post_user_id' => 'int UNSIGNED NOT NULL DEFAULT \'0\'', 
                'last_post_username' => 'varchar(50) NOT NULL DEFAULT \'\'', 
                'last_thread_title' => 'varchar(150) NOT NULL DEFAULT \'\'', 
                'moderate_messages' => 'tinyint UNSIGNED NOT NULL DEFAULT \'0\'', 
                'allow_posting' => 'tinyint UNSIGNED NOT NULL DEFAULT \'1\'', 
                'count_messages' => 'tinyint UNSIGNED NOT NULL DEFAULT \'1\'', 
                'find_new' => 'tinyint UNSIGNED NOT NULL DEFAULT \'1\'', 
                'social_forum_open' => 'tinyint UNSIGNED NOT NULL DEFAULT \'1\'', 
                'social_forum_moderated' => 'tinyint UNSIGNED NOT NULL DEFAULT \'0\'', 
                'social_forum_type' => 'varchar(25) NOT NULL DEFAULT \'\'', 
                'logo_date' => 'int UNSIGNED NOT NULL DEFAULT \'0\'', 
                'logo_width' => 'smallint UNSIGNED NOT NULL DEFAULT \'0\'', 
                'logo_height' => 'smallint UNSIGNED NOT NULL DEFAULT \'0\'', 
                'logo_crop_x' => 'int UNSIGNED NOT NULL DEFAULT \'0\'', 
                'logo_crop_y' => 'int UNSIGNED NOT NULL DEFAULT \'0\'', 
                'user_id' => 'int NOT NULL DEFAULT \'0\'', 
                'created_date' => 'int UNSIGNED NOT NULL DEFAULT \'0\'', 
                'style_id' => 'int UNSIGNED NOT NULL DEFAULT \'0\'', 
            ), 
            'xf_social_forum_member' => array(
                'social_forum_member_id' => 'int UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY', 
                'user_id' => 'int NOT NULL', 
                'social_forum_id' => 'int NOT NULL', 
                'is_social_forum_moderator' => 'tinyint NOT NULL DEFAULT \'0\'', 
                'is_social_forum_creator' => 'tinyint NOT NULL DEFAULT \'0\'', 
                'join_date' => 'int NOT NULL', 
                'is_approved' => 'tinyint NOT NULL DEFAULT \'0\'', 
                'is_invited' => 'tinyint NOT NULL DEFAULT \'0\'', 
            ), 
            'xf_social_forum_read' => array(
                'social_forum_read_id' => 'int UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY', 
                'user_id' => 'int UNSIGNED NOT NULL', 
                'social_forum_id' => 'int UNSIGNED NOT NULL', 
                'social_forum_read_date' => 'int UNSIGNED NOT NULL', 
            ), 
            'xf_social_forum_combination' => array(
                'social_forum_combination_id' => 'int UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY', 
                'social_forum_ids' => 'mediumtext NULL', 
                'cache_value' => 'mediumtext NULL', 
            ), 
            'xf_social_forum_watch' => array(
                'user_id' => 'int UNSIGNED NOT NULL', 
                'social_forum_id' => 'int UNSIGNED NOT NULL', 
                'notify_on' => 'enum(\'\',\'thread\',\'message\') NOT NULL', 
                'send_alert' => 'tinyint UNSIGNED NOT NULL', 
                'send_email' => 'tinyint UNSIGNED NOT NULL', 
            ), 
        );
    }

    protected function _getPrimaryKeys()
    {
        return array(
            'xf_social_forum_watch' => array(
                'user_id',
                'social_forum_id'
            ), 
        );
    }

    protected function _getUniqueKeys()
    {
        return array(
            'xf_social_forum_member' => array(
                'user_id_social_forum_id' => array(
                    'user_id', 
                    'social_forum_id', 
                ), 
            ), 
            'xf_social_forum_read' => array(
                'user_id_social_forum_id' => array(
                    'user_id', 
                    'social_forum_id', 
                ), 
            ), 
        );
    }

    protected function _getKeys()
    {
        return array(
            'xf_social_forum_read' => array(
                'social_forum_id' => array(
                    'social_forum_id', 
                ), 
                'social_forum_read_date' => array(
                    'social_forum_read_date', 
                ), 
            ), 
            'xf_thread' => array(
                'social_forum_id_last_post_date' => array(
                    'social_forum_id', 
                    'last_post_date', 
                ), 
                'social_forum_id_sticky_last_post_date' => array(
                    'social_forum_id', 
                    'sticky', 
                    'last_post_date', /* 'last_post_date' */
                ), 
            ), 
            'xf_social_forum_watch' => array(
                'social_forum_id_notify_on' => array(
                    'social_forum_id',
                    'notify_on'
                ),
            ), 
        );
    }

    protected function _getTableChanges()
    {
        return array(
            'xf_thread' => array(
                'social_forum_id' => 'int NOT NULL DEFAULT \'0\'', 
            ), 
            'xf_user_profile' => array(
                'primary_social_forum_id' => 'int NOT NULL DEFAULT \'0\'', 
                'secondary_social_forum_ids' => 'varchar(150) NULL DEFAULT \'\'', 
                'social_forum_combination_id' => 'int NOT NULL DEFAULT \'0\'', 
                'social_forums_joined' => 'int NOT NULL DEFAULT \'0\'', 
                'social_forums_created' => 'int NOT NULL DEFAULT \'0\'', 
            ), 
            'xf_resource' => array(
                'social_forum_id' => 'int NOT NULL DEFAULT \'0\'', 
            ), 
        );
    }

    protected function _getNodeTypes()
    {
        return array(
            'SocialCategory' => array(
                'handler_class' => 'ThemeHouse_SocialGroups_NodeHandler_SocialCategory',
                'controller_admin_class' => 'ThemeHouse_SocialGroups_ControllerAdmin_SocialCategory',
                'datawriter_class' => 'ThemeHouse_SocialGroups_DataWriter_SocialCategory',
                'permission_group_id' => 'forum', 
                'public_route_prefix' => 'social-categories', 
            ), 
        );
    }

    protected function _getContentTypes()
    {
        return array(
            'social_forum' => array(
                'addon_id' => 'ThemeHouse_SocialGroups',
                'fields' => array(
                    'alert_handler_class' => 'ThemeHouse_SocialGroups_AlertHandler_SocialForum',
                    'moderation_queue_handler_class' => 'ThemeHouse_SocialGroups_ModerationQueueHandler_SocialForum',
                ), 
            ), 
        );
    }

    protected function _getPermissionEntries()
    {
        return array(
            'forum' => array(
                'viewSocialForum' => array(
                    'permission_group_id' => 'general', 
                    'permission_id' => 'viewNode', 
                ), 
                'maxSocialForums' => array(
                    'permission_group_id' => 'forum', 
                    'permission_id' => 'joinSocialForum', 
                ), 
                'joinSocialForum' => array(
                    'permission_group_id' => 'forum', 
                    'permission_id' => 'viewOthers', 
                ), 
                'leaveSocialForum' => array(
                    'permission_group_id' => 'forum', 
                    'permission_id' => 'viewOthers', 
                ), 
            ), 
        );
    }

    protected function _postInstallAfterTransaction()
    {
        /* @var $socialForumCombinationModel ThemeHouse_SocialGroups_Model_SocialForumCombination */
        $socialForumCombinationModel = $this->getModelFromCache('ThemeHouse_SocialGroups_Model_SocialForumCombination');
        $socialForumCombinationModel->rebuildAllSocialForumCombinations();
    }

    protected function _postUninstall()
    {
        if ($this->_isAddOnInstalled('EWRporta') && class_exists('EWRporta_Model_Blocks') &&
             method_exists('EWRporta_Model_Blocks', 'getBlockById') &&
             method_exists('EWRporta_Model_Blocks', 'uninstallBlock')) {
            $blockModel = $this->getModelFromCache('EWRporta_Model_Blocks');
            $block = $blockModel->getBlockById('ThemeHouse_NewSocialForums');
            if ($block) {
                $blockModel->uninstallBlock($block);
            }
        }
    }

    protected function _postUninstallAfterTransaction()
    {
        if ($this->_isAddOnInstalled('NodesAsTabs') && class_exists('NodesAsTabs_Model_Options') &&
             method_exists('NodesAsTabs_Model_Options', 'deleteOrphans') &&
             method_exists('NodesAsTabs_Model_Options', 'rebuildCache')) {
            /* @var $optionsModel NodesAsTabs_Model_Options */
            $optionsModel = XenForo_Model::create('NodesAsTabs_Model_Options');

            $optionsModel->deleteOrphans();
            $optionsModel->rebuildCache();
        }
    }
}