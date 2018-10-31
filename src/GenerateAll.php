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
	 */
	public function __construct(string $namespace, string $yaml_path, string $dir){
		$generate_models = new GenerateModels($namespace);
		$this->saved_models = $generate_models->runFull($yaml_path, $dir);

		$generate_requests = new GenerateRequests($namespace);
		$this->saved_requests = $generate_requests->runFull($yaml_path, $dir);
	}
}
