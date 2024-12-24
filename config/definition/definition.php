<?php 

return static function($definition)
{
    $definition->rootNode()->arrayPrototype()
        ->info('Provide the namespace of the entity to add to the API.')
        ->children()

			/**
			 * Router settings
			 * --
			 * 
			 * @var array
			 */
			->arrayNode('routes')
                ->info('Defining rules for API routes.')
                ->addDefaultsIfNotSet()
                ->children()

                /**
                 * Route name syntax
                 * --
                 * @var string
                 */
                ->scalarNode('name_pattern')
                    ->info('Defining the routes name pattern.')
                    ->defaultValue('api:{provider}:{collection}:{action}')
                ->end()

                /**
                 * Path prefix
                 * --
                 * 
                 * @var string
                 * @required
                 * @default '/api'
                 */
                ->scalarNode('prefix')
                    ->info('Defining path prefix.')
                    ->defaultValue('/api')
                ->end()
                
            ->end()->end()

			/**
			 * Search settings
			 * --
			 * 
			 * @var array
			 */
			->arrayNode('search')
                ->info('Defining search indexing rules via API.')
                ->addDefaultsIfNotSet()
                ->children()

				->booleanNode('enable')
                    ->info('Sets search activation.')
                    ->defaultTrue()
                ->end()
				
                ->scalarNode('param')
                    ->info('Sets search request parameter.')
                    ->defaultValue('q')
                ->end()

			->end()->end()


			/**
			 * Pagination settings
			 * --
			 * 
			 * @var array
			 */
			->arrayNode('pagination')
                ->info('Provide the API pagination settings.')
                ->addDefaultsIfNotSet()
                ->children()

				->booleanNode('enable')
                    ->info('Sets pagination activation.')
                    ->defaultTrue()
                ->end()

				->integerNode('per_page')
                    ->info('Sets pagination items per page.')
                    ->defaultValue(10)
                    ->min(1)
                ->end()

			->end()->end()


			/**
			 * API Rest items URL rules
			 * --
			 * 
			 * @var array
			 */
			->arrayNode('url')
                ->info('Rest API Elements URL Rules')
                ->addDefaultsIfNotSet()
                ->children()

				->booleanNode('support')
                    ->info('')
                    ->defaultTrue()
                ->end()

				->booleanNode('absolute')
                    ->info('Generate absolute URL')
                    ->defaultTrue()
                ->end()

			->end()->end()







    ->end()->end();
};