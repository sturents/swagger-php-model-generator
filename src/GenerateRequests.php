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
	 * @throws InvalidArgumentException
	 */
	public function generate(string $file_path, bool $more_specificity = false): void{
		$api = Yaml::parseFile($file_path);
		$base_uri = self::stringNotEndWith($api['basePath'], '/');
		foreach ($api['paths'] as $path => $path_details){
			$path_no_params = $adapted_path = $this->pathNoParams($path);
			if ($more_specificity){
				$adapted_path = $this->pathWithParamsNoPlaceholders($path);
			}
			$path_camel = $this->pathToCamelCase($adapted_path);
			foreach ($path_details as $method => $method_details){
				$class_name = ucfirst($method).$path_camel;
				$class = new ClassType($class_name);
				$class->setExtends(self::REQUEST_CLASS_NAME);
				$class->addComment($method_details['summary']);
				$class->addConstant('METHOD', strtoupper($method));
				$class->addConstant('URI', $base_uri ? "$base_uri/$path_no_params" : $path_no_params);

				$this->handleParams($method_details, $class);

				$this->handleResponse($method_details, $class);

				$this->classes[$class_name] = $class;
			}
		}
	}

	private function pathToCamelCase(string $path): string{
		$path = str_replace('/', '-', $path);

		$path_arr = explode('-', $path);
		foreach ($path_arr as &$item){
			$item = ucfirst($item);
		}
		unset($item);

		return implode('', $path_arr);
	}

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

		return implode('/', $path_no_params);
	}

	private function pathWithParamsNoPlaceholders(string $path): string{
		$path = substr($path, 1);

		$parts = explode('/', $path);
		$path_with_params = [];
		foreach ($parts as $item){
			$item = str_replace(['{', '}'], '', $item);
			$path_with_params[] = $item;
		}

		return implode('/', $path_with_params);
	}

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
			$is_nullable = ($path_param['nullable'] ?? null)===true;
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
				->setStatic()
				->setValue($param_names)
				->setType('array')
				->setVisibility('protected');
		}
	}

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
			$is_nullable = ($path_param['nullable'] ?? null)===true;
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
			catch (InvalidArgumentException $e) {
				$query_params_property = $class->addProperty('query_params')
					->setStatic()
					->setType('array')
					->setVisibility('protected');
				$query_params_array = [];
			}
			$value = array_merge($query_params_array, $param_names);
			$query_params_property->setStatic()
				->setValue($value)
				->setType('array')
				->setVisibility('protected');
		}
	}

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
	 * @throws RuntimeException
	 * @throws FileNotFoundException
	 */
	public function saveClasses(string $dir): void{
		$dir = $this->dirNamespace($dir, self::NAMESPACE_REQUEST);
		$use_ns = $this->namespaceModel();
		$use = "use $use_ns\\SwaggerModel;\n";
		$this->saveClassesInternal($dir, $this->namespaceRequest(), $use);
	}

	/**
	 * @throws FileNotFoundException
	 */
	public function dumpParentClass(string $dir): void{
		$dir = $this->dirNamespace($dir, self::NAMESPACE_REQUEST);
		$this->dumpParentInternal($dir, __DIR__.'/SwaggerRequest.php', $this->namespaceRequest());
		$this->dumpParentInternal($dir, __DIR__.'/SwaggerClient.php', $this->namespaceRequest(), $this->namespaceModel());
	}

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
	 * @throws InvalidArgumentException
	 */
	protected function handleResponse(array $method_details, ClassType $class): void{
		$response_models = [];

		$model_ns = $this->namespaceModel();
		$has_2xx = false;
		$list_types = [];
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
					$class_string = "\\$model_ns\\$type";
					$type = "$class_string::class";
					$list_types[] = $class_string;
				}
				else {
					$type = "''";
				}
			}
			else {
				$type = "''";
			}

			$response_models[] = "'$code_string' => $type";

			if ((int)$code_string>0 && (int)$code_string<400){
				$has_2xx = true;
			}
		}

		if (!$has_2xx){
			throw new InvalidArgumentException('Response blocks must contain at least one positive response type');
		}

		$list_types = array_unique($list_types);
		if (!$list_types){
			$list_types = ['SwaggerModel'];
		}
		foreach ($list_types as $list_type){
			$list_types[] = "list<$list_type>";
		}

		$response_models = implode(",\n\t", $response_models);
		$response_models = "[\n\t$response_models\n]";
		$class->addMethod('sendWith')
			->addBody("return \$client->make(\$this, $response_models);")
			->addComment('@return '.implode('|', $list_types))
			->addParameter('client')
			->setType('SwaggerClient');
	}
}
