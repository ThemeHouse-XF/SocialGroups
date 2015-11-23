<?php

class ThemeHouse_SocialGroups_Model_SocialForumCombination extends XenForo_Model
{

    public function rebuildAllSocialForumCombinations()
    {
        $socialForumCombinations = $this->_getDb()->fetchPairs(
            '
            SELECT social_forum_combination_id, secondary_social_forum_ids
            FROM xf_user_profile
            WHERE social_forum_combination_id != 0
            GROUP BY secondary_social_forum_ids
        ');

        $this->rebuildSocialForumCombinations($socialForumCombinations);
    }

    public function rebuildExistingSocialForumCombinationForSocialForumId($socialForumId)
    {
        $socialForumCombinations = $this->_getDb()->fetchPairs(
            '
            SELECT social_forum_combination_id, social_forum_ids
            FROM xf_social_forum_combination
            WHERE FIND_IN_SET(?, social_forum_ids)
            GROUP BY social_forum_ids
        ', $socialForumId);

        $this->rebuildSocialForumCombinations($socialForumCombinations);
    }

    public function rebuildSocialForumCombinations(array $socialForumCombinations)
    {
        $socialForumModel = ThemeHouse_SocialGroups_SocialForum::getSocialForumModel();

        $socialForums = $socialForumModel->getSocialForums();
        $socialForums = $this->_prepareSocialForumsForCombination($socialForums);

        foreach ($socialForumCombinations as $socialForumCombinationId => $socialForumIds) {
            $socialForumCombination = array(
                'social_forum_combination_id' => $socialForumCombinationId,
                'social_forum_ids' => $socialForumIds
            );
            $this->rebuildSocialForumCombination($socialForumCombination, $socialForums);
        }
    }

    public function rebuildSocialForumCombination(array $socialForumCombination, array $socialForums = null)
    {
        $socialForumIds = explode(',', $socialForumCombination['social_forum_ids']);

        if (is_null($socialForums)) {
            $socialForumModel = ThemeHouse_SocialGroups_SocialForum::getSocialForumModel();

            $socialForums = $socialForumModel->getSocialForumsByIds($socialForumIds);
            $socialForums = $this->_prepareSocialForumsForCombination($socialForums);
        } else {
            foreach ($socialForums AS $socialForumId => $socialForum) {
                if (!in_array($socialForumId, $socialForumIds)) {
                    unset($socialForums[$socialForumId]);
                }
            }
            if ($socialForums) {
                $socialForums = $this->_prepareSocialForumsForCombination($socialForums);
            }
        }

        $socialForumCombination['social_forum_ids'] = implode(',', array_keys($socialForums));

        $cacheValue = serialize($socialForums);

        $this->_getDb()->query(
            '
            INSERT INTO xf_social_forum_combination (social_forum_combination_id, social_forum_ids, cache_value)
            VALUES (?, ?, ?)
            ON DUPLICATE KEY UPDATE social_forum_ids = VALUES(social_forum_ids), cache_value = VALUES(cache_value)
            ',
            array(
                $socialForumCombination['social_forum_combination_id'],
                $socialForumCombination['social_forum_ids'],
                $cacheValue
            ));
    }

    public function getSocialForumCombinationBySocialForumIds($socialForumIds)
    {
        if (!$socialForumIds) {
            return 0;
        }

        if (!is_array($socialForumIds)) {
            $socialForumIds = explode(',', $socialForumIds);
        }
        sort($socialForumIds);

        $socialForumIds = implode(',', $socialForumIds);

        $socialForumCombinationId = $this->_getDb()->fetchOne(
            '
            SELECT social_forum_combination_id
            FROM xf_social_forum_combination
            WHERE social_forum_ids = ?
        ', $socialForumIds);

        if (!$socialForumCombinationId) {
            $this->_getDb()->query(
                '
                INSERT INTO xf_social_forum_combination (social_forum_ids)
                VALUES (?)
                ', $socialForumIds);
            $socialForumCombinationId = $this->_getDb()->lastInsertId();
        }

        return $socialForumCombinationId;
    }

    /**
     * Gets all social forum combinations that involve the specified social
     * forum.
     *
     * @param integer $socialForumId
     *
     * @return array Format: [social_forum_combination_id] => social forum
     * combination info
     */
    public function getSocialForumCombinationsBySocialForumId($socialForumId)
    {
        return $this->fetchAllKeyed(
            '
            SELECT social_forum_combination_id, social_forum_ids
            FROM xf_social_forum_combination
            WHERE FIND_IN_SET(?, social_forum_ids)
		', 'social_forum_combination_id', $socialForumId);
    }

    private function _prepareSocialForumForCombination(array $socialForum)
    {
        return XenForo_Application::arrayFilterKeys($socialForum,
            array(
                'social_forum_id',
                'title',
                'logo_date',
                'logo_width',
                'logo_height',
                'logo_crop_x',
                'logo_crop_y'
            ));
    }

    private function _prepareSocialForumsForCombination(array $socialForums)
    {
        foreach ($socialForums as &$socialForum) {
            $socialForum = $this->_prepareSocialForumForCombination($socialForum);
        }

        return $socialForums;
    }

    /**
     *
     * @param int $socialForumId
     * @return array
     */
    public function deleteSocialForumCombinationsForSocialForum($socialForumId)
    {
        $combinations = $this->getSocialForumCombinationsBySocialForumId($socialForumId);

        $db = $this->_getDb();
        XenForo_Db::beginTransaction($db);

        $socialForumCombinations = array();

        foreach ($combinations as $combinationId => $combination) {
            $this->deleteSocialForumCombination($combinationId);

            $socialForumIds = explode(',', $combination['social_forum_ids']);
            unset($socialForumIds[array_search($socialForumId, $socialForumIds)]);

            $newCombinationId = $this->getSocialForumCombinationBySocialForumIds($socialForumIds);

            $socialForumIds = implode(',', $socialForumIds);

            $db->update('xf_user_profile', array(
                'secondary_social_forum_ids' => $socialForumIds,
                'social_forum_combination_id' => $newCombinationId
            ), 'social_forum_combination_id = ' . $combinationId);

            $socialForumCombinations[$newCombinationId] = $socialForumIds;
        }

        XenForo_Db::commit($db);

        $this->rebuildSocialForumCombinations($socialForumCombinations);

        return array_keys($combinations);
    }

    /**
     * Deletes the specified social forum combination.
     *
     * @param integer $combinationId
     */
    public function deleteSocialForumCombination($combinationId)
    {
        $db = $this->_getDb();

        $combinationCondition = 'social_forum_combination_id = ' . $db->quote($combinationId);

        $db->delete('xf_social_forum_combination', $combinationCondition);
    }
}