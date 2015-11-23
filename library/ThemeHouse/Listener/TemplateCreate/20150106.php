<?php

abstract class ThemeHouse_Listener_TemplateCreate
{

    protected $_templateName = null;

    protected $_params = null;

    /**
     *
     * @var XenForo_Template_Abstract
     */
    protected $_template = null;

    /**
     *
     * @param string $templateName
     * @param array $params
     * @param XenForo_Template_Abstract $template
     */
    public function __construct(&$templateName, array &$params, XenForo_Template_Abstract $template)
    {
        $this->_templateName = $templateName;
        $this->_params = $params;
        $this->_template = $template;
    }

    /**
     * Called whenever the template object constructor is called.
     * You may use this event to modify the name of the template being called,
     * to modify the params being passed to the template, or to pre-load
     * additional templates as needed.
     *
     * @param string $templateName - the name of the template to be rendered
     * @param array $params - key-value pairs of parameters that are available
     * to the template
     * @param XenForo_Template_Abstract $template - the template object itself
     */
    public static function templateCreate(&$templateName, array &$params, XenForo_Template_Abstract $template)
    {
        // This only works on PHP 5.3+, so method should be overridden for now
        $class = get_called_class();
        $templateCreate = new $class($templateName, $params, $template);
        list($templateName, $params) = $templateCreate->run();
    }

    /**
     *
     * @see ThemeHouse_Listener_Template::run()
     */
    public function run()
    {
        $templates = $this->_getTemplates();
        foreach ($templates as $templateName) {
            if ($templateName == $this->_templateName) {
                $callback = $this->_getTemplateCallbackFromTemplateName($templateName);
                $this->_runTemplateCallback($callback);
            }
        }
        
        $templateCallbacks = $this->_getTemplateCallbacks();
        foreach ($templateCallbacks as $templateName => $callback) {
            if ($templateName == $this->_templateName) {
                $this->_runTemplateCallback($callback);
            }
        }
        
        return array(
            $this->_templateName,
            $this->_params
        );
    }

    /**
     *
     * @param string $templateName
     * @return $callback
     */
    protected function _getTemplateCallbackFromTemplateName($templateName)
    {
        return array(
            '$this',
            '_' . lcfirst(str_replace(" ", "", ucwords(str_replace("_", " ", $templateName))))
        );
    }

    /**
     *
     * @param callback Callback to run. Use an array with a string '$this' to
     * callback to this object.
     *
     * @return boolean
     */
    protected function _runTemplateCallback($callback)
    {
        if (is_array($callback) && isset($callback[0]) && $callback[0] == '$this') {
            $callback[0] = $this;
        }
        
        return (boolean) call_user_func_array($callback, 
            array(
                $this->_templateName,
                $this
            ));
    }

    /**
     *
     * @return array
     */
    protected function _getTemplateCallbacks()
    {
        return array();
    }

    /**
     *
     * @return array
     */
    protected function _getTemplates()
    {
        return array();
    }

    /**
     *
     * @param string $templateName
     */
    protected function _preloadTemplate($templateName)
    {
        $this->_template->preloadTemplate($templateName);
    }

    /**
     *
     * @param array $templateNames
     */
    protected function _preloadTemplates(array $templateNames)
    {
        foreach ($templateNames as $templateName) {
            $this->_preloadTemplate($templateName);
        }
    }

    /**
     * Use account_personal_details, account_privacy
     */
    protected final function _accountPrivacyDob()
    {
    }

    /**
     * Use post_permalink
     */
    protected final function _addthisAjax()
    {
    }

    /**
     * Use PAGE_CONTAINER
     */
    protected final function _adAboveContent()
    {
    }

    /**
     * Use PAGE_CONTAINER
     */
    protected final function _adAboveTopBreadcrumb()
    {
    }

    /**
     * Use PAGE_CONTAINER
     */
    protected final function _adBelowBottomBreadcrumb()
    {
    }

    /**
     * Use PAGE_CONTAINER
     */
    protected final function _adBelowContent()
    {
    }

    /**
     * Use PAGE_CONTAINER
     */
    protected final function _adBelowTopBreadcrumb()
    {
    }

    /**
     * Use forum_view
     */
    protected final function _adForumViewAboveNodeList()
    {
    }

    /**
     * Use forum_view
     */
    protected final function _adForumViewAboveThreadList()
    {
    }

    /**
     * Use header, logo_block, PAGE_CONTAINER
     */
    protected final function _adHeader()
    {
    }

    /**
     * Use member_view
     */
    protected final function _adMemberViewAboveMessages()
    {
    }

