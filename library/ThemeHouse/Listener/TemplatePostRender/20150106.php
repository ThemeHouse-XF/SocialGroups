<?php

abstract class ThemeHouse_Listener_TemplatePostRender extends ThemeHouse_Listener_Template
{

	protected $_templateName = null;

	protected $_containerData = null;

	public function __construct($templateName, &$content, array &$containerData, XenForo_Template_Abstract $template)
	{
		$this->_templateName = $templateName;
		$this->_containerData = $containerData;
		parent::__construct($content, $template);
	}

	// This only works on PHP 5.3+, so method should be overridden for now
	public static function templatePostRender($templateName, &$content, array &$containerData,
		XenForo_Template_Abstract $template)
	{
		$class = get_called_class();
		$templatePostRender = new $class($templateName, $content, $containerData, $template);
		list($content, $containerData) = $templatePostRender->run();
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
			$this->_contents,
			$this->_containerData
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

	protected function _appendTemplateToContainerData($templateName)
	{
		$template = $this->_render($templateName);

		if (!isset($this->_containerData['topctrl'])) {
			$this->_containerData['topctrl'] = $template;
		} else {
			$this->_containerData['topctrl'] .= $template;
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