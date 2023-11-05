# Symfony API

## Install

> composer.json

```json
"repositories": [
    { "type": "vcs", "url": "https://github.com/OSW3/symfony-api" }
],
```

```shell
composer require osw3/symfony-api
```

> composer.json

```json
"osw3/symfony-api": "*",
```

## Add to Kernel

```php
return [
    OSW3\SymfonyApi\OSW3SymfonyApiBundle::class => ['all' => true],
];
```

## Route

```yaml
symfony_api:
    resource: '@OSW3SymfonyApiBundle/Resources/config/routes.yaml'
```

## Config 

```yaml
osw3_symfony_api:

    ## Name of the API config
    ## --
    ## 
    my_api_v1:

        ## API Version
        ## --
        ## Define the value of the API version in the URI
        ## > e.g.: site.com/api/v1/...
        ##
        ## @var int
        ## @required
        ##
        version: 1

        ## Response Links settings
        ## --
        ##
        links:

            ## Links state
            ## --
            ## If true, add the entity link to the response
            ## 
            ## @var bool
            ## @default true
            ## 
            state: true

            ## Links absolute
            ## --
            ## If true, generate an absolute link
            ## 
            ## @var bool
            ## @default true
            ## 
            absolute: true

        ## Response pagination settings
        ## --
        ##
        pagination:

            ## Pagination state
            ## --
            ## If true, the response will paginate
            ## 
            ## @var bool
            ## @default true
            ## 
            state: true

            ## Items per page
            ## --
            ## Number of items per page
            ## 
            ## @var integer
            ## @default 10
            ## 
            item_per_page: 5

        ## Search by API settings
        ## --
        ##
        search:

            ## Is the search by the API is allowed
            ## --
            ## If true, the search is allowed
            ## 
            ## @var bool
            ## @default true
            ## 
            allowed: true

            ## The search parameter of the URL
            ## --
            ## e.g.: site.com/api/v1/?q=xxx
            ## 
            ## @var string
            ## @default "q"
            ## 
            param: q

        ## Index entities
        ## --
        ##
        collections:

            ## Name of the collection
            ## --
            ##
            all_books:

                ## Entity Class 
                ## --
                ## The name of the entity of the collection
                ##
                ## @var string
                ## @required
                ## 
                class: App\Entity\Item

                ## Repository methods
                ## --
                ## Repository methods apply to retrieve the list or one of entities
                ## 
                ## @var array
                ## 
                repository_methods:
                    # findAll: findBy
                    # findOne: find

                ## URL Paths for plural or singular item
                ## --
                ## Will be automatically generated if not set
                ## 
                paths:
                    
                    ## Singular path
                    ## --
                    ## 
                    ## @var string
                    ## @default singular of App\Entity\Book\Book\Book
                    ## 
                    singular: book
                    
                    ## Plural path
                    ## --
                    ## 
                    ## @var string
                    ## @default plural of App\Entity\Book\Book\Book
                    ## 
                    plural: books

                ## Serializer Groups
                ## --
                ## Groups ID for serialization
                ##
                ## @var string[]
                ## @default []
                ## 
                serializer_groups: ['book']

                ## Response default sorter
                ## --
                ## Override this default sorter with the sorter param in the URL
                ## e.g.: site.com/api/v1/books/?sorter=title:DESC,price:ASC
                ##         
                ## e.g.: title: 
                ##          order: ASC
                ##
                ## @var array
                ## 
                sorter:
                    title: 
                        order: ASC

                ## Privileges access
                ## --
                ##
                privileges:

                    ## Name of the privilege
                    ## --
                    ## 
                    public:

                        ## Granted / Role
                        ## --
                        ## 
                        ## @var string
                        ## @default null
                        ## 
                        granted: null #'PUBLIC_ACCESS'

                        ## Allowed methods
                        ## --
                        ## 
                        ## @var enum[]
                        ## @default ['GET', 'POST', 'PUT', 'PATCH', 'DELETE']
                        ## 
                        methods: ['GET', 'POST', 'PUT', 'PATCH', 'DELETE']

                ## Privileges access
                ## --
                ##
                search:

                    ## Exclude
                    ## --
                    ## Exclude the entity from the search request
                    ## 
                    ## @var bool
                    ## @default false
                    ## 
                    excluded: false

                    ## Criteria
                    ## --
                    ## Used to generate a WHERE clause of the query
                    ## 
                    ## @var array
                    ## 
                    criteria:

                        ## For the property "title"
                        ## sql : ... WHERE `title` LIKE '%$expression%'
                        title:
                            match: like
                        description:
                            match: like

```