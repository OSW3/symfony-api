# API

Add simple API to Symfony projects

## Install
<!-- 
### Add the private repository

```json
"repositories": [
    { "type": "vcs", "url": "https://github.com/OSW3/symfony-api" }
],
``` 
-->

### Instal the bundle

```shell
comoser require osw3/symfony-api
```

### Prepare for update

In your composer.json file, change the line of the dependency to prepare futures updates of the bundle.

```json
"osw3/symfony-api": "*",
```

### Enable the bundle

Add the bundle in the `config/bundle.php` file.

```php
return [
    OSW3\SymfonyApi\OSW3SymfonyApiBundle::class => ['all' => true],
];
```

## Usage

### Enable the router

Add the bundle route to the `config/routes.yaml` file.

```yaml
_symfony_api:
    resource: '@OSW3SymfonyApiBundle/Resources/config/routes.yaml'
```

### Configure the bundle

```yaml
osw3_symfony_api:

    my_api_v1:
        version: 1
        collections:
            all_books:
                class: App\Entity\Book
                privileges:
                    public:
                        granted: null #'PUBLIC_ACCESS'
                        methods: ['GET', 'POST', 'PUT', 'PATCH', 'DELETE']

```
