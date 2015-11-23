<?php

/**
 *
 * @see XenForo_Route_Prefix_Forums
 */
class ThemeHouse_SocialGroups_Extend_XenForo_Route_Prefix_Forums extends XFCP_ThemeHouse_SocialGroups_Extend_XenForo_Route_Prefix_Forums
{

    /**
     *
     * @see XenForo_Route_Prefix_Forums::buildLink()
     */
    public function buildLink($originalPrefix, $outputPrefix, $action, $extension, $data, array &$extraParams)
    {
        if (isset($data['social_forum_id'])) {
            if (ThemeHouse_SocialGroups_SocialForum::hasInstance()) {
                $socialForum = ThemeHouse_SocialGroups_SocialForum::getInstance()->toArray();
            } else {
                $socialForum = $data;
            }
            $class = XenForo_Application::resolveDynamicClass('ThemeHouse_SocialGroups_Route_Prefix_SocialForums',
                'route_prefix');
            $router = new $class();
            $link = $router->buildLink('social-forums', 'social-forums', $action, $extension, $socialForum,
                $extraParams);

            if (XenForo_Application::isRegistered('routeFiltersOut')) {
                $routeFilters = XenForo_Application::get('routeFiltersOut');
                if (isset($routeFilters['social-forums'])) {
                    foreach ($routeFilters['social-forums'] as $filter) {
                        list ($from, $to) = XenForo_Link::translateRouteFilterToRegex($filter['find_route'],
                            $filter['replace_route']);

                        $newLink = preg_replace($from, $to, $link);
                        if ($newLink != $link) {
                            $link = $newLink;
                            break;
                        }
                    }
                }
            }

            return $link;
        }
        return parent::buildLink($originalPrefix, $outputPrefix, $action, $extension, $data, $extraParams);
    }
}