    /**
     * Use member_view
     */
    protected final function _adMemberViewBelowAvatar()
    {
    }

    /**
     * Use member_view
     */
    protected final function _adMemberViewSidebarBottom()
    {
    }

    /**
     * Use conversation_message, conversation_view,
     * conversation_view_new_messages, message, post, post_deleted,
     * post_moderated, thread_reply_new_posts, thread_view
     */
    protected final function _adMessageBelow()
    {
    }

    /**
     * Use conversation_message, conversation_view,
     * conversation_view_new_messages, message, post, post_deleted,
     * post_moderated, thread_reply_new_posts, thread_view
     */
    protected final function _adMessageBody()
    {
    }

    /**
     * Use PAGE_CONTAINER, sidebar_visitor_panel
     */
    protected final function _adSidebarBelowVisitorPanel()
    {
    }

    /**
     * Use PAGE_CONTAINER
     */
    protected final function _adSidebarBottom()
    {
    }

    /**
     * Use PAGE_CONTAINER
     */
    protected final function _adSidebarTop()
    {
    }

    /**
     * Use forum_view, thread_list
     */
    protected final function _adThreadListBelowStickies()
    {
    }

    /**
     * Use thread_view
     */
    protected final function _adThreadViewAboveMessages()
    {
    }

    /**
     * Use thread_view
     */
    protected final function _adThreadViewBelowMessages()
    {
    }

    /**
     * Use account_alerts_popup
     */
    protected final function _alert()
    {
    }

    /**
     * Use conversation_message, conversation_view,
     * conversation_view_new_messages, post, post_deleted, post_moderated,
     * thread_reply_new_posts, thread_view
     */
    protected final function _attachedFiles()
    {
    }

    /**
     * Use conversation_add, conversation_message_edit, conversation_reply,
     * conversation_view, post_edit, quick_reply, thread_create, thread_reply,
     * thread_view
     */
    protected final function _attachmentEditor()
    {
    }

    /**
     * Use attachment_editor, conversation_add, conversation_message_edit,
     * conversation_reply, conversation_view, post_edit, quick_reply,
     * thread_create, thread_reply, thread_view
     */
    protected final function _attachmentEditorAttachment()
    {
    }

    /**
     * Use attachment_editor, conversation_add, conversation_message_edit,
     * conversation_reply, conversation_view, post_edit, quick_reply,
     * thread_create, thread_reply, thread_view
     */
    protected final function _attachmentUploadButton()
    {
    }

    /**
     * Use PAGE_CONTAINER
     */
    protected final function _breadcrumb()
    {
    }

    /**
     * Use online_user_ip, post_ip, profile_post_ip
     */
    protected final function _contentIp()
    {
    }

    /**
     * Use conversation_list
     */
    protected final function _conversationListItem()
    {
    }

    /**
     * Use conversation_list_popup
     */
    protected final function _conversationListPopupItem()
    {
    }

    /**
     * Use conversation_view, conversation_view_new_messages
     */
    protected final function _conversationMessage()
    {
    }

    /**
     * Use conversation_view
     */
    protected final function _conversationRecipients()
    {
    }

    /**
     * Use account_contact_details, account_personal_details,
     * account_preferences, register_facebook, register_form
     */
    protected final function _customFieldsEdit()
    {
    }

    /**
     * Use account_contact_details, account_personal_details,
     * account_preferences, custom_fields_edit, register_facebook, register_form
     */
    protected final function _customFieldEdit()
    {
    }

    /**
     * Use member_view
     */
    protected final function _customFieldView()
    {
    }

    /**
     * Use editor
     */
    protected final function _editorJsSetup()
    {
    }

    /**
     * Use PAGE_CONTAINER
     */
    protected final function _footer()
    {
    }

    /**
     * Use PAGE_CONTAINER
     */
    protected final function _googleAnalytics()
    {
    }

    /**
     * Use PAGE_CONTAINER
     */
    protected final function _header()
    {
    }

    /**
     * Use account_personal_details, register_form
     */
    protected final function _helperBirthdayInput()
    {
    }

    /**
     * Use contact, lost_password, register_form, thread_create, thread_reply
     */
    protected final function _helperCaptchaUnit()
    {
    }

    /**
     * Use inline_mod_post_delete, inline_mod_profile_post_delete,
     * inline_mod_thread_delete, post_delete, profile_post_delete, thread_delete
     */
    protected final function _helperDeletionTypeUnit()
    {
    }

    /**
     * Use error_with_login, login
     */
    protected final function _helperLoginForm()
    {
    }

