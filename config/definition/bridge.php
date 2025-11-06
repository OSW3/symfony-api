<?php 

use Symfony\Component\Filesystem\Path;
use Symfony\Component\Config\Definition\Configurator\DefinitionConfigurator;

return static function (DefinitionConfigurator $configurator, RouterInterface|UrlGeneratorInterface $router): void {
    $definition = require Path::join(__DIR__, "definition.php");
    $definition($configurator, $router);
};