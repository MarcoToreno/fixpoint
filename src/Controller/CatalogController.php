<?php

namespace App\Controller;

use App\Catalog\CatalogFilter;
use App\Entity\Category;
use App\Entity\RepairService;
use App\Repository\RepairServiceRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final class CatalogController extends AbstractController
{
    #[Route('/', name: 'catalog_index', methods: ['GET'])]
    public function index(Request $request, RepairServiceRepository $services, EntityManagerInterface $manager): Response
    {
        $filter = CatalogFilter::fromRequest($request);

        return $this->render('catalog/index.html.twig', [
            'services' => $services->search($filter->query, $filter->category),
            'categories' => $manager->getRepository(Category::class)->findBy([], ['name' => 'ASC']),
            'filter' => $filter,
        ]);
    }

    #[Route('/services/{id}', name: 'catalog_show', requirements: ['id' => '\d+'], methods: ['GET'])]
    public function show(RepairService $service): Response
    {
        return $this->render('catalog/show.html.twig', ['service' => $service]);
    }
}
