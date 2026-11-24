<?php

namespace App\Catalog;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;

final readonly class CatalogFilter
{
    private function __construct(public string $query, public ?int $category)
    {
    }

    public static function fromRequest(Request $request): self
    {
        $parameters = $request->query->all();
        $query = $parameters['q'] ?? '';
        $category = $parameters['category'] ?? '';
        if (!is_string($query) || !mb_check_encoding($query, 'UTF-8') || mb_strlen($query) > 120) {
            throw new BadRequestHttpException('Пошук має бути рядком до 120 символів.');
        }
        if ($category === '') {
            return new self(trim($query), null);
        }
        $id = filter_var($category, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
        if ($id === false) {
            throw new BadRequestHttpException('Категорія має бути додатним цілим числом.');
        }
        return new self(trim($query), $id);
    }
}
