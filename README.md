# Symfony API

## Install

```shell
composer require osw3/symfony-api
```

## Add to Kernel

```php
return [
    OSW3\SymfonyApi\OSW3SymfonyApi::class => ['all' => true],
];
```

## Configure Route

```yaml
symfony_api:
    resource: '@OSW3SymfonyApi/Resources/config/routes.yaml'
```
