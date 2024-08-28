<?php
namespace SwaggerGen;

use Psr\Http\Message\RequestInterface;
use SwaggerGen\SwaggerModel;

interface SwaggerClient {

	/**
	 * @template T of SwaggerModel
	 *
	 * @param array<array-key, class-string<T>|''> $response_models
	 * @return T|list<T>
	 */
	public function make(SwaggerRequest $swagger, array $response_models);

	public function messageFromRequest(SwaggerRequest $swagger):RequestInterface;
}
