<?php

/**
 * Author: imsamurai <im.samuray@gmail.com>
 * Date: 31.03.2014
 * Time: 16:12:58
 */
App::uses('AdvancedShell', 'AdvancedShell.Console/Command');
App::uses('AdvancedTask', 'AdvancedShell.Console');

class TestAdvancedShellTask extends AdvancedShell {

	public $name = 'ta_task';

	public function getOptionParser() {
		return parent::getOptionParser()
						->description('Test task help');
	}

}

//class TestAdvancedTask extends AdvancedTask {
//
//	public $name = 'ta_task';
//
//	public function getOptionParser() {
//		return parent::getOptionParser()
//						->description('Test task help');
//	}
//
//}
