<?php

/**
 *
 * @see XenForo_ControllerPublic_Account
 */
class ThemeHouse_SocialGroups_Extend_XenForo_ControllerPublic_Account extends XFCP_ThemeHouse_SocialGroups_Extend_XenForo_ControllerPublic_Account
{

    /**
     *
     * @see XenForo_ControllerPublic_Account::actionPersonalDetails()
     */
    public function actionPersonalDetails()
    {
        $response = parent::actionPersonalDetails();

        if ($response instanceof XenForo_ControllerResponse_View) {
            $response->subView->params['maxSecondarySocialForums'] = $this->_getUserModel()->getMaximumSecondarySocialForums();
        }

        return $response;
    }

    /**
     *
     * @see XenForo_ControllerPublic_Account::actionPersonalDetailsSave()
     */
    public function actionPersonalDetailsSave()
    {
        $GLOBALS['XenForo_ControllerPublic_Account'] = $this;

        return parent::actionPersonalDetailsSave();
    }
}