# Pagination

Add simple pagination to Symfony projects

## Install
<!-- 
### Add the private repository

```json
"repositories": [
    { "type": "vcs", "url": "https://github.com/OSW3/symfony-pagination" }
],
``` 
-->

### Instal the bundle

```shell
comoser require osw3/symfony-pagination
```

### Prepare for update

In your composer.json file, change the line of the dependency to prepare futures updates of the bundle.

```json
"osw3/symfony-pagination": "*",
```

### Enable the bundle

Add the bundle in the `config/bundle.php` file.

```php
return [
    OSW3\SymfonyPagination\OSW3SymfonyPaginationBundle::class => ['all' => true],
];
```

## Usage

### 1. in the controller

```php
#[Route('/', name: 'app_book_index', methods: ['GET'])]
public function index(BookRepository $bookRepository, PaginationService $paginationService): Response
{
    /// 1. Set up the Paging 
    /// --
    
    // A. Set the repository to the service
    $repository = $paginationService->setRepository($bookRepository);

    // B. (optional) Set the repository method
    $repository->setMethod('findBy');
    

    /// 2. Find entities
    /// --

    // A. Find entities with optional sorter
    $books = $repository->find(['title' => 'ASC']);

    // B. or Find entities based on criteria and optional sorter
    // $books = $repository->findBy(
    //     ['author' => "John"],
    //     ['title' => 'ASC']
    // );


    /// 3. Render the view
    /// --

    // Render the view with books list and the pagination service
    return $this->render('book/index.html.twig', [
        'paginationService' => $paginationService,
        'books' => $books,
    ]);
}
```

### 2. in Twig view

#### Shows paging buttons

```twig
{{ pagination({
    paginationService: paginationService,
    route: 'app_book_index',
    absolute: true
}) }}
```

#### Shows the total number of items

```twig
{{ pagination_total() }}
```

#### Shows the total number of pages

```twig
{{ pagination_pages() }}
```

#### Shows the current page number

```twig
{{ pagination_page() }}
```

#### Shows the number of items per page

```twig
{{ pagination_per_page() }}
```
