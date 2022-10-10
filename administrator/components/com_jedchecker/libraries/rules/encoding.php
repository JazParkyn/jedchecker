<?php
/**
 * @package    Joomla.JEDChecker
 *
 * @copyright  Copyright (C) 2017 - 2022 Open Source Matters, Inc. All rights reserved.
 * 			   Copyright (C) 2008 - 2016 compojoom.com . All rights reserved.
 * @author     Daniel Dimitrov <daniel@compojoom.com>
 * 			   02.06.12
 *
 * @license    GNU General Public License version 2 or later; see LICENSE.txt
 */

defined('_JEXEC') or die('Restricted access');

// Include the rule base class
require_once JPATH_COMPONENT_ADMINISTRATOR . '/models/rule.php';

// Include the helper class
require_once JPATH_COMPONENT_ADMINISTRATOR . '/libraries/helper.php';

/**
 * class JedcheckerRulesEncoding
 *
 * This class checks if base64 encoding is used in the files
 *
 * @since  1.0
 */
class JedcheckerRulesEncoding extends JEDcheckerRule
{
	/**
	 * The formal ID of this rule. For example: SE1.
	 *
	 * @var    string
	 */
	protected $id = 'encoding';

	/**
	 * The title or caption of this rule.
	 *
	 * @var    string
	 */
	protected $title = 'COM_JEDCHECKER_RULE_ENCODING';

	/**
	 * The description of this rule.
	 *
	 * @var    string
	 */
	protected $description = 'COM_JEDCHECKER_RULE_ENCODING_DESC';

	/**
	 * The ordering value to sort rules in the menu.
	 *
	 * @var    integer
	 */
	public static $ordering = 900;

	/**
	 * Regular expression to look for encoding functions.
	 *
	 * @var    string
	 */
	protected $encodingsRegex;

	/**
	 * Initiates the file search and check
	 *
	 * @return    void
	 */
	public function check()
	{
		// Get the functions to look for
		$encodings = explode(',', $this->params->get('encodings'));

		// Prepare regex
		foreach ($encodings as $i => $encoding)
		{
			$encodings[$i] = preg_quote(trim($encoding), '/');
		}

		$this->encodingsRegex = '/' . implode('|', $encodings) . '/i';

		// Find all php files of the extension
		$files = JFolder::files($this->basedir, '\.php$', true, true);

		// Iterate through all files
		foreach ($files as $file)
		{
			// Try to find the base64 use in the file
			if ($this->find($file))
			{
				// The error has been added by the find() method
			}
		}
	}

	/**
	 * Reads a file and searches for any encoding function defined in the params
	 * Not a very clever way of doing this, but it should be fine for now
	 *
	 * @param   string  $file  The path to the file
	 *
	 * @return boolean True if the statement was found, otherwise False.
	 */
	protected function find($file)
	{
		$content = file_get_contents($file);
		$origContent = JEDCheckerHelper::splitLines($content);

		// Exclude comments
		$content = JEDCheckerHelper::cleanPhpCode(
			$content,
			JEDCheckerHelper::CLEAN_HTML | JEDCheckerHelper::CLEAN_COMMENTS
		);
		$content = JEDCheckerHelper::splitLines($content);

		$found = false;

		foreach ($content as $i => $line)
		{
			if (preg_match($this->encodingsRegex, $line))
			{
				$found = true;
				$this->report->addWarning($file, JText::_('COM_JEDCHECKER_ERROR_ENCODING'), $i + 1, $origContent[$i]);
			}
		}

		return $found;
	}
}
