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
class ScheduleNoSplit extends ScheduleSplitter {

	/**
	 * No split
	 *
	 * @param array $arguments
	 */
	public function split(array $arguments = array()) {
		return new ArrayIterator(array($arguments));
	}

}