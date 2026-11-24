<?php

namespace App\Controller\Admin;

use App\Entity\Category;
use Doctrine\DBAL\Exception\ForeignKeyConstraintViolationException;
use Doctrine\ORM\EntityManagerInterface;
use EasyCorp\Bundle\EasyAdminBundle\Config\Action;
use EasyCorp\Bundle\EasyAdminBundle\Config\Actions;
use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;
use Symfony\Component\HttpKernel\Exception\ConflictHttpException;

final class CategoryCrudController extends AbstractCrudController
{
    public static function getEntityFqcn(): string
    {
        return Category::class;
    }

    public function configureCrud(Crud $crud): Crud
    {
        return $crud->setEntityLabelInSingular('Категорія')
            ->setEntityLabelInPlural('Категорії')->setDefaultSort(['name' => 'ASC']);
    }

    public function configureActions(Actions $actions): Actions
    {
        return $actions->add(Crud::PAGE_INDEX, Action::DETAIL)
            ->disable(Action::BATCH_DELETE)
            ->update(Crud::PAGE_INDEX, Action::DELETE, static fn (Action $action) =>
                $action->displayIf(static fn (Category $category) => $category->getServices()->isEmpty()))
            ->update(Crud::PAGE_DETAIL, Action::DELETE, static fn (Action $action) =>
                $action->displayIf(static fn (Category $category) => $category->getServices()->isEmpty()));
    }

    public function configureFields(string $pageName): iterable
    {
        yield TextField::new('name', 'Назва')->setRequired(true);
        yield TextField::new('description', 'Опис')->setRequired(true);
    }

    public function deleteEntity(EntityManagerInterface $entityManager, object $entityInstance): void
    {
        if ($entityInstance instanceof Category && !$entityInstance->getServices()->isEmpty()) {
            throw new ConflictHttpException('Спочатку перенесіть або видаліть послуги цієї категорії.');
        }

        try {
            parent::deleteEntity($entityManager, $entityInstance);
        } catch (ForeignKeyConstraintViolationException $exception) {
            throw new ConflictHttpException('Категорія містить пов’язані послуги.', $exception);
        }
    }
}
