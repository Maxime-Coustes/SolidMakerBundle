<?= "<?php\n" ?>
namespace App\Repository;

interface SolidRepositoryInterface
{
    public function getEntityClass(): string;
}
