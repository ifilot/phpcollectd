<?php

require 'config.inc.php';		// include configuration file
require 'classes/rrdgraph.php';	// load the graph generating class

// initialize class and set general properties
$options = array(
	'dn' => $config['lib_path'],
	'print' => false,
);
$graph = new Collectd_Graph;
$graph->init();
$graph->set($options);

// set specific options
$options = array(
	'endtime' => (1*3600*1),
	'thumbnail' => false,
);
$graph->mkGraph('cpu', 'img/cpu.png', $options);
$graph->mkGraph('interface', 'img/interface.png', $options);
$graph->mkGraph('load', 'img/load.png', $options);
$graph->mkGraph('temp', 'img/temp.png', $options);
$graph->mkGraph('freq', 'img/freq.png', $options);

// set specific options and create thumbnails
$options = array(
	'endtime' => (1*3600*1),
	'thumbnail' => true,
);
$graph->mkGraph('cpu', 'img/cpu_thumb.png', $options);
$graph->mkGraph('interface', 'img/interface_thumb.png', $options);
$graph->mkGraph('load', 'img/load_thumb.png', $options);
$graph->mkGraph('temp', 'img/temp_thumb.png', $options);
$graph->mkGraph('freq', 'img/freq_thumb.png', $options);
