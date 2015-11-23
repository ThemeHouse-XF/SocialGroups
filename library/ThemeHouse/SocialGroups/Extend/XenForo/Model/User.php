<?php

/**
 *
 * @see XenForo_Model_User
 */
class ThemeHouse_SocialGroups_Extend_XenForo_Model_User extends XFCP_ThemeHouse_SocialGroups_Extend_XenForo_Model_User
{

    /**
     *
     * @see XenForo_Model_User::__construct()
     */
    public function __construct()
    {
        parent::__construct();

        if (isset(XenForo_Model_User::$userContentChanges)) {
            XenForo_Model_User::$userContentChanges['xf_social_forum'][] = array(
                'user_id'
            );
            XenForo_Model_User::$userContentChanges['xf_social_forum_member'][] = array(
                'user_id'
            );
            XenForo_Model_User::$userContentChanges['xf_social_forum_read'][] = array(
                'user_id'
            );
        }
    }

    /**
     *
     * @see XenForo_Model_User::prepareUserFetchOptions()
     */
    public function prepareUserFetchOptions(array $fetchOptions)
    {
        $userFetchOptions = parent::prepareUserFetchOptions($fetchOptions);

        $selectFields = $userFetchOptions['selectFields'];
        $joinTables = $userFetchOptions['joinTables'];

        /*        if (!empty($fetchOptions['join'])) {
            if ($fetchOptions['join'] & self::FETCH_USER_PROFILE) {
                if (XenForo_Application::get('options')->th_socialGroups_secondaryPostBit) {
                    $selectFields .= ',
    					social_forum_combination.cache_value AS secondary_social_forums';
                    $joinTables .= '
    					INNER JOIN xf_social_forum_combination AS social_forum_combination ON
    						(social_forum_combination.social_forum_combination_id = user_profile.social_forum_combination_id)';
                }
            }
        }*/

        return array(
            'selectFields' => $selectFields,
            'joinTables' => $joinTables
        );
    }

	/**
	 * Determines the maximum number of secondary social forums for the specified user.
	 *
	 * @param string $errorPhraseKey Returned by ref. Phrase key of more specific error
	 * @param array|null $viewingUser Viewing user ref
	 *
	 * @return integer
	 */
	public function getMaximumSecondarySocialForums(&$errorPhraseKey = '', array $viewingUser = null)
	{
		$this->standardizeViewingUserReference($viewingUser);

		if (!$viewingUser['user_id']) {
		    return 0;
		}

		return XenForo_Permission::hasPermission($viewingUser['permissions'], 'general', 'maxSecondarySocialForums');
	}
}