<?php

require_once "vendor/autoload.php";
$configs['database'] = (require "config/database.php");

use Illuminate\Database\Capsule\Manager as Capsule;

/*
 * Database
 */
$capsule = new Capsule;
$capsule->addConnection($configs['database']);

// Set the event dispatcher used by Eloquent models... (optional)
use Illuminate\Events\Dispatcher;
use Illuminate\Container\Container;
$capsule->setEventDispatcher(new Dispatcher(new Container));

// Make this Capsule instance available globally via static methods... (optional)
$capsule->setAsGlobal();

// Setup the Eloquent ORM... (optional; unless you've used setEventDispatcher())
$capsule->bootEloquent();

/*
 * Main
 */
if (!isset($argv[1])) {
    echo "No TLD specified!\n";
    exit;
}

$tld = $argv[1];
$nicClass = "\Deft\Nic\\".ucfirst($tld);

if (!class_exists($nicClass)) {
	echo "Invalid TLD or implementation not created!\n";
	exit;
}

$nic = new $nicClass($tld);