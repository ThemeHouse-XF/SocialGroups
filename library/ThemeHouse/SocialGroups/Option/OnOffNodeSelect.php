<?php

class ThemeHouse_SocialGroups_Option_OnOffNodeSelect
{

    /**
     * Renders checkboxes allowing the selection of nodes.
     *
     * @param XenForo_View $view View object
     * @param string $fieldPrefix Prefix for the HTML form field name
     * @param array $preparedOption Prepared option info
     * @param boolean $canEdit True if an "edit" link should appear
     *
     * @return XenForo_Template_Abstract Template object
     */
    public static function renderOption(XenForo_View $view, $fieldPrefix, array $preparedOption, $canEdit)
    {
        $preparedOption['formatParams'] = array();

        /* @var $nodeModel XenForo_Model_Node */
        $nodeModel = XenForo_Model::create('XenForo_Model_Node');

        foreach ($nodeModel->getAllNodes() as $nodeId => $node) {
            if ($node['node_type_id'] == "SocialCategory") {
                $preparedOption['formatParams'][$nodeId] = $node['title'];
            }
        }

        $preparedOption['formatParams'] = XenForo_ViewAdmin_Helper_Option::prepareMultiChoiceOptions($fieldPrefix,
            $preparedOption);

        return XenForo_ViewAdmin_Helper_Option::renderOptionTemplateInternal(
            'th_option_onoff_nodeselect_socialgroups', $view, $fieldPrefix, $preparedOption, $canEdit);
    }
}