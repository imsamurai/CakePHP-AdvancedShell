<?php

/**
 * Author: imsamurai <im.samuray@gmail.com>
 * Date: 29.11.2012
 * Time: 14:33:50
 * Format: http://book.cakephp.org/2.0/en/console-and-shells.html#shell-tasks
 */
App::uses('AdvancedShell', 'AdvancedShell.Console/Command');
App::uses('DateRange', 'DateRange.Utility');
App::uses('ScheduleSplitByRange', 'AdvancedShell.Console/Command/Task/Scheduled');
App::uses('ScheduleSplitByUsers', 'AdvancedShell.Console/Command/Task/Scheduled');
App::uses('ScheduleNoSplit', 'AdvancedShell.Console/Command/Task/Scheduled');

/**
 * Advanced shell task
 * 
 * @package AdvancedShell
 * @subpackage Task
 */
class AdvancedTask extends AdvancedShell {

	/**
	 * Current subcommand name (method in task)
	 *
	 * @var string
	 */
	public $action = null;

	/**
	 * If true newx task will wait for previous.
	 * First task don't wait anyone
	 *
	 * @var bool
	 */
	protected $_scheduleNextTaskDependsOnPrevious = true;

	/**
	 * Class for splitting one task into many by arguments
	 *
	 * @var ScheduleSplitter
	 */
	protected $_ScheduleSplitter = null;

	/**
	 * Execute command
	 */
	public function execute() {
	}

	/**
	 * {@inheritdoc}
	 * 
	 * @param string $command
	 * @param array $argv
	 * @return bool
	 */
	public function runCommand($command, $argv) {
		if (!empty($this->args[0]) && $this->hasMethod($this->args[0])) {
			$this->action = $this->args[0];
		} else {
			$this->action = null;
		}
		$this->statisticsStart('AdvancedShell');
		Configure::write('debug', (int)Hash::get($this->params, 'debug'));
		if ($this->isScheduled()) {
			if ($this->params['scheduled-no-split']) {
				$this->setScheduleSplitter(new ScheduleNoSplit());
			}
			$this->schedule();
			$out = null;
		} elseif ($this->action) {
			$out = $this->{$this->action}();
		} else {
			$out = parent::runCommand($command, $argv);
		}
		$this->statisticsEnd('AdvancedShell');
		$this->sqlDump();
		$this->hr();
		return $out;
	}

	/**
	 * Returns ScheduleSplitter for current task
	 * 
	 * @return ScheduleSplitter
	 */
	public function getScheduleSplitter() {
		return new ScheduleNoSplit();
	}

	/**
	 * Set ScheduleSplitter for current task
	 * 
	 * @param ScheduleSplitter $Splitter
	 */
	public function setScheduleSplitter(ScheduleSplitter $Splitter) {
		$this->_ScheduleSplitter = $Splitter;
	}

	/**
	 * Returns true if script must be scheduled
	 *
	 * @return bool
	 */
	public function isScheduled() {
		return $this->params['scheduled'];
	}

	/**
	 * Adds script to sceduler
	 * (for ex by date range)
	 */
	public function schedule() {
		if (!empty($this->params['scheduled-wait-prev'])) {
			$this->_scheduleNextTaskDependsOnPrevious = ($this->params['scheduled-wait-prev'] === 'yes');
		}
		list($command, $path, $arguments, $options) = $this->_scheduleVars();
		$lastTaskId = null;
		foreach ($this->getScheduleSplitter()->split($arguments) as $_arguments) {
			$_options = $options;
			if ($this->_scheduleNextTaskDependsOnPrevious && !is_null($lastTaskId)) {
				$_options['dependsOn'][] = $lastTaskId;
			}
			$task = $this->_schedule($command, $path, $_arguments, $_options);
			$lastTaskId = $task['id'];
		}
	}

	/**
	 * {@inheritdoc}
	 * 
	 * @return ConsoleOptionParser
	 */
	public function getOptionParser() {
		$parser = parent::getOptionParser();
		$parser->addOption('range', array(
					'help' => 'Either a date range (separator "-") in format ' . Configure::read('Task.dateFormat')
				))
				->addOption('interval', array(
					'help' => 'Interval in date time format (for ex: 15 minutes, 1 hour, 2 days). Defaults 1 day'
				))
				->addOption('debug', array(
					'help' => 'Sets debug level',
					'short' => 'd',
					'default' => Configure::read('debug')
		));
		if (CakePlugin::loaded('Task')) {
			$parser->addOption('scheduled', array(
						'help' => 'If set then script will be run by task daemon',
						'boolean' => true
					))
					->addOption('scheduled-no-split', array(
						'help' => 'If set then script will not be splitten by arguments',
						'boolean' => true
					))
					->addOption('scheduled-process-timeout', array(
						'help' => 'Sets task process timeout',
						'default' => Configure::read('Task.timeout')
					))
					->addOption('scheduled-depends-on', array(
						'help' => 'Tasks ids that must be done before current task can start. Format: coma separated',
					))
					->addOption('scheduled-wait-prev', array(
						'help' => 'If `yes` each task will wait for previous task, else each task will run independenly. 
						See `$_scheduleNextTaskDependsOnPrevious` for script defaults that used when this parameter 
						was omitted.',
			));
		} else {
			$parser->epilog($parser->epilog() . "\n" . 'For using `scheduled` install and enable Task plugin https://github.com/imsamurai/cakephp-task-plugin');
		}
		return $parser;
	}

