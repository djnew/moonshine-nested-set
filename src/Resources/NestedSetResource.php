<?php

declare(strict_types=1);

namespace Djnew\MoonShineNestedSet\Resources;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Response;
use MoonShine\Laravel\Resources\ModelResource;
use MoonShine\Support\Attributes\AsyncMethod;
use MoonShine\Support\Enums\SortDirection;

abstract class NestedSetResource extends ModelResource
{
    public string $treeRelationName = 'childrenNestedset';

    protected SortDirection $sortDirection = SortDirection::ASC;

    protected bool $usePagination = false;

    protected int $itemsPerPage = 15;

    protected bool $isAsync = true;

    public bool $showUpDownButtons = false;

    public function treeKey(): string
    {
        return $this->getModel()->getParentIdName();
    }

    public function getSortColumn(): string
    {
        return $this->getModel()->getLftName();
    }

    public function getQuery(): Builder
    {
        return parent::getQuery()
            ->whereNull($this->treeKey())
            ->with($this->treeRelationName);
    }

    public function itemContent(Model $item): string
    {
        return '';
    }

    public function sortable(): bool
    {
        return true;
    }

    public function wrapable(): bool
    {
        return true;
    }

    #[AsyncMethod]
    public function nestedsetDown(): void
    {
        $this->resolveNestedSetItem()?->down();
    }

    #[AsyncMethod]
    public function nestedsetUp(): void
    {
        $this->resolveNestedSetItem()?->up();
    }

    #[AsyncMethod]
    public function nestedset(): Response
    {
        $request = request();
        $id = $request->input('id');

        if ($id === null || $id === '') {
            return response()->noContent();
        }

        $model = $this->getModel();
        $parentId = $this->normalizeParentId($request->input('parent'));
        $index = $request->integer('index');
        $orderedIds = $request
            ->string('data')
            ->explode(',')
            ->filter(static fn (string $value): bool => $value !== '')
            ->values();

        $model->getConnection()->transaction(function () use ($model, $id, $parentId, $index, $orderedIds): void {
            /** @var Model $element */
            $element = $model->newModelQuery()->whereKey($id)->firstOrFail();
            $setAfter = $index > 0;

            if ($orderedIds->contains(static fn (string $orderedId): bool => (string) $orderedId === (string) $id)
                && $orderedIds->count() > 1
            ) {
                $neighborId = $setAfter
                    ? $orderedIds->get($index - 1)
                    : $orderedIds->get($index + 1);

                if ($neighborId !== null && (string) $neighborId !== (string) $id) {
                    /** @var Model|null $neighbor */
                    $neighbor = $element->newScopedQuery()->whereKey($neighborId)->first();

                    if ($neighbor !== null) {
                        $setAfter
                            ? $element->insertAfterNode($neighbor)
                            : $element->insertBeforeNode($neighbor);

                        return;
                    }
                }
            }

            if (! $this->parentIdsEqual($element->getAttribute($this->treeKey()), $parentId)) {
                $this->moveAsOnlyChildOrRoot($element, $parentId);
            }
        });

        return response()->noContent();
    }

    private function resolveNestedSetItem(): ?Model
    {
        $id = $this->getItemID();

        if ($id === null || $id === '') {
            return null;
        }

        return $this->getModel()->newModelQuery()->whereKey($id)->first();
    }

    private function normalizeParentId(mixed $parentId): mixed
    {
        return $parentId === '' ? null : $parentId;
    }

    private function parentIdsEqual(mixed $left, mixed $right): bool
    {
        $left = $this->normalizeParentId($left);
        $right = $this->normalizeParentId($right);

        if ($left === null || $right === null) {
            return $left === $right;
        }

        return (string) $left === (string) $right;
    }

    private function moveAsOnlyChildOrRoot(Model $element, mixed $parentId): void
    {
        if ($parentId === null) {
            $element->makeRoot()->save();

            return;
        }

        /** @var Model $parent */
        $parent = $element->newScopedQuery()->whereKey($parentId)->firstOrFail();

        $element->appendToNode($parent)->save();
    }
}
