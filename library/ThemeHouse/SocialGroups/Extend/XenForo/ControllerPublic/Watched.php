<?php
class ThemeHouse_SocialGroups_Extend_XenForo_ControllerPublic_Watched extends XFCP_ThemeHouse_SocialGroups_Extend_XenForo_ControllerPublic_Watched
{

    /**
     * List of all new watched content.
     *
     * @return XenForo_ControllerResponse_Abstract
     */
    public function actionSocialForums()
    {
        /* @var $socialForumModel ThemeHouse_SocialGroups_Model_SocialForum */
        $socialForumModel = ThemeHouse_SocialGroups_SocialForum::getSocialForumModel();
        $forumWatchModel = $this->_getForumWatchModel();
        $visitor = XenForo_Visitor::getInstance();

        $socialForumsWatched = $forumWatchModel->getUserSocialForumWatchByUser($visitor['user_id']);

        $socialForumIds = array_keys($socialForumsWatched);

        $fetchOptions = array(
            'join' => ThemeHouse_SocialGroups_Model_SocialForum::FETCH_SOCIAL_MEMBER |
                ThemeHouse_SocialGroups_Model_SocialForum::FETCH_AVATAR,
            'readUserId' => $visitor['user_id']
        );

        $socialForums = $socialForumModel->getSocialForumsByIds($socialForumIds, $fetchOptions);

        foreach ($socialForums as &$socialForum) {
            $socialForum = $socialForumModel->prepareSocialForum($socialForum);
        }

        $viewParams = array(
            'socialForums' => $socialForums,
            'socialForumsWatched' => $socialForumsWatched
        );

        return $this->responseView('ThemeHouse_SocialGroups_ViewPublic_Watched_SocialForums', 'th_watch_social_forums_socialgroups', $viewParams);
    }

    public function actionSocialForumsUpdate()
    {
        $this->_assertPostOnly();

        $input = $this->_input->filter(array(
            'social_forum_ids' => array(
                XenForo_Input::UINT,
                'array' => true
            ),
            'do' => XenForo_Input::STRING
        ));

        $watch = $this->_getForumWatchModel()->getUserSocialForumWatchBySocialForumIds(XenForo_Visitor::getUserId(), $input['social_forum_ids']);

        foreach ($watch as $forumWatch) {
            $dw = XenForo_DataWriter::create('ThemeHouse_SocialGroups_DataWriter_SocialForumWatch');
            $dw->setExistingData($forumWatch, true);

            switch ($input['do']) {
                case 'stop' :
                    $dw->delete();
                    break;

                case 'email' :
                    $dw->set('send_email', 1);
                    $dw->save();
                    break;

                case 'no_email' :
                    $dw->set('send_email', 0);
                    $dw->save();
                    break;

                case 'alert' :
                    $dw->set('send_alert', 1);
                    $dw->save();
                    break;

                case 'no_alert' :
                    $dw->set('send_alert', 0);
                    $dw->save();
                    break;
            }
        }

        return $this->responseRedirect(XenForo_ControllerResponse_Redirect::SUCCESS, $this->getDynamicRedirect(XenForo_Link::buildPublicLink('watched/social-forums')));
    }
}