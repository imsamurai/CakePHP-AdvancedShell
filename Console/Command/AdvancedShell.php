<?php

/**
 * Author: imsamurai <im.samuray@gmail.com>
 * Date: 15.11.11
 * Time: 17:12
 * Format: http://book.cakephp.org/2.0/en/console-and-shells.html#creating-a-shell
 */

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
		if (!$this->command || $this->command === 'main') {
			$this->_welcome();
		}
	}

	/**
	 * {@inheritdoc}
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
	 * {@inheritdoc}
	 * 
	 * @param string $command
	 * @param array $argv
	 * @return void
	 */
	public function runCommand($command, $argv) {
		$this->OptionParser = $this->getOptionParser();

		if (!empty($this->params['help'])) {
			$this->_welcome();
			return $this->out($this->OptionParser->help($command));
		}
		if (!($command && $command !== 'main' && $command !== 'execute')) {
			return parent::runCommand($command, $argv);
		}
		if (!in_array(Inflector::camelize($command), $this->enabledTasks) && !method_exists($this, $command)) {
			$this->_welcome();
			$out = parent::runCommand($command, $argv);
			if ($out === false) {
				$this->err('<error>Task "' . $command . '" not found!</error>');
			}
			return $out;
		}
		$this->statisticsStart('AdvancedShell');
		$this->out('<info>Task ' . Inflector::camelize($command) . ' started... (' . date('Y.m.d H:i:s') . ')</info>', 2);
		$this->tasks = array(Inflector::camelize($command));
		$this->loadTasks();
		$out = parent::runCommand($command, $argv);
		$this->out();
		$this->out('<info>Task ' . Inflector::camelize($command) . ' finished (' . date('Y.m.d H:i:s') . ')</info>');
		$this->statisticsEnd('AdvancedShell');

		$this->_sqlDump();
		$this->hr();
		return $out;
	}

	/**
	 * Start statistics
	 */
	public function statisticsStart($name) {
		$this->_startTime[$name] = microtime(true);
	}

	/**
	 * Stop and output statistics
	 */
	public function statisticsEnd($name) {
		$this->hr();
		$this->out('Time: ' . sprintf('%6.3f', (microtime(true) - $this->_startTime[$name])) . 'sec');
		$this->out('Memory: ' . ((memory_get_peak_usage(true) / (1024 * 1024)) . "Mb max used"));
		$this->hr();
	}

	/**
	 * Shows sql dump
	 */
	protected function _sqlDump() {
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
			$logs[$source] = $db->getLog();
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