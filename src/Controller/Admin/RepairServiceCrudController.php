<?php

namespace App\Controller\Admin;

use App\Entity\RepairService;
use EasyCorp\Bundle\EasyAdminBundle\Config\Action;
use EasyCorp\Bundle\EasyAdminBundle\Config\Actions;
use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;
use EasyCorp\Bundle\EasyAdminBundle\Config\Filters;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Field\AssociationField;
use EasyCorp\Bundle\EasyAdminBundle\Field\BooleanField;
use EasyCorp\Bundle\EasyAdminBundle\Field\IntegerField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextareaField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;
use EasyCorp\Bundle\EasyAdminBundle\Filter\BooleanFilter;
use EasyCorp\Bundle\EasyAdminBundle\Filter\EntityFilter;

final class RepairServiceCrudController extends AbstractCrudController
{
    public static function getEntityFqcn(): string
    {
        return RepairService::class;
    }

    public function configureCrud(Crud $crud): Crud
    {
        return $crud->setEntityLabelInSingular('Послуга')->setEntityLabelInPlural('Послуги')
            ->setSearchFields(['title', 'description', 'category.name'])
            ->setDefaultSort(['id' => 'ASC']);
    }

    public function configureActions(Actions $actions): Actions
    {
        return $actions->add(Crud::PAGE_INDEX, Action::DETAIL);
    }

    public function configureFilters(Filters $filters): Filters
    {
        return $filters->add(EntityFilter::new('category', 'Категорія'))
            ->add(BooleanFilter::new('available', 'Доступна'));
    }

    public function configureFields(string $pageName): iterable
    {
        yield TextField::new('title', 'Назва')->setRequired(true);
        yield AssociationField::new('category', 'Категорія')->setRequired(true);
        yield TextareaField::new('description', 'Опис')->hideOnIndex()->setRequired(true);
        yield IntegerField::new('price', 'Ціна, грн');
        yield IntegerField::new('duration', 'Тривалість, хв');
        yield BooleanField::new('available', 'Доступна')->renderAsSwitch(false);
    }
}
