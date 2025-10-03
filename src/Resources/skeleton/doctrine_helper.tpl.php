<?= "<?php\n" ?>

namespace App\Utils;

use Doctrine\ORM\Mapping\Column;
use ReflectionClass;

class DoctrineHelper
{
    /**
     * Retourne la liste des colonnes Doctrine d'une entité donnée.
     *
     * Utilise la reflection pour récupérer toutes les propriétés annotées
     * avec #[ORM\Column]. Permet d'exclure la colonne `id` si nécessaire.
     *
     * @param class-string $entityClass
     * @param bool $excludeId
     * @return string[]
     */
    public static function getDoctrineColumns(string $entityClass, bool $excludeId = true): array
    {
        static $columnsCache = [];

        if (isset($columnsCache[$entityClass])) {
            return $columnsCache[$entityClass];
        }

        $columns = [];
        $reflection = new ReflectionClass($entityClass);

        foreach ($reflection->getProperties() as $property) {
            $attrs = $property->getAttributes(Column::class);
            if (!empty($attrs) && (!$excludeId || $property->getName() !== 'id')) {
                $columns[] = $property->getName();
            }
        }

        $columnsCache[$entityClass] = $columns;

        return $columns;
    }
}
