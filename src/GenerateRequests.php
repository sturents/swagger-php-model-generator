<?php
namespace SwaggerGen;

use Nette\PhpGenerator\ClassType;
use Psr\Http\Message\MessageInterface;
use Symfony\Component\Yaml\Yaml;

class GenerateRequests extends ClassGenerator {
	private $base_uri;

	/**
	 * GenerateRequests constructor.
	 * @param string $namespace_name
	 * @param string $base_uri
	 */
	public function __construct(string $namespace_name, string $base_uri){
		parent::__construct($namespace_name);
		$this->base_uri = $base_uri;
	}

	/**
	 * @param string $file_path
	 */
	public function generate(string $file_path){
		$api = Yaml::parseFile($file_path);

		foreach ($api['paths'] as $path => $path_details){
			$path = $this->pathToCamelCase($path);
			foreach ($path_details as $method => $method_details){

				$class_name = ucfirst($method).$path;
				$class = new ClassType($class_name);
				$class->addImplement("\\".MessageInterface::class);

				$this->classes[$class_name] = $class;
			}
		}
	}

	/**
	 * @param string $path
	 *
	 * @return string
	 */
	private function pathToCamelCase(string $path){
		$path = substr($path, 1);

		$path = explode('/', $path);
		$path_no_params = [];
		foreach ($path as $item){
			if (strpos($item, '{')!==false){
				continue;
			}
			$path_no_params[] = $item;
		}
		$path = implode('-', $path_no_params);

		$path = explode('-', $path);
		foreach ($path as &$item){
			$item = ucfirst($item);
		}
		$path = implode('', $path);

		return $path;
	}
}
