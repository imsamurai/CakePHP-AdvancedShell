<?php

/**
 * Author: imsamurai <im.samuray@gmail.com>
 * Date: 31.03.2014
 * Time: 16:10:05
 * Format: http://book.cakephp.org/2.0/en/development/testing.html
 */
require_once dirname(__FILE__) . DS . 'tasks.php';
App::uses('ShellDispatcher', 'Console');
App::uses('ConsoleOutput', 'Console');
App::uses('ConsoleInput', 'Console');

/**
 * AdvancedShellTest
 * 
 * @property string $out Console output
 * @property AdvancedShell $Shell AdvancedShell
 */
class AdvancedShellTest extends CakeTestCase {

	/**
	 * Fixtures
	 *
	 * @var array
	 */
	public $fixtures = array(
		'plugin.AdvancedShell.AdvancedShellModel'
	);

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
				'AdvancedShell', array('createFile', '_stop', '_checkUnitTest'), array($Output, $Output, $Input)
		);
	}

	/**
	 * Test statistics output
	 */
	public function testStatistics() {
		$this->Shell->statisticsStart('test');
		$this->Shell->statisticsEnd('test');

		debug($this->out);

		$this->assertRegExp('/Took: \d+d \d+h \d+m \d+s/i', $this->out);
		$this->assertRegExp('/Memory: \d+\.\d{3}Mb max used/i', $this->out);
	}

	/**
	 * Test sqldump output
	 * 
	 * @param int $debugLevel
	 * @param bool $dumpVisible
	 * 
	 * @dataProvider sqlDumpProvider
	 */
	public function testSqlDump($debugLevel, $dumpVisible) {
		ClassRegistry::init('AdvancedShellModel')->find('all');
		Configure::write('debug', $debugLevel);
		$this->Shell->sqlDump();

		debug($this->out);

		$this->assertSame($dumpVisible, !empty($this->out));
		if ($dumpVisible) {
			$this->assertTrue(strpos($this->out, 'Source:') !== false);
		}
	}

	/**
	 * Data provider for testSqlDump
	 * 
	 * @return array
	 */
	public function sqlDumpProvider() {
		return array(
			//set #0
			array(
				//debugLevel
				0,
				//dumpVisible
				false
			),
			//set #1
			array(
				//debugLevel
				1,
				//dumpVisible
				false
			),
			//set #2
			array(
				//debugLevel
				2,
				//dumpVisible
				true
			),
			//set #3
			array(
				//debugLevel
				3,
				//dumpVisible
				true
			),
		);
	}

	/**
	 * Test auto add subtask into option parser
	 */
	public function testAutoSubtasks() {
		$this->Shell->tasks = array('TestAdvancedShellTask');
		$this->Shell->TestAdvancedShellTask = new TestAdvancedShellTask($this->Shell->stdout, $this->Shell->stderr, $this->Shell->stdin);

		$this->assertRegExp('/ta_task  Test task help/i', $this->Shell->getOptionParser()->help());
		$this->assertSame(array('ta_task'), array_keys($this->Shell->getOptionParser()->subcommands()));
	}

}
