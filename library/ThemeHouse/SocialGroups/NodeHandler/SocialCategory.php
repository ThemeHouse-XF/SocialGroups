<?php

class ThemeHouse_SocialGroups_NodeHandler_SocialCategory extends XenForo_NodeHandler_Forum
{

    /**
     * Renders the specified node for display in a node tree.
     *
     * @param XenForo_View $view View object doing the rendering
     * @param array $node Information about this node
     * @param array $permissions Permissions for this node
     * @param array $renderedChildren List of rendered children, [node id] =>
     * rendered output
     * @param integer $level The level this node should be rendered at, relative
     * to how it's to be displayed.
     *
     * @return string XenForo_Template_Abstract
     */
    public function renderNodeForTree(XenForo_View $view, array $node, array $permissions, array $renderedChildren,
        $level)
    {
        $templateLevel = ($level <= 2 ? $level : 'n');

        return $view->createTemplateObject('th_node_level_' . $templateLevel . '_socialgroups',
            array(
                'level' => $level,
                'forum' => $node,
                'renderedChildren' => $renderedChildren
            ));
    }
}