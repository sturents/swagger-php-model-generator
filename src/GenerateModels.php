<?php
namespace SwaggerGen;

use Exception;
use Nette\PhpGenerator\ClassType;
use Nette\PhpGenerator\Method;
use Nette\PhpGenerator\PhpNamespace;
use Nette\PhpGenerator\Property;
use Symfony\Component\Serializer\NameConverter\CamelCaseToSnakeCaseNameConverter;
use Symfony\Component\Yaml\Yaml;

class GenerateModels extends ClassGenerator {

	/**
	 * Generates classes in the "classes" field
	 *
	 * @param string $file_path
	 */
	public function generate(string $file_path) :void{
		$namespace_name = $this->namespaceModel();

		$api = Yaml::parseFile($file_path);

		$namespace = new PhpNamespace($namespace_name);

		foreach ($api['definitions'] as $class_name => $class_details){

			$class = new ClassType($class_name, $namespace);
			$class->setExtends("$namespace_name\\".self::MODEL_CLASS_NAME);
			$class->addComment('** This file was generated automatically, you might want to avoid editing it **');

			if (!empty($class_details['description'])){
				$class->addComment("\n".$class_details['description']);
			}

			$required = $properties = null;
			if (isset($class_details['allOf'])){
				$parent_class_name = $this->typeFromRef($class_details['allOf'][0]);
				$class->setExtends("$namespace_name\\$parent_class_name");
				$required = $class_details['allOf'][1]['required'];
				$properties = $class_details['allOf'][1]['properties'];
			}
			else {
				$required = $class_details['required'];
				$properties = $class_details['properties'];
			}

			$this->classProperties($required ?? [], $class, true);
			$this->classProperties($properties ?? [], $class);

			$this->classes[$class_name] = $class;
		}
	}

	/**
	 * @param array $properties
	 * @param ClassType $class
	 * @param bool $required
	 * @throws Exception
	 */
	private function classProperties(array $properties, ClassType $class, bool $required = false): void{
		$converter = new CamelCaseToSnakeCaseNameConverter;
		$namespace_name = $this->namespaceModel();

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
			$property = $class->addProperty($property_name)->setVisibility('protected');
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
				$sub_type = $sub_typehint = '';
			}
			if ($comment_type==='number'){
				$comment_type = 'float';
			}

			$property->addComment("@var $comment_type");

			if ($required){
				$property->addComment('@required');
			}
			else {
				$this->defaultValue($property, $type);
			}

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
				->addComment('@return $this');

			$set_parameter = $setter->addParameter($property_name);
			if ($this->notScalarType($type)){
				$set_parameter->setTypeHint($typehint);
			}

			if ($sub_type){
				$property_name_singular = $this->unPlural($property_name);
				$capital_case_singular = $this->unPlural($capital_case);
				/**
				 * @var Method $add_to
				 */
				$add_to = $class->addMethod('add'.$capital_case_singular)
					->setBody("\$this->{$property_name}[] = \$$property_name_singular;\n\nreturn \$this;")
					->addComment("@param $sub_type \$$property_name_singular")
					->addComment('')
					->addComment('@return $this');

				$set_parameter = $add_to->addParameter($property_name_singular);
				if ($this->notScalarType($sub_type)){
					$set_parameter->setTypeHint($sub_typehint);
				}
			}
		}
	}

	/**
	 * @param string $dir
	 * @throws Exception
	 */
	public function saveClasses(string $dir) :void{
		$dir = $this->dirNamespace($dir, self::NAMESPACE_MODEL);
		$this->saveClassesInternal($dir, $this->namespaceModel());
	}

	/**
	 * @param string $dir
	 * @throws Exception
	 */
	public function dumpParentClass(string $dir) :void{
		$dir = $this->dirNamespace($dir, self::NAMESPACE_MODEL);
		$this->dumpParentInternal($dir, __DIR__.'/SwaggerModel.php', $this->namespaceModel());
	}

	/**
	 * @param Property $property
	 * @param string $type
	 * @throws Exception
	 */
	private function defaultValue(Property $property, string $type): void{
		if ($this->notScalarType($type)){
			return;
		}

		switch ($type){
			case 'string':
				$property->setValue('');
			break;
			case 'integer':
				$property->setValue(0);
			break;
			case 'number':
				$property->setValue(0.0);
			break;
			case 'boolean':
				$property->setValue(false);
			break;
			default:
				throw new Exception("The property with name {$property->getName()} and type $type was not recognised to set a default value");
		}
	}
}
