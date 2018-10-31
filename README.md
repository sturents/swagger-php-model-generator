# Swagger PHP Model Generator

Generates simple models with no dependencies from a Swagger/Open API 2.0 file

Install using composer

```sh
composer require sturents/swagger-php-model-generator
```

Run with:

```php

$generator = new SwaggerGen\GenerateAll('MyNamespace', '/path/to/api.yaml', '/path/to/library/src/');
echo $generator->saved_models.' - '.$generator->saved_requests;
```

Or run simply from this repo using:

```sh
php example.php --yaml-path /path/to/api.yaml --model-dir /path/to/generate/models --namespace MyNamespace
```
