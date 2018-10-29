# Swagger PHP Model Generator

Generates simple models with no dependencies from a Swagger/Open API 2.0 file

Install using composer

```sh
composer require sturents/swagger-php-model-generator
```

Run with:

```php

$generate_models = new SwaggerGen\GenerateModels('MyNamespace');
$generate_models->runFull('/path/to/api.yaml', '/path/to/library/src/');

$generate_requests = new SwaggerGen\GenerateRequests('MyNamespace', 'https://my-website.com/api/');
$generate_requests->runFull('/path/to/api.yaml', '/path/to/library/src/');
```

Or run simply from this repo using:

```sh
php example.php --yaml-path /path/to/api.yaml --model-dir /path/to/generate/models --namespace MyNamespace
```
