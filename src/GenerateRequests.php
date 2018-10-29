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

				$path_params = $this->handleParams($method_details, $class);

				$this->handlePathParams($path_params, $class);

				$this->handleGoodResponse($method_details, $class);

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

	/**
	 * @param $path_params
	 * @param $class
	 */
	private function handlePathParams(array $path_params, ClassType $class){
		if (empty($path_params)){
			return;
		}

		$constructor = $class->addMethod('__construct');
		$param_names = [];
		foreach ($path_params as $path_param){
			$param_name = $path_param['name'];
			$param_names[] = $param_name;
			$constructor->addParameter($param_name);
			$class->addProperty($param_name)
				->addComment($path_param['description'])
				->addComment('')
				->addComment("@var {$path_param['type']}");
			$constructor->addBody("\$this->$param_name = \$$param_name;");
		}

		if (!empty($param_names)){
			$class->addProperty('param_names')
				->setStatic(true)
				->setValue($param_names)
				->setVisibility('protected');
		}
	}

	/**
	 * @param array $method_details
	 * @param ClassType $class
	 *
	 * @return array
	 */
	protected function handleParams(array $method_details, ClassType $class){
		$parameters = $method_details['parameters'] ?: [];
		$path_params = [];
		foreach ($parameters as $parameter){
			switch ($parameter['in']){
				case 'path':
					$path_params[] = $parameter;
				break;
				case 'body':
					$this->handleBodyParam($parameter, $class);
				break;
			}
		}

		return $path_params;
	}

	/**
	 * @param string $dir
	 * @throws \Exception
	 */
	public function saveClasses(string $dir){
		$dir = $this->dirNamespace($dir, self::NAMESPACE_REQUEST);
		$this->saveClassesInternal($dir, $this->namespaceRequest());
	}

	/**
	 * @param string $dir
	 */
	public function dumpParentClass(string $dir){
		$dir = $this->dirNamespace($dir, self::NAMESPACE_REQUEST);
		$this->dumpParentInternal($dir, __DIR__.'/SwaggerRequest.php', $this->namespaceRequest());
		$this->dumpParentInternal($dir, __DIR__.'/SwaggerClient.php', $this->namespaceRequest(), $this->namespaceModel());
	}

	/**
	 * @param array $parameter
	 * @param ClassType $class
	 */
	private function handleBodyParam(array $parameter, ClassType $class){
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
	 * @throws \Exception
	 */
	protected function handleGoodResponse(array $method_details, ClassType $class){
		$response_models = [];

		$model_ns = $this->namespaceModel();
		$has_default = $has_2xx = false;
		foreach ($method_details['responses'] as $code_string => $method){
			$get_type_from = isset($method['$ref']) ? $method : $method['schema'];
			if (!is_null($get_type_from)){
				$type = $comment_type = $this->typeFromRef($get_type_from);
				$type = "\\$model_ns\\$type";
			}
			else {
				$type = '';
			}

			$type = str_replace("\\", "\\\\", $type);
			$response_models[] = "'$code_string' => '$type'";

			if ((int)$code_string>0 && (int)$code_string<400) {
				$has_2xx = true;
			}
		}

		if (!$has_2xx){
			throw new \Exception("Response blocks must contain at least one positive response type");
		}

		$response_models = implode(",\n\t", $response_models);
		$response_models = "[\n\t$response_models\n]";
		$class->addMethod('send')
			->addBody("return \$client->send(\$this, $response_models);")
			->addComment("@param SwaggerClient \$client")
			->addComment("@return string[]")
			->addParameter('client')
			->setTypeHint('SwaggerClient');
	}
}
