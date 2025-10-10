<?= "<?php\n" ?>

namespace App\Utils;

use App\Utils\DoctrineHelper;

class EntityArrayConverter
{
    /**
    * Convertit une entité en tableau associatif.
    *
    * Priorité aux valeurs en mémoire contenues dans __newValues si elles existent.
    * Utilise les getters publics pour récupérer les autres propriétés.
    * Si aucun getter n'est disponible, la valeur sera null.
    *
    * @param object $entity L'entité à convertir
    * @return array Tableau associatif des propriétés de l'entité
    */
    public static function toArray(object $entity): array
    {
        $array = [];
        $reflection = new \ReflectionClass($entity);

        foreach ($reflection->getProperties() as $property) {
            $name = $property->getName();
            $getter = 'get' . ucfirst($name);

            // priorité à __newValues si existant
            if (property_exists($entity, '__newValues') && isset($entity->__newValues[$name])) {
                $array[$name] = $entity->__newValues[$name];
            } elseif (method_exists($entity, $getter)) {
                // utilise le getter si disponible
                $array[$name] = $entity->$getter();
            } else {
                // fallback, récupère directement la valeur privée (attention SonarQube)
                $array[$name] = null;
            }
        }

        return $array;
    }
}