    /**
     * Use post_edit, thread_create, thread_reply
     */
    protected final function _helperThreadWatchInput()
    {
    }

    /**
     * Use help_bb_codes
     */
    protected final function _helpBbCodesExample()
    {
    }

    /**
     * Use find_new_threads, forum_view, inline_mod_controls_thread,
     * member_view, thread_list, thread_view
     */
    protected final function _inlineModControls()
    {
    }

    /**
     * Use find_new_threads, forum_view, thread_list
     */
    protected final function _inlineModControlsThread()
    {
    }

    /**
     * Use inline_mod_thread_merge, inline_mod_thread_move, thread_fields_move,
     * thread_move
     */
    protected final function _inlineModThreadHelperRedirect()
    {
    }

    /**
     * Use conversation_message, conversation_view,
     * conversation_view_new_messages, member_view, message, post,
     * post_deleted, post_moderated, profile_post, thread_reply_new_posts,
     * thread_view
     */
    protected final function _likesSummary()
    {
    }

    /**
     * Use PAGE_CONTAINER
     */
    protected final function _loginBar()
    {
    }

    /**
     * Use PAGE_CONTAINER
     */
    protected final function _loginBarForm()
    {
    }

    /**
     * Use header, PAGE_CONTAINER
     */
    protected final function _logoBlock()
    {
    }

    /**
     * Use account_following, account_ignored, member_followers,
     * member_following, member_list, member_list_item_follower,
     * member_list_item_ignored, online_list, post_likes, profile_post_likes
     */
    protected final function _memberListItem()
    {
    }

    /**
     * Use account_following
     */
    protected final function _memberListItemFollower()
    {
    }

    /**
     * Use account_ignored
     */
    protected final function _memberListItemIgnored()
    {
    }

    /**
     * Use conversation_message, conversation_view,
     * conversation_view_new_messages, post, post_deleted, post_moderated,
     * thread_reply_new_posts, thread_view
     */
    protected final function _message()
    {
    }

    /**
     * Use post_deleted_placeholder, thread_view
     */
    protected final function _messageDeletedPlaceholder()
    {
    }

    /**
     * Use member_view, profile_post
     */
    protected final function _messageSimple()
    {
    }

    /**
     * Use member_view, profile_post_deleted
     */
    protected final function _messageSimpleDeletedPlaceholder()
    {
    }

    /**
     * Use conversation_message, conversation_view,
     * conversation_view_new_messages, message, post, post_deleted,
     * post_moderated, quick_reply, thread_reply_new_posts, thread_view
     */
    protected final function _messageUserInfo()
    {
    }

    /**
     * Use PAGE_CONTAINER
     */
    protected final function _moderatorBar()
    {
    }

    /**
     * Use header, PAGE_CONTAINER
     */
    protected final function _navigation()
    {
    }

    /**
     * Use header, navigation, PAGE_CONTAINER
     */
    protected final function _navigationVisitorTab()
    {
    }

    /**
     * Use member_recent_activity, news_feed_page, news_feed_page_global
     */
    protected final function _newsFeed()
    {
    }

    /**
     * Use news_feed_item_post_insert, news_feed_item_post_like,
     * news_feed_item_thread_insert
     */
    protected final function _newsFeedAttachedImages()
    {
    }

    /**
     * Use member_recent_activity, news_feed_page, news_feed_page_global
     */
    protected final function _newsFeedEnd()
    {
    }

    /**
     * Use account_likes, member_recent_activity, news_feed, news_feed_page,
     * news_feed_page_global
     */
    protected final function _newsFeedItem()
    {
    }

    /**
     * Use node_forum_level_1
     */
    protected final function _nodeForumLevel2()
    {
    }

    /* END ThemeHouse_Listener_TemplateCreate::_nodeForumLevel2 */
    
    /**
     * Use node_link_level_1
     */
    protected final function _nodeLinkLevel2()
    {
    }

    /* END ThemeHouse_Listener_TemplateCreate::_nodeLinkLevel2 */
    
    /**
     * Use forum_list, forum_view
     */
    protected final function _nodeList()
    {
    }

    /**
     * Use node_page_level_1
     */
    protected final function _nodePageLevel2()
    {
    }

    /* END ThemeHouse_Listener_TemplateCreate::_nodePageLevel2 */
    
    /**
     * Use notices, PAGE_CONTAINER
     */
    protected final function _notice()
    {
    }

    /**
     * Use PAGE_CONTAINER
     */
    protected final function _notices()
    {
    }

