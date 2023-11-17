<?php 
namespace OSW3\SymfonyApi\DependencyInjection;

use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\Config\Definition\ConfigurationInterface;

class Configuration implements ConfigurationInterface
{
	/**
	 * define the name of the configuration tree.
	 * > /config/packages/symfony_api.yaml
	 *
	 * @var string
	 */
	public const NAME = "symfony_api";

	/**
	 * Define the translation domain
	 *
	 * @var string
	 */
	public const DOMAIN = 'symfony_api';
	
	/**
	 * Update and return the Configuration Builder
	 *
	 * @return TreeBuilder
	 */
	public function getConfigTreeBuilder(): TreeBuilder
	{
		$builder = new TreeBuilder( self::NAME );
		$rootNode = $builder->getRootNode();

		$rootNode->useAttributeAsKey('name')->arrayPrototype()->children()

			/**
			 * API Version
			 * --
			 * 
			 * @var integer
			 * @required
			 */
			->integerNode('version')->isRequired()->end()

			/**
			 * Response Links settings
			 */
			->arrayNode('links')->addDefaultsIfNotSet()->children()
				
				/**
				 * Links state
				 * --
				 * If true, add the entity link to the response
				 * 
				 * @var bool
				 * @default true
				 */
				->booleanNode('state')->defaultTrue()->end()

				/**
				 * Links absolute
				 * --
				 * If true, generate an absolute link
				 * 
				 * @var bool
				 * @default true
				 */
				->booleanNode('absolute')->defaultTrue()->end()

			->end()->end()

			/**
			 * Response pagination settings
			 */
			->arrayNode('pagination')->addDefaultsIfNotSet()->children()
				
				/**
				 * Pagination state
				 * --
				 * If true, the response will paginate
				 * 
				 * @var bool
				 * @default true
				 */
				->booleanNode('state')->defaultTrue()->end()

				/**
				 * Items per page
				 * --
				 * Number of items per page
				 * 
				 * @var integer
				 * @default 10
				 */
				->integerNode('item_per_page')->defaultValue(10)->end()
				
			->end()->end()

			/**
			 * Search by API settings
			 */
			->arrayNode('search')->addDefaultsIfNotSet()->children()
				
				/**
				 * Is the search by the API is allowed
				 * --
				 * If true, the search is allowed
				 * 
				 * @var bool
				 * @default true
				 */
				->booleanNode('allowed')->defaultTrue()->end()

				/**
				 * The search parameter of the URL
				 * --
				 * e.g.: site.com/api/v1/?q=xxx
				 * 
				 * @var string
				 * @default q
				 */
				->scalarNode('param')->defaultValue('q')->end()
				
			->end()->end()

			/**
			 * Index entities
			 */
			->arrayNode('collections')->useAttributeAsKey('collection')->arrayPrototype()->children()

				/**
				 * Entity Class 
				 * --
				 * The name of the entity of the collection
				 * 
				 * @var string
				 * @required
				 */
				->scalarNode('class')->isRequired()->end()
				
				/**
				 * Repository methods apply to retrieve the list or one of entities
				 */
				->arrayNode('repository_methods')->addDefaultsIfNotSet()->children()

					/**
					 * FindBy method
					 * --
					 * Define the name of the method used to find the list of entities
					 * 
					 * @var string 
					 * @default findBy
					 */
					->scalarNode('findAll')->defaultValue('findBy')->end()

					/**
					 * Find One
					 * --
					 * Define the name of the method used to find one of entity
					 * 
					 * @var string 
					 * @default findOneBy
					 */
					->scalarNode('findOne')->defaultValue('find')->end()

				->end()->end()


				/**
				 * URL Paths for plural or singular item
				 */
				->arrayNode('paths')->addDefaultsIfNotSet()->children()

					/**
					 * Singular path
					 * --
					 * 
					 * @var string 
					 * @default singular of App\Entity\Book\Book\Book
					 */
					->scalarNode('singular')->defaultNull()->end()

					/**
					 * Plural path
					 * --
					 * 
					 * @var string 
					 * @default plural of App\Entity\Book\Book\Book
					 */
					->scalarNode('plural')->defaultNull()->end()

				->end()->end()

				/**
				 * Serialization Scope
				 * --
				 * 
				 * @var string[]
				 * @default []
				 */
				->arrayNode('scope')->scalarPrototype()->end()->end()

				/**
				 * Response default sorter
				 * 
				 * @var array
				 */
				->arrayNode('sorter')->arrayPrototype()->children()
					->enumNode('order')->values(['ASC', 'DESC'])->defaultValue('ASC')->end()
				->end()->end()->end()

				/**
				 * Privileges access
				 */
				->arrayNode('privileges')->useAttributeAsKey('privilege')->arrayPrototype()->children()
				
					/**
					 * Granted / Role
					 * --
					 * Allowed role to access to this entity
					 * TODO: replace NULL by ANONYMOUS constant
					 * 
					 * @var string|null
					 * @default null
					 */
					->scalarNode('granted')->defaultNull()->end()

					/**
					 * Allowed methods
					 * --
					 * 
					 * @var enum[]
					 * @default ['GET', 'POST', 'PUT', 'PATCH', 'DELETE']
					 */
					->arrayNode('methods')
						->scalarPrototype()
							->validate()
							->ifNotInArray(['GET', 'POST', 'PUT', 'PATCH', 'DELETE'])
								->thenInvalid('Invalid method specified. Valid methods are: GET, POST, PUT, PATCH, DELETE')
							->end()
						->end()
						->defaultValue(['GET', 'POST', 'PUT', 'PATCH', 'DELETE'])
					->end()

				->end()->end()->end()

				/**
				 * Search settings for this entity
				 */
				->arrayNode('search')->addDefaultsIfNotSet()->children()
				
					/**
					 * Exclude the entity from the search request
					 * --
					 * 
					 * @var bool 
					 * @default false
					 */
					->booleanNode('excluded')->defaultFalse()->end()

					/**
					 * Criteria 
					 * Used to generate a WHERE clause of the query
					 * 
					 * @var array
					 */
					->arrayNode('criteria')->arrayPrototype()->children()

						/**
						 * Matching method
						 * 
						 * @var string
						 * @enum 
						 * @default like
						 */
						->enumNode('match')
							->values([
								"equal", 
								"not", 
								"like", 
								"left-like", 
								"right-like", 
								"not-like", 
								"not-left-like", 
								"not-right-like",
							])
							->defaultValue("like")
						->end()

					->end()->end()->end()

				->end()->end()

			->end()->end()->end()

		->end()->end();


		$rootNode->validate()->always(function($config){
			$this->checkUniqVersion($config);
			$this->setDefaultPaths($config);
			return $config;
		})->end();
		
		return $builder;
	}




