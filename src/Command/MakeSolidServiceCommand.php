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
use Doctrine\ORM\Mapping\Column;

class MakeSolidServiceCommand extends AbstractMaker
{
    public function configureDependencies(DependencyBuilder $dependencies): void
    {
        // Pas de dépendances supplémentaires pour ce Maker
    }

    public static function getCommandName(): string
    {
        return 'make:solid-service';
    }

    public static function getCommandDescription(): string
    {
        return 'Crée un service avec repository et interface basés sur le nom de l’entité';
    }

    public function configureCommand(Command $command, InputConfiguration $inputConfig): void
    {
        $command->addArgument(
            'entityName',
            InputArgument::REQUIRED,
            'Nom de l’entité (ex: Recipe pour App\Entity\Recipe)'
        );
    }

    public function generate(InputInterface $input, ConsoleStyle $io, Generator $generator): void
    {
        $entityName = $input->getArgument('entityName');

        // Déduire noms et namespaces
        $entityClass = "App\\Entity\\$entityName";
        $interfaceName = "{$entityName}ServiceInterface";
        $interfaceNamespace = "App\\Interface\\$interfaceName";
        $repositoryClass = "App\\Repository\\{$entityName}Repository";
        $repositoryShortName = "{$entityName}Repository";
        $serviceName = "{$entityName}Service";
        $columns = [];
        $reflection = new \ReflectionClass("App\\Entity\\$entityName");
        foreach ($reflection->getProperties() as $property) {
            // On filtre uniquement les propriétés annotées #[ORM\Column]
            $attrs = $property->getAttributes(Column::class);
            if (!empty($attrs)) {
                $columns[] = $property->getName();
            }
        }

        $templatePath = __DIR__ . '/../Resources/skeleton/solid_service.tpl.php';
        $generator->generateClass(
            "App\\Service\\$serviceName",
            $templatePath,
            [
                'name' => $serviceName,
                'interface' => $interfaceName,
                'interfaceNamespace' => $interfaceNamespace,
                'entityClass' => $entityClass,
                'repositoryClass' => $repositoryClass,
                'repositoryShortName' => $repositoryShortName,
                'columns' => $columns,
            ]
        );

        $helperPath = "src/Utils/DoctrineHelper.php";
        $abstractRepoPath = "src/Repository/AbstractSolidRepository.php";
        $interfaceRepoPath = "src/Repository/SolidRepositoryInterface.php";
        // si DoctrineHelper, AbstractSolidRepository et SolidRepositoryInterface n'existent pas, je le crée
        $doctrineHelperTemplatePath = __DIR__ . '/../Resources/skeleton/doctrine_helper.tpl.php';
        $abstractRepoTpl = __DIR__ . '/../Resources/skeleton/abstract_solid_repository.tpl.php';
        $interfaceRepoTpl = __DIR__ . '/../Resources/skeleton/solid_repository_interface.tpl.php';
        if (!file_exists($helperPath)) {
            $generator->generateClass(
                "App\\Utils\\DoctrineHelper",
                $doctrineHelperTemplatePath
            );
            $io->success("DoctrineHelper généré dans src/Utils/ ✅");
        }
        // Génération de l'interface SolidRepositoryInterface
        if (!file_exists($interfaceRepoPath)) {
            $generator->generateClass(
                "App\\Repository\\SolidRepositoryInterface",
                $interfaceRepoTpl
            );
            $io->success("SolidRepositoryInterface générée dans src/Repository/ ✅");
        }

        // Génération de AbstractSolidRepository
        if (!file_exists($abstractRepoPath)) {
            $generator->generateClass(
                "App\\Repository\\AbstractSolidRepository",
                $abstractRepoTpl
            );
            $io->success("AbstractSolidRepository généré dans src/Repository/ ✅");
        }

        $repositoryPath = "src/Repository/{$entityName}Repository.php";
        if (file_exists($repositoryPath)) {
            $io->text("Le repository {$entityName}Repository existe déjà !  ✅");
            try {
                $this->extendsUsage($repositoryPath, $io);
            } catch (\Throwable $th) {
                $io->warning("⚠️ Impossible de mettre à jour {$entityName}Repository : " . $th->getMessage());
            }
            $io->success("Repository {$entityName}Repository mis à jour pour hériter de AbstractSolidRepository ✅");
        } else {
            // Le créer via le generator si besoin
        }


        $generator->writeChanges();
        $io->success("Service $serviceName généré ✅");
    }

    private function extendsUsage(string $repositoryPath, ConsoleStyle $io): void
    {
        [$level, $message] = $this->processRepositoryUpdate($repositoryPath);

        match ($level) {
            'error' => $io->error($message),
            'warning' => $io->warning($message),
            'info' => $io->text($message),
            default => $io->success($message),
        };
    }

    private function processRepositoryUpdate(string $repositoryPath): array
    {
        $level = '';
        $message = '';

        if (!$this->checkFilePermissions($repositoryPath)) {
            $level = 'warning';
            $message = "Impossible de modifier {$repositoryPath} (droits insuffisants ou fichier verrouillé).";
        } else {
            $content = file_get_contents($repositoryPath);
            if ($content === false) {
                $level = 'error';
                $message = "Lecture impossible du fichier {$repositoryPath}.";
            } elseif (str_contains($content, 'AbstractSolidRepository')) {
                $level = 'info';
                $message = "{$repositoryPath} hérite déjà de AbstractSolidRepository. ✅";
            } elseif (!preg_match('/class\s+\w+\s+extends\s+ServiceEntityRepository\b/', $content)) {
                $level = 'warning';
                $message = "Aucune déclaration de classe 'extends ServiceEntityRepository' trouvée dans {$repositoryPath}.";
            } else {
                [$level, $message] = $this->updateRepositoryContent($repositoryPath, $content);
            }
        }

        return [$level, $message];
    }


    private function checkFilePermissions(string $repositoryPath): bool
    {
        return is_readable($repositoryPath) && is_writable($repositoryPath);
    }

    private function updateRepositoryContent(string $repositoryPath, string $content): array
    {
        $newContent = preg_replace(
            '/(class\s+\w+\s+)extends\s+ServiceEntityRepository\b/',
            '${1}extends AbstractSolidRepository implements SolidRepositoryInterface',
            $content,
            1
        );

        if ($newContent === null) {
            return ['error', "Erreur lors de la modification de {$repositoryPath}"];
        }

        $updatedContent = preg_replace(
            '/@extends\s+ServiceEntityRepository/',
            '@extends AbstractSolidRepository',
            $newContent
        ) ?? $newContent;

        if (file_put_contents($repositoryPath, $updatedContent) === false) {
            return ['error', "Impossible d’écrire dans {$repositoryPath}"];
        }

        return ['success', "{$repositoryPath} mis à jour pour hériter de AbstractSolidRepository ✅"];
    }
}
