<?php
/**
 * @package    Joomla.JEDChecker
 *
 * @copyright  Copyright (C) 2017 - 2019 Open Source Matters, Inc. All rights reserved.
 * 			   Copyright (C) 2008 - 2016 compojoom.com . All rights reserved.
 * @author     Daniel Dimitrov <daniel@compojoom.com>
 * 			   eaxs <support@projectfork.net>
 *
 * @license    GNU General Public License version 2 or later; see LICENSE.txt
 */

defined('_JEXEC') or die('Restricted access');

// Include the rule base class
require_once JPATH_COMPONENT_ADMINISTRATOR . '/models/rule.php';

/**
 * class JedcheckerRulesJexec
 *
 * This class searches all files for the _JEXEC check
 * which prevents direct file access.
 *
 * @since  1.0
 */
class JedcheckerRulesJexec extends JEDcheckerRule
{
	/**
	 * The formal ID of this rule. For example: SE1.
	 *
	 * @var    string
	 */
	protected $id = 'PH2';

	/**
	 * The title or caption of this rule.
	 *
	 * @var    string
	 */
	protected $title = 'COM_JEDCHECKER_RULE_PH2';

	/**
	 * The description of this rule.
	 *
	 * @var    string
	 */
	protected $description = 'COM_JEDCHECKER_RULE_PH2_DESC';

	/**
	 * Initiates the file search and check
	 *
	 * @return    void
	 */
	public function check()
	{
		// Find all php files of the extension
		$files = JFolder::files($this->basedir, '.php$', true, true);

		// Iterate through all files
		foreach ($files as $file)
		{
			// Try to find the _JEXEC check in the file
			if (!$this->find($file) && !$this->is_class_only_declaration($file))
			{
				// Add as error to the report if it was not found
				$this->report->addError($file, JText::_('COM_JEDCHECKER_ERROR_JEXEC_NOT_FOUND'));
			}
		}
	}

	/**
	 * Reads a file and searches for the _JEXEC statement
	 *
	 * @param   string  $file  - The path to the file
	 *
	 * @return boolean True if the statement was found, otherwise False.
	 */
	protected function find($file)
	{
		$content = (array) file($file);

		// Get the constants to look for
		$defines = $this->params->get('constants');
		$defines = explode(',', $defines);

		$hascode = 0;

		foreach ($content AS $line)
		{
			$tline = trim($line);

			if ($tline == '' || $tline == '<?php' || $tline == '?>')
			{
				continue;
			}

			if ($tline['0'] != '/' && $tline['0'] != '*')
			{
				$hascode = 1;
			}

			// Search for "defined"
			$pos_1 = stripos($line, 'defined');

			// Skip the line if "defined" is not found
			if ($pos_1 === false)
			{
				continue;
			}

			// Search for "die".
			//  "or" may not be present depending on syntax
			$pos_3 = stripos($line, 'die');

			// Skip the line if "die" is not found
			if ($pos_3 === false)
			{
				continue;
			}

			// Search for the constant name
			foreach ($defines AS $define)
			{
				$define = trim($define);

				// Search for the define
				$pos_2 = strpos($line, $define);

				// Skip the line if the define is not found
				if ($pos_2 === false)
				{
					continue;
				}

				// Check the position of the words
				if ($pos_2 > $pos_1 && $pos_3 > $pos_2)
				{
					unset($content);

					return true;
				}
			}
		}

		unset($content);

		return $hascode ? false : true;
	}

	/**
	 * Check the file is a class/interface/trait declaration only
	 * (and so _JEXEC guard is not necessary)
	 *
	 * @param   string  $file  - The path to the file
	 *
	 * @return boolean True if the file is a declaration only, otherwise False.
	 */
	protected function is_class_only_declaration($file)
	{
		// load file and strip comments
		$content = php_strip_whitespace($file);
		// check there is no intermittent PHP and HTML codes
		if (strrpos($content, '<?') !== 0) {
			return false;
		}
		// some regexp magic instead of the full PHP parser
		return (bool)preg_match(
			'#^' .
			'<\?php\s+' .
			'(?:namespace [0-9A-Za-z_\\\\]+ ?; ?)?' .
			'(?:use [0-9A-Za-z_\\\\]+( as [0-9A-Za-z_]+) ?; ?)*' .
			'(?:' .
			'(?:(?:abstract )?class|interface|trait) [0-9A-Za-z_]+' .
			'(?: extends [0-9A-Za-z_\\\\]+(?:, ?[0-9A-Za-z_\\\\]+)*)?' .
			'(?: implements [0-9A-Za-z_\\\\]+(?:, ?[0-9A-Za-z_\\\\]+)*)?' .
			' ?' .
			// recursive checking of curly braces and strings
			'(\{((?>[^\{\}\'"]+)|\'(?:(?>[^\'\\\\]+)|\\\\.)*\'|"(?:(?>[^"\\\\]+)|\\\\.)*"|(?-2))*\})' .
			' ?' .
			')+' .
			'$#',
			$content);
	}
}
