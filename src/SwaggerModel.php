<?php
namespace SwaggerGen;

use DateTimeInterface;
use JsonSerializable;

class SwaggerModel implements JsonSerializable {
	public const DATE_FORMAT = 'Y-m-d H:i:s';

	private $_is_error = false;

	/**
	 * @return array
	 */
	public function toArray(): array{
		$this->preOutput();

		$data = [];
		foreach (get_object_vars($this) as $key => $val){
			if (strpos($key, '_')===0){
				continue;
			}

			$data[$key] = $val;
		}

		$data = $this->toArrayData($data);

		return $data;
	}

	/**
	 * Allows changing the data format before serializing
	 */
	protected function preOutput(): void{
	}

	/**
	 * @return array
	 */
	public function jsonSerialize(){
		return $this->toArray();
	}

	/**
	 * @param array $data
	 *
	 * @return array
	 */
	private function toArrayData(array $data): array{
		foreach ($data as &$val){

			if (is_object($val)){
				if ($val instanceof self){
					$val = $val->toArray();
				}
				elseif ($val instanceof DateTimeInterface) {
					$val = $val->format(self::DATE_FORMAT);
				}
				else {
					$val = (array) $val;
				}
			}

			if (is_array($val)){
				$val = $this->toArrayData($val);
			}
		}

		return $data;
	}

	/**
	 * @return bool
	 */
	public function isError(): bool{
		return $this->_is_error;
	}

	public function asError(): void{
		$this->_is_error = true;
	}
}
