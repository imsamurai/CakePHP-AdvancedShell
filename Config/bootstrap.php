<?php

/**
 * Author: imsamurai <im.samuray@gmail.com>
 * Date: Dec 23, 2013
 * Time: 9:55:10 PM
 * Format: http://book.cakephp.org/2.0/en/views.html
 * 
 */
Configure::write('Task', array(
	'maxSlots' => 16,
	'timeout' => 60 * 60 * 8, //8 hours
	'dateFormat' => 'd.m.Y'
));
