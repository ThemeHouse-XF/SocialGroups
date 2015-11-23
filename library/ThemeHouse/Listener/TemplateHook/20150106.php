<?php

abstract class ThemeHouse_Listener_TemplateHook extends ThemeHouse_Listener_Template
{

	protected $_hookName = null;

	protected $_hookParams = null;

	protected $_viewParams = null;

	/**
	 *
	 * @param string $hookName
	 * @param string $contents.
	 * @param array $hookParams
	 * @param XenForo_Template_Abstract $template
	 */
	public function __construct($hookName, &$contents, array $hookParams, XenForo_Template_Abstract $template)
	{
		$this->_hookName = $hookName;
		$this->_hookParams = $hookParams;
		parent::__construct($contents, $template);
	}

	/**
	 * Called whenever a template hook is encountered (via <xen:hook> tags).
	 * You may use this event to modify the final output of that portion of the
	 * template.
	 *
	 * A template hook may pass a block of final template output with it; you
	 * may either adjust this text (such as with regular expressions) or add
	 * additional output before or after the contents. Some blocks will not
	 * pass contents with them; they are primarily designed to allow you to add
	 * additional components in those positions.
	 *
	 * @param string $hookName - the name of the template hook being called
	 * @param string $contents - the contents of the template hook block. This
	 * content will be the final rendered output of the block. You should
	 * manipulate this, such as by adding additional output at the end.
	 * @param array $hookParams - explicit key-value params that have been
	 * passed to the hook, enabling content-aware decisions. These will not
	 * be all the params that are available to the template.
	 * @param XenForo_Template_Abstract $template - the raw template object
	 * that has called this hook. You can access the template name and
	 * full, raw set of parameters via this object.
	 */
	public static function templateHook($hookName, &$contents, array $hookParams, XenForo_Template_Abstract $template)
	{
		// This only works on PHP 5.3+, so method should be overridden for now
		if (function_exists('get_called_class')) {
			$className = get_called_class();
		} else {
			$className = get_class();
		}

		$contents = self::createAndRun($className, $class, $extend, $type);
	}

	/**
	 *
	 * @see ThemeHouse_Listener_Template::run()
	 */
	public function run()
	{
		$hooks = $this->_getHooks();
		foreach ($hooks as $hookName) {
			if ($hookName == $this->_hookName) {
				$callback = $this->_getHookCallbackFromHookName($hookName);
				$this->_runHookCallback($callback);
			}
		}

		$hookCallbacks = $this->_getHookCallbacks();
		foreach ($hookCallbacks as $hookName => $callback) {
			if ($hookName == $this->_hookName) {
				$this->_runHookCallback($callback);
			}
		}

		return parent::run();
	}

	/**
	 *
	 * @param string $hookName
	 * @return $callback
	 */
	protected function _getHookCallbackFromHookName($hookName)
	{
		return array(
			'$this',
			'_' . lcfirst(str_replace(" ", "", ucwords(str_replace("_", " ", $hookName))))
		);
	}

	/**
	 *
	 * @param callback Callback to run. Use an array with a string '$this' to
	 * callback to this object.
	 *
	 * @return boolean
	 */
	protected function _runHookCallback($callback)
	{
		if (is_array($callback) && isset($callback[0]) && $callback[0] == '$this') {
			$callback[0] = $this;
		}

		return (boolean) call_user_func_array($callback,
			array(
				$this->_hookName,
				$this
			));
	}

	/**
	 *
	 * @return array
	 */
	protected function _getHookCallbacks()
	{
		return array();
	}

	/**
	 *
	 * @return array
	 */
	protected function _getHooks()
	{
		return array();
	}

	/**
	 *
	 * @see ThemeHouse_Listener_Template::_fetchViewParams()
	 */
	protected function _fetchViewParams()
	{
		if (!$this->_viewParams) {
			$this->_viewParams = array_merge($this->_template->getParams(), $this->_hookParams);
		}
		return $this->_viewParams;
	}

	/**
	 * Template name: account_alert_preferences
	 * Since version: 1.0.0
	 */
	protected function _accountAlertsMessagesInThreads()
	{
	}

	/**
	 * Template name: account_alert_preferences
	 * Since version: 1.0.0
	 */
	protected function _accountAlertsMessagesOnProfilePages()
	{
	}

	/**
	 * Template name: account_alert_preferences
	 * Since version: 1.0.0
	 */
	protected function _accountAlertsAchievements()
	{
	}

