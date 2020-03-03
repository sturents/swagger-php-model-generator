<?php
namespace SwaggerGen;

use SwaggerGen\SwaggerModel;

interface SwaggerClient {

	/**
	 * @param SwaggerRequest $request
	 * @param string[] $response_models
	 * @return SwaggerModel|SwaggerModel[]
	 */
	public function make(SwaggerRequest $request, array $response_models);
}
