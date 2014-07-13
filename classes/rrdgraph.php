<?php
/**
 * Collecd_Graph file
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
class Collectd_Graph {

	// default settings
	var $alpha = 0.25;	// defines how much colors should be 'lightened in the graph'

	// provide some default settings, these can all be overwritten in config.inc.php
	public function init() {
		$this->options = array(
			'print' => true, // whether to print additional information in the graph
		);
	}

	/**
	 *
	 * Common set of instructions for generating graphs
	 *
	 * This is a general function that is called every time a graph is being
	 * generated. Default instructions for rrd_graph are set here.
	 *
	 * @param    string 	$type type of graph, i.e. 'cpu', 'freq', 'temp'
	 * @param    string 	$loc location where to write the graph image to
	 * @param    array  	$options array of options
	 * @return   array  	array of instructions to parse to rrd_graph() function
	 *
	 */
	public function mkGraph($type, $loc, $options) {
		$lns = array();

		switch($type) {
			case 'cpu':
				$options = array_merge($options, array(
					'title' => gethostname().'::CPU utilization',
					'vlabel' => 'Jiffies',
					'upper-limit' => 100,
					'lower-limit' => 0,
					'rigid' => true,
					)
				);
			break;
			case 'interface':
				$options = array_merge($options, array(
					'title' => gethostname().'::Network interface',
					'vlabel' => 'bytes/sec',
					)
				);
			break;
			case 'temp':
				$options = array_merge($options, array(
					'title' => gethostname().'::Temperature (40-80 dC)',
					'vlabel' => 'dC',
					'upper-limit' => 80,
					'lower-limit' => 40,
					'rigid' => true,
					)
				);
			break;
			case 'load':
				$options = array_merge($options, array(
					'title' => gethostname().'::Load average',
					'vlabel' => 'load',
					'lower-limit' => 0,
					)
				);
			break;
			case 'freq':
				$options = array_merge($options, array(
					'title' => gethostname().'::CPU frequency',
					'vlabel' => 'load',
					'lower-limit' => 700,
					)
				);
			break;
		}

		// generate default part
		$lns = array_merge($lns, $this->_common_graph($options));
		
		switch($type) {
			case 'cpu':
				$lns = array_merge($lns, $this->_graph_cpu($options));
			break;
			case 'interface':
				$lns = array_merge($lns, $this->_graph_interface($options));
			break;
			case 'temp':
				$lns = array_merge($lns, $this->_graph_temperature($options));
			break;
			case 'load':
				$lns = array_merge($lns, $this->_graph_load($options));
			break;
			case 'freq':
				$lns = array_merge($lns, $this->_graph_freq($options));
			break;
		}
		if(!rrd_graph($loc, $lns)) {
			echo rrd_error();
		}
	}

	public function set($options = array()) {
		foreach($options as $key => $option) {
			$this->options[$key] = $option;
		}
	}

	/**
	 *
	 * Common set of instructions for generating graphs
	 *
	 * This is a general function that is called every time a graph is being
	 * generated. Default instructions for rrd_graph are set here.
	 *
	 * @param    array  $options array of options
	 * @return   array  array of instructions to parse to rrd_graph() function
	 *
	 */
	private function _common_graph($options) {
		$lns = array(
			'--end','now',
			'--start','end-'.$options['endtime'],
			'--font','TITLE:7:',
			'-t '.$options['title'],
			'--units-length','5',
			//'--vertical-label', $options['vlabel'],
		);
		if($options['thumbnail']) {
			$lns = array_merge($lns, array(
				'-w 100',
				'-h 35',
				'--only-graph',
			));
		} else {
			$lns = array_merge($lns, array(
				'-w '.$this->options['width'],
				'-h '.$this->options['height']
			));
		}
		if(isset($options['upper-limit'])) {
			$lns = array_merge($lns, array('--upper-limit', $options['upper-limit']));
		}
		if(isset($options['lower-limit'])) {
			$lns = array_merge($lns, array('--lower-limit', $options['lower-limit']));
		}
		if(isset($options['rigid'])) {
			$lns = array_merge($lns, array('--rigid'));
		}
		return $lns;
	}

	/**
	 *
	 * Generate a cpu states graph
	 *
	 * @param    array  $options array of options
	 * @return   array  array of instructions to parse to rrd_graph() function
	 *
	 */
	private function _graph_cpu($options) {
		// default options
		$this->options = array_merge($this->options, array(
			'vlabel' => 'Jiffies',
			'endtime' => (1*3600*1),
			)
		);
		// overrides
		$this->set($options);

		// for a reason that eludes me, on Mac OS X,  not all cpu states are inventorized,
		// therefore generate a somewhat different graph when on Mac OS X...
		// I have set the colors in such a way that these are consistent
		$ds_linux = array('idle','nice','user','wait','system','softirq','interrupt','steal');
		$ds_mac = array('idle','nice','user','system');
		if(PHP_OS == "Darwin") {
			$ds = $ds_mac;
			$colorfg1 = "e8e8e8";
			$colorfg2 = "00e000";
			$colorfg3 = "0000ff";
			$colorfg4 = "ff0000";
		} else {
			$ds = $ds_linux;
			$colorfg1 = "e8e8e8";
			$colorfg2 = "00e000";
			$colorfg3 = "0000ff";
			$colorfg4 = "ffb000";
			$colorfg5 = "ff0000";
			$colorfg6 = "ff00ff";
			$colorfg7 = "a000a0";
			$colorfg8 = "000000";
		}

		$dn = $this->options['dn'].'cpu-0/';

		for($i=1; $i<=count($ds); $i++) {
			${'colorbg'.$i} = $this->_lighten(${'colorfg'.$i});
		}

		$format = '%4.0lf %s';

		$lns = array();
		for($i=1; $i<=count($ds); $i++) {
			$lns[] = 'DEF:min'.$i.'='.$dn.'cpu-'.$ds[$i-1].'.rrd:value:MIN';
			$lns[] = 'DEF:avg'.$i.'='.$dn.'cpu-'.$ds[$i-1].'.rrd:value:AVERAGE';
			$lns[] = 'DEF:max'.$i.'='.$dn.'cpu-'.$ds[$i-1].'.rrd:value:MAX';
		}

		$lns[] = 'CDEF:cdef'.count($ds).'=avg'.count($ds).',UN,0,avg'.count($ds).',IF';
		for($i=count($ds)-1; $i>=1;$i--) {
			$lns[] = 'CDEF:cdef'.$i.'=avg'.$i.',UN,0,avg'.$i.',IF,cdef'.($i+1).',+';
		}

		//redefine $ds names to make them the same size
		for($i=0; $i<count($ds); $i++) {
			for($j=strlen($ds[$i]); $j<10; $j++) {
				$ds[$i].= ' ';
			}
		}

		for($i=1; $i<=count($ds); $i++) {
			$lns[] = 'AREA:cdef'.$i.'#'.${'colorbg'.$i};
			$lns[] = 'LINE1:cdef'.$i.'#'.${'colorfg'.$i}.($this->options['print'] ? ':'.$ds[$i-1] : '');
			if($this->options['print']) {
				$lns[] = 'GPRINT:min'.$i.':MIN:'.$format.' Min';
				$lns[] = 'GPRINT:avg'.$i.':AVERAGE:'.$format.' Avg';
				$lns[] = 'GPRINT:max'.$i.':MAX:'.$format.' Max';
				$lns[] = 'GPRINT:avg'.$i.':LAST:'.$format.' Last \n';
			}
		}

		return $lns;
	}

	/**
	 *
	 * Generate a load average graph
	 *
	 * @param    array  $options array of options
	 * @return   array  array of instructions to parse to rrd_graph() function
	 *
	 */
	private function _graph_load($options) {
		$this->options = array_merge($this->options, array(
			)
		);
		$this->set($options);

		$filename = $this->options['dn'].'interface-'.$this->options['interface'].'/if_octets.rrd';

		$green = "00ff00";
		$red = "ff0000";
		$blue = "0000ff";
		$faded_green = $this->_lighten($green);
		$faded_blue = $this->_lighten($blue);
		$faded_red = $this->_lighten($red);

		$filename = $this->options['dn'].'load/load.rrd';
		$name = 'Core temperature';

		$lns[] = 'DEF:s_min='.$filename.':shortterm:MIN';
		$lns[] = 'DEF:s_avg='.$filename.':shortterm:AVERAGE';
		$lns[] = 'DEF:s_max='.$filename.':shortterm:MAX';
		$lns[] = 'DEF:m_min='.$filename.':midterm:MIN';
		$lns[] = 'DEF:m_avg='.$filename.':midterm:AVERAGE';
		$lns[] = 'DEF:m_max='.$filename.':midterm:MAX';
		$lns[] = 'DEF:l_min='.$filename.':longterm:MIN';
		$lns[] = 'DEF:l_avg='.$filename.':longterm:AVERAGE';
		$lns[] = 'DEF:l_max='.$filename.':longterm:MAX';
		$lns[] = 'AREA:s_max#'.$faded_green;
		$lns[] = 'LINE1:s_avg#'.$green.($this->options['print'] ? ':1 min' : '');
		if($this->options['print']) {
			$lns[] = 'GPRINT:s_min:MIN:%.2lf Min';
			$lns[] = 'GPRINT:s_avg:AVERAGE:%.2lf Avg';
			$lns[] = 'GPRINT:s_max:MAX:%.2lf Max';
			$lns[] = 'GPRINT:s_avg:LAST:%.2lf Last\n';
		}
		$lns[] = 'LINE1:m_avg#'.$blue.($this->options['print'] ? ':5 min' : '');
		if($this->options['print']) {
			$lns[] = 'GPRINT:m_min:MIN:%.2lf Min';
			$lns[] = 'GPRINT:m_avg:AVERAGE:%.2lf Avg';
			$lns[] = 'GPRINT:m_max:MAX:%.2lf Max';
			$lns[] = 'GPRINT:m_avg:LAST:%.2lf Last\n';
		}
		$lns[] = 'LINE1:l_avg#'.$red.($this->options['print'] ? ':15 min' : '');
		if($this->options['print']) {
			$lns[] = 'GPRINT:l_min:MIN:%.2lf Min';
			$lns[] = 'GPRINT:l_avg:AVERAGE:%.2lf Avg';
			$lns[] = 'GPRINT:l_max:MAX:%.2lf Max';
			$lns[] = 'GPRINT:l_avg:LAST:%.2lf Last\n';
		}
		return $lns;
	}

	/**
	 *
	 * Generate a (network) interface graph
	 *
	 * @param    array  $options array of options
	 * @return   array  array of instructions to parse to rrd_graph() function
	 *
	 */
	private function _graph_interface($options) {
		$this->options = array_merge($this->options, array(
			'title' => 'Network Traffic Eth0',
			'vlabel' => 'Bytes / second',
			'endtime' => (1*3600*1),
			)
		);
		$this->set($options);

		$rx_color_fg = "0000ff";
		$tx_color_fg = "00b000";

		$rx_color_bg = $this->_lighten($rx_color_fg);
		$tx_color_bg = $this->_lighten($tx_color_fg);

		$rx_ds = 'rx';
		$tx_ds = 'tx';
		$factor = 1;

		$overlap_color = "efefff";

		$rx_ds_name = "Incoming";
		$tx_ds_name = "Outgoing";

		$format = "%5.1lf%s";

		$filename = $this->options['dn'].'interface-'.$this->options['interface'].'/if_octets.rrd';

		$lns[] = 'DEF:min_rx_raw='.$filename.':'.$rx_ds.':MIN';
		$lns[] = 'DEF:avg_rx_raw='.$filename.':'.$rx_ds.':AVERAGE';
		$lns[] = 'DEF:max_rx_raw='.$filename.':'.$rx_ds.':MAX';
		$lns[] = 'DEF:min_tx_raw='.$filename.':'.$tx_ds.':MIN';
		$lns[] = 'DEF:avg_tx_raw='.$filename.':'.$tx_ds.':AVERAGE';
		$lns[] = 'DEF:max_tx_raw='.$filename.':'.$tx_ds.':MAX';
		$lns[] = 'CDEF:min_rx=min_rx_raw,'.$factor.',*';
		$lns[] = 'CDEF:avg_rx=avg_rx_raw,'.$factor.',*';
		$lns[] = 'CDEF:max_rx=max_rx_raw,'.$factor.',*';
		$lns[] = 'CDEF:min_tx=min_tx_raw,'.$factor.',*';
		$lns[] = 'CDEF:avg_tx=avg_tx_raw,'.$factor.',*';
		$lns[] = 'CDEF:max_tx=max_tx_raw,'.$factor.',*';
		$lns[] = 'CDEF:avg_rx_bytes=avg_rx,8,*';
		$lns[] = 'VDEF:global_min_rx=min_rx,MINIMUM';
		$lns[] = 'VDEF:global_avg_rx=avg_rx,AVERAGE';
		$lns[] = 'VDEF:global_max_rx=max_rx,MAXIMUM';
		$lns[] = 'VDEF:global_tot_rx=avg_rx_bytes,TOTAL';
		$lns[] = 'CDEF:avg_tx_bytes=avg_tx,8,*';
		$lns[] = 'VDEF:global_min_tx=min_tx,MINIMUM';
		$lns[] = 'VDEF:global_avg_tx=avg_tx,AVERAGE';
		$lns[] = 'VDEF:global_max_tx=max_tx,MAXIMUM';
		$lns[] = 'VDEF:global_tot_tx=avg_tx_bytes,TOTAL';
		$lns[] = 'CDEF:overlap=avg_rx,avg_tx,LT,avg_rx,avg_tx,IF';
		$lns[] = 'AREA:avg_rx#'.$rx_color_bg;
		$lns[] = 'AREA:avg_tx#'.$tx_color_bg;
		$lns[] = 'AREA:overlap#'.$overlap_color;

		$lns[] = 'LINE1:avg_rx#'.$rx_color_fg.($this->options['print'] ? ':'.$rx_ds_name : '');
		if($this->options['print']) {
			$lns[] = 'GPRINT:global_min_rx:'.$format.' Min';
			$lns[] = 'GPRINT:global_avg_rx:'.$format.' Avg';
			$lns[] = 'GPRINT:global_max_rx:'.$format.' Max';
			$lns[] = 'GPRINT:global_tot_rx:ca. '.$format.' Total\n';
		}
		
		$lns[] = 'LINE1:avg_tx#'.$tx_color_fg.($this->options['print'] ? ':'.$tx_ds_name : '');
		if($this->options['print']) {
			$lns[] = 'GPRINT:global_min_tx:'.$format.' Min';
			$lns[] = 'GPRINT:global_avg_tx:'.$format.' Avg';
			$lns[] = 'GPRINT:global_max_tx:'.$format.' Max';
			$lns[] = 'GPRINT:global_tot_tx:ca. '.$format.' Total\n';
		}

		return $lns;
	}

	/**
	 *
	 * Generate a temperature graph (for the Raspberry PI)
	 *
	 * @param    array  $options array of options
	 * @return   array  array of instructions to parse to rrd_graph() function
	 *
	 */
	private function _graph_temperature($options) {
		$this->options = array_merge($this->options, array(
			'title' => 'Core temperature',
			'vlabel' => 'dC',
			'endtime' => (1*3600*1),
			)
		);
		$this->set($options);

		$fg_green = "00ff00";
		$bg_green = $this->_lighten($fg_green);
		$fg_orange = "ffb000";
		$bg_orange = $this->_lighten($fg_orange);
		$fg_red = "ff0000";
		$bg_red = $this->_lighten($fg_red);

		$low = 50;
		$high = 70;

		$format = "%5.1lf%s";

		$filename = $this->options['dn'].'table-thermal/gauge-pi.rrd';
		$name = 'Core temperature';

		$lns[] = 'DEF:min='.$filename.':value:MIN';
		$lns[] = 'DEF:avg='.$filename.':value:AVERAGE';
		$lns[] = 'DEF:max='.$filename.':value:MAX';
		$lns[] = 'CDEF:minc=min,1000,/';
		$lns[] = 'CDEF:avgc=avg,1000,/';
		$lns[] = 'CDEF:maxc=max,1000,/';
		$lns[] = 'CDEF:ds_red=maxc,'.$high.',GT,maxc,UNKN,IF';
		$lns[] = 'CDEF:ds_orange=maxc,'.$low.',GT,maxc,'.$high.',GT,'.$high.',maxc,IF,UNKN,IF';
		$lns[] = 'CDEF:ds_green=maxc,'.$low.',GT,'.$low.',maxc,IF';
		$lns[] = 'AREA:ds_red#'.$bg_red;
		$lns[] = 'LINE1:ds_red#'.$fg_red.($this->options['print'] ? ':'.$name : '');
		$lns[] = 'AREA:ds_orange#'.$bg_orange;
		$lns[] = 'LINE1:ds_orange#'.$fg_orange;
		$lns[] = 'AREA:ds_green#'.$bg_green;
		$lns[] = 'LINE1:ds_green#'.$fg_green;
		if($this->options['print']) {
			$lns[] = 'GPRINT:minc:MIN:'.$format.' Min';
			$lns[] = 'GPRINT:avgc:AVERAGE:'.$format.' Avg';
			$lns[] = 'GPRINT:maxc:MAX:'.$format.' Max';
		}
		return $lns;
	}

	/**
	 *
	 * Generate a frequency graph (for the Raspberry PI)
	 *
	 * @param    array  $options array of options
	 * @return   array  array of instructions to parse to rrd_graph() function
	 *
	 */
	private function _graph_freq($options) {
		$this->options = array_merge($this->options, array(
			'title' => 'Core temperature',
			'vlabel' => 'dC',
			'endtime' => (1*3600*1),
			)
		);
		$this->set($options);

		$fg_green = "00ff00";
		$bg_green = $this->_lighten($fg_green);
		$fg_orange = "ffb000";
		$bg_orange = $this->_lighten($fg_orange);
		$fg_red = "ff0000";
		$bg_red = $this->_lighten($fg_red);

		$low = 800;
		$high = 950;

		$format = "%5.1lf%s";

		$filename = $this->options['dn'].'table-frequency/gauge-pi.rrd';
		$name = 'Core temperature';

		$lns[] = 'DEF:min='.$filename.':value:MIN';
		$lns[] = 'DEF:avg='.$filename.':value:AVERAGE';
		$lns[] = 'DEF:max='.$filename.':value:MAX';
		$lns[] = 'CDEF:minc=min,1000,/';
		$lns[] = 'CDEF:avgc=avg,1000,/';
		$lns[] = 'CDEF:maxc=max,1000,/';
		$lns[] = 'CDEF:ds_red=maxc,'.$high.',GT,maxc,UNKN,IF';
		$lns[] = 'CDEF:ds_orange=maxc,'.$low.',GT,maxc,'.$high.',GT,'.$high.',maxc,IF,UNKN,IF';
		$lns[] = 'CDEF:ds_green=maxc,'.$low.',GT,'.$low.',maxc,IF';
		$lns[] = 'AREA:ds_red#'.$bg_red;
		$lns[] = 'LINE1:ds_red#'.$fg_red.($this->options['print'] ? ':'.$name : '');
		$lns[] = 'AREA:ds_orange#'.$bg_orange;
		$lns[] = 'LINE1:ds_orange#'.$fg_orange;
		$lns[] = 'AREA:ds_green#'.$bg_green;
		$lns[] = 'LINE1:ds_green#'.$fg_green;
		if($this->options['print']) {
			$lns[] = 'GPRINT:minc:MIN:'.$format.' Min';
			$lns[] = 'GPRINT:avgc:AVERAGE:'.$format.' Avg';
			$lns[] = 'GPRINT:maxc:MAX:'.$format.' Max';
		}
		return $lns;
	}

	/**
	 *
	 * Generate a 'lightened' version of a color
	 *
	 * @param    string  $rgb an RGB color index (without the '#')
	 * @return   string  an RGB color index of the 'lightened' color
	 *
	 */
	private function _lighten($rgb) {
		if(strlen($rgb) != 6) {
			die('Invalid color code encountered');
		}

		for($i=0; $i<3; $i++) {
			$c[$i] = hexdec(substr($rgb,$i*2,2));
			$cc[$i] = dechex($this->alpha * $c[$i] + (1 - $this->alpha) * 255);
		}

		return $cc[0].$cc[1].$cc[2];
	}
}
