<?php

/**
 *
 * @see XenResource_DataWriter_Resource
 */
class ThemeHouse_SocialGroups_Extend_XenResource_DataWriter_Resource extends XFCP_ThemeHouse_SocialGroups_Extend_XenResource_DataWriter_Resource
{

    /**
     *
     * @see XenResource_DataWriter_Resource::_getFields()
     */
    protected function _getFields()
    {
        $fields = parent::_getFields();
        $fields['xf_resource']['social_forum_id'] = array(
            'type' => self::TYPE_UINT,
            'default' => '0'
        );

        return $fields;
    }

    /**
     *
     * @see XenResource_DataWriter_Resource::_insertDiscussionThread()
     */
    protected function _insertDiscussionThread($nodeId, $prefixId = 0)
    {
        /* @var $nodeModel XenForo_Model_Node */
        $nodeModel = $this->getModelFromCache('XenForo_Model_Node');

        $node = $nodeModel->getNodeById($nodeId);

        if (!$node) {
            return false;
        }

        if ($node['node_type_id'] == 'SocialCategory') {
            $socialForumId = 0;
            if (isset($GLOBALS['XenResource_ControllerPublic_Resource'])) {
                /* @var $controller XenResource_ControllerPublic_Resource */
                $controller = $GLOBALS['XenResource_ControllerPublic_Resource'];

                $socialForumId = $controller->getInput()->filterSingle('social_forum_id', XenForo_Input::UINT);
            }

            if ($socialForumId) {
                $socialForumModel = ThemeHouse_SocialGroups_SocialForum::getSocialForumModel();
                $socialForum = $socialForumModel->getSocialForumById($socialForumId);
            }

            if (!empty($socialForum) && $socialForum['node_id'] == $node['node_id']) {
                ThemeHouse_SocialGroups_SocialForum::setup($socialForum);

                // TODO check permissions
            } else {
                $writer = XenForo_DataWriter::create('ThemeHouse_SocialGroups_DataWriter_SocialForum');
                $writer->bulkSet(
                    array(
                        'node_id' => $nodeId,
                        'user_id' => XenForo_Visitor::getUserId(),
                        'title' => $this->_getThreadTitle(),
                        'description' => $this->get('tag_line'),
                        'social_forum_type' => 'resource'
                    ));
                $writer->save();

                ThemeHouse_SocialGroups_SocialForum::setup($writer->getMergedData());

                $this->set('social_forum_id', $writer->get('social_forum_id'), '', array(
                    'setAfterPreSave' => true
                ));
            }
        }

        return parent::_insertDiscussionThread($nodeId, $prefixId);
    }

    /**
     *
     * @see XenResource_DataWriter_Resource::_resourceMadeVisible()
     */
    protected function _resourceMadeVisible(array &$postSaveChanges)
    {
        parent::_resourceMadeVisible($postSaveChanges);

        if (ThemeHouse_SocialGroups_SocialForum::hasInstance()) {
            if ($this->get('social_forum_id')) {
                $postSaveChanges['social_forum_id'] = $this->get('social_forum_id');
                unset($postSaveChanges['discussion_thread_id']);

                $this->set('discussion_thread_id', 0, '', array(
                    'setAfterPreSave' => true
                ));
            }
        }
    }
}