<?php

namespace Maxime\SolidMakerBundle\Command;

use Symfony\Bundle\MakerBundle\ConsoleStyle;
use Symfony\Bundle\MakerBundle\DependencyBuilder;
use Symfony\Bundle\MakerBundle\Generator;
use Symfony\Bundle\MakerBundle\InputConfiguration;
use Symfony\Bundle\MakerBundle\Maker\AbstractMaker;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;

class MakeSolidRepositoryCommand extends AbstractMaker
{
    public static function getCommandName(): string
    {
        return 'make:solid-repository';
    }

    public static function getCommandDescription(): string
    {
        return 'Met à jour un repository pour hériter de AbstractSolidRepository et implémenter SolidRepositoryInterface.';
    }

    public function configureCommand(Command $command, InputConfiguration $inputConfig): void
    {
        $command->addArgument(
            'entityName',
            InputArgument::REQUIRED,
            'Nom de l’entité (ex: Recipe)'
        );
    }

    public function configureDependencies(DependencyBuilder $dependencies): void
    {
        // Pas de dépendances spécifiques
    }

    public function generate(InputInterface $input, ConsoleStyle $io, Generator $generator): void
    {
        $entityName = $input->getArgument('entityName');
        $repositoryPath = "src/Repository/{$entityName}Repository.php";

        // Étape 1 : S’assurer que les classes de base (Abstract + Interface) existent
        $this->ensureBaseRepositoryClasses($generator, $io);

        // Étape 2 : Repository déjà présent
        if (file_exists($repositoryPath)) {
            $io->text("Le repository {$entityName}Repository existe déjà ✅");

            // ✅ Mettre à jour l’héritage + interface si nécessaire
            [$level, $message] = $this->processRepositoryUpdate($repositoryPath);
            $this->printMessage($io, $level, $message);

            // ✅ Ajouter les méthodes custom manquantes (findAllActive, etc.)
            $this->addCustomRepositoryMethods($repositoryPath, $entityName, $io);
        } else {
            // Étape 3 : Génération initiale
            $io->warning("⚠️ Le repository {$entityName}Repository n'existe pas encore. Création en cours...");

            $templatePath = __DIR__ . '/../Resources/skeleton/solid_repository.tpl.php';
            $generator->generateClass(
                "App\\Repository\\{$entityName}Repository",
                $templatePath,
                [
                    'entityName' => $entityName,
                ]
            );

            $io->success("Repository {$entityName}Repository généré avec succès ✅");
        }

        // Étape 4 : Écriture des changements sur le disque
        $generator->writeChanges();
    }


    private function ensureBaseRepositoryClasses(Generator $generator, ConsoleStyle $io): void
    {
        $paths = [
            'helper' => ['src/Utils/DoctrineHelper.php', __DIR__ . '/../Resources/skeleton/doctrine_helper.tpl.php'],
            'interface' => ['src/Repository/SolidRepositoryInterface.php', __DIR__ . '/../Resources/skeleton/solid_repository_interface.tpl.php'],
            'abstract' => ['src/Repository/AbstractSolidRepository.php', __DIR__ . '/../Resources/skeleton/abstract_solid_repository.tpl.php'],
        ];

        foreach ($paths as $key => [$target, $template]) {
            if (!file_exists($target)) {
                $className = sprintf('%s\\%s', $generator->getRootNamespace(), match ($key) {
                    'helper' => 'Utils\\DoctrineHelper',
                    'interface' => 'Repository\\SolidRepositoryInterface',
                    'abstract' => 'Repository\\AbstractSolidRepository',
                });

                $generator->generateClass($className, $template);
                $io->success(basename($target) . " généré ✅");
            }
        }
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
                // 🔒 Créer une sauvegarde avant modification
                copy($repositoryPath, $repositoryPath . '.bak');
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
        // 1️⃣  Remplacer dans les commentaires et annotations (DocBlocks, use, etc.)
        $content = preg_replace(
            '/ServiceEntityRepository\b/',
            'AbstractSolidRepository',
            $content
        );

        // 2️⃣  Remplacer l’héritage de la classe (toutes occurrences également)
        $content = preg_replace(
            '/extends\s+AbstractSolidRepository\b/', // évite doublons si déjà modifié
            'extends AbstractSolidRepository implements SolidRepositoryInterface',
            $content
        );

        //3️⃣ Retire l'ancien use inutile à présent
        $content = preg_replace(
            '/^use\s+Doctrine\\\\Bundle\\\\DoctrineBundle\\\\Repository\\\\AbstractSolidRepository;\s*$/m',
            'use App\\\\Repository\\\\AbstractSolidRepository;',
            $content
        );

        if ($content === null) {
            return ['error', "Erreur lors de la modification de {$repositoryPath}"];
        }

        // 4️⃣ Écriture du fichier modifié
        if (file_put_contents($repositoryPath, $content) === false) {
            return ['error', "Impossible d’écrire dans {$repositoryPath}"];
        }

        return ['success', "{$repositoryPath} mis à jour pour hériter de AbstractSolidRepository ✅"];
    }


    private function printMessage(ConsoleStyle $io, string $level, string $message): void
    {
        match ($level) {
            'error' => $io->error($message),
            'warning' => $io->warning($message),
            'info' => $io->text($message),
            default => $io->success($message),
        };
    }

    private function addCustomRepositoryMethods(string $repositoryPath, string $entityName, ConsoleStyle $io): void
    {
        $content = file_get_contents($repositoryPath);
        if (!preg_match('/class\s+' . $entityName . 'Repository\b/', $content)) {
            $io->warning("Structure inattendue dans {$repositoryPath}");
            return;
        }


        $io->warning("contains ?", str_contains($content, 'findAllActive'));
        // Exemple : on ajoute une méthode findAllActive si absente
        if (!str_contains($content, 'findAllActive')) {
            $customMethod = <<<PHP

            public function findAllActive(): array
            {
                return \$this->createQueryBuilder('e')
                    ->where('e.active = true')
                    ->getQuery()
                    ->getResult();
            }

            PHP;
            // 🧩 Injecter la méthode juste avant la dernière accolade fermante
            // $content = preg_replace('/}\s*$/s', $customMethod . "\n}", $content, 1);
            $content = preg_replace(
                '/(class\s+' . $entityName . 'Repository.*\{)(.*)(\})/s',
                '$1$2' . $customMethod . "\n}",
                $content
            );


            file_put_contents($repositoryPath, $content);
            $io->success("Méthode findAllActive ajoutée à {$entityName}Repository ✅");
        }
    }
}
