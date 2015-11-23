<?php

/**
 *
 * @see XenForo_ControllerPublic_Forum
 */
class ThemeHouse_SocialGroups_Extend_XenForo_ControllerPublic_Forum extends XFCP_ThemeHouse_SocialGroups_Extend_XenForo_ControllerPublic_Forum
{

    /**
     *
     * @see XenForo_ControllerPublic_Forum::actionIndex()
     */
    public function actionIndex()
    {
        /* @var $response XenForo_ControllerResponse_View */
        $response = parent::actionIndex();

        return $this->_getSocialCategoryResponse($response);
    }

    /**
     *
     * @see XenForo_ControllerPublic_Forum::actionForum()
     */
    public function actionForum()
    {
    	/* @var $response XenForo_ControllerResponse_View */
    	$response = parent::actionForum();
    
    	return $this->_getSocialCategoryResponse($response);
    }
    
    protected function _getSocialCategoryResponse(XenForo_ControllerResponse_Abstract $response)
    {
    	if (!ThemeHouse_SocialGroups_SocialForum::hasInstance() && $response instanceof XenForo_ControllerResponse_View) {
    		if (isset($response->params['forum']) && isset($response->params['forum']['node_type_id'])) {
    			if ($response->params['forum']['node_type_id'] == "SocialCategory") {
    				return $this->responseRedirect(XenForo_ControllerResponse_Redirect::RESOURCE_CANONICAL_PERMANENT,
    						XenForo_Link::buildPublicLink('social-categories', $response->params['forum']));
    			}
    		}
    	}
    	
    	return $response;
    }
}