	/**
	 * Template name: account_alert_preferences
	 * Since version: 1.0.0
	 */
	protected function _accountAlertsExtra()
	{
	}

	/**
	 * Template name: account_contact_details
	 * Since version: 1.0.0
	 */
	protected function _accountContactDetailsEmailPassword()
	{
	}

	/**
	 * Template name: account_contact_details
	 * Since version: 1.0.0
	 */
	protected function _accountContactDetailsMessaging()
	{
	}

	/**
	 * Template name: account_contact_details
	 * Since version: 1.0.0
	 */
	protected function _accountContactDetailsIdentities()
	{
	}

	/**
	 * Template name: account_facebook
	 * Since version: 1.0.0
	 */
	protected function _accountFacebookAssociated()
	{
	}

	/**
	 * Template name: account_facebook
	 * Since version: 1.0.0
	 */
	protected function _accountFacebookNotAssociated()
	{
	}

	/**
	 * Template name: account_following
	 * Since version: 1.0.0
	 *
	 * Before 1.1.3, this hook was used instead of account_ignored_memberlist
	 * @see ThemeHouse_Listener_TemplateHook::_accountIgnoredMemberlist
	 */
	protected function _accountFollowingMemberlist()
	{
	}

	/**
	 * Template name: account_following
	 * Since version: 1.0.0
	 */
	protected function _accountFollowingControls()
	{
	}

	/**
	 * Template name: account_personal_details
	 * Since version: 1.0.0
	 */
	protected function _accountPersonalDetailsStatus()
	{
	}

	/**
	 * Template name: account_personal_details
	 * Since version: 1.0.0
	 */
	protected function _accountPersonalDetailsBiometrics()
	{
	}

	/**
	 * Template name: account_personal_details
	 * Since version: 1.0.0
	 */
	protected function _accountPersonalDetailsInformation()
	{
	}

	/**
	 * Template name: account_personal_details
	 * Since version: 1.0.0
	 */
	protected function _accountPersonalDetailsAbout()
	{
	}

	/**
	 * Template name: account_preferences
	 * Since version: 1.0.0
	 */
	protected function _accountPreferencesAppearance()
	{
	}

	/**
	 * Template name: account_preferences
	 * Since version: 1.0.0
	 */
	protected function _accountPreferencesLocale()
	{
	}

	/**
	 * Template name: account_preferences
	 * Since version: 1.0.0
	 */
	protected function _accountPreferencesOptions()
	{
	}

	/**
	 * Template name: account_privacy
	 * Since version: 1.0.0
	 */
	protected function _accountPrivacyTop()
	{
	}

	/**
	 * Template name: account_privacy
	 * Since version: 1.0.0
	 */
	protected function _accountPrivacyPersonalDetails()
	{
	}

	/**
	 * Template name: account_privacy
	 * Since version: 1.0.0
	 */
	protected function _accountPrivacyNewsFeed()
	{
	}

	/**
	 * Template name: account_privacy
	 * Since version: 1.0.0
	 */
	protected function _accountPrivacyContactDetails()
	{
	}

	/**
	 * Template name: account_privacy
	 * Since version: 1.0.0
	 */
	protected function _accountPrivacyBottom()
	{
	}

	/**
	 * Template name: account_wrapper
	 * Since version: 1.0.0
	 */
	protected function _accountWrapperSidebar()
	{
	}

	/**
	 * Template name: account_wrapper
	 * Since version: 1.0.0
	 */
	protected function _accountWrapperSidebarYourAccount()
	{
	}

	/**
	 * Template name: account_wrapper
	 * Since version: 1.0.0
	 */
	protected function _accountWrapperSidebarConversations()
	{
	}

	/**
	 * Template name: account_wrapper
	 * Since version: 1.0.0
	 */
	protected function _accountWrapperSidebarSettings()
	{
	}

	/**
	 * Template name: account_wrapper
	 * Since version: 1.0.0
	 */
	protected function _accountWrapperContent()
	{
	}

	/**
	 * Template name: ad_above_content
	 * Since version: 1.0.0
	 */
	protected function _adAboveContent()
	{
	}

	/**
	 * Template name: ad_above_top_breadcrumb
	 * Since version: 1.0.0
	 */
	protected function _adAboveTopBreadcrumb()
	{
	}

	/**
	 * Template name: ad_below_content
	 * Since version: 1.0.0
	 */
	protected function _adBelowContent()
	{
	}

