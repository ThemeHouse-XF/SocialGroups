<?php

/**
 * Cache rebuilder for social forums.
 */
class ThemeHouse_SocialGroups_CacheRebuilder_SocialForum extends XenForo_CacheRebuilder_Abstract
{

    /**
     * Gets rebuild message.
     */
    public function getRebuildMessage()
    {
        return new XenForo_Phrase('th_social_forums_socialgroups');
    }

    /**
     * Shows the exit link.
     */
    public function showExitLink()
    {
        return true;
    }

    /**
     * Rebuilds the data.
     *
     * @see XenForo_CacheRebuilder_Abstract::rebuild()
     */
    public function rebuild($position = 0, array &$options = array(), &$detailedMessage = '')
    {
        $options['batch'] = max(1, isset($options['batch']) ? $options['batch'] : 10);

        $socialForumModel = ThemeHouse_SocialGroups_SocialForum::getSocialForumModel();

        if ($position == 0) {
            $socialForumModel->unlinkMovedThreads();
        }

        $socialForums = $socialForumModel->getSocialForums(array(),
            array(
                'limit' => $options['batch'],
                'offset' => $position
            ));

        XenForo_Db::beginTransaction();

        foreach ($socialForums as $socialForum) {
            $position++;

            /* @var $socialForumDw ThemeHouse_SocialGroups_DataWriter_SocialForum */
            $socialForumDw = XenForo_DataWriter::create('ThemeHouse_SocialGroups_DataWriter_SocialForum',
                XenForo_DataWriter::ERROR_SILENT);
            if ($socialForumDw->setExistingData($socialForum, true)) {
                $socialForumDw->rebuildCounters();
                $socialForumDw->save();
            }
        }

        XenForo_Db::commit();

        $detailedMessage = XenForo_Locale::numberFormat($position);

        if (!$socialForums) {
            return true;
        } else {
            return $position;
        }
    }
}