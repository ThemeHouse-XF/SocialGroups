<?php

class ThemeHouse_SocialGroups_SocialForum implements ArrayAccess
{

    /**
     * Instance manager.
     *
     * @var ThemeHouse_SocialGroups_SocialForum
     */
    private static $_instance;

    /**
     * Array of social forum info.
     *
     * @var array
     */
    protected $_socialForum = array();

    /**
     * Array of social forum members.
     *
     * @var array
     */
    protected $_members = array();

    /**
     * Array of resource.
     *
     * @var array
     */
    protected $_resource = array();

    /**
     * Social forum model cache.
     *
     * @var ThemeHouse_SocialGroups_Model_SocialForum
     */
    protected static $_socialForumModel = null;

    /**
     * Protected constructor.
     * Use {@link getInstance()} instead.
     */
    protected function __construct()
    {
    }

    /**
     * Gets the browsing user's info.
     *
     * @return ThemeHouse_SocialGroups_SocialForum
     */
    public static final function getInstance()
    {
        return (self::$_instance ? self::$_instance : array());
    }

    /**
     * Determines if we have a visitor instance setup.
     *
     * @return boolean
     */
    public static function hasInstance()
    {
        return (self::$_instance ? true : false);
    }

    /**
     * Gets the social forum info in array format (for areas that require actual
     * arrays).
     *
     * @return array
     */
    public function toArray()
    {
        return $this->_socialForum;
    }

    /**
     * Setup the social forum singleton.
     *
     * @param integer $socialForum Social forum to setup as
     *
     * @return ThemeHouse_SocialGroups_SocialForum
     */
    public static function setup($socialForum)
    {
        $visitor = XenForo_Visitor::getInstance();
        $object = new self();

        $socialForumModel = self::getSocialForumModel();

        $fetchOptions = array(
            'readUserId' => $visitor['user_id'],
            'watchUserId' => $visitor['user_id'],
            'join' => ThemeHouse_SocialGroups_Model_SocialForum::FETCH_SOCIAL_MEMBER
        );

        if (is_numeric($socialForum) || is_string($socialForum)) {
            $socialForum = $socialForumModel->getCurrentSocialForumById($socialForum, $fetchOptions);
        }

        if ($socialForum) {
            $object->_socialForum = $socialForum;
        } else {
            self::$_instance = null;
            return null;
        }

        if (!isset($object->_socialForum['social_forum_title']) && isset($object->_socialForum['title'])) {
            $object->_socialForum['social_forum_title'] = $object->_socialForum['title'];
        }

        if (isset($object->_socialForum['social_forum_id'])) {
            $object->_members = XenForo_Model::create('ThemeHouse_SocialGroups_Model_SocialForumMember')->getSocialForumUsers(
                array(
                    'social_forum_id' => $object->_socialForum['social_forum_id']
                ));
        }
        self::$_instance = $object;

        if (isset($object->_socialForum['social_forum_id'])) {
            $nodePermissions = $socialForumModel->getNodePermissions($object->_socialForum,
                $object->getSocialForumMembers());
            self::$_instance['node_permissions'] = $nodePermissions;
            XenForo_Visitor::getInstance()->setNodePermissions($object->_socialForum['node_id'], $nodePermissions);
        }

        return self::$_instance;
    }

    public function getSocialForumMembers()
    {
        return $this->_members;
    }

    public function setResource(array $resource)
    {
        $this->_resource = $resource;
    }

    public function getResource()
    {
        return $this->_resource;
    }

    public function getMember()
    {
        $visitor = XenForo_Visitor::getInstance();

        $members = $this->getSocialForumMembers();

        if (array_key_exists($visitor['user_id'], $members)) {
            return $members[$visitor['user_id']];
        }

        return array(
            'is_social_forum_moderator' => false,
            'is_approved' => false,
            'is_social_forum_creator' => false,
            'is_invited' => false
        );
    }

    /**
     * For ArrayAccess.
     *
     * @param string $offset
     */
    public function offsetExists($offset)
    {
        return isset($this->_socialForum[$offset]);
    }

    /**
     * For ArrayAccess.
     *
     * @param string $offset
     */
    public function offsetGet($offset)
    {
        return $this->_socialForum[$offset];
    }

    /**
     * For ArrayAccess.
     *
     * @param string $offset
     * @param mixed $value
     */
    public function offsetSet($offset, $value)
    {
        $this->_socialForum[$offset] = $value;
    }

    /**
     * For ArrayAccess.
     *
     * @param string $offset
     */
    public function offsetUnset($offset)
    {
        unset($this->_socialForum[$offset]);
    }

    /**
     *
     * @return ThemeHouse_SocialGroups_Model_SocialForum
     */
    public static function getSocialForumModel()
    {
        if (!self::$_socialForumModel) {
            if (!class_exists('XFCP_ThemeHouse_SocialGroups_Model_SocialForum', false)) {
                $createClass = XenForo_Application::resolveDynamicClass('XenForo_Model_Forum', 'model');
                eval('class XFCP_ThemeHouse_SocialGroups_Model_SocialForum extends ' . $createClass . ' {}');
            }
            self::$_socialForumModel = XenForo_Model::create('ThemeHouse_SocialGroups_Model_SocialForum');
        }

        return self::$_socialForumModel;
    }
}