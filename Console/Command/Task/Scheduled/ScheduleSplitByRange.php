<?php

/**
 * Author: imsamurai <im.samuray@gmail.com>
 * Date: 18.06.2013
 * Time: 23:00:12
 *
 */
App::uses('ScheduleSplitter', 'AdvancedShell.Console/Command/Task/Scheduled');
App::uses('AdvancedTask', 'AdvancedShell.Console/Command/Task');

/**
 * @package AdvancedShell
 * @subpackage Scheduled
 */
class ScheduleSplitByRange extends ScheduleSplitter {

	/**
	 * Split arguments by date range
	 *
	 * @param array $arguments
	 */
	public function split(array $arguments = array()) {
		$splittedArguments = new AppendIterator();

		foreach ($this->_options['Period'] as $Date) {
			$_arguments = $arguments;
			$_arguments['--range'] = $Date->format(AdvancedTask::HUMAN_DATE_FORMAT);
			$splittedArguments->append($this->splitInner($_arguments));
		}

		return $splittedArguments;
	}

}
