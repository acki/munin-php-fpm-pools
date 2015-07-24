#!/usr/bin/env php
<?php

/**
* @author n2j7
* @doc_link http://munin-monitoring.org/wiki/protocol-config
* @doc_link http://munin-monitoring.org/wiki/PluginConcise
* @doc_link http://munin-monitoring.org/wiki/HowToWritePlugins
*/

define('EXIT_OK', 0);
define('EXIT_WRONG_MODE', 3);
define('EXIT_CMD_ERROR', 4);

$match_result = preg_match('/.*php-fpm_([^_]+)_?([^_]+)?$/', $argv[0], $settings);
if (!$match_result) {
	fwrite(STDERR, "Wrong script name used\n");
	exit(2);
}

$is_config_requested = ($argc == 2 && $argv[1]=='config');
$is_single_graph = (count($settings)==3);
// php binary file
$php_bin = getenv('phpbin');
$php_bin = ($php_bin===false) ? 'php-fpm' : $php_bin;// default value if not set in env

$query = null;
$rq_poolname = null;
$mode = null;
if ($is_single_graph) {
	list($query, $rq_poolname, $mode) = $settings;
}
else{
	list($query, $mode) = $settings;
}

$configs = array(
	'memory' => array(
		'graph_title PHP5-FPM Memory Usage',
		'graph_vlabel RAM Mb',
		'graph_category PHP',
		'graph_args --base 1024',
		'ram.label RAM',
	),
	'memory_multi' => array(
		'graph_title PHP5-FPM Memory Usage',
		'graph_vlabel RAM Mb',
		'graph_category PHP',
		'graph_args --base 1024',
	),
	'connections' => array(
		'graph_title PHP5-FPM Accepted Connections',
		'graph_vlabel Connections',
		'graph_category PHP',
		'graph_args --base 1000 -l 0',
		'conn.label Accepted',
		'conn.draw AREA',
		'conn.type DERIVE',
		'conn.min 0',
	),
	'connections_multi' => array(
		'graph_title PHP5-FPM Accepted Connections',
		'graph_vlabel Connections',
		'graph_category PHP',
		'graph_args --base 1000 -l 0',
	),
	'processes' => array(
		'graph_title PHP5-FPM Processes Count',
		'graph_vlabel Processes',
		'graph_category PHP',
		'graph_args --base 1000 -l 0',
		'proc.label Pool',
	),
	'processes_multi' => array(
		'graph_title PHP5-FPM Processes Count',
		'graph_vlabel Processes',
		'graph_category PHP',
		'graph_args --base 1000 -l 0',
	),
	'status' => array(
		'graph_title PHP5-FPM Processes Statuses',
		'graph_vlabel Processes',
		'graph_category PHP',
		'graph_args --base 1000 -l 0',
		'actv.label Accepted',
		'actv.draw AREASTACK',
		'actv.type GAUGE',
		'actv.min 0',
		'idle.label Iddle',
		'idle.draw AREASTACK',
		'idle.type GAUGE',
		'idle.min 0',
		'summ.label Total',
		'summ.draw LINE2',
		'summ.type GAUGE',
		'summ.min 0',
	),
	'status_multi' => array(
		'graph_title PHP5-FPM Processes Statuses',
		'graph_vlabel Processes',
		'graph_category PHP',
		'graph_args --base 1000 -l 0',
		'actv.label Accepted',
		'actv.draw LINE2',
		'actv.type GAUGE',
		'actv.min 0',
		'idle.label Iddle',
		'idle.draw LINE2',
		'idle.type GAUGE',
		'idle.min 0',
		'summ.label Total',
		'summ.draw AREA',
		'summ.type GAUGE',
		'summ.min 0',
	),
	'average' => array(
		'graph_title PHP5-FPM Average Process Memory Usage',
		'graph_vlabel RAM Mb',
		'graph_category PHP',
		'graph_args --base 1024',
		'avg.label RAM',
	),
	'average_multi' => array(
		'graph_title PHP5-FPM Average Process Memory Usage',
		'graph_vlabel RAM Mb',
		'graph_category PHP',
		'graph_args --base 1024',
	),
);

