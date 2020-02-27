<?php

namespace SwaggerGen;

class GenerateAll {
	public $saved_models = 0;
	public $saved_requests = 0;

	/**
	 * Generates all required files into the specified directory
	 *
	 * @param string $namespace
	 * @param string $yaml_path
	 * @param string $dir
	 * @param bool   $more_specificity
	 */
	public function __construct(string $namespace, string $yaml_path, string $dir, bool $more_specificity = false){
		$generate_models = new GenerateModels($namespace);
		$this->saved_models = $generate_models->runFull($yaml_path, $dir);

		$generate_requests = new GenerateRequests($namespace);
		if ($more_specificity){
			$this->saved_requests = $generate_requests->runFullWithMoreSpecificity($yaml_path, $dir);
		}
		else {
			$this->saved_requests = $generate_requests->runFull($yaml_path, $dir);
		}
	}
}