	/**
	 * Template name: ad_below_top_breadcrumb
	 * Since version: 1.0.0
	 */
	protected function _adBelowTopBreadcrumb()
	{
	}

	/**
	 * Template name: ad_forum_view_above_node_list
	 * Since version: 1.0.0
	 */
	protected function _adForumViewAboveNodeList()
	{
	}

	/**
	 * Template name: ad_forum_view_above_thread_list
	 * Since version: 1.0.0
	 */
	protected function _adForumViewAboveThreadList()
	{
	}

	/**
	 * Template name: ad_header
	 * Since version: 1.0.0
	 */
	protected function _adHeader()
	{
	}

	/**
	 * Template name: ad_member_view_above_messages
	 * Since version: 1.0.0
	 */
	protected function _adMemberViewAboveMessages()
	{
	}

	/**
	 * Template name: ad_member_view_below_avatar
	 * Since version: 1.0.0
	 */
	protected function _adMemberViewBelowAvatar()
	{
	}

	/**
	 * Template name: ad_member_view_sidebar_bottom
	 * Since version: 1.0.0
	 */
	protected function _adMemberViewSidebarBottom()
	{
	}

	/**
	 * Template name: ad_message_below
	 * Since version: 1.0.0
	 */
	protected function _adMessageBelow()
	{
	}

	/**
	 * Template name: ad_message_body
	 * Since version: 1.0.0
	 */
	protected function _adMessageBody()
	{
	}

	/**
	 * Template name: ad_sidebar_below_visitor_panel
	 * Since version: 1.0.0
	 */
	protected function _adSidebarBelowVisitorPanel()
	{
	}

	/**
	 * Template name: ad_sidebar_bottom
	 * Since version: 1.0.0
	 */
	protected function _adSidebarBottom()
	{
	}

	/**
	 * Template name: ad_sidebar_top
	 * Since version: 1.0.0
	 */
	protected function _adSidebarTop()
	{
	}

	/**
	 * Template name: ad_thread_list_below_stickies
	 * Since version: 1.0.0
	 */
	protected function _adThreadListBelowStickies()
	{
	}

	/**
	 * Template name: ad_thread_view_above_messages
	 * Since version: 1.0.0
	 */
	protected function _adThreadViewAboveMessages()
	{
	}

	/**
	 * Template name: ad_thread_view_below_messages
	 * Since version: 1.0.0
	 */
	protected function _adThreadViewBelowMessages()
	{
	}

	/**
	 * Template name: editor
	 * Since version: 1.0.0
	 */
	protected function _editor()
	{
	}

	/**
	 * Template name: editor_js_setup
	 * Since version: 1.0.0
	 */
	protected function _editorJsSetup()
	{
	}

	/**
	 * Template name: editor_js_setup
	 * Since version: 1.0.0
	 */
	protected function _editorTinymceInit()
	{
	}

	/**
	 * Template name: footer
	 * Since version: 1.0.0
	 */
	protected function _footer()
	{
	}

	/**
	 * Template name: footer
	 * Since version: 1.0.0
	 */
	protected function _footerLinks()
	{
	}

	/**
	 * Template name: footer
	 * Since version: 1.0.0
	 */
	protected function _footerLinksLegal()
	{
	}

	/**
	 * Template name: forum_list
	 * Since version: 1.0.0
	 */
	protected function _forumListNodes()
	{
	}

	/**
	 * Template name: forum_list
	 * Since version: 1.0.0
	 */
	protected function _forumListSidebar()
	{
	}

	/**
	 * Template name: forum_view
	 * Since version: 1.0.0
	 */
	protected function _forumViewPagenavBefore()
	{
	}

	/**
	 * Template name: forum_view
	 * Since version: 1.0.0
	 */
	protected function _forumViewThreadsBefore()
	{
	}

	/**
	 * Template name: header
	 * Since version: 1.0.0
	 */
	protected function _header()
	{
	}

	/**
	 * Template name: logo_block
	 * Since version: 1.0.0
	 */
	protected function _headerLogo()
	{
	}

	/**
	 * Template name: member_card
	 * Since version: 1.0.0
	 */
	protected function _memberCardLinks()
	{
	}

	/**
	 * Template name: member_card
	 * Since version: 1.0.0
	 * @deprecated
	 *
	 *
	 */
	protected function _memberCardStats1()
	{
	}
 /* END ThemeHouse_Listener_TemplateHook::_memberCardStats1 */

