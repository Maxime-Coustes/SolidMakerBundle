<?= "<?php\n" ?>

namespace App\Controller;

use <?= $entityClass ?>;
use <?= $entityClass ?>Collection;
use App\Service\<?= $entityName ?>Service;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;

#[Route('/api/<?= strtolower($entityName) ?>s', name: 'api_<?= strtolower($entityName) ?>_')]
class <?= $entityName ?>Controller extends AbstractController
{

    public function __construct(private readonly <?= $serviceName ?> $<?= lcfirst(basename(str_replace('\\', '/', $serviceName))) ?>)
    {
    }

    #[Route('/create', name: 'create', methods: ['POST'])]
    public function create(Request $request): JsonResponse
    {
        $data = json_decode($request->getContent(), true);
        $collection = new <?= $entityName ?>Collection();

        foreach ($data['<?= strtolower($entityName) ?>s'] ?? [] as $payload) {
            $entity = new <?= $entityName ?>();

            // Exemple minimal : seules les propriétés présentes dans le payload sont définies
            foreach ($payload as $field => $value) {
                $setter = 'set' . ucfirst($field);
                if (method_exists($entity, $setter)) {
                    $entity->$setter($value);
                }
            }

            $collection->add<?= $entityName ?>($entity);
        }

        $result = $this-><?= lcfirst($entityName) ?>Service->create<?= $entityName ?>Collection($collection);

        return $this->json($result);
    }

    #[Route('/update', name: 'update', methods: ['PUT'])]
    public function update(Request $request): JsonResponse
    {
        $data = json_decode($request->getContent(), true);
        $collection = new <?= $entityName ?>Collection();
        $notFound = new <?= $entityName ?>Collection();

        foreach ($data['<?= strtolower($entityName) ?>s'] ?? [] as $payload) {
            if (empty($payload['id'])) {
                continue;
            }

            $<?= strtolower($entityName) ?> = new <?= $entityName ?>();
            $<?= strtolower($entityName) ?>->setId($payload['id']);

            foreach ($payload as $field => $value) {
               $<?= strtolower($entityName) ?>->{"__newValues"}[$field] = $value;
            }

            $collection->add<?= $entityName ?>($<?= strtolower($entityName) ?>);
        }

        if ($collection->isEmpty()) {
            return $this->json([
                'updated' => [],
                'not_found' => $notFound->get<?= $entityName ?>s(),
                'message' => 'Aucun élément valide à mettre à jour.'
            ], 404);
        }

        $updated = $this-><?= lcfirst($entityName) ?>Service->update<?= $entityName ?>s($collection);

        return $this->json([
            'updated' => $updated['updated']->get<?= $entityName ?>s(),
            'not_found' => $updated['not_found']->get<?= $entityName ?>s(),
        ]);
    }


    #[Route('', name: 'list', methods: ['GET'])]
    public function list(): JsonResponse
    {
        $<?= lcfirst(basename(str_replace('\\', '/', $entityName))) ?>s = $this-><?= lcfirst(basename(str_replace('\\', '/', $serviceName))) ?>->findAll();

         $data = array_map(fn($<?= lcfirst(basename(str_replace('\\', '/', $entityName))) ?>) => [
            'id' => $<?= lcfirst(basename(str_replace('\\', '/', $entityName))) ?>->getId(),
            'name' => $<?= lcfirst(basename(str_replace('\\', '/', $entityName))) ?>->getName(),
            'status' => $<?= lcfirst(basename(str_replace('\\', '/', $entityName))) ?>->getStatus(),
            // ajouter d'autres propriétés si nécessaire
        ], $<?= lcfirst(basename(str_replace('\\', '/', $entityName))) ?>s);

        return $this->json($data);
    }

    #[Route('/{id}', name: 'read', methods: ['GET'])]
    public function read(int $id): JsonResponse
    {
        $entity = $this-><?= lcfirst(basename(str_replace('\\', '/', $serviceName))) ?>->find($id);

        if (!$entity) {
            return new JsonResponse(['error' => '<?= $entityClass ?> not found'], JsonResponse::HTTP_NOT_FOUND);
        }

        return new JsonResponse($entity);
    }

    #[Route('/{id}', name: 'delete', methods: ['DELETE'])]
    public function delete(int $id): JsonResponse
    {
        $this-><?= lcfirst(basename(str_replace('\\', '/', $serviceName))) ?>->delete<?= $entityName ?>ById($id);

        return new JsonResponse(['message' => sprintf('<?= $entityName ?> %d deleted', $id)]);
    }
}