function getDefinedPools() {
	$pools_cnt = (int) getenv('fpmpoolscount');
	if ($pools_cnt==0) {
		return array();
	}

	$pools = array();
	for ($i=0; $i < $pools_cnt; $i++) { 
		$name = getenv('fpmpool_' . $i . '_name');
		$url = getenv('fpmpool_' . $i . '_url');
		$pools[$name] = $url;
	}

	return $pools;
}

// print config
if ($is_config_requested) {
	$cfg_mode = $mode . ((!$is_single_graph) ? '_multi' : '');
	if (!array_key_exists($mode, $configs)) {
		fwrite(STDERR, "Wrong script MODE specified\n");
		exit(EXIT_WRONG_MODE);
	}

	if ($is_single_graph){
		foreach ($configs[$mode] as $line) {
			echo $line . "\n";
		}
		exit(EXIT_OK);
	}
	else{
		foreach ($configs[$mode.'_multi'] as $line) {
			echo $line . "\n";
		}
	}
}

// print data
switch($mode) {
	case 'average':
		$ps_output = null;
		exec("ps -eo rss,command | grep ${php_bin} | grep -v grep", $ps_output);
		$pools_mem = array();
		$pools_cnt = array();
		if (!is_array($ps_output)) {
			fwrite(STDERR, "Can't build and execute correct command\n");
			exit(EXIT_WRONG_MODE);
		}
		foreach ($ps_output as $line) {
			//split fields
			$line = trim($line);
			$line_parts = explode(' ', $line);
			//$line_parts = preg_split('/\s+/', $line);
			if (strpos($line_parts[1], $php_bin) === false) {
				// exclude wrong processes
				continue;
			}

			list($mem, $proc_name, $type, $pool_name) = $line_parts;

			// skip master process
			if ($type == 'master') {
				continue;
			}

			// dots are depricated by munin
			// (due to they are used for splitting structures)
			// The characters must be [a-zA-Z0-9_]
			$pool_name = str_replace('.', '_', $pool_name);

			// for single graph skip others
			if ($is_single_graph && $pool_name != $rq_poolname) {
				continue;
			}
			

			if (!array_key_exists($pool_name, $pools_mem)){
				$pools_mem[$pool_name] = $mem/1024;
			}
			else {
				$pools_mem[$pool_name] += $mem/1024;
			}

			if (!array_key_exists($pool_name, $pools_cnt)){
				$pools_cnt[$pool_name] = 1;
			}
			else {
				$pools_cnt[$pool_name]++;
			}
		}

		// configure our pools
		if ($is_config_requested) {
			foreach ($pools_mem as $pool_name => $value) {
				echo "avg_${pool_name}.label ${pool_name}\n";
				echo "avg_${pool_name}.draw LINE2\n";
				echo "avg_${pool_name}.type GAUGE\n";
			}
			exit(EXIT_OK);
		}
		// sort by keys for preventing color changes for a time
		ksort($pools_mem);

		if ($is_single_graph) {
			$mem = isset($pools_mem[$rq_poolname]) ? $pools_mem[$rq_poolname] : 0;
			$cnt = isset($pools_cnt[$rq_poolname]) ? $pools_cnt[$rq_poolname] : 1;
			$val = $mem/$cnt;
			echo "avg.value ${val}\n";
			exit(EXIT_OK);
		}
		else{
			foreach ($pools_mem as $pool_name => $mem) {
				$cnt = isset($pools_cnt[$rq_poolname]) ? $pools_cnt[$rq_poolname] : 1;
				$value = $mem / $cnt;
				echo "avg_${pool_name}.value ${value}\n";
			}
			exit(EXIT_OK);
		}

	break;

	case 'connections':
		$responses = array();
		$pools = getDefinedPools();

		// configure our pools
		if ($is_config_requested) {
			foreach ($pools as $pool_name => $value) {
				echo "conn_${pool_name}.label ${pool_name}\n";
				echo "conn_${pool_name}.draw LINE1\n";
				echo "conn_${pool_name}.type DERIVE\n";
				echo "conn_${pool_name}.min 0\n";
			}
			exit(EXIT_OK);
		}

		foreach ($pools as $name => $url) {
			if ($is_single_graph && $name != $rq_poolname){
				continue;
			}

			$resp = file_get_contents($url);
			if ($resp===false) {
				fwrite(STDERR, "Can't get stats info for ${name} from ${url}\n");
				continue;
			}
			$responses[$name] = json_decode($resp, true);
		}

		if ($is_single_graph) {
			$val = isset($responses[$rq_poolname]) ? $responses[$rq_poolname]['accepted conn'] : 0;
			echo "conn.value ${val}\n";
			exit(EXIT_OK);
		}
		else{
			foreach ($responses as $pool_name => $data) {
				$value = $data['accepted conn'];
				echo "conn_${pool_name}.value ${value}\n";
			}
			exit(EXIT_OK);
		}
	break;

	case 'memory':
		$ps_output = null;
		exec("ps -eo rss,command | grep ${php_bin} | grep -v grep", $ps_output);
		$pools_mem = array();
		if (!is_array($ps_output)) {
			fwrite(STDERR, "Can't build and execute correct command\n");
			exit(EXIT_WRONG_MODE);
		}
		foreach ($ps_output as $line) {
			//split fields
			$line = trim($line);
			$line_parts = explode(' ', $line);
			//$line_parts = preg_split('/\s+/', $line);
			if (strpos($line_parts[1], $php_bin) === false) {
				// exclude wrong processes
				continue;
			}

			list($mem, $proc_name, $type, $pool_name) = $line_parts;

			// skip master process
			if ($type == 'master') {
				continue;
			}

			// dots are depricated by munin
			// (due to they are used for splitting structures)
			// The characters must be [a-zA-Z0-9_]
			$pool_name = str_replace('.', '_', $pool_name);

			// for single graph skip others
			if ($is_single_graph && $pool_name != $rq_poolname) {
				continue;
			}
			

			if (!array_key_exists($pool_name, $pools_mem)){
				$pools_mem[$pool_name] = $mem/1024;
			}
			else {
				$pools_mem[$pool_name] += $mem/1024;
			}
		}

		// configure our pools
		if ($is_config_requested) {
			// php memory warning border
			$php_mem_warn = getenv('phpmemwarn');
			$php_mem_warn = ($php_mem_warn===false) ? '100' : $php_mem_warn;// default value in Mb if not set in env

			// php memory critical border
			$php_mem_crit = getenv('phpmemcrit');
			$php_mem_crit = ($php_mem_crit===false) ? '200' : $php_mem_crit;// default value in Mb if not set in env

			foreach ($pools_mem as $pool_name => $value) {
				echo "ram_${pool_name}.label ${pool_name}\n";
				echo "ram_${pool_name}.draw AREASTACK\n";
				echo "ram_${pool_name}.type GAUGE\n";
				echo "ram_${pool_name}.warning ${php_mem_warn}\n";
				echo "ram_${pool_name}.critical ${php_mem_crit}\n";
				// @EXPLAIN: let munin choose color for areas
				//echo "ram_${pool_name}.colour = rrggbb\n";
				// @TODO: draw crit and warn lines once per graph
				//echo "ram_${pool_name}.line ${php_mem_crit}:ff0000:Critical\n";

			}
			exit(EXIT_OK);
		}
		// sort by keys for preventing color changes for a time
		ksort($pools_mem);

		if ($is_single_graph) {
			$val = isset($pools_mem[$rq_poolname]) ? $pools_mem[$rq_poolname] : 0;
			echo "ram.value ${val}\n";
			exit(EXIT_OK);
		}
		else{
			foreach ($pools_mem as $pool_name => $value) {
				echo "ram_${pool_name}.value ${value}\n";
			}
			exit(EXIT_OK);
		}
		
	break;

	case 'processes':
		$ps_output = null;
		exec("ps -eo command | grep ${php_bin} | grep -v grep", $ps_output);
		$pools_proc = array();
		if (!is_array($ps_output)) {
			fwrite(STDERR, "Can't build and execute correct command\n");
			exit(EXIT_WRONG_MODE);
		}

		foreach ($ps_output as $line) {
			//split fields
			$line = trim($line);
			$line_parts = explode(' ', $line);
			//$line_parts = preg_split('/\s+/', $line);
			if (strpos($line_parts[0], $php_bin) === false) {
				// exclude wrong processes
				continue;
			}

			list($proc_name, $type, $pool_name) = $line_parts;

			// skip master process
			if ($type == 'master') {
				continue;
			}

			// dots are depricated by munin
			// (due to they are used for splitting structures)
			// The characters must be [a-zA-Z0-9_]
			$pool_name = str_replace('.', '_', $pool_name);

			// for single graph skip others
			if ($is_single_graph && $pool_name != $rq_poolname) {
				continue;
			}

			if (!array_key_exists($pool_name, $pools_proc)){
				$pools_proc[$pool_name] = 1;
			}
			else {
				$pools_proc[$pool_name]++;
			}
		}

		// configure our pools
		if ($is_config_requested) {
			foreach ($pools_proc as $pool_name => $value) {
				echo "proc_${pool_name}.label ${pool_name}\n";
				echo "proc_${pool_name}.draw LINE1\n";
				echo "proc_${pool_name}.type GAUGE\n";
			}
			exit(EXIT_OK);
		}
		// sort by keys for preventing color changes for a time
		ksort($pools_proc);

		if ($is_single_graph) {
			$val = isset($pools_proc[$rq_poolname]) ? $pools_proc[$rq_poolname] : 0;
			echo "proc.value ${val}\n";
			exit(EXIT_OK);
		}
		else{
			foreach ($pools_proc as $pool_name => $value) {
				echo "proc_${pool_name}.value ${value}\n";
			}
			exit(EXIT_OK);
		}
	break;

	case 'status':
		$responses = array();
		$pools = getDefinedPools();

		// configure our pools
		if ($is_config_requested) {
			$order_pools = array();
			foreach ($pools as $pool_name => $value) {
				echo "actv_${pool_name}.label ${pool_name}\n";
				echo "actv_${pool_name}.draw AREASTACK\n";
				echo "actv_${pool_name}.type GAUGE\n";
				echo "actv_${pool_name}.min 0\n";
				array_push($order_pools, "actv_${pool_name}");
			}
			$pools_str = implode(' ', $order_pools);
			echo sprintf("graph_order summ %s actv idle\n", $pools_str);

			exit(EXIT_OK);
		}

		list($actv, $idle, $summ) = array(0,0,0); // default values

		foreach ($pools as $name => $url) {
			if ($is_single_graph && $name != $rq_poolname){
				continue;
			}

			$resp = file_get_contents($url);
			if ($resp===false) {
				fwrite(STDERR, "Can't get stats info for ${name} from ${url}\n");
				continue;
			}
			$responses[$name] = json_decode($resp, true);

			if (isset($responses[$name]['active processes'])) {
				$actv += (int)$responses[$name]['active processes'];
			}
			if (isset($responses[$name]['idle processes'])) {
				$idle += (int)$responses[$name]['idle processes'];
			}
			if (isset($responses[$name]['total processes'])) {
				$summ += (int)$responses[$name]['total processes'];
			}
		}

		if ($is_single_graph) {
			echo "actv.value ${actv}\n";
			echo "idle.value ${idle}\n";
			echo "summ.value ${summ}\n";
			exit(EXIT_OK);
		}
		else{
			echo "summ.value ${summ}\n";
			foreach ($responses as $pool_name => $data) {
				$value = $data['active processes'];
				echo "actv_${pool_name}.value ${value}\n";
			}
			echo "actv.value ${actv}\n";
			$idle = $actv + $idle;// second stack on single graph
			echo "idle.value ${idle}\n";
			exit(EXIT_OK);
		}
	break;

	default:
		fwrite(STDERR, "Wrong script MODE specified\n");
		exit(EXIT_WRONG_MODE);
	break;
}