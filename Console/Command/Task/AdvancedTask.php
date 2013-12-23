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
	 * {@inheritdoc}
	 *
	 * @var array 
	 */
	public $uses = array();

	/**
	 * Splits script arguments for multitasking
	 *
	 * @var ScheduleSplitter
	 */
	protected $_ScheduleSplitter = null;

	/**
	 * If true newt task will wait for previous.
	 * First task don't wait anyone
	 *
	 * @var bool
	 */
	protected $_scheduleNextTaskDependsOnPrevious = true;

	/**
	 * Allow unlogged users or not when user id is not specified
	 *
	 * @var bool
	 */
	public $allowUnlogged = true;

	/**
	 * {@inheritdoc}
	 * 
	 * @return boolean
	 */
	public function execute() {
		Configure::write('debug', (int) Hash::get($this->params, 'debug'));
		if ($this->isScheduled()) {
			if (is_null($this->_ScheduleSplitter) || $this->params['scheduled-no-split']) {
				$this->_ScheduleSplitter = new ScheduleNoSplit();
			}
			$this->schedule();
			return false;
		}
		return true;
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
	 */
	public function schedule() {
		if (!empty($this->params['scheduled-wait-prev'])) {
			$this->_scheduleNextTaskDependsOnPrevious = ($this->params['scheduled-wait-prev'] === 'yes');
		}
		list($command, $path, $arguments, $options) = $this->_scheduleVars();
		$lastTaskId = null;
		foreach ($this->_ScheduleSplitter->split($arguments) as $_arguments) {
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
		$parser->description('Task global options')
				->addOption('range', array(
					'help' => 'Either a date range (separator "-") in format ' . Configure::read('Task.dateFormat')
				))
				->addOption('interval', array(
					'help' => 'Interval in date time format (for ex: 15 minutes, 1 hour, 2 days). Defaults 1 day'
				))
				->addOption('scheduled', array(
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
				))
				->addOption('debug', array(
					'help' => 'Sets debug level',
					'short' => 'd'
				))
		;
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
		$command = 'Console/cake cron ' . Inflector::underscore($this->name);
		$path = $argv[2] . DS;
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
			} else if (!is_bool($value)) {
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
	 * Returns valid user id's
	 *
	 * @return array
	 */
	protected function _getUsersIds() {
		if (isset($this->args[0])) {
			$user_ids = array($this->args[0]);
		} else {
			$user_ids = $this->User->find('list', array('fields' => array('User.id')));
			if ($this->allowUnlogged && !$this->params['skip-unlogged']) {
				array_unshift($user_ids, 0);
			}
		}

		return $user_ids;
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
	 * @param string $default_shift Shift date if $Date is null, for ex. "1 day"
	 * @param string $interval Interval, for ex. "1 hour"
	 * @return DatePeriod
	 */
	protected function _getPeriod(DateTime $Date = null, $default_shift = '', $interval = null) {
		if ($Date !== null) {
			return $this->_getPeriodByDate($Date, $interval);
		}
		if (!empty($this->params['range'])) {
			$range = explode('-', $this->params['range']);
			if (empty($range[1])) {
				$range[1] = $range[0];
			}
			$Start = DateTime::createFromFormat(Configure::read('Task.dateFormat'), $range[0]);
			$Start->setTime(00, 00, 00);
			$End = DateTime::createFromFormat(Configure::read('Task.dateFormat'), $range[1]);
			$End->setTime(00, 00, 00);
			$End->modify('+1 day');
			$Now = new Datetime('now');
			if ($End > $Now) {
				$End = $Now;
			}
			$Range = new DateRange($Start, $End);
		} else {
			$Range = new DateRange('now ' . $default_shift, 'now +1 day ' . $default_shift);
		}

		return $Range->period($this->_getInterval($interval));
	}

	/**
	 * Returns DatePeriod starting from $Date splitted by $interval
	 * 
	 * @param DateTime $Date Start date
	 * @param string $interval Interval, for ex. "1 hour"
	 * @return DatePeriod
	 */
	protected function _getPeriodByDate(DateTime $Date, $interval = null) {
		$Start = clone $Date;
		$End = clone $Date;
		$End->modify('+1 day');
		$Range = new DateRange($Start, $End);

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
		} else if (!empty($this->params['interval'])) {
			return $this->params['interval'];
		} else {
			return '1 day';
		}
	}

}
