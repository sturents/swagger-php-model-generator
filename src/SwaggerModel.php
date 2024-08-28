<?php
namespace SwaggerGen;

use DateTimeInterface;
use JsonSerializable;

class SwaggerModel implements JsonSerializable {
	public const DATE_FORMAT = 'Y-m-d H:i:s';

	private bool $_is_error = false;

	public function toArray(): array{
		$this->preOutput();

		$data = [];
		foreach (get_object_vars($this) as $key => $val){
			if (strpos($key, '_')===0){
				continue;
			}

			$data[$key] = $val;
		}

		return $this->toArrayData($data);
	}

	/**
	 * Allows changing the data format before serializing
	 */
	protected function preOutput(): void{
	}

	/**
	 * @return array
	 */
	#[\ReturnTypeWillChange]
	public function jsonSerialize(){
		return $this->toArray();
	}

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

	public function isError(): bool{
		return $this->_is_error;
	}

	public function asError(): void{
		$this->_is_error = true;
	}
}
