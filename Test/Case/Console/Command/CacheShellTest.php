<?php

/**
 * Author: imsamurai <im.samuray@gmail.com>
 * Date: 14.07.2014
 * Time: 12:15:24
 * Format: http://book.cakephp.org/2.0/en/development/testing.html
 */
App::uses('ShellDispatcher', 'Console');
App::uses('ConsoleOutput', 'Console');
App::uses('ConsoleInput', 'Console');
App::uses('CacheShell', 'AdvancedShell.Console/Command');

/**
 * CacheShellTest
 * 
 * @property string $out Console output
 * @property CacheShell $Shell CacheShell
 */
class CacheShellTest extends CakeTestCase {

	/**
	 * {@inheritdoc}
	 */
	public function setUp() {
		parent::setUp();
		$this->out = '';
		$Output = $this->getMock('ConsoleOutput', array(
			'_write'
		));
		$Output->expects($this->any())
				->method('_write')
				->will($this->returnCallback(
								function($out) {
									$this->out .= $out;
								}
						)
		);

		$Input = $this->getMock('ConsoleInput', array(), array(), '', false);

		$this->Shell = $this->getMock(
				'CacheShell', array('createFile', '_stop', '_checkUnitTest'), array($Output, $Output, $Input)
		);
	}

	/**
	 * Test cache clear task
	 * 
	 * @param string $viewNames
	 * 
	 * @dataProvider clearProvider
	 */
	public function testClearViews($viewNames) {
		$views = array_map('trim', explode(',', $viewNames));
		$filePath = TMP . 'cache' . DS . 'views' . DS;
		foreach ($views as $view) {
			file_put_contents($filePath . $view, $view);
			$this->assertTrue(file_exists($filePath . $view));
		}
		$this->Shell->startup();
		$this->Shell->initialize();
		$this->Shell->runCommand('clear', array('clear', $viewNames, '--views'));

		foreach ($views as $view) {
			$this->assertFalse(file_exists($filePath . $view));
		}
		debug($this->out);
	}

	/**
	 * Test cache clear task
	 * 
	 * @param string $cacheNames
	 * 
	 * @dataProvider clearProvider
	 */
	public function testClear($cacheNames) {
		$cacheKey = 'CacheShellTest';
		$caches = array_map('trim', explode(',', $cacheNames));
		if ($caches[0] === 'all') {
			$caches = Cache::configured();
		}
		foreach ($caches as $cache) {
			Cache::write($cacheKey, true, $cache);
			$this->assertTrue(Cache::read($cacheKey, $cache));
		}
		$this->Shell->startup();
		$this->Shell->initialize();
		$this->Shell->runCommand('clear', array('clear', $cacheNames));

		foreach ($caches as $cache) {
			$this->assertFalse(Cache::read($cacheKey, $cache));
		}
		debug($this->out);
	}

	/**
	 * Data provider for testClear
	 * 
	 * @return array
	 */
	public function clearProvider() {
		return array(
			//set #0
			array(
				//cache names
				'all'
			),
			//set #1
			array(
				//cache names
				'default'
			),
			//set #2
			array(
				//cache names
				'_cake_model_,default'
			),
			//set #3
			array(
				//cache names
				'_cake_model_,  default'
			),
		);
	}

}
