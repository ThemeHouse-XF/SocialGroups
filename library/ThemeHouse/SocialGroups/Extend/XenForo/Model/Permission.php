<?php

/**
 *
 * @see XenForo_Model_Permission
 */
class ThemeHouse_SocialGroups_Extend_XenForo_Model_Permission extends XFCP_ThemeHouse_SocialGroups_Extend_XenForo_Model_Permission
{

    /**
     *
     * @see XenForo_Model_Permission::getPermissionByGroup()
     */
    public function getPermissionByGroup($permissionGroupId)
    {
        return $this->fetchAllKeyed(
            '
			SELECT *
			FROM xf_permission
			WHERE permission_group_id = ' . $this->_getDb()
                ->quote($permissionGroupId) . '
			AND permission_type = \'flag\'
			ORDER BY interface_group_id DESC, display_order ASC
		', 'permission_id');
    }

    /**
     *
     * @param array $preparedOption
     * @param string $guest
     * @return array $preparedOption
     */
    public function getSocialGroupsPreparedOption(array $preparedOption, $guest = false)
    {
        $preparedOption['count'] = array(
            1,
            2,
            3
        );

        $permissions = $this->getPermissionByGroup('forum');

        foreach ($permissions as $permissionId => $permission) {
            if (($permissionId != "joinSocialForum" || $guest) &&
                 ($permissionId != "leaveSocialForum" || !$guest)) {
                $preparedOption['permissions'][$permissionId] = new XenForo_Phrase('permission_forum_' . $permissionId);
            }
        }

        return $preparedOption;
    }
}