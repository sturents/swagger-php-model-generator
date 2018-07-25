<?php

require __DIR__.'/vendor/autoload.php';

use SwaggerGen\GenerateModels;

$opt_fields  = [
	"yaml-path",     // Required value
	"namespace",     // Required value
	"model-dir",     // Required value
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

$generator = new GenerateModels($options['namespace']);
$generator->generate($options['yaml-path']);

echo "Saving ".count($generator->classes)." files to {$options['model-dir']}\n";
$generator->saveClasses($options['model-dir']);

echo "Done\n";
