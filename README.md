# Swagger PHP Model Generator

Generates simple models with no dependencies from a Swagger/Open API 2.0 file

Install using composer

```sh
composer require sturents/swagger-php-model-generator
```

Run with:

```php

$generator = new SwaggerGen\GenerateModels('MyNamespace');
$generator->generate('/path/to/api.yaml');
$generator->saveClasses('/path/to/generate/models/');
```

Or run simply from this repo using:

```sh
php example.php --yaml-path /path/to/api.yaml --model-dir /path/to/generate/models --namespace MyNamespace
```
