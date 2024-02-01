<?php 
namespace OSW3\Api\DependencyInjection;

use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\Config\Definition\ConfigurationInterface;
use Symfony\Component\Security\Core\Role\RoleHierarchyInterface;

class Configuration implements ConfigurationInterface
{
	public const NAME      = "api";
	public const DOMAIN    = 'api';
	public const BASE_PATH = '/api';
	private const SORTER   = ['ASC', 'DESC'];
	private const FILTERS  = [
		"equal", 			// = expression
		"not",				// != expression
		"not-equal",		// != expression
		"like", 			// LIKE '%expression%'
		"left-like", 		// LIKE '%expression'
		"right-like", 		// LIKE 'expression%'
		"not-like", 		//  NOT LIKE '%expression%'
		"not-left-like", 	//  NOT LIKE '%expression'
		"not-right-like",	//  NOT LIKE 'expression%'
		"greater",			//  > 'expression'
		"greater-or-equal",	//  >= 'expression'
		"lesser",			//  < 'expression'
		"lesser-or-equal",	//  <= 'expression'
	];

	public function getConfigTreeBuilder(): TreeBuilder
	{
		$builder = new TreeBuilder( self::NAME );
		$rootNode = $builder->getRootNode();

		/**
		 * API Providers
		 * --
		 * 
		 * @var array
		 */
		$rootNode->useAttributeAsKey('provider')->arrayPrototype()->children()

			/**
			 * Router settings
			 * --
			 * 
			 * @var array
			 */
			->arrayNode('router')->addDefaultsIfNotSet()->children()

				/**
				 * Route name syntax
				 * --
				 * @var string
				 */
				->scalarNode('name')->defaultValue('api:{provider}:{collection}:{action}')->end()

				/**
				 * Path prefix
				 * --
				 * 
				 * @var string
				 * @required
				 * @default '/api'
				 */
				// ->scalarNode('prefix')->defaultValue('/api')->cannotBeEmpty()->end()
				->scalarNode('prefix')->defaultNull()->end()
				
			->end()->end()


			/**
			 * Search settings
			 * --
			 * 
			 * @var array
			 */
			->arrayNode('search')->addDefaultsIfNotSet()->children()

				->booleanNode('enabled')->defaultTrue()->end()
				->scalarNode('param')->defaultValue('q')->end()

			->end()->end()


			/**
			 * Pagination settings
			 * --
			 * 
			 * @var array
			 */
			->arrayNode('pagination')->addDefaultsIfNotSet()->children()

				->booleanNode('enabled')->defaultTrue()->end()
				->integerNode('per_page')->defaultValue(10)->min(1)->end()

			->end()->end()


			->arrayNode('url_generator')->addDefaultsIfNotSet()->children()

				->booleanNode('support')->defaultTrue()->end()
				->booleanNode('absolute')->defaultTrue()->end()

			->end()->end()


			/**
			 * Collection settings
			 * --
			 * 
			 * @var array
			 */
			->arrayNode('collections')->arrayPrototype()->children()

				/**
				 * Collection paths
				 * --
				 * 
				 * @var array
				 */
				->arrayNode('paths')->addDefaultsIfNotSet()->children()

					/**
					 * Path plural
					 * --
					 * 
					 * @var string
					 */
					->scalarNode('plural')->defaultNull()->end()

					/**
					 * Path singular
					 * --
					 * 
					 * @var string
					 */
					->scalarNode('singular')->defaultNull()->end()

				->end()->end()

				/**
				 * Privileges access
				 * --
				 * 
				 * @var array
				 */
				->arrayNode('privileges')->arrayPrototype()->children()

					/**
					 * Granted access
					 * --
					 * 
					 * @var string
					 * @default PUBLIC_ACCESS
					 */
					->scalarNode('granted')->defaultValue('PUBLIC_ACCESS')->end()

					/**
					 * Allowed HTTP methods
					 * --
					 * 
					 * @var array
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
				 * Entity settings 
				 * --
				 * 
				 * @var array
				 */
				->arrayNode('entity_manager')->addDefaultsIfNotSet()->children()

					/**
					 * The entity class
					 * --
					 * 
					 * @var string
					 * @required
					 */
					->scalarNode('class')->isRequired()->end()

					/**
					 * Repository Methods
					 * --
					 * 
					 * @var array
					 */
					->arrayNode('methods')->addDefaultsIfNotSet()->children()

						/**
						 * Method to Find an entity by ID
						 * --
						 * 
						 * @var string
						 */
						->scalarNode('find')->defaultValue('find')->end()

						/**
						 * Method to find all entity
						 * --
						 * 
						 * @var string
						 */
						->scalarNode('findAll')->defaultValue('findBy')->end()

						// ->scalarNode('findBy')->defaultValue('findBy')->end()
						// ->scalarNode('findOneBy')->defaultValue('findOneBy')->end()
						// ->scalarNode('count')->defaultValue('count')->end()

					->end()->end()


					/**
					 * Serializer groups
					 * --
					 * 
					 * @var array
					 */
					->arrayNode('groups')->scalarPrototype()->end()->end()

				->end()->end()

				->arrayNode('results')->addDefaultsIfNotSet()->children()

					/**
					 * results default sorter
					 * --
					 * 
					 * @var array
					 */
					->arrayNode('sorter')->variablePrototype()->end()->end()

					/**
					 * Links settings for each entity
					 * --
					 * 
					 * @vr array
					 */
					->enumNode('links')

						->beforeNormalization()
							->ifTrue(fn ($value) => $value === null)->then(fn () => 'relative')
							->ifTrue(fn ($value) => $value === false)->then(fn () => 'none')
						->end()

						->values(['none','relative','absolute'])
						->defaultValue('relative')
					->end()

				->end()->end()

				/**
				 * Search settings
				 * --
				 * 
				 * @var array
				 */
				->arrayNode('search')->addDefaultsIfNotSet()->children()

					/**
					 * Exclude from the search
					 * --
					 * 
					 * @var boolean
					 * @default false
					 */
					->booleanNode('exclude')->defaultFalse()->end()

					/**
					 * Search criteria
					 * --
					 * 
					 * @var array
					 */
					->arrayNode('criteria')->variablePrototype()->end()->end()

				->end()->end()

			->end()->end()->end()

		->end()->end();


		$rootNode->validate()->always(function($config){
			foreach ($config as $provider => $x)
			{
				// api.<provider>.router.prefix
				// --

				// Prefix value must be url friendly
				if ($config[$provider]['router']['prefix'] != null && !preg_match('/^\/([a-zA-Z0-9\-._~%]+\/?)*$/', $config[$provider]['router']['prefix']) )
				{
					throw new \InvalidArgumentException(sprintf("A valid URL path expected, %s given.", $config[$provider]['router']['prefix']));
				}

				foreach ($config[$provider]['collections'] as $collection => $y)
				{

					// api.<provider>.collections.<collection>.paths
					// --
	
					// Set default "plural" and "singular" paths
					$singular = self::singularize($collection);
					$plural = self::pluralize($singular);
		
					if (empty($config[$provider]['collections'][$collection]['paths']['singular']))
					{
						$config[$provider]['collections'][$collection]['paths']['singular'] = $singular;
					}
		
					if (empty($config[$provider]['collections'][$collection]['paths']['plural']))
					{
						$config[$provider]['collections'][$collection]['paths']['plural'] = $plural;
					}
		
					$config[$provider]['collections'][$collection]['paths']['singular'] = $this->slugify($config[$provider]['collections'][$collection]['paths']['singular']);
					$config[$provider]['collections'][$collection]['paths']['plural'] = $this->slugify($config[$provider]['collections'][$collection]['paths']['plural']);
					
					
					
					// api.<provider>.collections.<collection>.privileges
					// --

					// Set default granted value
					foreach ($config[$provider]['collections'][$collection]['privileges'] as $privilege => $z)
					{
						if ($config[$provider]['collections'][$collection]['privileges'][$privilege]['granted'] === null)
						{
							$config[$provider]['collections'][$collection]['privileges'][$privilege]['granted'] = 'PUBLIC_ACCESS';
						}
					}


					// api.<provider>.collections.<collection>.results.sorter
					// --

					foreach ($config[$provider]['collections'][$collection]['results']['sorter'] as $property => $z)
					{
						if ($config[$provider]['collections'][$collection]['results']['sorter'][$property] === null)
						{
							$config[$provider]['collections'][$collection]['results']['sorter'][$property] = 'ASC';
						}

						if (!in_array(strtoupper($config[$provider]['collections'][$collection]['results']['sorter'][$property]), self::SORTER, true))
						{
							throw new \InvalidArgumentException(sprintf("Wrong value for the sorter \"%s\", %s expected.", $property, join(", ", self::SORTER)));
						}

						$config[$provider]['collections'][$collection]['results']['sorter'][$property] = strtoupper($config[$provider]['collections'][$collection]['results']['sorter'][$property]);
					}


					// api.<provider>.collections.<collection>.search.criteria
					// --

					foreach ($config[$provider]['collections'][$collection]['search']['criteria'] as $property => $z)
					{
						if ($config[$provider]['collections'][$collection]['search']['criteria'][$property] === null)
						{
							$config[$provider]['collections'][$collection]['search']['criteria'][$property] = 'like';
						}

						if (!in_array(strtolower($config[$provider]['collections'][$collection]['search']['criteria'][$property]), self::FILTERS, true))
						{
							throw new \InvalidArgumentException(sprintf("Wrong value for the sorter \"%s\", %s expected.", $property, join(", ", self::FILTERS)));
						}

						$config[$provider]['collections'][$collection]['search']['criteria'][$property] = strtolower($config[$provider]['collections'][$collection]['search']['criteria'][$property]);
					}
				}
			}

			return $config;
		})->end();

		return $builder;
	}




