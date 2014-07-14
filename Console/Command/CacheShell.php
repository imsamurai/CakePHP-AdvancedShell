<?php

/**
 * Author: imsamurai <im.samuray@gmail.com>
 * Date: 14.07.2014
 * Time: 11:48:06
 */
App::uses('AdvancedShell', 'AdvancedShell.Console/Command');

/**
 * Cache shell
 * 
 * @package AdvancedShell
 * @subpackage Shell
 */
class CacheShell extends AdvancedShell {

	/**
	 * {@inheritdoc}
	 *
	 * @var array 
	 */
	public $tasks = array(
		'Clear' => array(
			'className' => 'AdvancedShell.CacheClear'
		),
	);

	/**
	 * {@inheritdoc}
	 *
	 * @var ConsoleOptionParser 
	 */
	public function getOptionParser() {
		$parser = parent::getOptionParser();
		return $parser->description('Manage cache');
	}
	
}
