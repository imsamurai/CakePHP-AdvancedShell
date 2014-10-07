<?php

/**
 * Author: imsamurai <im.samuray@gmail.com>
 * Date: 31.03.2014
 * Time: 16:12:58
 */

App::uses('AdvancedShell', 'AdvancedShell.Console/Command');
App::uses('AdvancedTask', 'AdvancedShell.Console');

/**
 * TestAdvancedShellTask
 */
class TestAdvancedShellTask extends AdvancedShell {

	/**
	 * Name
	 *
	 * @var string
	 */
	public $name = 'ta_task';

	/**
	 * {@inheritdoc}
	 * 
	 * @return ConsoleOptionParser
	 */
	public function getOptionParser() {
		return parent::getOptionParser()
						->description('Test task help');
	}

}