	/**
	 * Returns variables for schedule
	 *
	 * @global array $argv
	 * @return array
	 */
	protected function _scheduleVars() {
		global $argv;
		$path = $argv[2] . DS;
		$shellName = $argv[3];
		$taskName = $argv[4];

		if (isset($this->args[0]) && $this->hasMethod($this->args[0])) {
			$methodName = $this->args[0];
			array_shift($this->args);
		} else {
			$methodName = null;
		}

		$command = implode(' ', array_filter(array(
			'Console/cake',
			$shellName,
			$taskName,
			$methodName
		)));

		$arguments = $this->args;
		foreach ($this->params as $name => $value) {
			if (in_array($name, array(
						'scheduled',
						'scheduled-depends-on',
						'scheduled-wait-prev',
						'skip-unlogged',
						'scheduled-process-timeout'
							), true)) {
				continue;
			}
			if (is_bool($value) && $value) {
				$arguments[] = '--' . $name;
			} elseif (!is_bool($value)) {
				$arguments['--' . $name] = $value;
			}
		}

		$options = array(
			'timeout' => $this->params['scheduled-process-timeout'],
			'dependsOn' => empty($this->params['scheduled-depends-on']) ? array() : explode(',', $this->params['scheduled-depends-on'])
		);

		return array($command, $path, $arguments, $options);
	}

	/**
	 * Adds script to sceduler
	 *
	 * @param string $command
	 * @param string $path
	 * @param array $arguments
	 * @param array $options
	 */
	protected function _schedule($command, $path, array $arguments, array $options) {
		$TaskClient = ClassRegistry::init('Task.TaskClient');
		$task = $TaskClient->add($command, $path, $arguments, $options);
		if ($task) {
			$waitFor = empty($options['dependsOn']) ? 'none' : implode(', ', $options['dependsOn']) . ' task(s)';
			$this->out("Task #{$TaskClient->id} successfuly added, wait for $waitFor");
		} else {
			$this->err('Error! Task not added!');
		}
		return $task;
	}

	/**
	 * Returns DatePeriod starting from $Date or now with $default_shift
	 * splitted by $interval
	 * 
	 * @param DateTime $Date Start date
	 * @param string $defaultShift Shift date if $Date is null, for ex. "1 day"
	 * @param string $interval Interval, for ex. "1 hour"
	 * @return DatePeriod
	 */
	protected function _getPeriod(DateTime $Date = null, $defaultShift = '', $interval = null) {
		return $this->_getRange($Date, $defaultShift, $interval)->period($this->_getInterval($interval));
	}

	/**
	 * Returns DateRange starting from $Date or now with $default_shift
	 * splitted by $interval
	 * 
	 * @param DateTime $Date Start date
	 * @param string $defaultShift Shift date if $Date is null, for ex. "1 day"
	 * @param string $interval Interval, for ex. "1 hour"
	 * @return DatePeriod
	 */
	protected function _getRange(DateTime $Date = null, $defaultShift = '', $interval = null) {
		if ($Date !== null) {
			return $this->_getPeriodByDate($Date, $interval);
		}
		if (!empty($this->params['range'])) {
			$range = explode('-', $this->params['range']);
			$Range = new DateRange($range[0], empty($range[1]) ? null : $range[1]);
		} else {
			$Range = new DateRange('now ' . $defaultShift, 'now ' . $defaultShift);
		}

		return $Range;
	}

	/**
	 * Returns DatePeriod starting from $Date splitted by $interval
	 * 
	 * @param DateTime $Date Start date
	 * @param string $interval Interval, for ex. "1 hour"
	 * @return DatePeriod
	 */
	protected function _getPeriodByDate(DateTime $Date, $interval = null) {
		$Range = new DateRange(clone $Date);
		return $Range->period($this->_getInterval($interval));
	}

	/**
	 * Returns interval value specified by parameter or default value
	 *
	 * @param string $interval If not null method return this value
	 *
	 * @return string
	 */
	protected function _getInterval($interval) {
		if ($interval !== null) {
			return $interval;
		} elseif (!empty($this->params['interval'])) {
			return $this->params['interval'];
		} else {
			return '1 day';
		}
	}

}
