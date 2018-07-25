<?php
namespace SwaggerGen;

use Nette\PhpGenerator\ClassType;
use Nette\PhpGenerator\Method;
use Nette\PhpGenerator\PhpNamespace;
use Nette\PhpGenerator\Property;
use Symfony\Component\Serializer\NameConverter\CamelCaseToSnakeCaseNameConverter;
use Symfony\Component\Yaml\Yaml;

class GenerateModels {
	/**
	 * @var string
	 */
	private $namespace_name;

	/**
	 * @var ClassType[]
	 */
	public $classes = [];

	/**
	 * Creates a generator for a specific namespace
	 *
	 * @param string $namespace_name
	 */
	public function __construct(string $namespace_name){
		$this->namespace_name = $this->stringNotEndWith($namespace_name, '\\');
	}

	/**
	 * Generates classes in the "classes" field
	 *
	 * @param string $file_path
	 */
	public function generate(string $file_path){
		$namespace_name = $this->namespace_name;

		$api = Yaml::parseFile($file_path);

		$namespace = new PhpNamespace($namespace_name);

		foreach ($api['definitions'] as $class_name => $class_details){

			$class = new ClassType($class_name, $namespace);

			if (!empty($class_details['description'])){
				$class->addComment($class_details['description']);
			}

			if (isset($class_details['allOf'])){
				$parent_class_name = $this->typeFromRef($class_details['allOf'][0]);
				$class->setExtends("$namespace_name\\$parent_class_name");
				$properties = $class_details['allOf'][1]['properties'];
			}
			else {
				$properties = $class_details['properties'];
			}

			$this->classProperties($properties, $class);

			$this->classes[$class_name] = $class;
		}
	}

	/**
	 * @param array $properties
	 * @param ClassType $class
	 */
	private function classProperties(array $properties, ClassType $class){
		$converter = new CamelCaseToSnakeCaseNameConverter;
		$namespace_name = $this->namespace_name;

		foreach ($properties as $property_name => $property_details){
			if (isset($property_details['$ref'])){
				$type = $this->typeFromRef($property_details);
				$typehint = "$namespace_name\\$type";
			}
			else {
				$type = $property_details['type'];
				$typehint = $type;
			}

			/**
			 * @var Property $property
			 */
			$property = $class->addProperty($property_name)->setVisibility('private');
			$property->addComment($property_details['description'] ?? "\n");

			if ($type==='array'){
				$sub_type = $this->typeFromRef($property_details['items']);
				$comment_type = "{$sub_type}[]";
				if (isset($property_details['items']['$ref'])){
					$sub_typehint = "$namespace_name\\$sub_type";
				}
				else {
					$sub_typehint = $sub_type;
				}
			}
			else {
				$comment_type = $type;
				$sub_type  = $sub_typehint = '';
			}

			$property->addComment("@var $comment_type");

			$capital_case = $converter->denormalize($property_name);#
			$capital_case = ucfirst($capital_case);
			$class->addMethod('get'.$capital_case)
				->setBody("return \$this->$property_name;")
				->addComment("@return $comment_type");
			/**
			 * @var Method $setter
			 */
			$setter = $class->addMethod('set'.$capital_case)
				->setBody("\$this->$property_name = \$$property_name;\n\nreturn \$this;")
				->addComment("@param $comment_type \$$property_name")
				->addComment('')
				->addComment("@return \$this");

			$set_parameter = $setter->addParameter($property_name);
			if ($this->notScalarType($type)){
				$set_parameter->setTypeHint($typehint);
			}

			if ($sub_type){
				$property_name_singular = $this->stringNotEndWith($property_name, 's');
				$capital_case_singular = $this->stringNotEndWith($capital_case, 's');
				/**
				 * @var Method $add_to
				 */
				$add_to = $class->addMethod('add'.$capital_case_singular)->setBody("\$this->{$property_name}[] = \$$property_name_singular;\n\nreturn \$this;")->addComment("@param $sub_type \$$property_name_singular")->addComment('')->addComment("@return \$this");

				$set_parameter = $add_to->addParameter($property_name_singular);
				if ($this->notScalarType($sub_type)){
					$set_parameter->setTypeHint($sub_typehint);
				}
			}
		}
	}

	/**
	 * Saves generated classes down as PHP files
	 *
	 * @param string $dir
	 * @throws \Exception
	 */
	public function saveClasses(string $dir){
		if (empty($this->classes)){
			throw new \Exception("No classes were created, try running the generate() method first");
		}

		if (!file_exists($dir)){
			mkdir($dir, 0775, true);
		}
		if (!file_exists($dir)){
			throw new \Exception("The directory $dir did not exist and could not be created");
		}

		$dir = $this->stringNotEndWith($dir, '/');

		$namespace_name = $this->namespace_name;

		foreach ($this->classes as $class_name => $class){
			$php_file = (string) $class;
			$php_file = "<?php\n\nnamespace $namespace_name;\n\n$php_file\n";
			file_put_contents("{$dir}/{$class_name}.php", $php_file);
		}
	}

	/**
	 * Utility function
	 *
	 * @param string $string
	 * @param string $char
	 * @return string
	 */
	private function stringNotEndWith(string $string, string $char){
		return $string[strlen($string)-1]===$char ? substr($string, 0, -1): $string;
	}

	/**
	 * Changes a Swagger definition into a type
	 *
	 * @param array $property
	 * @return string
	 */
	private function typeFromRef(array $property){
		if (!isset($property['$ref'])){
			return $property['type'];
		}

		return str_replace('#/definitions/', '', $property['$ref']);
	}

	/**
	 * @param $type
	 * @return bool
	 */
	private function notScalarType($type){
		return !in_array($type, ['integer', 'string', 'boolean', 'number']);
	}
}
