<?php

namespace SwaggerGen;

use Nette\InvalidArgumentException;
use Nette\FileNotFoundException;
use Nette\PhpGenerator\ClassType;
use RuntimeException;
use Symfony\Component\Yaml\Yaml;

use function explode;
use function ucfirst;
use function strtoupper;
use function strtolower;
use function str_replace;
use function implode;
use function substr;
use function strpos;

class GenerateRequests extends ClassGenerator {

	/**
	 * @param string $file_path
	 * @param bool   $more_specificity
	 * @throws InvalidArgumentException
	 */
	public function generate(string $file_path,  bool $more_specificity = false): void{
		$api = Yaml::parseFile($file_path);
		$base_uri = $this->stringNotEndWith($api['basePath'], '/');
		foreach ($api['paths'] as $path => $path_details){
			if ($more_specificity){
				$adapted_path = $this->pathWithParamsNoPlaceholders($path);
			}
			else {
				$adapted_path = $this->pathNoParams($path);
			}
			$path_camel = $this->pathToCamelCase($adapted_path);
			foreach ($path_details as $method => $method_details){
				$class_name = ucfirst($method).$path_camel;
				$class = new ClassType($class_name);
				$class->setExtends(self::REQUEST_CLASS_NAME);
				$class->addComment($method_details['summary']);
				$uri = empty($base_uri) ? $path : "{$base_uri}/{$path}";
				$class->addConstant('URI', $uri);
				$class->addConstant('METHOD', strtoupper($method));

				$this->handleParams($method_details, $class);

				$this->handleResponse($method_details, $class);

				$this->classes[$class_name] = $class;
			}
		}
	}

	/**
	 * @param string $path
	 *
	 * @return string
	 */
	private function pathToCamelCase(string $path): string{
		$path = str_replace('/', '-', $path);

		$path_arr = explode('-', $path);
		foreach ($path_arr as &$item){
			$item = ucfirst($item);
		}
		unset($item);
		$path = implode('', $path_arr);

		return $path;
	}

	/**
	 * @param string $path
	 * @return string
	 */
	private function pathNoParams(string $path): string{
		$path = substr($path, 1);

		$path_arr = explode('/', $path);
		$path_no_params = [];
		foreach ($path_arr as $item){
			if (strpos($item, '{')!==false){
				continue;
			}
			$path_no_params[] = $item;
		}
		$path = implode('/', $path_no_params);

		return $path;
	}

	/**
	 * @param string $path
	 *
	 * @return string
	 */
	private function pathWithParamsNoPlaceholders(string $path){
		$path = substr($path, 1);

		$path = explode('/', $path);
		$path_with_params = [];
		foreach ($path as $item){
			$item = str_replace('{', '', $item);
			$item = str_replace('}', '', $item);
			$path_with_params[] = $item;
		}
		$path = implode('/', $path_with_params);

		return $path;
	}

	/**
	 * @param array $path_params
	 * @param ClassType $class
	 */
	private function handlePathParams(array $path_params, ClassType $class): void{
		if (empty($path_params)){
			return;
		}

		$constructor = $class->addMethod('__construct');
		$param_names = [];
		foreach ($path_params as $path_param){
			$param_name = $path_param['name'];
			$param_names[] = $param_name;
			if (isset($path_param['required'])){
				$constructor->addParameter($param_name);
			}
			else {
				$constructor->addParameter($param_name, null);
			}
            $type = $path_param['type'] ?? 'null';
            $is_nullable = ($path_param['nullable'] ?? null)==='true';
            if ($is_nullable && $type!=='null'){
                $type = "?{$type}";
            }
			$class->addProperty($param_name)
				->addComment($path_param['description'])
				->addComment('')
				->addComment("@var {$type}");
			$constructor->addBody("\$this->$param_name = \$$param_name;");
		}

		if (!empty($param_names)){
			$class->addProperty('path_params')
				->setStatic(true)
				->setValue($param_names)
				->setVisibility('protected');
		}
	}

