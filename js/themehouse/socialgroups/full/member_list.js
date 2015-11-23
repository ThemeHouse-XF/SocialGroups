
/** @param {jQuery} $ jQuery Object */
!function($, window, document, _undefined)
{
	/**
	 * Initializes various controls for social forum member listings
	 *
	 * @param jQuery $('form.SocialMemberList')
	 */
	XenForo.SocialMemberList = function($form) { this.__construct($form); };
	XenForo.SocialMemberList.prototype =
	{
		__construct: function($form)
		{
			this.$form = $form;

			$('a.EditControl', this.$form).live('click', $.context(this, 'editControlClick'));

			this.$editor = null;
			this.loaderXhr = null;
		},

		/**
		 * Handles clicks on the 'Edit' control
		 *
		 * @param event e
		 *
		 * @return boolean
		 */
		editControlClick: function(e)
		{
			if (this.loaderXhr)
			{
				return false;
			}

			var $editControl = $(e.target),
				$memberListItem = $editControl.closest('.memberListItem');

			if (this.$editor)
			{
				if (this.$editor.is(':animated'))
				{
					return false;
				}

				this.$editor.xfRemove('xfSlideUp');
			}

			$memberListItem.addClass('AjaxProgress');

			var href = $editControl.data('href');
			if (!href || href.match(/^javascript:/))
			{
				href = $editControl.attr('href');
			}

			this.loaderXhr = XenForo.ajax(
				href,
				'',
				$.context(this, 'editorLoaded')
			);

			return false;
		},

		/**
		 * Runs when the ajax editor loader returns its data, initializes the new editor
		 *
		 * @param object ajaxData
		 * @param string textStatus
		 */
		editorLoaded: function(ajaxData, textStatus)
		{
			this.loaderXhr = null;

			var $memberListItem = $('#member-' + ajaxData.memberId + '.memberListItem');

			if (XenForo.hasResponseError(ajaxData))
			{
				$memberListItem.removeClass('AjaxProgress');
				return false;
			}

			new XenForo.ExtLoader(ajaxData, $.context(function()
			{
				this.$editor = $(ajaxData.templateHtml)
					.data('memberListItemId', $memberListItem.attr('id'))
					.xfInsert('insertAfter', $memberListItem, 'xfSlideDown', XenForo.speed.fast, $.context(function()
					{
						$memberListItem.removeClass('AjaxProgress');

						$(document).trigger('TitlePrefixRecalc');

					}, this));
			}, this));
		}
	};

	// *********************************************************************

	/**
	 * Handler for the inline thread editor on thread lists
	 *
	 * @param jQuery .memberListItemEdit
	 */
	XenForo.MemberListItemEditor = function($editor) { this.__construct($editor); };
	XenForo.MemberListItemEditor.prototype =
	{
		__construct: function($editor)
		{
			this.$editor = $editor;

			this.$saveButton = $('input:submit', this.$editor).click($.context(this, 'save'));

			this.$cancelButton = $('input:reset', this.$editor).click($.context(this, 'cancel'));
		},

		/**
		 * Saves the changes made to the inline editor
		 *
		 * @param event e
		 *
		 * @return boolean
		 */
		save: function(e)
		{
			if (!this.saverXhr)
			{
				var ajaxData = this.$editor.closest('form').serializeArray();
					ajaxData = XenForo.ajaxDataPush(ajaxData, '_returnMemberListItem', 1);

				this.$editor.addClass('InProgress');

				this.saverXhr = XenForo.ajax(
					this.$saveButton.data('submiturl') ? this.$saveButton.data('submiturl') : this.$saveButton.data('submitUrl'),
					ajaxData,
					$.context(this, 'saveSuccess')
				);
			}

			return false;
		},

		/**
		 * Cancels an edit, removes the editor
		 *
		 * @param event e
		 *
		 * @return boolean false
		 */
		cancel: function(e)
		{
			this.removeEditor();

			return false;
		},

		/**
		 * Handles the save method's returned ajax data
		 *
		 * @param object ajaxData
		 * @param string textStatus
		 */
		saveSuccess: function(ajaxData, textStatus)
		{
			this.saverXhr = null;
			this.$editor.removeClass('InProgress');

			if (XenForo.hasResponseError(ajaxData))
			{
				return false;
			}

			this.removeEditor();

			var $oldMemberListItem = $('#member-' + ajaxData.memberId);

			$oldMemberListItem.fadeOut(XenForo.speed.normal, function()
			{
				if ($(ajaxData.templateHtml)) {
					$(ajaxData.templateHtml).xfInsert('insertBefore', $oldMemberListItem, 'xfFadeIn', XenForo.speed.normal);
				}
				$oldMemberListItem.remove();
			});
		},

		/**
		 * Removes the editor from the DOM
		 */
		removeEditor: function()
		{
			// TODO: why doesn't this use xfRemove() ?
			this.$editor.parent().xfSlideUp(
			{
				duration: XenForo.speed.slow,
				easing: 'easeOutBounce',
				complete: function()
				{
					$(this).remove();
				}
			});

			this.$editor = null;
		}
	};

	// *********************************************************************

	XenForo.register('form.SocialMemberList', 'XenForo.SocialMemberList');

	XenForo.register('.memberListItemEdit', 'XenForo.MemberListItemEditor');
	
}
(jQuery, this, document);