	/**
	 * Template name: member_card
	 * Since version: 1.0.0
	 * @deprecated
	 *
	 *
	 */
	protected function _memberCardStats2()
	{
	}
 /* END ThemeHouse_Listener_TemplateHook::_memberCardStats2 */

	/**
	 * Template name: member_view
	 * Since version: 1.0.0
	 */
	protected function _memberViewSidebarStart()
	{
	}

	/**
	 * Template name: member_view
	 * Since version: 1.0.0
	 */
	protected function _memberViewSidebarMiddle1()
	{
	}
 /* END ThemeHouse_Listener_TemplateHook::_memberViewSidebarMiddle1 */

	/**
	 * Template name: member_view
	 * Since version: 1.0.0
	 */
	protected function _memberViewSidebarMiddle2()
	{
	}
 /* END ThemeHouse_Listener_TemplateHook::_memberViewSidebarMiddle2 */

	/**
	 * Template name: member_view
	 * Since version: 1.0.0
	 */
	protected function _memberViewSidebarEnd()
	{
	}

	/**
	 * Template name: member_view
	 * Since version: 1.0.0
	 */
	protected function _memberViewTabsHeading()
	{
	}

	/**
	 * Template name: member_view
	 * Since version: 1.0.0
	 */
	protected function _memberViewTabsContent()
	{
	}

	/**
	 * Template name: message
	 * Since version: 1.0.0
	 */
	protected function _messageNotices()
	{
	}

	/**
	 * Template name: message
	 * Since version: 1.0.0
	 */
	protected function _messageContent()
	{
	}

	/**
	 * Template name: message_user_info
	 * Since version: 1.0.0
	 */
	protected function _messageUserInfoAvatar()
	{
	}

	/**
	 * Template name: message_user_info
	 * Since version: 1.0.0
	 */
	protected function _messageUserInfoText()
	{
	}

	/**
	 * Template name: message_user_info
	 * Since version: 1.0.0
	 */
	protected function _messageUserInfoExtra()
	{
	}

	/**
	 * Template name: moderator_bar
	 * Since version: 1.0.0
	 */
	protected function _moderatorBar()
	{
	}

	/**
	 * Template name: navigation
	 * Since version: 1.0.0
	 */
	protected function _navigationTabsForums()
	{
	}

	/**
	 * Template name: navigation
	 * Since version: 1.0.0
	 */
	protected function _navigationTabsMembers()
	{
	}

	/**
	 * Template name: navigation
	 * Since version: 1.0.0
	 */
	protected function _navigationTabsHelp()
	{
	}

	/**
	 * Template name: navigation_visitor_tab
	 * Since version: 1.0.0
	 */
	protected function _navigationVisitorTabsStart()
	{
	}

	/**
	 * Template name: navigation_visitor_tab
	 * Since version: 1.0.0
	 */
	protected function _navigationVisitorTabLinks1()
	{
	}
 /* END ThemeHouse_Listener_TemplateHook::_navigationVisitorTabLinks1 */

	/**
	 * Template name: navigation_visitor_tab
	 * Since version: 1.0.0
	 */
	protected function _navigationVisitorTabLinks2()
	{
	}
 /* END ThemeHouse_Listener_TemplateHook::_navigationVisitorTabLinks2 */

	/**
	 * Template name: navigation_visitor_tab
	 * Since version: 1.0.0
	 */
	protected function _navigationTabsAccount()
	{
	}

	/**
	 * Template name: navigation_visitor_tab
	 * Since version: 1.0.0
	 */
	protected function _navigationVisitorTabsMiddle()
	{
	}

	/**
	 * Template name: navigation_visitor_tab
	 * Since version: 1.0.0
	 */
	protected function _navigationVisitorTabsEnd()
	{
	}

	/**
	 * Template name: PAGE_CONTAINER
	 * Since version: 1.0.0
	 */
	protected function _pageContainerHead()
	{
	}

	/**
	 * Template name: PAGE_CONTAINER
	 * Since version: 1.0.0
	 */
	protected function _body()
	{
	}

	/**
	 * Template name: PAGE_CONTAINER
	 * Since version: 1.0.0
	 */
	protected function _pageContainerContentTop()
	{
	}

	/**
	 * Template name: PAGE_CONTAINER
	 * Since version: 1.0.0
	 */
	protected function _pageContainerNotices()
	{
	}

	/**
	 * Template name: PAGE_CONTAINER
	 * Since version: 1.0.0
	 */
	protected function _pageContainerBreadcrumbTop()
	{
	}

