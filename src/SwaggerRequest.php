<?php

namespace SwaggerGen;

use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\StreamInterface;
use Psr\Http\Message\UriInterface;

class SwaggerRequest {

	public const METHOD = '';
	public const URI = '';
	public const RESPONSE_CLASS = null;

	protected ?string $body = null;

	protected static array $path_params = [];
	protected static array $query_params = [];

	protected array $headers = [];

	public bool $response_is_array = false;

	final public function getMethod(): string{
		return static::METHOD;
	}

	final public function getUri(): string{
		return static::URI.$this->getPath();
	}

	final public function getBody(): ?string{
		return $this->body;
	}

	final public function getResponseClass(): ?string{
		return static::RESPONSE_CLASS;
	}

	public function getQuery(): array{
		$query = [];
		foreach (static::$query_params as $param_name){
			if (!is_null($this->{$param_name})){
				$query[$param_name] = $this->{$param_name};
			}
		}

		return $query;
	}

	public function getPath(): string{
		$query = [];
		foreach (static::$path_params as $param_name){
			$query[] = $this->{$param_name} ?? '';
		}
		if (count($query)>0){
			return '/'.implode('/', $query);
		}

		return '';
	}
}
