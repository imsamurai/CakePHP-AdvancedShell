<?php

/**
 * Author: imsamurai <im.samuray@gmail.com>
 * Date: 15.11.11
 * Time: 16:41
 * Format: http://book.cakephp.org/2.0/en/console-and-shells.html#creating-a-shell
 */
App::uses('AdvancedShell', 'AdvancedShell.Console/Command');

/**
 * Main shell for tasks
 * 
 * @package AdvancedShell
 * @subpackage Shell
 */
class AdvancedCronShell extends AdvancedShell {

	/**
	 * List of enabled tasks
	 *
	 * @var array 
	 */
	public $enabledTasks = array();

	public function __construct($stdout = null, $stderr = null, $stdin = null) {
		$this->enabledTasks = Configure::read('Console.Cron.enabledTasks');
		parent::__construct($stdout, $stderr, $stdin);
	}

	/**
	 * {@inheritdoc}
	 * 
	 * @return ConsoleOptionParser
	 */
	public function getOptionParser() {
		$parser = parent::getOptionParser();
		$parser->description('Cron shell global options');

		foreach ($this->enabledTasks as $taskName) {
			$parser->addSubcommand(Inflector::underscore($taskName), array(
				'help' => $this->Tasks->load($taskName)->getOptionParser()->description(),
				'parser' => $this->Tasks->load($taskName)->getOptionParser()
			));
		}
		return $parser;
	}

}
