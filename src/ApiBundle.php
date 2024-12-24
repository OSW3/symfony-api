<?php 
namespace OSW3\Api;

use OSW3\Api\DependencyInjection\Configuration;
use Symfony\Component\HttpKernel\Bundle\Bundle;
use Symfony\Component\DependencyInjection\ContainerBuilder;

class ApiBundle extends Bundle
{
    public function build(ContainerBuilder $container): void
    {
        $projectDir = $container->getParameter('kernel.project_dir');


        // Generate the YAML bundle configuration file in the project
        // --
        
        (new Configuration)->generateProjectConfig($projectDir);
    }
}