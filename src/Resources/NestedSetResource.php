<?php

declare(strict_types=1);

namespace Djnew\MoonShineNestedSet\Resources;

use Illuminate\Contracts\Database\Eloquent\Builder;
use Illuminate\Contracts\Pagination\CursorPaginator;
use Illuminate\Contracts\Pagination\Paginator;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;
use Illuminate\Support\LazyCollection;
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

    abstract public function treeKey(): ?string;


    public function getItems(): Collection|LazyCollection|CursorPaginator|Paginator
    {
        return $this->isPaginationUsed()
            ? $this->paginate()
                ->whereNull($this->treeKey())
                ->paginate($this->itemsPerPage)
            : $this->getQuery()
                ->whereNull($this->treeKey())
                ->get();
    }

    public function getQuery(): Builder
    {
        return parent::getQuery()->whereNull($this->treeKey())->with($this->treeRelationName);
    }

    public function sortDirection(): string
    {
        return 'asc';
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
        $item     = $this->getModel()::find($this->getItemID());
        $neighbor = $item->nextSiblings()->get()->first();
        $item?->insertAfterNode($neighbor);
    }


    #[AsyncMethod]
    public function nestedsetUp(): void
    {
        $item     = $this->getModel()::find($this->getItemID());
        $neighbor = $item->prevSiblings()->get()->first();
        $item?->insertBeforeNode($neighbor);
    }

    #[AsyncMethod]
    public function nestedset() {
        /** @var NestedsetResource $resource */
        $request = request();
        $resource = $this;
        $keyName  = $resource->getModel()->getKeyName();
        $model    = $resource->getModel();

        if ($resource->treeKey() && $request->str('data')) {

            $id       = $request->get('id');
            $index    = $request->integer('index');
            $parentId = $request->get('parent');

            $element = $model
                ->newModelQuery()
                ->firstWhere($keyName, $id);

            $caseStatement = $request
                ->str('data')
                ->explode(',');

            $setAfter = $index > 0;
            if (false !== $caseStatement->search($id) && $caseStatement->count() > 1) {
                $neighbor = $resource->getModel()->newModelQuery()
                    ->firstWhere(
                        $keyName,
                        $setAfter ? $caseStatement[--$index] : $caseStatement[++$index]
                    );

                if ($neighbor) {
                    if ($setAfter) {
                        $element?->insertAfterNode($neighbor);
                    } else {
                        $element?->insertBeforeNode($neighbor);
                    }
                }
            }

            if ($element->{$this->treeKey()} !== $parentId) {
                $element?->setParentId($parentId)->save();
                $resource->getModel()?->fixTree();
            }
        }

        return response()->noContent();
    }
}