	/**
	 * @param array $query_params
	 * @param ClassType $class
	 */
	private function handleQueryParams(array $query_params, ClassType $class): void{
		if (empty($query_params)){
			return;
		}

		$param_names = [];
		foreach ($query_params as $query_param){
			$param_name = $query_param['name'];
			$param_names[] = $param_name;

			if ($query_param['required']){
				$method = $class->hasMethod('__construct') ? $class->getMethod('__construct') : $class->addMethod('__construct');
			}
			else {
				$method = $class->addMethod('set'.$this->pathToCamelCase($query_param['name']));
			}

            $type = $path_param['type'] ?? 'null';
            $is_nullable = ($path_param['nullable'] ?? null)==='true';
            if ($is_nullable && $type!=='null'){
                $type = "?{$type}";
            }

			$method->addParameter($param_name);
			$class->addProperty($param_name)
				->addComment($query_param['description'])
				->addComment('')
				->addComment("@var {$type}");
			$method->addBody("\$this->$param_name = \$$param_name;");
		}

		if (!empty($param_names)){
			try {
				$query_params_property = $class->getProperty('query_params');
				$query_params_array = $query_params_property->getValue();
			}
			catch (InvalidArgumentException $e){
				$query_params_property = $class->addProperty('query_params')
					->setStatic(true)
					->setVisibility('protected');
				$query_params_array = [];
			}
			$value = array_merge($query_params_array, $param_names);
			$query_params_property->setStatic(true)
				->setValue($value)
				->setVisibility('protected');
		}
	}

	/**
	 * @param array $method_details
	 * @param ClassType $class
	 */
	protected function handleParams(array $method_details, ClassType $class): void{
		$parameters = $method_details['parameters'] ?? [];
		$path_params = $query_params = [];
		foreach ($parameters as $parameter){
			switch ($parameter['in']){
				case 'path':
					$path_params[] = $parameter;
				break;
				case 'query':
					$query_params[] = $parameter;
				break;
				case 'body':
					$this->handleBodyParam($parameter, $class);
				break;
			}
		}

		$this->handlePathParams($path_params, $class);
		$this->handleQueryParams($query_params, $class);
	}

	/**
	 * @param string $dir
	 * @throws RuntimeException
	 * @throws FileNotFoundException
	 */
	public function saveClasses(string $dir) :void{
		$dir = $this->dirNamespace($dir, self::NAMESPACE_REQUEST);
		$use_ns = $this->namespaceModel();
		$use = "use $use_ns\\SwaggerModel;\n";
		$this->saveClassesInternal($dir, $this->namespaceRequest(), $use);
	}

	/**
	 * @param string $dir
	 * @throws FileNotFoundException
	 */
	public function dumpParentClass(string $dir) :void{
		$dir = $this->dirNamespace($dir, self::NAMESPACE_REQUEST);
		$this->dumpParentInternal($dir, __DIR__.'/SwaggerRequest.php', $this->namespaceRequest());
		$this->dumpParentInternal($dir, __DIR__.'/SwaggerClient.php', $this->namespaceRequest(), $this->namespaceModel());
	}

	/**
	 * @param array $parameter
	 * @param ClassType $class
	 */
	private function handleBodyParam(array $parameter, ClassType $class): void{
		$schema = $parameter['schema'];
		if (empty($schema)){
			return;
		}

		$type = $this->typeFromRef($schema);
		$name = strtolower($parameter['name']);
		$model_ns = $this->namespaceModel();
		$model_class = "\\$model_ns\\$type";
		$class->addMethod('setBody')
			->addComment("@param $model_class \$$name")
			->setBody("\$this->body = json_encode(\$$name);")
			->addParameter($name)
			->setTypeHint($model_class);
	}

	/**
	 * @param array $method_details
	 * @param ClassType $class
	 * @throws InvalidArgumentException
	 */
	protected function handleResponse(array $method_details, ClassType $class): void{
		$response_models = [];

		$model_ns = $this->namespaceModel();
		$has_2xx = false;
		foreach ($method_details['responses'] as $code_string => $method){
			$get_type_from = isset($method['$ref']) ? $method : ($method['schema'] ?? null);
			if (!is_null($get_type_from)){
				if (($get_type_from['type'] ?? null)==='array'){
					$type = $this->typeFromRef($get_type_from['items']);
				}
				else {
					$type = $this->typeFromRef($get_type_from);
				}
				if ($this->notScalarType($type)){
					$type = "\\$model_ns\\$type::class";
				}
				else {
					$type = "''";
				}
			}
			else {
				$type = "''";
			}

			$response_models[] = "'$code_string' => '$type'";

			if ((int)$code_string>0 && (int)$code_string<400){
				$has_2xx = true;
			}
		}

		if (!$has_2xx){
			throw new InvalidArgumentException('Response blocks must contain at least one positive response type');
		}

		$response_models = implode(",\n\t", $response_models);
		$response_models = "[\n\t$response_models\n]";
		$class->addMethod('sendWith')
			->addBody("return \$client->make(\$this, $response_models);")
			->addComment('@param SwaggerClient $client')
			->addComment('@return SwaggerModel')
			->addParameter('client')
			->setTypeHint('SwaggerClient');
	}
}
