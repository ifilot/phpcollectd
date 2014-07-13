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

// import the configuration file
if (!file_exists('config.inc.php'))
  throw new Exception ('config.inc.php does not exist, please create one');
else {
  require_once('config.inc.php'); 
}
// import the Collectd_graph class
if (!file_exists('classes/rrdgraph.php'))
  throw new Exception ('classes/rrdgraph.php does not exist, there is probably something wrong with your installation');
else {
  require_once('classes/rrdgraph.php'); 
}

// initialize class and set general properties
$options = array(
	'dn' => $config['lib_path'],
	'interface' => $config['io'],
	'print' => $config['print'],
	'width' => $config['width'],
	'height' => $config['height'],
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