	// VALIDATORS
	// --

	private function checkUniqVersion($config): void
	{
		$versions = [];
		foreach ($config as $apiName => $apiData) 
		{
			$version = $apiData['version'];

			if (in_array($version, $versions)) 
			{
				// Todo: translate this message
				throw new \InvalidArgumentException(sprintf('La valeur de "version" dans %s doit être unique.', $apiName));
			}

			$versions[] = $version;
		}
	}

	private function setDefaultPaths(&$config): void 
	{
		foreach ($config as $apiName => $apiData) 
		{
			$collections = $apiData['collections'];
			foreach ($collections as $collectionName => $collectionData)
			{
				$name = explode("\\", $collectionData['class']);
				$name = end($name);
				$name = strtolower($name);
	
				if ($collectionData['paths']['singular'] === null)
				{
					$config[$apiName]['collections'][$collectionName]['paths']['singular'] = $this->singularize($name);
				}
				if ($collectionData['paths']['plural'] === null)
				{
					$config[$apiName]['collections'][$collectionName]['paths']['plural'] = $this->pluralize($name);
				}
			}
		}
	}











	// UTILS
	// --

    private function singularize(string $word): string
    {
        if (preg_match('/(.*[^aeiou])ies$/', $word, $matches)) {
            return $matches[1] . 'y';
        } elseif (preg_match('/(.*)(ses|xes|zes|ches|shes)$/', $word, $matches)) {
            return $matches[1]; 
        } elseif (preg_match('/(.*)s$/', $word, $matches)) {
            return $matches[1]; 
        } else {
            return $word;
        }
    }
    private function pluralize(string $word): string
    {
        if (preg_match('/(.*[^aeiou])y$/', $word, $matches)) {
            return $matches[1] . 'ies';
        } elseif (preg_match('/(.*)(s|x|z|ch|sh)$/', $word, $matches)) {
            return $matches[0] . 'es';
        } else {
            return $word . 's'; 
        }
    }
}