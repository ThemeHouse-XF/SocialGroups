<?php

/**
 * Helper for social category related pages.
 * Provides validation methods, amongst other things.
 */
class ThemeHouse_SocialGroups_ControllerHelper_SocialCategory extends XenForo_ControllerHelper_ForumThreadPost
{

    /**
     * Gets the specified forum or throws an error.
     *
     * @param integer|string $forumIdOrName Forum ID or node name
     * @param array $fetchOptions Options that control the data fetched with the
     * forum
     *
     * @return array
     */
    public function getForumOrError($forumIdOrName, array $fetchOptions = array())
    {
        if (is_int($forumIdOrName) || $forumIdOrName === strval(intval($forumIdOrName))) {
            $forum = $this->_controller->getModelFromCache('ThemeHouse_SocialGroups_Model_SocialCategory')->getForumById(
                $forumIdOrName, $fetchOptions);
        } else {
            $forum = $this->_controller->getModelFromCache('ThemeHouse_SocialGroups_Model_SocialCategory')->getForumByNodeName(
                $forumIdOrName, $fetchOptions);
        }

        if (!$forum) {
            throw $this->_controller->responseException(
                $this->_controller->responseError(
                    new XenForo_Phrase('th_requested_social_category_not_found_socialgroups'), 404));
        }

        return $forum;
    }
}