	/**
	 * Template name: PAGE_CONTAINER
	 * Since version: 1.0.0
	 */
	protected function _pageContainerContentTitleBar()
	{
	}

	/**
	 * Template name: PAGE_CONTAINER
	 * Since version: 1.0.0
	 */
	protected function _pageContainerSidebar()
	{
	}

	/**
	 * Template name: PAGE_CONTAINER
	 * Since version: 1.0.0
	 */
	protected function _pageContainerBreadcrumbBottom()
	{
	}

	/**
	 * Template name: page_container_js_body
	 * Since version: 1.0.0
	 */
	protected function _pageContainerJsBody()
	{
	}

	/**
	 * Template name: search_bar
	 * Since version: 1.0.0
	 */
	protected function _quickSearch()
	{
	}

	/**
	 * Template name: search_form_tabs
	 * Since version: 1.0.0
	 */
	protected function _searchFormTabs()
	{
	}

	/**
	 * Template name: thread_create
	 * Since version: 1.0.0
	 */
	protected function _threadCreateFieldsMain()
	{
	}

	/**
	 * Template name: thread_create
	 * Since version: 1.0.0
	 */
	protected function _threadCreateFieldsExtra()
	{
	}

	/**
	 * Template name: thread_list
	 * Since version: 1.0.0
	 */
	protected function _threadListStickies()
	{
	}

	/**
	 * Template name: thread_list
	 * Since version: 1.0.0
	 */
	protected function _threadListThreads()
	{
	}

	/**
	 * Template name: thread_list
	 * Since version: 1.0.0
	 */
	protected function _threadListOptions()
	{
	}

	/**
	 * Template name: thread_view
	 * Since version: 1.0.0
	 */
	protected function _threadViewPagenavBefore()
	{
	}

	/**
	 * Template name: thread_view
	 * Since version: 1.0.0
	 */
	protected function _threadViewFormBefore()
	{
	}

	/**
	 * Template name: thread_view
	 * Since version: 1.0.0
	 */
	protected function _threadViewQrBefore()
	{
	}

	/**
	 * Template name: xenforo.css
	 * Since version: 1.0.0
	 */
	protected function _xenforoCssExtra()
	{
	}

	/**
	 * Template name: ad_below_bottom_breadcrumb
	 * Since version: 1.0.1
	 */
	protected function _adBelowBottomBreadcrumb()
	{
	}

	/**
	 * Template name: member_card
	 * Since version: 1.0.1
	 */
	protected function _memberCardStats()
	{
	}

	/**
	 * Template name: sidebar_visitor_panel
	 * Since version: 1.0.1
	 */
	protected function _sidebarVisitorPanelStats()
	{
	}

	/**
	 * Template name: account_alert_preferences
	 * Since version: 1.0.2
	 */
	protected function _accountAlertsAfterPosts()
	{
	}

	/**
	 * Template name: account_alert_preferences
	 * Since version: 1.0.2
	 */
	protected function _accountAlertsAfterProfilePosts()
	{
	}

	/**
	 * Template name: conversation_message
	 * Since version: 1.0.2
	 */
	protected function _conversationMessagePrivateControls()
	{
	}

	/**
	 * Template name: conversation_message
	 * Since version: 1.0.2
	 */
	protected function _conversationMessagePublicControls()
	{
	}

	/**
	 * Template name: member_recent_content
	 * Since version: 1.0.2
	 */
	protected function _memberRecentContentSearchContentTypes()
	{
	}

	/**
	 * Template name: member_view
	 * Since version: 1.0.2
	 */
	protected function _memberViewInfoBlock()
	{
	}

	/**
	 * Template name: member_view
	 * Since version: 1.0.2
	 */
	protected function _memberViewSearchContentTypes()
	{
	}

	/**
	 * Template name: pagenode_container
	 * Since version: 1.0.2
	 */
	protected function _pagenodeContainerArticle()
	{
	}

	/**
	 * Template name: post
	 * Since version: 1.0.2
	 */
	protected function _postPrivateControls()
	{
	}

	/**
	 * Template name: post
	 * Since version: 1.0.2
	 */
	protected function _postPublicControls()
	{
	}

	/**
	 * Template name: profile_post
	 * Since version: 1.0.2
	 */
	protected function _profilePostPrivateControls()
	{
	}

	/**
	 * Template name: profile_post
	 * Since version: 1.0.2
	 */
	protected function _profilePostPublicControls()
	{
	}

