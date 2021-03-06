<?php

/**
 * Author: imsamurai <im.samuray@gmail.com>
 * Date: 15.11.11
 * Time: 17:12
 * Format: http://book.cakephp.org/2.0/en/console-and-shells.html#creating-a-shell
 */

App::uses('Shell', 'Console');

/**
 * Advanced shell with sqldump and statistics
 * 
 * @package AdvancedShell
 * @subpackage Shell
 */
class AdvancedShell extends Shell {

	/**
	 * {@inheritdoc}
	 *
	 * @var array 
	 */
	public $tasks = array();

	/**
	 * {@inheritdoc}
	 *
	 * @var array 
	 */
	public $uses = array();

	/**
	 * Timer
	 *
	 * @var array 
	 */
	protected $_startTime = array();

	/**
	 * {@inheritdoc}
	 */
	public function startup() {
		$this->stdout->styles('b', array('bold' => true));
		$this->stdout->styles('ok', array('text' => 'green'));
		$this->stdout->styles('sqlinfo', array('text' => 'black', 'background' => 'white'));
	}

	/**
	 * Displays a header for the shell
	 */
	protected function _welcome() {
		$this->out();
		$this->out('<info>Welcome to ' . Configure::read('App.name') . ' ' . Configure::version() . ' Console</info>');
		$this->hr();
		$this->out('App : ' . APP_DIR);
		$this->out('Path: ' . APP);
		$this->hr();
	}

	/**
	 * Start statistics
	 * 
	 * @param string $name
	 */
	public function statisticsStart($name) {
		$this->_startTime[$name] = new DateTime();
	}

	/**
	 * Stop and output statistics
	 * 
	 * @param string $name
	 */
	public function statisticsEnd($name) {
		$this->hr();
		$this->out('Took: ' . $this->_startTime[$name]->diff(new DateTime())->format('%ad %hh %im %ss'));
		$this->out('Memory: ' . sprintf('%0.3f', memory_get_peak_usage(true) / (1024 * 1024)) . "Mb max used");
		$this->hr();
	}

	/**
	 * {@inheritdoc}
	 * 
	 * @return ConsoleOptionParser
	 */
	public function getOptionParser() {
		$parser = parent::getOptionParser();
		$parser->description('There is no help');
		$taskNames = array_keys(Hash::normalize($this->tasks));
		foreach ($taskNames as $taskName) {
			$Task = $this->{$taskName};
			$parser->addSubcommand(Inflector::underscore($Task->name), array(
				'help' => $Task->getOptionParser()->description(),
				'parser' => $Task->getOptionParser()
			));
		}
		return $parser;
	}

	/**
	 * Shows sql dump
	 * 
	 * @param bool $sorted Get the queries sorted by time taken, defaults to false.
	 * @param bool $clear If True the existing log will cleared.
	 */
	public function sqlDump($sorted = false, $clear = true) {
		if (!class_exists('ConnectionManager') || Configure::read('debug') < 2) {
			return;
		}
		$sources = ConnectionManager::sourceList();

		$logs = array();
		foreach ($sources as $source) {
			$db = ConnectionManager::getDataSource($source);
			if (!method_exists($db, 'getLog')) {
				continue;
			}
			$logs[$source] = $db->getLog($sorted, $clear);
		}

		if (empty($logs)) {
			return;
		}
		$this->out('<b>SQL dump:</b>');
		foreach ($logs as $source => $log) {
			$this->out("<b>Source: $source, queries: {$log['count']}, took: {$log['time']}ms</b>");

			foreach ($log['log'] as $k => $i) {
				$i += array('error' => '');
				$this->out(($k + 1) . ". {$i['query']} <sqlinfo>{e:{$i['error']}, a:{$i['affected']}, t:{$i['took']}, n:{$i['numRows']}}</sqlinfo>");
			}
		}
	}

}
