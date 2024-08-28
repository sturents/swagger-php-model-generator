<?php

require __DIR__.'/vendor/autoload.php';

use SwaggerGen\GenerateAll;

$opt_fields  = [
	"yaml-path",
	"namespace",
	"dir",
	"more-specificity",
];
$options = getopt("", array_map(static fn($option) => "{$option}:", $opt_fields));

$opt_errors = [];
foreach ($opt_fields as $opt_field){
	if (empty($options[$opt_field])){
		$opt_errors[] = "The option '--$opt_field' was not set";
	}
}
if (!empty($opt_errors)){
	throw new Exception("The following option errors were encountered: \n".implode("\n", $opt_errors)."\n");
}

echo "Generating models under namespace '{$options['namespace']}' from the YAML file at '{$options['yaml-path']}', will save to {$options['dir']}\n";

$generator = new GenerateAll($options['namespace'], $options['yaml-path'], $options['dir'], $options['more-specificity']==='true');

echo "Saved ".$generator->saved_models." model classes\n";
echo "Saved ".$generator->saved_requests." request classes\n";

echo "Done\n";
