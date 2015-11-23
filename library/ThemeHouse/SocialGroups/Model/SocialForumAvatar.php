<?php

/**
 * Model for social forum avatars.
 */
class ThemeHouse_SocialGroups_Model_SocialForumAvatar_Base extends XenForo_Model_Avatar
{

    /**
     * Processes an avatar upload for a social forum.
     *
     * @param XenForo_Upload $upload The uploaded avatar.
     * @param integer $socialForumId Social forum ID avatar belongs to
     * @param array|false $permissions Social forum's permissions. False to skip
     * permission checks
     *
     * @return array Changed avatar fields
     */
    public function uploadAvatar(XenForo_Upload $upload, $socialForumId, $permissions)
    {
        if (!$socialForumId) {
            throw new XenForo_Exception('Missing social forum ID.');
        }

        return parent::uploadAvatar($upload, $socialForumId, $permissions);
    }

    /**
     * Applies the avatar file to the specified social forum.
     *
     * @param integer $socialForumId
     * @param string $fileName
     * @param constant|false $imageType Type of image (IMAGETYPE_GIF,
     * IMAGETYPE_JPEG, IMAGETYPE_PNG)
     * @param integer|false $width
     * @param integer|false $height
     * @param array|false $permissions
     *
     * @return array
     */
    public function applyAvatar($socialForumId, $fileName, $imageType = false, $width = false, $height = false, $permissions = false)
    {
        if (!$imageType || !$width || !$height) {
            $imageInfo = getimagesize($fileName);
            if (!$imageInfo) {
                throw new XenForo_Exception('Non-image passed in to applyAvatar');
            }
            $width = $imageInfo[0];
            $height = $imageInfo[1];
            $imageType = $imageInfo[2];
        }

        if (!in_array($imageType,
            array(
                IMAGETYPE_GIF,
                IMAGETYPE_JPEG,
                IMAGETYPE_PNG
            ))) {
            throw new XenForo_Exception('Invalid image type passed in to applyAvatar');
        }

        if (!XenForo_Image_Abstract::canResize($width, $height)) {
            throw new XenForo_Exception(new XenForo_Phrase('uploaded_image_is_too_big'), true);
        }

        // require 2:1 aspect ratio or squarer
        if ($width > 2 * $height || $height > 2 * $width) {
            throw new XenForo_Exception(
                new XenForo_Phrase('please_provide_an_image_whose_longer_side_is_no_more_than_twice_length'), true);
        }

        $outputFiles = array();
        $outputType = $imageType;

        reset(self::$_sizes);
        list ($sizeCode, $maxDimensions) = each(self::$_sizes);

        $shortSide = ($width > $height ? $height : $width);

        if ($shortSide > $maxDimensions) {
            $newTempFile = tempnam(XenForo_Helper_File::getTempDir(), 'xf');
            $image = XenForo_Image_Abstract::createFromFile($fileName, $imageType);
            if (!$image) {
                throw new XenForo_Exception(new XenForo_Phrase('image_could_be_processed_try_another_contact_owner'),
                    true);
            }
            $image->thumbnailFixedShorterSide($maxDimensions);
            $image->output($outputType, $newTempFile, self::$imageQuality);

            $width = $image->getWidth();
            $height = $image->getHeight();

            $outputFiles[$sizeCode] = $newTempFile;
        } else {
            $outputFiles[$sizeCode] = $fileName;
        }

        if (is_array($permissions)) {
            $maxFileSize = XenForo_Permission::hasContentPermission($permissions, 'maxSocialForumAvatarSize');
            if ($maxFileSize != -1 && filesize($outputFiles[$sizeCode]) > $maxFileSize) {
                foreach ($outputFiles as $tempFile) {
                    if ($tempFile != $fileName) {
                        @unlink($tempFile);
                    }
                }

                throw new XenForo_Exception(
                    new XenForo_Phrase('your_avatar_file_size_large_smaller_x',
                        array(
                            'size' => XenForo_Locale::numberFormat($maxFileSize, 'size')
                        )), true);
            }
        }

        $crop = array(
            'x' => array(
                'm' => 0
            ),
            'y' => array(
                'm' => 0
            )
        );

        while (list ($sizeCode, $maxDimensions) = each(self::$_sizes)) {
            $newTempFile = tempnam(XenForo_Helper_File::getTempDir(), 'xf');
            $image = XenForo_Image_Abstract::createFromFile($fileName, $imageType);
            if (!$image) {
                continue;
            }

            $image->thumbnailFixedShorterSide($maxDimensions);

            if ($image->getOrientation() != XenForo_Image_Abstract::ORIENTATION_SQUARE) {
                $crop['x'][$sizeCode] = floor(($image->getWidth() - $maxDimensions) / 2);
                $crop['y'][$sizeCode] = floor(($image->getHeight() - $maxDimensions) / 2);
                $image->crop($crop['x'][$sizeCode], $crop['y'][$sizeCode], $maxDimensions, $maxDimensions);
            }

            $image->output($outputType, $newTempFile, self::$imageQuality);
            unset($image);

            $outputFiles[$sizeCode] = $newTempFile;
        }

        if (count($outputFiles) != count(self::$_sizes)) {
            foreach ($outputFiles as $tempFile) {
                if ($tempFile != $fileName) {
                    @unlink($tempFile);
                }
            }
            throw new XenForo_Exception(new XenForo_Phrase('image_could_be_processed_try_another_contact_owner'), true);
        }

        // done in 2 loops as multiple items may point to same file
        foreach ($outputFiles as $sizeCode => $tempFile) {
            $this->_writeAvatar($socialForumId, $sizeCode, $tempFile);
        }
        foreach ($outputFiles as $tempFile) {
            if ($tempFile != $fileName) {
                @unlink($tempFile);
            }
        }

        $dwData = array(
            'logo_date' => XenForo_Application::$time,
            'logo_width' => $width,
            'logo_height' => $height,
            'logo_crop_x' => $crop['x']['m'],
            'logo_crop_y' => $crop['y']['m']
        );

        $dw = XenForo_DataWriter::create('ThemeHouse_SocialGroups_DataWriter_SocialForum');
        $dw->setExistingData($socialForumId);
        $dw->bulkSet($dwData);
        $dw->save();

        return $dwData;
    }

