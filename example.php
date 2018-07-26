<?php

require __DIR__.'/vendor/autoload.php';

use SwaggerGen\GenerateModels;
use SwaggerGen\GenerateRequests;

$opt_fields  = [
	"yaml-path",
	"namespace",
	"dir",
	"uri",
];
$options = getopt("", array_map(function($option){
	return "{$option}:";
}, $opt_fields));

$opt_errors = [];
foreach ($opt_fields as $opt_field){
	if (empty($options[$opt_field])){
		$opt_errors[] = "The option '--$opt_field' was not set";
	}
}
if (!empty($opt_errors)){
	throw new Exception("The following option errors were encountered: \n".implode("\n", $opt_errors)."\n");
}

echo "Generating models under namespace '{$options['namespace']}' from the YAML file at '{$options['yaml-path']}'\n";

$generator = new GenerateModels("{$options['namespace']}\\Models");
$generator->generate($options['yaml-path']);

$model_dir = "{$options['dir']}/Models";
echo "Saving ".count($generator->classes)." files to {$model_dir}\n";
$generator->saveClasses($model_dir);

$generator = new GenerateRequests("{$options['namespace']}\\Requests", $options['uri']);
$generator->generate($options['yaml-path']);

$request_dir = "{$options['dir']}/Requests";
echo "Saving ".count($generator->classes)." files to {$request_dir}\n";
$generator->saveClasses($request_dir);

echo "Done\n";
