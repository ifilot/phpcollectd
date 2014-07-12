<?php
/**
 * run script
 *
 * This file gathers the collectd data and creates the graphs using rrdtool
 *
 * Requirements:
 * - PHP-rrdtool
 *
 * PHP 5
 *
 * PHPCollectd
 * Copyright (c), Ivo Filot
 *
 * Licensed under The MIT License
 * For full copyright and license information, please see the file LICENSE
 * Redistributions of files must retain the above copyright notice.
 *
 * @copyright     Copyright (c) Ivo Filot
 * @link          https://github.com/ifilot/phpcollectd
 * @license       MIT License (http://www.opensource.org/licenses/mit-license.php)
 */
require 'config.inc.php';		// include configuration file
require 'classes/rrdgraph.php';	// load the graph generating class

// initialize class and set general properties
$options = array(
	'dn' => $config['lib_path'],
	'print' => false,
);
// create a new Collectd_Graph instance
$graph = new Collectd_Graph;
$graph->init();
$graph->set($options);

// set specific options
$options = array(
	'endtime' => (1*3600*1), // show the past hour
	'thumbnail' => false,
);

// create the graphs
$graph->mkGraph('cpu', 'img/cpu.png', $options);
$graph->mkGraph('interface', 'img/interface.png', $options);
$graph->mkGraph('load', 'img/load.png', $options);
if($config['os'] == 'pi') {
	$graph->mkGraph('temp', 'img/temp.png', $options);
	$graph->mkGraph('freq', 'img/freq.png', $options);
}