	// UTILS
	// --
	
	const IRREGULARS = array(
		'child' => 'children',
		'person' => 'people',
		'man' => 'men',
		'woman' => 'women',
		'tooth' => 'teeth',
		'foot' => 'feet',
		'mouse' => 'mice',
		'ox' => 'oxen',
		'goose' => 'geese',
		'deer' => 'deer',
		'fish' => 'fish',
		'sheep' => 'sheep',
		'cactus' => 'cacti',
		'focus' => 'foci',
		'fungus' => 'fungi',
		'nucleus' => 'nuclei',
		'syllabus' => 'syllabi',
		'radius' => 'radii',
		'datum' => 'data',
		'medium' => 'media',
		'analysis' => 'analyses',
		'crisis' => 'crises',
		'thesis' => 'theses',
		'phenomenon' => 'phenomena',
		'index' => 'indices',
		'matrix' => 'matrices',
		'axis' => 'axes',
		'appendix' => 'appendices',
		'bacterium' => 'bacteria',
		'curriculum' => 'curricula',
		'formula' => 'formulas',
		'larva' => 'larvae',
		'stimulus' => 'stimuli',
		'virus' => 'viruses',
		'alumnus' => 'alumni',
		'focus' => 'foci',
		'criterion' => 'criteria',
		'datum' => 'data',
		'medium' => 'media',
		'radius' => 'radii',
		'criterion' => 'criteria',
		'crisis' => 'crises',
		'diagnosis' => 'diagnoses',
		'hypothesis' => 'hypotheses',
		'parenthesis' => 'parentheses',
		'appendix' => 'appendices',
		'matrix' => 'matrices',
		'thesis' => 'theses',
		'phenomenon' => 'phenomena',
	);

    public static function singularize(string $word): string
    {
		$irregulars = array_flip(self::IRREGULARS);

		if (array_key_exists($word, $irregulars)) {
			return $irregulars[$word];
		}


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
    public static function pluralize(string $word): string
    {
		$irregulars = self::IRREGULARS;

		if (array_key_exists($word, $irregulars)) {
			return $irregulars[$word];
		}

		$rules = array(
			'/(s|x|z)$/i' => "$1es",
			'/([^aeiouy])y$/i' => "$1ies",
			'/$/' => "s"
		);

		foreach ($rules as $pattern => $replacement) {
			if (preg_match($pattern, $word)) {
				return preg_replace($pattern, $replacement, $word);
			}
		}

		return $word;
    }
	private function slugify($str) 
	{
		$str = preg_replace('/[^a-zA-Z0-9-]+/', '-', $str);
		$str = strtolower($str);
		$str = trim($str, '-');
		$str = preg_replace('/-+/', '-', $str);
	
		return $str;
	}
}