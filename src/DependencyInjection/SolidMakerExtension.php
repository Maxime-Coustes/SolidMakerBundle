<?php

namespace Maxime\SolidMakerBundle\DependencyInjection;

use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Extension\Extension;
use Symfony\Component\DependencyInjection\Loader\YamlFileLoader;
use Symfony\Component\Config\FileLocator;

class SolidMakerExtension extends Extension
{
    public function load(array $configs, ContainerBuilder $container)
    {
        // Vérifier si le fichier existe avant de le charger
        $file = __DIR__ . '/../Resources/config/services.yaml';
        if (file_exists($file)) {
            $loader = new YamlFileLoader($container, new FileLocator(__DIR__ . '/../Resources/config'));
            $loader->load('services.yaml');
        }
    }
}
