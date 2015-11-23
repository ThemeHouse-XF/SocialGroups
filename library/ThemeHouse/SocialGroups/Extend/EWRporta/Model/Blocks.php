<?php

/**
 *
 * @see EWRporta_Model_Blocks
 */
class ThemeHouse_SocialGroups_Extend_EWRporta_Model_Blocks extends XFCP_ThemeHouse_SocialGroups_Extend_EWRporta_Model_Blocks
{

    /**
     *
     * @see EWRporta_Model_Blocks::getBlockParams
     */
    public function getBlockParams($block, $page = 1, $params = array())
    {
        switch ($block['block_id']) {
            case 'ThemeHouse_NewSocialForums':
                $model = new ThemeHouse_SocialGroups_Blocks_NewSocialForums();
                break;
        }

        if (isset($model)) {
            $params[$block['block_id']] = $model->getModule($block['options'], $page);
        }

        return parent::getBlockParams($block, $page, $params);
    }
}