    /**
     * Use forum_list, member_view, pagenode_container, thread_view
     */
    protected final function _openGraphMeta()
    {
    }

    /**
     * Use PAGE_CONTAINER
     */
    protected final function _pageContainerJsBody()
    {
    }

    /**
     * Use editor_dialog_code, editor_dialog_color_picker, editor_dialog_image,
     * editor_dialog_link, editor_dialog_media, PAGE_CONTAINER
     */
    protected final function _pageContainerJsHead()
    {
    }

    /**
     * Use thread_poll_results, thread_view
     */
    protected final function _pollBlock()
    {
    }

    /**
     * Use thread_poll_results, thread_view
     */
    protected final function _pollBlockResult()
    {
    }

    /**
     * Use thread_view
     */
    protected final function _pollBlockVote()
    {
    }

    /**
     * Use post_deleted, post_moderated, thread_reply_new_posts, thread_view
     */
    protected final function _post()
    {
    }

    /**
     * Use thread_view
     */
    protected final function _postDeletedPlaceholder()
    {
    }

    /**
     * Use conversation_message_edit_preview
     */
    protected final function _postEditPreview()
    {
    }

    /**
     * Use find_new_threads, forum_view, news_feed_page, news_feed_page_global,
     * online_list, thread_list, watch_threads, watch_threads_all
     */
    protected final function _previewTooltip()
    {
    }

    /**
     * Use member_view
     */
    protected final function _profilePost()
    {
    }

    /**
     * Use member_view, profile_post, profile_post_comments
     */
    protected final function _profilePostComment()
    {
    }

    /**
     * Use profile_post_comments
     */
    protected final function _profilePostCommentsBefore()
    {
    }

    /**
     * Use member_view
     */
    protected final function _profilePostDeleted()
    {
    }

    /**
     * Use conversation_view, thread_view
     */
    protected final function _quickReply()
    {
    }

    /**
     * Use report_list, report_list_closed
     */
    protected final function _reportListItem()
    {
    }

    /**
     * Use header, PAGE_CONTAINER
     */
    protected final function _searchBar()
    {
    }

    /**
     * Use forum_view, post_delete, post_edit, post_edit_inline, post_ip,
     * post_like, post_likes, post_report, thread_create, thread_delete,
     * thread_edit, thread_move, thread_poll_edit, thread_poll_results,
     * thread_poll_voters, thread_reply, thread_view, thread_watch
     */
    protected final function _searchBarForumOnly()
    {
    }

    /**
     * Use post_delete, post_edit, post_edit_inline, post_ip, post_like,
     * post_likes, post_report, thread_delete, thread_edit, thread_move,
     * thread_poll_edit, thread_poll_results, thread_poll_voters, thread_reply,
     * thread_view, thread_watch
     */
    protected final function _searchBarThreadOnly()
    {
    }

    /**
     * Use search_form, search_form_post, search_form_profile_post
     */
    protected final function _searchFormTabs()
    {
    }

    /**
     * Use pagenode_container, thread_view
     */
    protected final function _sharePage()
    {
    }

    /**
     * Use forum_list, news_feed_page_global
     */
    protected final function _sidebarOnlineUsers()
    {
    }

    /**
     * Use forum_list, member_view
     */
    protected final function _sidebarSharePage()
    {
    }

    /**
     * Use PAGE_CONTAINER
     */
    protected final function _sidebarVisitorPanel()
    {
    }

    /**
     * Use inline_mod_thread_move, thread_move
     */
    protected final function _threadFieldsMove()
    {
    }

    /**
     * Use thread_create, thread_edit, thread_reply
     */
    protected final function _threadFieldsStatus()
    {
    }

    /**
     * Use forum_view
     */
    protected final function _threadList()
    {
    }

    /**
     * Use find_new_threads, forum_view, thread_list, watch_threads,
     * watch_threads_all
     */
    protected final function _threadListItem()
    {
    }

    /**
     * Use find_new_threads, forum_view, thread_list, thread_list_item,
     * watch_threads, watch_threads_all
     */
    protected final function _threadListItemDeleted()
    {
    }

    /**
     * Use inline_mod_post_move, thread_create, thread_edit, thread_move
     */
    protected final function _titlePrefixEdit()
    {
    }

    /**
     * Use inline_mod_post_move, inline_mod_thread_move, thread_create,
     * thread_edit, thread_fields_move, thread_list_item_edit, thread_move,
     * title_prefix_edit
     */
    protected final function _titlePrefixEditOptions()
    {
    }

    /**
     * Use help_trophies, member_trophies
     */
    protected final function _trophy()
    {
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