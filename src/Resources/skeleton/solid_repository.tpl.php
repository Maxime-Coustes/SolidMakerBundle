<?= "<?php\n" ?>
namespace App\Repository;

use App\Entity\<?= $entityName ?>;
use App\Entity\<?= $entityName ?>Collection;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

class <?= $entityName ?>Repository extends AbstractSolidRepository implements SolidRepositoryInterface
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, <?= $entityName ?>::class);
    }

    // Exemple de méthode custom
    public function findByName(string $name): ?<?= $entityName ?>
    {
        return $this->findOneBy(['name' => $name]);
    }

    /**
     * Persiste une collection d'entités <?= $entityName ?>.
     */
    public function create<?= $entityName ?>s(<?= $entityName ?>Collection $<?= lcfirst($entityName) ?>s): void
    {
        $em = $this->getEntityManager();

        foreach ($<?= lcfirst($entityName) ?>s->get<?= $entityName ?>s() as $<?= lcfirst($entityName) ?>) {
            $em->persist($<?= lcfirst($entityName) ?>);
        }

        $em->flush();
    }

    /**
     * Met à jour une collection d'entités <?= $entityName ?>.
     *
     * Ici on suppose que les entités de la collection sont déjà attachées à l'EntityManager.
     */
    public function update<?= $entityName ?>s(<?= $entityName ?>Collection $<?= lcfirst($entityName) ?>s): void
    {
        $em = $this->getEntityManager();

        foreach ($<?= lcfirst($entityName) ?>s->get<?= $entityName ?>s() as $<?= lcfirst($entityName) ?>) {
            // persist n'est pas nécessaire si l'entité est déjà gérée, flush suffit
            if (!$em->contains($<?= lcfirst($entityName) ?>)) {
                $em->persist($<?= lcfirst($entityName) ?>);
            }
        }

        $em->flush();
    }

    /**
     * Supprime une entité <?= $entityName ?>.
     */
    public function delete<?= $entityName ?>(<?= $entityName ?> $<?= lcfirst($entityName) ?>): void
    {
        $em = $this->getEntityManager();
        $em->remove($<?= lcfirst($entityName) ?>);
        $em->flush();
    }
}
