<?php

/*
 * Configuration file
 */

$config['hostname'] = exec('hostname -f'); // use this hack to get the FQDN
$config['lib_path'] = _get_lib_path($config['hostname']);
$config['debug'] = true;
$config['io'] = 'en1';
$config['node_pattern'] = '/pi/';
$config['graphs'] = array('cpu','load','interface','temp','freq');
$config['os'] = 'Linux';

if($config['debug']) {
	error_reporting(E_ALL);
	ini_set('display_errors', 1);
}

function _get_lib_path($hostname) {
	if(file_exists('/var/lib/collectd/rrd/'.$hostname)) {
		return '/var/lib/collectd/rrd/'.$hostname.'/';
	} elseif(file_exists('/var/lib/collectd/'.$hostname)) {
		return '/var/lib/collectd/'.$hostname.'/';
	} elseif(file_exists('/var/lib/collectd/localhost')) {
		return '/var/lib/collectd/localhost/';
	} else {
		die('Cannot establish collectd directory');
	}
}