    /**
     * Re-crops an existing avatar with a square, starting at the specified
     * coordinates
     *
     * @param integer $socialForumId
     * @param integer $x
     * @param integer $y
     *
     * @return array Changed avatar fields
     */
    public function recropAvatar($socialForumId, $x, $y)
    {
        $sizeList = self::$_sizes;

        // get rid of the first entry in the sizes array
        list ($largeSizeCode, $largeMaxDimensions) = each($sizeList);

        $outputFiles = array();

        $avatarFile = $this->getAvatarFilePath($socialForumId, $largeSizeCode);
        $imageInfo = getimagesize($avatarFile);
        if (!$imageInfo) {
            throw new XenForo_Exception('Non-image passed in to recropAvatar');
        }
        $imageType = $imageInfo[2];

        // now loop through the rest
        while (list ($sizeCode, $maxDimensions) = each($sizeList)) {
            $image = XenForo_Image_Abstract::createFromFile($avatarFile, $imageType);
            $image->thumbnailFixedShorterSide($maxDimensions);

            if ($image->getOrientation() != XenForo_Image_Abstract::ORIENTATION_SQUARE) {
                $ratio = $maxDimensions / $sizeList['m'];

                $xCrop = floor($ratio * $x);
                $yCrop = floor($ratio * $y);

                if ($image->getWidth() > $maxDimensions && $image->getWidth() - $xCrop < $maxDimensions) {
                    $xCrop = $image->getWidth() - $maxDimensions;
                }
                if ($image->getHeight() > $maxDimensions && $image->getHeight() - $yCrop < $maxDimensions) {
                    $yCrop = $image->getHeight() - $maxDimensions;
                }

                $image->crop($xCrop, $yCrop, $maxDimensions, $maxDimensions);
            }

            $newTempFile = tempnam(XenForo_Helper_File::getTempDir(), 'xf');

            $image->output($imageType, $newTempFile, self::$imageQuality);
            unset($image);

            $outputFiles[$sizeCode] = $newTempFile;
        }

        foreach ($outputFiles as $sizeCode => $tempFile) {
            $this->_writeAvatar($socialForumId, $sizeCode, $tempFile);
        }
        foreach ($outputFiles as $tempFile) {
            @unlink($tempFile);
        }

        $dwData = array(
            'logo_date' => XenForo_Application::$time,
            'logo_crop_x' => $x,
            'logo_crop_y' => $y
        );

        $dw = XenForo_DataWriter::create('ThemeHouse_SocialGroups_DataWriter_SocialForum');
        $dw->setExistingData($socialForumId);
        $dw->bulkSet($dwData);
        $dw->save();

        return $dwData;
    }

    /**
     * Deletes a social forum's avatar.
     *
     * @param integer $socialForumId
     * @param boolean $updateSocialForum
     *
     * @return array Changed avatar fields
     */
    public function deleteAvatar($socialForumId, $updateSocialForum = true)
    {
        foreach (array_keys(self::$_sizes) as $size) {
            $filePath = $this->getAvatarFilePath($socialForumId, $size);
            if (file_exists($filePath) && is_writable($filePath)) {
                unlink($filePath);
            }
        }

        $dwData = array(
            'logo_date' => 0,
            'logo_width' => 0,
            'logo_height' => 0,
            'logo_crop_x' => 0,
            'logo_crop_y' => 0
        );

        if ($updateSocialForum) {
            $dw = XenForo_DataWriter::create('ThemeHouse_SocialGroups_DataWriter_SocialForum',
                XenForo_DataWriter::ERROR_SILENT);
            $dw->setExistingData($socialForumId);
            $dw->bulkSet($dwData);
            $dw->save();
        }

        return $dwData;
    }
}

if (XenForo_Application::$versionId >= 1020051) {

    class ThemeHouse_SocialGroups_Model_SocialForumAvatar extends ThemeHouse_SocialGroups_Model_SocialForumAvatar_Base
    {

        /**
         * Get the file path to a social forum avatar.
         *
         * @param integer $socialForumId
         * @param string $size Size code
         *
         * @return string
         */
        public function getAvatarFilePath($socialForumId, $size, $externalDataPath = null)
        {
            if ($externalDataPath === null) {
                $externalDataPath = XenForo_Helper_File::getExternalDataPath();
            }

            return sprintf('%s/social_forum_avatars/%s/%d/%d.jpg', $externalDataPath, $size,
                floor($socialForumId / 1000), $socialForumId);
        }
    }
} else {

    class ThemeHouse_SocialGroups_Model_SocialForumAvatar extends ThemeHouse_SocialGroups_Model_SocialForumAvatar_Base
    {

        /**
         * Get the file path to a social forum avatar.
         *
         * @param integer $socialForumId
         * @param string $size Size code
         *
         * @return string
         */
        public function getAvatarFilePath($socialForumId, $size)
        {
            return sprintf('%s/social_forum_avatars/%s/%d/%d.jpg', XenForo_Helper_File::getExternalDataPath(), $size,
                floor($socialForumId / 1000), $socialForumId);
        }
    }
}