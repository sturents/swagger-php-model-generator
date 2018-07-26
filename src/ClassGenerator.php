<?php


namespace SwaggerGen;


use Nette\PhpGenerator\ClassType;

class ClassGenerator {
	const REQUEST_CLASS_NAME = 'SwaggerRequest';

	/**
	 * @var string
	 */
	protected $namespace_name;

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
			$use = '';
			if ($class_name===self::REQUEST_CLASS_NAME){
				$use = "\nuse Psr\\Http\\Message\\RequestInterface;\n";
			}

			$php_file = (string) $class;
			$php_file = "<?php\nnamespace $namespace_name;\n$use\n$php_file\n";
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
	protected function stringNotEndWith(string $string, string $char){
		return $string[strlen($string)-1]===$char ? substr($string, 0, -1): $string;
	}
}
