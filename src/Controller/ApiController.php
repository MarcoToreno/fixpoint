<?php

namespace App\Controller;

use App\Catalog\CatalogFilter;
use App\Entity\Category;
use App\Entity\RepairService;
use App\Repository\RepairServiceRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/api', name: 'api_')]
final class ApiController extends AbstractController
{
    #[Route('/services', name: 'services', methods: ['GET'])]
    public function list(Request $request, RepairServiceRepository $repository): JsonResponse
    {
        try {
            $filter = CatalogFilter::fromRequest($request);
        } catch (BadRequestHttpException $exception) {
            return $this->json(['error' => $exception->getMessage()], 400);
        }

        $services = $repository->search($filter->query, $filter->category);

        return $this->json(['data' => array_map($this->serviceData(...), $services), 'count' => count($services)]);
    }

    #[Route('/services/{id}', name: 'service', requirements: ['id' => '\d+'], methods: ['GET'])]
    public function show(string $id, RepairServiceRepository $repository): JsonResponse
    {
        $number = filter_var($id, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
        $service = $number === false ? null : $repository->find($number);

        return $service === null
            ? $this->json(['error' => 'Послугу не знайдено.'], 404)
            : $this->json(['data' => $this->serviceData($service)]);
    }

    #[Route('/categories', name: 'categories', methods: ['GET'])]
    public function categories(EntityManagerInterface $manager): JsonResponse
    {
        $categories = $manager->getRepository(Category::class)->findBy([], ['name' => 'ASC']);

        return $this->json(['data' => array_map($this->categoryData(...), $categories)]);
    }

    private function serviceData(RepairService $service): array
    {
        return [
            'id' => $service->getId(),
            'title' => $service->getTitle(),
            'description' => $service->getDescription(),
            'price_uah' => $service->getPrice(),
            'duration_minutes' => $service->getDuration(),
            'available' => $service->isAvailable(),
            'category' => $this->categoryData($service->getCategory()),
        ];
    }

    private function categoryData(Category $category): array
    {
        return ['id' => $category->getId(), 'name' => $category->getName(), 'description' => $category->getDescription()];
    }
}
