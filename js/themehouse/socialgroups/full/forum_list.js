/** @param {jQuery} $ jQuery Object */
!function($, window, document, _undefined)
{
	/**
	 * Pops open the social forum list control panel
	 *
	 * @param jQuery .SocialForumListOptionsHandle a
	 */
	XenForo.SocialForumListOptions = function($handle) { this.__construct($handle); };
	XenForo.SocialForumListOptions.prototype =
	{
		__construct: function($handle)
		{
			this.$handle = $handle.click($.context(this, 'toggleOptions'));

			this.$options = $('form.SocialForumListOptions').hide();

			this.$submit = $('input:submit', this.$options).click($.context(this, 'hideOptions'));
			this.$reset = $('input:reset', this.$options).click($.context(this, 'hideOptions'));
		},

		/**
		 * Shows or hides the options panel
		 *
		 * @param event e
		 *
		 * @return boolean false
		 */
		toggleOptions: function(e)
		{
			if (this.$options.is(':animated'))
			{
				return false;
			}

			if (this.$options.is(':hidden'))
			{
				this.showOptions();
			}
			else
			{
				this.hideOptions();
			}

			return false;
		},

		/**
		 * Shows the options panel
		 */
		showOptions: function()
		{
			this.$options.xfFadeDown(XenForo.speed.normal, function()
			{
				$(this).find('input, select, textarea, button').get(0).focus();
			});
		},

		/**
		 * Hides the options panel
		 */
		hideOptions: function()
		{
			this.$options.xfFadeUp(XenForo.speed.normal);
		}
	};

	// *********************************************************************

	XenForo.register('#SocialForumListOptionsHandle a', 'XenForo.SocialForumListOptions');

}
(jQuery, this, document);