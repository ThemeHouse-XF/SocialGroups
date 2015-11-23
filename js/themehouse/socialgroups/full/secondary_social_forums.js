
/** @param {jQuery} $ jQuery Object */
!function($, window, document, _undefined)
{
	/**
	 * Initializes checkboxes for secondary social groups
	 *
	 * @param jQuery $('.SecondarySocialForums')
	 */
	XenForo.SecondarySocialForums = function($form) { this.__construct($form); };
	XenForo.SecondarySocialForums.prototype =
	{
		__construct: function($ul)
		{
			this.$ul = $ul;

			$('li input[type=checkbox]', this.$ul).click($.context(this, 'inputClick'));
		},

		/**
		 * Handles clicks on a checkbox
		 *
		 * @param event e
		 *
		 * @return boolean
		 */
		inputClick: function(e)
		{
			$selectCount = $("li input[type=checkbox]:checked", this.$ul).length;
			
			$maxSecondarySocialForums = this.$ul.data('maxsecondarysocialforums');
			
			if ($maxSecondarySocialForums > 0 && $selectCount > $maxSecondarySocialForums && $(e.target).attr('checked')) {
				e.preventDefault();
			}
		}
	};

	// *********************************************************************

	XenForo.register('.SecondarySocialForums', 'XenForo.SecondarySocialForums');
	
}
(jQuery, this, document);