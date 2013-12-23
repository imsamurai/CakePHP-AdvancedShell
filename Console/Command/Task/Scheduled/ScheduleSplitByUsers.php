<?php

/**
 * Author: imsamurai <im.samuray@gmail.com>
 * Date: 18.06.2013
 * Time: 23:00:12
 *
 */
App::uses('ScheduleSplitter', 'AdvancedShell.Console/Command/Task/Scheduled');

/**
 * @package AdvancedShell
 * @subpackage Scheduled
 */
class ScheduleSplitByUsers extends ScheduleSplitter {

	/**
	 * Split arguments by date range
	 *
	 * @param array $arguments
	 */
	public function split(array $arguments = array()) {
		$splittedArguments = new AppendIterator();

		if (count($this->_options['userIds']) === 1) {
			$splittedArguments->append($this->splitInner($arguments));
		} else {
			foreach ($this->_options['userIds'] as $userId) {
				$_arguments = $arguments;
				array_unshift($_arguments, $userId);
				$splittedArguments->append($this->splitInner($_arguments));
			}
		}

		return $splittedArguments;
	}

}