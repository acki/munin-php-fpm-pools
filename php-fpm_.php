#!/usr/bin/env php
<?php

/**
* @author n2j7
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
// php memory warning border
$php_mem_warn = getenv('phpmemwarn');
$php_mem_warn = ($php_mem_warn===false) ? '100000' : $php_mem_warn;// default value if not set in env
// php memory critical border
$php_mem_crit = getenv('phpmemcrit');
$php_mem_crit = ($php_mem_crit===false) ? '200000' : $php_mem_crit;// default value if not set in env

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
		'graph_vlabel RAM',
		'graph_category PHP',
		'graph_args --base 1024',
		'ram.label ram',
	),
	'memory_multi' => array(
		'graph_title PHP5-FPM Memory Usage',
		'graph_vlabel RAM',
		'graph_category PHP',
		'graph_args --base 1024',
	)
);

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
	break;

	case 'connections':
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
			foreach ($pools_mem as $pool_name => $value) {
				echo "ram_${pool_name}.label = ${value}\n";
				echo "ram_${pool_name}.draw = AREASTACK\n";
				echo "ram_${pool_name}.type = GAUGE\n";
				echo "ram_${pool_name}.warning = ${php_mem_warn}\n";
				echo "ram_${pool_name}.critical = ${php_mem_crit}\n";
				// @EXPLAIN: let munin choose color for areas
				//echo "ram_${pool_name}.colour = rrggbb\n";
				// @TODO: draw crit and warn lines once per graph
				//echo "ram_${pool_name}.line ${php_mem_crit}:ff0000:Critical\n";

			}
			exit(EXIT_OK);
		}

		if ($is_single_graph) {
			$val = isset($pools_mem[$rq_poolname]) ? $pools_mem[$rq_poolname] : 0;
			echo "ram.value = ${val}\n";
			exit(EXIT_OK);
		}
		else{
			foreach ($pools_mem as $pool_name => $value) {
				echo "ram_${pool_name} = ${value} RAM\n";
			}
			exit(EXIT_OK);
		}
		
	break;

	case 'processes':
	break;

	case 'connections':
	break;

	default:
		fwrite(STDERR, "Wrong script MODE specified\n");
		exit(EXIT_WRONG_MODE);
	break;
}