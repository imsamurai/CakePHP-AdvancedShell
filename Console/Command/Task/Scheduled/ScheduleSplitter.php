<?php

/**
 * Author: imsamurai <im.samuray@gmail.com>
 * Date: 18.06.2013
 * Time: 23:00:39
 */

/**
 * @package AdvancedShell
 * @subpackage Scheduled
 */
abstract class ScheduleSplitter {

	/**
	 * Options for splitting
	 *
	 * @var array
	 */
	protected $_options = null;

	/**
	 * Inner splitter
	 *
	 * @var ScheduleSplitter
	 */
	protected $_InnerSplitter = null;

	/**
	 * Constructor
	 *
	 * @param array $options
	 * @param ScheduleSplitter $InnerSplitter
	 */
	public function __construct(array $options = array(), ScheduleSplitter $InnerSplitter = null) {
		$this->_options = $options;
		$this->_InnerSplitter = $InnerSplitter;
	}

	/**
	 * Returns iterator over arguments splitted by inner splitter
	 *
	 * @param array $arguments Script arguments
	 *
	 * @return Iterator
	 */
	public function splitInner(array $arguments = array()) {
		if (is_null($this->_InnerSplitter)) {
			return new ArrayIterator(array($arguments));
		}
		return $this->_InnerSplitter->split($arguments);
	}

	/**
	 * Returns iterator over arguments
	 *
	 * @param array $arguments Script arguments
	 *
	 * @return Iterator
	 */
	abstract public function split(array $arguments = array());
}
