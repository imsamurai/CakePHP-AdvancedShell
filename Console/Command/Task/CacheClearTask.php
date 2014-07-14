<?php

/**
 * Author: imsamurai <im.samuray@gmail.com>
 * Date: 14.07.2014
 * Time: 11:50:36
 * Format: http://book.cakephp.org/2.0/en/console-and-shells.html#shell-tasks
 */
App::uses('AdvancedTask', 'AdvancedShell.Console/Command/Task');

/**
 * CacheClear Task
 * 
 * @package AdvancedShell
 * @subpackage Task
 */
class CacheClearTask extends AdvancedTask {

	/**
	 * {@inheritdoc}
	 *
	 * @var string 
	 */
	public $name = 'Clear';

	/**
	 * {@inheritdoc}
	 */
	public function execute() {
		parent::execute();
		$cacheNames = $this->args[0];
		if ($this->args[0] === 'all') {
			$cacheNames = Cache::configured();
		} else {
			$cacheNames = array_map('trim', explode(',', $this->args[0]));
		}
		foreach ($cacheNames as $cacheName) {
			$success = Cache::clear(false, $cacheName);
			if ($success) {
				$this->out("Cache: $cacheName cleared");
			} else {
				$this->err("Cache: $cacheName NOT cleared!");
			}
		}
	}

	/**
	 * {@inheritdoc}
	 *
	 * @var ConsoleOptionParser 
	 */
	public function getOptionParser() {
		$parser = parent::getOptionParser();
		$parser->addArgument('name(s)', array(
			'required' => true,
			'help' => 'Enter one or more cache names separated by comma or "all"' .
			"\n<comment>(choises: " . implode(',', array_merge(Cache::configured(), array('all'))) . ")</comment>"
		));
		return $parser->description('Clear cache');
	}

}
