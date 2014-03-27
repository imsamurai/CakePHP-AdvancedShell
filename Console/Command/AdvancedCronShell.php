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

	/**
	 * {@inheritdoc}
	 * 
	 * @param ConsoleOutput $stdout
	 * @param ConsoleOutput $stderr
	 * @param ConsoleInput $stdin
	 */
	public function __construct($stdout = null, $stderr = null, $stdin = null) {
		parent::__construct($stdout, $stderr, $stdin);
		$this->enabledTasks = Hash::normalize((array)Configure::read("Console.{$this->name}.enabledTasks"));
	}

	/**
	 * {@inheritdoc}
	 * 
	 * @return ConsoleOptionParser
	 */
	public function getOptionParser() {
		$parser = parent::getOptionParser();
		$parser->description($this->name . ' shell global options');

		foreach ($this->enabledTasks as $name => $settings) {
			$parser->addSubcommand(Inflector::underscore($name), array(
				'help' => $this->Tasks->load($name, $settings)->getOptionParser()->description(),
				'parser' => $this->Tasks->load($name, $settings)->getOptionParser()
			));
		}
		return $parser;
	}

}
