<?php
namespace SwaggerGen;

use SwaggerGen\SwaggerModel;

interface SwaggerClient {

	/**
	 * @param SwaggerRequest $request
	 * @param SwaggerModel $response_model
	 * @return SwaggerModel
	 */
	public function send(SwaggerRequest $request, SwaggerModel $response_model);
}
