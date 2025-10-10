<?php

namespace Maxime\SolidMakerBundle\Command;

use Symfony\Bundle\MakerBundle\Maker\AbstractMaker;
use Symfony\Bundle\MakerBundle\ConsoleStyle;
use Symfony\Bundle\MakerBundle\DependencyBuilder;
use Symfony\Bundle\MakerBundle\Generator;
use Symfony\Bundle\MakerBundle\InputConfiguration;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

class MakeSolidControllerCommand extends AbstractMaker
{
    public static function getCommandName(): string
    {
        return 'make:solid-controller';
    }

    public static function getCommandDescription(): string
    {
        return 'Crée un contrôleur REST complet basé sur le service et la collection d’une entité donnée.';
    }

    public function configureDependencies(DependencyBuilder $dependencies): void
    {
        // Aucune dépendance spécifique nécessaire
    }

    public function configureCommand(Command $command, InputConfiguration $inputConfig): void
    {
        $command
            ->addArgument('entityName', InputArgument::REQUIRED, 'Nom de l’entité (ex: Recipe)')
            ->setDescription(self::getCommandDescription());
    }

    public function generate(InputInterface $input, ConsoleStyle $io, Generator $generator): void
    {
        $entityName = ucfirst(trim($input->getArgument('entityName')));
        $entityClass = "App\\Entity\\$entityName";
        $entityVarName = lcfirst($entityName);
        $entityRouteName = strtolower($entityName);
        $serviceName = "{$entityName}Service";

        $templatePath = __DIR__ . '/../Resources/skeleton/solid_controller.tpl.php';
        $this->ensureEntityArrayConverterExists($generator, $io);

        $generator->generateClass(
            "App\\Controller\\{$entityName}Controller",
            $templatePath,
            [
                'serviceName' => $serviceName,
                'entityClass' => $entityClass,
                'entityName' => $entityName,
                'entityVarName' => $entityVarName,
                'entityRouteName' => $entityRouteName,
            ]
        );

        $generator->writeChanges();
        $io->success("✅ Controller {$entityName}Controller généré avec succès !");
    }

    private function ensureEntityArrayConverterExists(Generator $generator, ConsoleStyle $io): void
    {
        $targetPath = 'src/Utils/EntityArrayConverter.php';
        $templatePath = __DIR__ . '/../Resources/skeleton/EntityArrayConverter.tpl.php';
        if (!file_exists($targetPath)) {
            $generator->generateClass(
                'App\\Utils\\EntityArrayConverter',
                $templatePath
            );
            $io->success('EntityArrayConverter ajouté au projet (via Generator).');
        } else {
            $io->text('EntityArrayConverter déjà présent.');
        }
        $generator->writeChanges();

    }
}
