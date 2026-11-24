<?php

namespace App\Controller\Admin;

use EasyCorp\Bundle\EasyAdminBundle\Attribute\AdminDashboard;
use EasyCorp\Bundle\EasyAdminBundle\Config\Dashboard;
use EasyCorp\Bundle\EasyAdminBundle\Config\MenuItem;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractDashboardController;
use Symfony\Component\HttpFoundation\Response;

#[AdminDashboard(routePath: '/admin', routeName: 'admin')]
final class DashboardController extends AbstractDashboardController
{
    public function index(): Response
    {
        return $this->redirectToRoute('admin_repair_service_index');
    }

    public function configureDashboard(): Dashboard
    {
        return Dashboard::new()->setTitle('FixPoint · Майстерня');
    }

    public function configureMenuItems(): iterable
    {
        yield MenuItem::section('Каталог');
        yield MenuItem::linkTo(RepairServiceCrudController::class, 'Послуги', 'fa fa-screwdriver-wrench');
        yield MenuItem::linkTo(CategoryCrudController::class, 'Категорії', 'fa fa-folder');
        yield MenuItem::linkToRoute('Публічний сайт', 'fa fa-arrow-up-right-from-square', 'catalog_index');
    }
}
