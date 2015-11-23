<?php

abstract class ThemeHouse_Listener_Template
{
	public static $contentsReplaceArray = array();

	/**
	 * Standard approach to caching other model objects for the lifetime of the
	 * model.
	 *
	 * @var array
	 */
	protected $_modelCache = array();

	protected $_contents = null;

	/**
	 *
	 * @var XenForo_Template_Abstract
	 */
	protected $_template = null;

	/**
	 *
	 * @param string $contents
	 * @param XenForo_Template_Abstract $template
	 */
	public function __construct(&$contents, XenForo_Template_Abstract $template = null)
	{
		$this->_contents = $contents;
		if ($template) {
			$this->_template = $template;
		}
	}

	/**
	 *
	 * @return string
	 */
	public function run()
	{
		$this->_run();

		return $this->_contents;
	}

	/**
	 * Method designed to be overridden by child classes to add run behaviours.
	 */
	protected function _run()
	{
		// TODO: remove returned value as no longer required
		return $this->_contents;
	}

	/**
	 * Gets the specified model object from the cache.
	 * If it does not exist,
	 * it will be instantiated.
	 *
	 * @param string $class Name of the class to load
	 *
	 * @return XenForo_Model
	 */
	public function getModelFromCache($class)
	{
		if (!isset($this->_modelCache[$class])) {
			$this->_modelCache[$class] = XenForo_Model::create($class);
		}

		return $this->_modelCache[$class];
	}

	/**
	 *
	 * @param string $rendered
	 * @param string|null $contents
	 * @param boolean $after
	 */
	protected function _append($rendered, &$contents = null, $after = true)
	{
		$this->_appendAtCodeSnippet(null, $rendered, $contents, $after);
	}

	/**
	 *
	 * @param string $templateName
	 * @param array|null $viewParams
	 * @param string|null $contents
	 * @param boolean $after
	 */
	protected function _appendTemplate($templateName, array $viewParams = null, &$contents = null, $after = true)
	{
		$rendered = $this->_render($templateName, $viewParams);
		$this->_append($rendered, $contents, $after);
	}

	/**
	 *
	 * @param string $slot
	 * @param string $rendered
	 * @param string|null $contents
	 * @param boolean $after
	 */
	protected function _appendAtSlot($slot, $rendered, &$contents = null, $after = true)
	{
		$codeSnippet = "<!-- slot: $slot -->";
		$this->_appendAtCodeSnippet($codeSnippet, $rendered, $contents, $after);
	}

