#!/usr/bin/env php
<?php
/**
 * This file is distributed under the MIT Open Source
 * License. See README.MD for details.
 * @author Ioan CHIRIAC
 */

// reading command line args
$args = array();
for($i = 1; $i < $argc; $i++) {
    $arg = explode('=', ltrim($argv[$i], '-'), 2);
    if ( !empty($arg[0]) ) {
        $args[$arg[0]] = isset($arg[1]) ? $arg[1] : true;
    }
}
// Convert seconds to human readable text.
function sec2h($secs) {
    $units = array(
        "week"   => 7*24*3600,
        "day"    =>   24*3600,
        "hour"   =>      3600,
        "minute" =>        60,
        "second" =>         1,
    );
    // specifically handle zero
    if ( $secs == 0 ) return "0 seconds";
    $s = "";
    foreach ( $units as $name => $divisor ) {
            if ( $quot = intval($secs / $divisor) ) {
                    $s .= "$quot $name";
                    $s .= (abs($quot) > 1 ? "s" : "") . ", ";
                    $secs -= $quot * $divisor;
            }
    }
    return substr($s, 0, -2);
}

// header
echo <<<CLI
Redis Job Queue - by Ioan Chiriac (released under MIT license)
This script handles job queues and launches workers
Url : https://github.com/ichiriac/redis-job-queue


CLI;

// init some global vars
include( __DIR__ . '/../src/RedisJobQueue.php');
$pid = null;
$config = array();
// searching for php
$php_dir = 'php' . (
    strtoupper(substr(PHP_OS, 0, 3)) === 'WIN' ?
    '.exe' : null
);
if ( !empty($_SERVER['Path']) ) {
    $scan = explode(';', $_SERVER['Path']);
    foreach($scan as $dir) {
        if ( file_exists($dir . '/' . $php_dir) ) {
            $php_dir = $dir . '/' . $php_dir;
            break;
        }
    }
}
define('PHPBIN', $php_dir);
define('VERBOSE', isset($args['verbose']));
// handling options
foreach( $args as $cmd => $arg) {
    switch( strtolower($cmd) ) {
        // shows the help
        case 'help':
            include('cmd/help.php');
            exit(0);
            break;
        // loads the configuration
        case 'config':
            $config = include('cmd/config.php');
            if ( !empty($config['pid']) ) {
                if ( file_exists($config['pid']) ) {
                    $pid = file_get_contents($config['pid']);
                }
            } else {
                $args['cli'] = true;
            }
            break;
        // show some status information
        case 'status':
            include('cmd/status.php');
            exit(0);
            break;
        // restart as a daemon
        case 'restart':
            include('cmd/stop.php');
            include('cmd/start.php');
            exit(0);
            break;
        // stops the daemon
        case 'stop':
            if ( include('cmd/stop.php') ) {
                exit(0);
            } else {
                exit(1);
            }
            break;
        // run as daemon
        case 'start':
            include('cmd/start.php');
            break;
        // configuration flags (ignored)
        case 'cli':
        case 'verbose':
            break;
        // default : invalid command
        default:
            echo 'ERROR : Invalid command "'.$cmd.'" (use --help)' . "\n";
            exit(1);
    }
}
// bad exit
echo 'ERROR : No command found [start|stop|restart|status] (use --help)' . "\n";
exit(1);
