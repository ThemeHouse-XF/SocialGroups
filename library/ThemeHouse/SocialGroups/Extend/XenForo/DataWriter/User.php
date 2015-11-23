<?php

/**
 *
 * @see XenForo_DataWriter_User
 */
class ThemeHouse_SocialGroups_Extend_XenForo_DataWriter_User extends XFCP_ThemeHouse_SocialGroups_Extend_XenForo_DataWriter_User
{

    /**
     *
     * @see XenForo_DataWriter_User::_getFields()
     */
    protected function _getFields()
    {
        $fields = parent::_getFields();
        $fields['xf_user_profile']['primary_social_forum_id'] = array(
            'type' => self::TYPE_UINT,
            'default' => 0
        );
        $fields['xf_user_profile']['secondary_social_forum_ids'] = array(
            'type' => self::TYPE_STRING,
            'default' => ''
        );
        $fields['xf_user_profile']['social_forum_combination_id'] = array(
            'type' => self::TYPE_UINT,
            'default' => 0
        );
        $fields['xf_user_profile']['social_forums_joined'] = array(
            'type' => self::TYPE_UINT,
            'default' => 0
        );
        $fields['xf_user_profile']['social_forums_created'] = array(
            'type' => self::TYPE_UINT,
            'default' => 0
        );
        return $fields;
    }

    /**
     *
     * @see XenForo_DataWriter_User::_preSave()
     */
    protected function _preSave()
    {
        parent::_preSave();

        if (!empty($GLOBALS['XenForo_ControllerAdmin_Tools']) || !empty($GLOBALS['XenForo_Deferred_User'])) {
            if ($this->get('user_id')) {
                /* @var $socialForumModel ThemeHouse_SocialGroups_Model_SocialForum */
                $socialForumModel = ThemeHouse_SocialGroups_SocialForum::getSocialForumModel();
                $this->set('social_forums_joined', $socialForumModel->countJoinedSocialForumsForUser($this->get('user_id')));
                $this->set('social_forums_created', $socialForumModel->countSocialForums(array('creator_id' => $this->get('user_id'))));
            }
        }

        if (!empty($GLOBALS['XenForo_ControllerPublic_Account'])) {
            /* @var $controller XenForo_ControllerPublic_Account */
            $controller = $GLOBALS['XenForo_ControllerPublic_Account'];

            if ($controller->getRouteMatch()->getAction() == 'personal-details-save') {
                $primarySocialForumId = 0;
                $secondarySocialForumIds = array();

                $xenOptions = XenForo_Application::get('options');
                if ($xenOptions->th_socialGroups_allowPrimary) {
                    $primarySocialForumId = $controller->getInput()->filterSingle('primary_social_forum_id', XenForo_Input::UINT);
                }
                if ($xenOptions->th_socialGroups_allowSecondary) {
                    $secondarySocialForumIds = $controller->getInput()->filterSingle('secondary_social_forum_ids', XenForo_Input::ARRAY_SIMPLE);
                }

                if ($primarySocialForumId && in_array($primarySocialForumId, $secondarySocialForumIds)) {
                    unset($secondarySocialForumIds[array_search($primarySocialForumId, $secondarySocialForumIds)]);
                }

                $maxSecondarySocialForums = $this->_getUserModel()->getMaximumSecondarySocialForums();
                if (count($secondarySocialForumIds) > $maxSecondarySocialForums) {
                    $secondarySocialForumIds = array_slice($secondarySocialForumIds, 0, $maxSecondarySocialForums);
                }

                $secondarySocialForumIds = implode(",", $secondarySocialForumIds);

                $this->set('primary_social_forum_id', $primarySocialForumId);
                $this->set('secondary_social_forum_ids', $secondarySocialForumIds);
            }
        }

        if ($this->isChanged('secondary_social_forum_ids') || !empty($GLOBALS['XenForo_Deferred_User'])) {
            $secondarySocialForumIds = $this->get('secondary_social_forum_ids');

            /* @var $socialForumCombinationModel ThemeHouse_SocialGroups_Model_SocialForumCombination */
            $socialForumCombinationModel = XenForo_Model::create('ThemeHouse_SocialGroups_Model_SocialForumCombination');
            $socialForumCombinationId = $socialForumCombinationModel->getSocialForumCombinationBySocialForumIds($secondarySocialForumIds);

            $this->set('social_forum_combination_id', $socialForumCombinationId);

            if ($socialForumCombinationId) {
                $socialForumCombination = array(
                    'social_forum_combination_id' => $socialForumCombinationId,
                    'social_forum_ids' => $secondarySocialForumIds
                );
                $socialForumCombinationModel->rebuildSocialForumCombination($socialForumCombination);
            }
        }
    }
}
