<?php
namespace SwaggerGen;

use Nette\PhpGenerator\ClassType;
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

		$model_ns = $this->namespaceModel();
		foreach ($api['paths'] as $path => $path_details){
			$path_no_params = $this->pathNoParams($path);
			$path_camel = $this->pathToCamelCase($path_no_params);
			foreach ($path_details as $method => $method_details){

				$class_name = ucfirst($method).$path_camel;
				$class = new ClassType($class_name);
				$class->setExtends(self::REQUEST_CLASS_NAME);
				$class->addComment($method_details['summary']);
				$class->addConstant('URI', "{$this->base_uri}/{$path_no_params}");
				$class->addConstant('METHOD', strtoupper($method));

				$method_details['parameters'] = $method_details['parameters'] ?: [];
				$path_params = [];
				foreach ($method_details['parameters'] as $parameter){
					switch ($parameter['in']){
						case 'path':
							$path_params[] = $parameter;
						break;
						case 'body':
							$schema = $parameter['schema'];
							if (!empty($schema)){
								$type = $this->typeFromRef($schema);
								$name = strtolower($parameter['name']);
								$model_class = "\\$model_ns\\$type";
								$class->addMethod('setBody')->addComment("@param $model_class \$$name")->setBody("\$this->body = json_encode(\$$name);")->addParameter($name)->setTypeHint($model_class);
							}
						break;
					}
				}

				if (!empty($path_params)){
					$constructor = $class->addMethod('__construct');
					$param_names = [];
					foreach ($path_params as $path_param){
						$param_name = $path_param['name'];
						$param_names[] = $param_name;
						$constructor->addParameter($param_name);
						$class->addProperty($param_name)->addComment($path_param['description'])->addComment('')->addComment("@var {$path_param['type']}");
						$constructor->addBody("\$this->$param_name = \$$param_name;");
					}

					if (!empty($param_names)){
						$class->addProperty('param_names')
							->setStatic(true)
							->setValue($param_names)
							->setVisibility('protected');
					}
				}

				$this->classes[$class_name] = $class;
			}
		}
	}

	public function saveClasses(string $dir){
		$this->saveClassesInternal($dir, $this->namespaceRequest());
	}

	/**
	 * @param string $dir
	 */
	public function dumpParentClass(string $dir){
		$file = __DIR__.'/SwaggerRequest.php';
		$namespace = $this->namespaceRequest();
		$this->dumpParentInternal($dir, $file, $namespace);
	}

	/**
	 * @param string $path
	 *
	 * @return string
	 */
	private function pathToCamelCase(string $path){
		$path = str_replace('/', '-', $path);

		$path = explode('-', $path);
		foreach ($path as &$item){
			$item = ucfirst($item);
		}
		$path = implode('', $path);

		return $path;
	}

	/**
	 * @param string $path
	 * @return array
	 */
	private function pathNoParams(string $path){
		$path = substr($path, 1);

		$path = explode('/', $path);
		$path_no_params = [];
		foreach ($path as $item){
			if (strpos($item, '{')!==false){
				continue;
			}
			$path_no_params[] = $item;
		}
		$path = implode('/', $path_no_params);

		return $path;
	}
}
