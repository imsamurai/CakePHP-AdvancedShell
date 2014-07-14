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
		if ($this->params['views']) {
			$this->_clearViews();
		} else {
			$this->_clearCache();
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
			'help' => 'Enter one or more cache/view names separated by comma or "all"' .
			"\n<comment>(choises: " . implode(',', array_merge(Cache::configured(), array('all'))) . ")</comment>"
		));
		$parser->addOption('views', array(
			'boolean' => true,
			'help' => 'If set views files will be deleted' .
			"\n<comment>(choises: " . implode(',', array('view_file_name*', 'all')) . ")</comment>"
		));
		return $parser->description('Clear cache');
	}

	/**
	 * Clear cache
	 */
	protected function _clearCache() {
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
	 * Clear views
	 */
	protected function _clearViews() {
		if ($this->args[0] === 'all') {
			$patterns = array('*');
		} else {
			$patterns = array_map('trim', explode(',', $this->args[0]));
		}

		foreach ($patterns as $pattern) {
			$files = glob(TMP . 'cache' . DS . 'views' . DS . basename($pattern));
			foreach ($files as $file) {
				if (!is_file($file)) {
					continue;
				}

				if (unlink($file)) {
					$this->out("View: $file deleted");
				} else {
					$this->err("View: $file NOT deleted!");
				}
			}
		}
	}

}
