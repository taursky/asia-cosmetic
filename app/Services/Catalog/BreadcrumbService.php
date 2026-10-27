<?php

namespace App\Services\Catalog;

use App\Models\Category;
use App\Models\Product;
use Illuminate\Support\Collection;

class BreadcrumbService
{
    /** @var Collection<int, Category>|null */
    private ?Collection $categories = null;

    /** @return array<int, array{title: string, url: string|null}> */
    public function catalog(): array
    {
        return [
            ['title' => 'Главная', 'url' => route('home')],
            ['title' => 'Каталог', 'url' => null],
        ];
    }

    /** @return array<int, array{title: string, url: string|null}> */
    public function category(Category $category): array
    {
        $items = $this->root();
        foreach ($this->ancestorsAndSelf($category) as $node) {
            $slug = $node->lang?->slug;
            $items[] = [
                'title' => $node->lang?->name ?: 'Раздел каталога',
                'url' => $slug ? route('catalog.category', ['categorySlug' => $slug]) : null,
            ];
        }
        $items[array_key_last($items)]['url'] = null;
        return $items;
    }

    /** @return array<int, array{title: string, url: string|null}> */
    public function product(Product $product): array
    {
        $items = $this->root();
        // Categories are already eager loaded by CatalogService::detailQuery().
        $category = $product->categories->first();
        if ($category) {
            foreach ($this->ancestorsAndSelf($category) as $node) {
                if ($node->lang?->slug) {
                    $items[] = [
                        'title' => $node->lang?->name ?: 'Раздел каталога',
                        'url' => route('catalog.category', ['categorySlug' => $node->lang->slug]),
                    ];
                }
            }
        }
        $items[] = ['title' => $product->lang?->name ?: 'Товар', 'url' => null];
        return $items;
    }

    /** @return array<int, array{title: string, url: string|null}> */
    public function collection(string $title): array
    {
        return [...$this->root(), ['title' => $title, 'url' => null]];
    }

    /** @return array<int, array{title: string, url: string|null}> */
    private function root(): array
    {
        return [
            ['title' => 'Главная', 'url' => route('home')],
            ['title' => 'Каталог', 'url' => route('catalog.index')],
        ];
    }

    /** @return array<int, Category> */
    private function ancestorsAndSelf(Category $category): array
    {
        // One batched load of the category tree and localized names per request.
        $this->categories ??= Category::query()
            ->where('is_active', true)
            ->with('lang')
            ->get(['id', 'parent_id', 'is_active'])
            ->keyBy('id');

        $path = [];
        $seen = [];
        $id = (int) $category->getKey();
        while ($id > 0 && ! isset($seen[$id])) {
            $seen[$id] = true;
            $node = $this->categories->get($id);
            if (! $node) {
                break;
            }
            $path[] = $node;
            $id = (int) ($node->parent_id ?? 0);
        }
        return array_reverse($path);
    }
}