	/**
	 * Template name: thread_view
	 * Since version: 1.0.2
	 */
	protected function _threadViewQrAfter()
	{
	}

	/**
	 * Template name: help_wrapper
	 * Since version: 1.0.3
	 */
	protected function _helpSidebarLinks()
	{
	}

	/**
	 * Template name: share_page
	 * Since version: 1.0.3
	 */
	protected function _sharePageOptions()
	{
	}

	/**
	 * Template name: sidebar_share_page
	 * Since version: 1.0.3
	 */
	protected function _sidebarSharePageOptions()
	{
	}

	/**
	 * Template name: account_avatar
	 * Since version: 1.1.0
	 */
	protected function _accountAvatar()
	{
	}

	/**
	 * Template name: account_ignored
	 * Since version: 1.1.0
	 */
	protected function _accountIgnoredControls()
	{
	}

	/**
	 * Template name: help_bb_codes
	 * Since version: 1.1.0
	 */
	protected function _helpBbCodes()
	{
	}

	/**
	 * Template name: login_bar_form
	 * Since version: 1.1.0
	 */
	protected function _loginBarEauthSet()
	{
	}

	/**
	 * Template name: login_bar_form
	 * Since version: 1.1.0
	 */
	protected function _loginBarEauthItems()
	{
	}

	/**
	 * Template name: message_simple
	 * Since version: 1.1.0
	 */
	protected function _messageSimpleNotices()
	{
	}

	/**
	 * Template name: message_user_info
	 * Since version: 1.1.0
	 */
	protected function _messageUserInfoCustomFields()
	{
	}

	/**
	 * Template name: node_forum_level_2
	 * Since version: 1.1.0
	 *
	 * Note: some missing params were not added until 1.1.4
	 */
	protected function _nodeForumLevel2BeforeLastpost()
	{
	}
 /* END ThemeHouse_Listener_TemplateHook::_nodeForumLevel2BeforeLastpost */

	/**
	 * Template name: thread_create
	 * Since version: 1.1.0
	 */
	protected function _threadCreate()
	{
	}

	/**
	 * Template name: thread_list_item
	 * Since version: 1.1.0
	 */
	protected function _threadListItemIconKey()
	{
	}

	/**
	 * Template name: thread_reply
	 * Since version: 1.1.0
	 */
	protected function _threadReply()
	{
	}

	/**
	 * Template name: account_ignored
	 * Since version: 1.1.3
	 *
	 * This hook replaces account_following_memberlist
	 * @see ThemeHouse_Listener_TemplateHook::_accountFollowingMemberlist
	 */
	protected function _accountIgnoredMemberlist()
	{
	}

	/**
	 * Template name: footer
	 * Since version: 1.1.4
	 */
	protected function _footerAfterCopyright()
	{
	}

	/**
	 * Template name: help_index
	 * Since version: 1.1.4
	 */
	protected function _helpIndexExtra()
	{
	}

	/**
	 * Template name: message
	 * Since version: 1.1.4
	 */
	protected function _messageBelow()
	{
	}

	/**
	 * Factory method to get the named template listener.
	 * The class must exist or be autoloadable or an exception will be thrown.
	 *
	 * @param string $className Class to load
	 * @param string $contents
	 * @param XenForo_Template_Abstract $template
	 *
	 * @return ThemeHouse_Listener_TemplateHook
	 */
	public static function create($className, &$contents, XenForo_Template_Abstract $template = null)
	{
		$createClass = XenForo_Application::resolveDynamicClass($className, 'listener_th');
		if (!$createClass) {
			throw new XenForo_Exception("Invalid listener '$className' specified");
		}

		return new $createClass($contents, $template);
	}

	/**
	 *
	 * @param string $className Class to load
	 * @param string $contents
	 * @param XenForo_Template_Abstract $template
	 *
	 * @return array
	 */
	public static function createAndRun($className, &$contents, XenForo_Template_Abstract $template = null)
	{
		$createClass = self::create($className, $contents, $template);

		if (XenForo_Application::debugMode()) {
			return $createClass->run();
		}
		try {
			return $createClass->run();
		} catch (Exception $e) {
			return $this->_contents;
		}
	}
}

if (function_exists('lcfirst') === false) {

	/**
	 * Make a string's first character lowercase
	 *
	 * @param string $str
	 * @return string the resulting string.
	 */
	function lcfirst($str)
	{
		$str[0] = strtolower($str[0]);
		return (string) $str;
	}
}