	/**
	 *
	 * @param string $contents
	 * @return string contents
	 */
	protected function _utf8Decode($contents = null)
	{
		if (!$contents)
			$contents = $this->_contents;
		$contents = utf8_encode($contents);
		$contents = preg_replace_callback("/[^\x01-\x7F]+/",
			create_function('$matches',
				'
			$key = array_search($matches[0], ThemeHouse_Listener_Template::$contentsReplaceArray);
			if ($key === false) {
				ThemeHouse_Listener_Template::$contentsReplaceArray[] = $matches[0];
				$key = count(ThemeHouse_Listener_Template::$contentsReplaceArray) - 1;
			}

			return "TH_".$key."_REPLACE";'), $contents);
		return $contents;
	}

	/**
	 *
	 * @param string $contents
	 * @return string contents
	 */
	protected function _utf8Encode($contents = null)
	{
		if (!$contents)
			$contents = $this->_contents;
		$contents = preg_replace_callback("/TH_([0-9]+)_REPLACE/",
			create_function('$matches',
				'
			if (isset(ThemeHouse_Listener_Template::$contentsReplaceArray[$matches[1]])) {
				return ThemeHouse_Listener_Template::$contentsReplaceArray[$matches[1]];
			}
			return "";'), $contents);

		$contents = preg_replace(
			'#<!DOCTYPE html PUBLIC "-//W3C//DTD HTML 4.0 Transitional//EN" "http://www.w3.org/TR/REC-html40/loose.dtd">\s*<html>\s*<body>(.*)</body>\s*</html>#s',
			'$1', $contents);
		$contents = $this->_indentContent(utf8_decode($contents));
		return $contents;
	}

	/**
	 *
	 * @param string $content
	 * @param string $tab
	 * @return string
	 */
	protected function _indentContent($content, $tab = "\t")
	{
		// add marker linefeeds to aid the pretty-tokeniser (adds a linefeed
		// between all tag-end boundaries)
		$content = preg_replace('/(>)(<)(\/*)/', "$1\n$2$3", $content);

		// now indent the tags
		$token = strtok($content, "\n");
		$result = ''; // holds formatted version as it is built
		$pad = 0; // initial indent
		$matches = array(); // returns from preg_matches()
		$textarea = 0; // no indenting inside textarea

		// scan each line and adjust indent based on opening/closing tags
		while ($token !== false) {
			$token = trim($token);
			// test for the various tag states

			// 1. open and closing tags on same line - no change
			if (preg_match('/.+<\/\w[^>]*>.*$/', $token, $matches)) {
				$indent = 0;
			} else
				if (preg_match('/^<\/(\w+)(.*)>/', $token, $matches)) {
					// 2. closing tag - outdent now
					if ($matches[1] == "textarea" || $matches[1] == "pre") {
						$textarea = -1;
					}
					$pad--;
					if ($indent > 0) {
						$indent = 0;
					}
				} else
					if (!$textarea && preg_match('/^<(\w+)(.*)>.*$/', $token, $matches)) {
						// 3. opening tag - don't pad this one, only subsequent
						// tags
						if ($matches[1] == "textarea" || $matches[1] == "pre") {
							$textarea = 1;
						}
						if ($matches[1] == "link" || $matches[1] == "meta" || $matches[1] == "img") {
							$indent = 0;
						} else {
							$indent = 1;
						}
					} else {
						// 4. no indentation needed
						$indent = 0;
					}

			if (!$textarea) {
				// pad the line with the required number of leading spaces
				$line = str_pad($token, strlen($token) + $pad, $tab, STR_PAD_LEFT);
				$result .= $line . "\n"; // add to the cumulative result, with
											 // linefeed
			} else {
				$line = $token;
				$result .= $line . "\n";
				$textarea = 0;
			}
			$token = strtok("\n"); // get the next token
			$pad += $indent; // update the pad size for subsequent lines
		}

		return $result;
	}

	/**
	 *
	 * @return boolean true if replaced, false otherwise
	 * @param string $query
	 * @param string $rendered
	 * @param string|null $contents
	 * @param Zend_Dom_Query $dom
	 * @param boolean $after
	 */
	protected function _appendAtDomQuery($query, $rendered, &$contents = null, Zend_Dom_Query &$dom = null, $after = true,
		$outside = true)
	{
		if (!$contents) {
			$contents = & $this->_contents;
		}
		if (!$contents) {
			return false;
		}
		if ($dom === null) {
			$dom = new Zend_Dom_Query($this->_utf8Decode($contents));
		}
		if (is_array($query)) {
			$result = $dom->query($query[0]);
		} else {
			$result = $dom->query($query);
		}
		if ($rendered && $result->count()) {
			$appendDom = new Zend_Dom_Query($this->_utf8Decode($rendered));
			if (is_array($query)) {
				$appendQuery = $appendDom->query($query[1]);
			} else {
				$appendQuery = $appendDom->query($query);
			}
			if ($appendQuery->count()) {
				$childNodes = $appendQuery->current()->childNodes;
				$firstChild = $result->current()->firstChild;
				foreach ($childNodes as $childNode) {
					if ($after) {
						$result->current()->appendChild(
							$result->getDocument()
								->importNode($childNode, true));
					} else {
						if ($outside) {
							$result->current()->parentNode->insertBefore(
								$result->getDocument()
									->importNode($childNode, true), $result->current());
						} else {
							$result->current()->insertBefore(
								$result->getDocument()
									->importNode($childNode, true), $result->current()->firstChild);
						}
					}
				}
				$contents = $this->_utf8Encode($result->getDocument()
					->saveHTML());
				return true;
			}
		}
		return false;
	}

	/**
	 *
	 * @return boolean true if replaced, false otherwise
	 * @param string $query
	 * @param string $rendered
	 * @param string|null $contents
	 * @param Zend_Dom_Query $dom
	 */
	protected function _appendAfterDomQuery($query, $rendered, &$contents = null, Zend_Dom_Query &$dom = null, $outside = true)
	{
		return $this->_appendAtDomQuery($query, $rendered, $contents, $dom, true, true, $outside);
	}

	/**
	 *
	 * @return boolean true if replaced, false otherwise
	 * @param string $query
	 * @param string $rendered
	 * @param string|null $contents
	 * @param Zend_Dom_Query $dom
	 */
	protected function _appendBeforeDomQuery($query, $rendered, &$contents = null, Zend_Dom_Query &$dom = null, $outside = true)
	{
		return $this->_appendAtDomQuery($query, $rendered, $contents, $dom, false, $outside);
	}

	/**
	 *
	 * @param string $slot
	 * @param string $templateName
	 * @param array|null $viewParams
	 * @param string|null $contents
	 * @param boolean $after
	 */
	protected function _appendTemplateAtSlot($slot, $templateName, array $viewParams = null, &$contents = null, $after = true)
	{
		$codeSnippet = "<!-- slot: $slot -->";
		$this->_appendTemplateAtCodeSnippet($codeSnippet, $templateName, $viewParams, $contents, $after);
	}

	/**
	 *
	 * @param string $slot
	 * @param string $templateName
	 * @param array|null $viewParams
	 * @param string|null $contents
	 */
	protected function _appendTemplateBeforeSlot($slot, $templateName, array $viewParams = null, &$contents = null)
	{
		$this->_appendTemplateAtSlot($slot, $templateName, $viewParams, $contents, false);
	}

	/**
	 *
	 * @param string $slot
	 * @param string $templateName
	 * @param array|null $viewParams
	 * @param string|null $contents
	 */
	protected function _appendTemplateAfterSlot($slot, $templateName, array $viewParams = null, &$contents = null)
	{
		$this->_appendTemplateAtSlot($slot, $templateName, $viewParams, $contents, true);
	}

	/**
	 *
	 * @param string $block
	 * @param string $templateName
	 * @param array|null $viewParams
	 * @param string|null $contents
	 * @param boolean $after
	 */
	protected function _prependTemplateBlock($block, $templateName, array $viewParams = null, &$contents = null, $after = true)
	{
		$codeSnippet = "<!-- block: $block -->";
		$this->_appendTemplateAtCodeSnippet($codeSnippet, $templateName, $viewParams, $contents, $after);
	}

	/**
	 *
	 * @param string $block
	 * @param string $templateName
	 * @param array|null $viewParams
	 * @param string|null $contents
	 * @param boolean $after
	 */
	protected function _appendTemplateBlock($block, $templateName, array $viewParams = null, &$contents = null, $after = false)
	{
		$codeSnippet = "<!-- end block: $block -->";
		$this->_appendTemplateAtCodeSnippet($codeSnippet, $templateName, $viewParams, $contents, $after);
	}

	/**
	 *
	 * @return boolean true if replaced, false otherwise
	 * @param string $query
	 * @param string $templateName
	 * @param array|null $viewParams
	 * @param string|null $contents
	 * @param Zend_Dom_Query $dom
	 * @param boolean $after
	 */
	protected function _appendTemplateAtDomQuery($query, $templateName, array $viewParams = null, &$contents = null,
		Zend_Dom_Query &$dom = null, $after = true, $outside = true)
	{
		$rendered = $this->_render($templateName, $viewParams);
		return $this->_appendAtDomQuery($query, $rendered, $contents, $dom, $after, $outside);
	}

	/**
	 *
	 * @return boolean true if replaced, false otherwise
	 * @param string $query
	 * @param string $templateName
	 * @param array|null $viewParams
	 * @param string|null $contents
	 * @param Zend_Dom_Query $dom
	 */
	protected function _appendTemplateBeforeDomQuery($query, $templateName, array $viewParams = null, &$contents = null,
		Zend_Dom_Query &$dom = null, $outside = true)
	{
		return $this->_appendTemplateAtDomQuery($query, $templateName, $viewParams, $contents, $dom, false, $outside);
	}

	/**
	 *
	 * @return boolean true if replaced, false otherwise
	 * @param string $query
	 * @param string $templateName
	 * @param array|null $viewParams
	 * @param string|null $contents
	 * @param Zend_Dom_Query $dom
	 */
	protected function _appendTemplateAfterDomQuery($query, $templateName, array $viewParams = null, &$contents = null,
		Zend_Dom_Query &$dom = null)
	{
		return $this->_appendTemplateAtDomQuery($query, $templateName, $viewParams, $contents, $dom, true);
	}

	/**
	 *
	 * @param string $codeSnippet
	 * @param string $rendered
	 * @param string|null $contents
	 * @param boolean $after
	 *
	 * @return boolean
	 */
	protected function _appendAtCodeSnippet($codeSnippet, $rendered, &$contents = null, $after = true)
	{
		if (!$contents) {
			$contents = & $this->_contents;
		}
		if ($after) {
			if ($codeSnippet) {
				$contents = str_replace($codeSnippet, $codeSnippet . "\n" . $rendered, $contents);
			} else {
				$contents .= "\n" . $rendered;
			}
		} else {
			if ($codeSnippet) {
				$contents = str_replace($codeSnippet, $rendered . "\n" . $codeSnippet, $contents);
			} else {
				$contents = $rendered . "\n" . $contents;
			}
		}
		return true;
	}

	/**
	 *
	 * @param string $codeSnippet
	 * @param string $templateName
	 * @param array|null $viewParams
	 * @param string|null $contents
	 * @param boolean $after
	 *
	 * @return boolean
	 */
	protected function _appendTemplateAtCodeSnippet($codeSnippet, $templateName, array $viewParams = null, &$contents = null,
		$after = true)
	{
		if (!$contents) {
			$contents = & $this->_contents;
		}
		if (!$contents || !$codeSnippet || strpos($contents, $codeSnippet) === false) {
			return false;
		}
		$rendered = $this->_render($templateName, $viewParams);
		return $this->_appendAtCodeSnippet($codeSnippet, $rendered, $contents, $after);
	}

	/**
	 *
	 * @param string $codeSnippet
	 * @param string $templateName
	 * @param array|null $viewParams
	 * @param string|null $contents
	 *
	 * @return boolean
	 */
	protected function _appendTemplateBeforeCodeSnippet($codeSnippet, $templateName, array $viewParams = null,
		&$contents = null)
	{
		return $this->_appendTemplateAtCodeSnippet($codeSnippet, $templateName, $viewParams, $contents, false);
	}

	/**
	 *
	 * @param string $codeSnippet
	 * @param string $templateName
	 * @param array|null $viewParams
	 * @param string|null $contents
	 *
	 * @return boolean
	 */
	protected function _appendTemplateAfterCodeSnippet($codeSnippet, $templateName, array $viewParams = null,
		&$contents = null)
	{
		return $this->_appendTemplateAtCodeSnippet($codeSnippet, $templateName, $viewParams, $contents, true);
	}

	/**
	 *
	 * @param string $rendered
	 * @param string|null $contents
	 */
	protected function _prepend($rendered, &$contents = null)
	{
		$this->_appendAtCodeSnippet(null, $rendered, $contents, false);
	}

	/**
	 *
	 * @param string $templateName
	 * @param array|null $viewParams
	 * @param string|null $contents
	 */
	protected function _prependTemplate($templateName, array $viewParams = null, &$contents = null)
	{
		$this->_appendTemplate($templateName, $viewParams, $contents, false);
	}

	/**
	 *
	 * @param string $codeSnippet
	 * @param string $rendered
	 * @param string|null $contents
	 */
	protected function _replaceAtCodeSnippet($codeSnippet, $rendered = '', &$contents = null)
	{
		if (!$contents) {
			$contents = & $this->_contents;
		}
		$contents = str_replace($codeSnippet, $rendered, $contents);
	}

	/**
	 *
	 * @param Zend_Dom_Query_Result $results
	 * @param ThemeHouse_Listener_Template $listener
	 * @param array $extraData
	 */
	protected static function _replaceInResults(Zend_Dom_Query_Result $results, ThemeHouse_Listener_Template $listener,
		array $extraData)
	{
		$rendered = $extraData['rendered'];
		$query = $extraData['query'];
		$appendDom = new Zend_Dom_Query($rendered);
		if (is_array($query)) {
			$appendQuery = $appendDom->query($query[1]);
		} else {
			$appendQuery = $appendDom->query($query);
		}
		if ($appendQuery->count()) {
			$newnode = $results->getDocument()->importNode($appendQuery->current(), true);
			$results->current()->parentNode->replaceChild($newnode, $results->current());
		}
		return $results;
	}

	/**
	 *
	 * @return boolean true if replaced, false otherwise
	 * @param string $query
	 * @param string|null $contents
	 * @param Zend_Dom_Query $dom
	 */
	protected function _replaceAtDomQuery($query, $rendered, &$contents = null)
	{
		$this->_replaceCallbackAtDomQuery($query,
			array(
				$this,
				"_replaceInResults"
			), $contents,
			array(
				'rendered' => $this->_utf8Decode($rendered),
				'query' => $query
			));
	}

	/**
	 *
	 * @return boolean true if replaced, false otherwise
	 * @param string $query
	 * @param callback $callback
	 * @param string|null $contents
	 * @param mixed $extraData
	 */
	protected function _replaceCallbackAtDomQuery($query, $callback, &$contents = null, $extraData = null)
	{
		if (!$contents) {
			$contents = & $this->_contents;
		}
		if (!$contents) {
			return false;
		}
		$dom = new Zend_Dom_Query($this->_utf8Decode($contents));
		if (is_array($query)) {
			$results = $dom->query($query[0]);
		} else {
			$results = $dom->query($query);
		}
		if ($results->count()) {
			$results = call_user_func_array($callback,
				array(
					$results,
					$this,
					$extraData
				));
			$contents = $this->_utf8Encode($results->getDocument()
				->saveHTML());
		}
		return false;
	}

	/**
	 *
	 * @param string $templateName
	 * @param array|null $viewParams
	 * @param string|null $contents
	 */
	protected function _replaceWithTemplate($templateName, array $viewParams = null, &$contents = null)
	{
		if (!$contents) {
			$contents = & $this->_contents;
		}
		$contents = $this->_render($templateName, $viewParams);
	}

	/**
	 *
	 * @param string $codeSnippet
	 * @param string $templateName
	 * @param array|null $viewParams
	 * @param string|null $contents
	 */
	protected function _replaceWithTemplateAtCodeSnippet($codeSnippet, $templateName, array $viewParams = null,
		&$contents = null)
	{
		$rendered = $this->_render($templateName, $viewParams);
		$this->_replaceAtCodeSnippet($codeSnippet, $rendered, $contents);
	}

	/**
	 *
	 * @param string $title
	 * @param string|null $contents
	 */
	protected function _replaceTitle($title, &$contents = null)
	{
		if (!$contents) {
			$contents = & $this->_contents;
		}
		$contents = preg_replace("%<xen:title>.*</xen:title>%", "<xen:title>" . $title . "</xen:title>", $contents);
	}

	/**
	 *
	 * @return array
	 */
	protected function _fetchViewParams()
	{
		if (!$this->_template) {
			return array();
		}
		return $this->_template->getParams();
	}

	/**
	 *
	 * @return string
	 * @param string $templateName
	 * @param array|null $viewParams
	 */
	protected function _render($templateName, $viewParams = null)
	{
		if (!$this->_template) {
			return '';
		}
		if (!$viewParams) {
			$viewParams = $this->_fetchViewParams();
		}
		return $this->_template->create($templateName, $viewParams)->render();
	}

	/**
	 *
	 * @return boolean true if replaced, false otherwise
	 * @param string $query
	 * @param string|null $contents
	 * @param Zend_Dom_Query $dom
	 */
	protected function _removeAtDomQuery($query, &$contents = null, Zend_Dom_Query &$dom = null)
	{
		if (!$contents) {
			$contents = & $this->_contents;
		}
		if (!$contents) {
			return false;
		}
		if (!$dom) {
			$dom = new Zend_Dom_Query($this->_utf8Decode($contents));
		}
		$results = $dom->query($query);
		if ($results->count()) {
			$results->current()->parentNode->removeChild($results->current()->firstChild->parentNode);
			$contents = $this->_utf8Encode($results->getDocument()
				->saveHTML());
			return true;
		}
		return false;
	}

	/**
	 *
	 * @return true if successful, false otherwise
	 * @param string $templateName
	 * @param array|null $viewParams
	 * @param string|null $contents
	 */
	protected function _appendTemplateAfterTopCtrl($templateName, $viewParams = null, &$contents = null)
	{
		if (!$this->_appendTemplateAfterDomQuery('.topCtrl', $templateName, $viewParams, $contents)) {
			return $this->_appendTemplateBeforeDomQuery('.breadBoxTop nav, .titleBar h1', $templateName, $viewParams,
				$contents);
		}
		return true;
	}

	/**
	 *
	 * @return true if successful, false otherwise
	 * @param string $templateName
	 * @param array|null $viewParams
	 * @param string|null $contents
	 */
	protected function _appendTemplateBeforeTopCtrl($templateName, $viewParams = null, &$contents = null)
	{
		if (!$this->_appendTemplateBeforeDomQuery('.topCtrl', $templateName, $viewParams, $contents)) {
			return $this->_appendTemplateBeforeDomQuery('.breadBoxTop nav, .titleBar h1', $templateName, $viewParams,
				$contents);
		}
		return true;
	}

	/**
	 *
	 * @return true if successful, false otherwise
	 * @param string $pattern Regular expression
	 * @param string $replacement
	 * @param string|null $contents
	 * @param int limit The maximum possible replacements for each pattern in
	 * each subject string (-1 for no limit). Defaults to 1.
	 * @param int count If specified, this variable will be filled with the
	 * number of replacements done.
	 */
	protected function _patternReplace($pattern, $replacement = '', &$contents = null, $limit = 1, &$count = 0)
	{
		if (!$contents) {
			$contents = & $this->_contents;
		}
		$newContents = preg_replace($pattern, $replacement, $contents, $limit, $count);
		if (!$newContents || $newContents == $contents) {
			return false;
		}
		$contents = $newContents;
		return true;
	}

	/**
	 *
	 * @return true if successful, false otherwise
	 * @param string $pattern Regular expression
	 * @param string $templateName
	 * @param array|null $viewParams
	 * @param string|null $contents
	 * @param int limit The maximum possible replacements for each pattern in
	 * each subject string (-1 for no limit). Defaults to 1.
	 * @param int count If specified, this variable will be filled with the
	 * number of replacements done.
	 */
	protected function _appendTemplateAtPattern($pattern, $templateName, array $viewParams = null, &$contents = null, $limit = 1,
		&$count = 0)
	{
		$replacement = '${0}' . $this->_escapeDollars($this->_render($templateName, $viewParams));
		return $this->_patternReplace($pattern, $replacement, $contents, $limit, $count);
	}

	/**
	 *
	 * @return true if successful, false otherwise
	 * @param string $pattern Regular expression
	 * @param string $templateName
	 * @param array|null $viewParams
	 * @param string|null $contents
	 * @param int limit The maximum possible replacements for each pattern in
	 * each subject string (-1 for no limit). Defaults to 1.
	 * @param int count If specified, this variable will be filled with the
	 * number of replacements done.
	 */
	protected function _prependTemplateAtPattern($pattern, $templateName, array $viewParams = null, &$contents = null,
		$limit = 1, &$count = 0)
	{
		$replacement = $this->_escapeDollars($this->_render($templateName, $viewParams)) . '${0}';
		return $this->_patternReplace($pattern, $replacement, $contents, $limit, $count);
	}

	/**
	 *
	 * @return true if successful, false otherwise
	 * @param string $pattern Regular expression
	 * @param string $templateName
	 * @param array|null $viewParams
	 * @param string|null $contents
	 * @param int limit The maximum possible replacements for each pattern in
	 * each subject string (-1 for no limit). Defaults to 1.
	 * @param int count If specified, this variable will be filled with the
	 * number of replacements done.
	 */
	protected function _replaceWithTemplateAtPattern($pattern, $templateName, array $viewParams = null, &$contents = null,
		$limit = 1, &$count = 0)
	{
		$replacement = $this->_escapeDollars($this->_render($templateName, $viewParams));
		return $this->_patternReplace($pattern, $replacement, $contents, $limit, $count);
	}

	protected function _escapeDollars($string)
	{
		return str_replace('$', '\$', $string);
	}
}