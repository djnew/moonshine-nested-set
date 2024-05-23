<?php

declare(strict_types=1);

namespace Djnew\MoonShineNestedSet\Resources;

use Illuminate\Contracts\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\{Route};
use MoonShine\Http\Requests\Resources\ViewAnyFormRequest;
use MoonShine\Resources\ModelResource;

abstract class NestedSetResource extends ModelResource
{
    public string $treeRelationName = 'childrenNestedset';
    protected string $sortDirection = 'ASC';

    protected bool $usePagination = false;

    protected int $itemsPerPage = 15;

    protected bool $isAsync = true;

    abstract public function treeKey(): ?string;


    public function getItems()
    {
        return $this->isPaginationUsed()
            ? $this->model::defaultOrder()
                ->whereNull($this->treeKey())
                ->paginate($this->itemsPerPage)
            : $this->model::defaultOrder()
                ->whereNull($this->treeKey())
                ->get();
    }

    public function getQuery(): Builder
    {
        return parent::query()->whereNull($this->treeKey())->with($this->treeRelationName);
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

    public function nestedsetDown(): void
    {
        $item     = $this->model::find($this->getItemID());
        $neighbor = $item->nextSiblings()->get()->first();
        $item?->insertAfterNode($neighbor);
    }


    public function nestedsetUp(): void
    {
        $item     = $this->model::find($this->getItemID());
        $neighbor = $item->prevSiblings()->get()->first();
        $item?->insertBeforeNode($neighbor);
    }

    protected function resolveRoutes(): void
    {
        parent::resolveRoutes();


        Route::post('nestedset', function (ViewAnyFormRequest $request) {
            /** @var NestedsetResource $resource */
            $resource = $request->getResource();
            $keyName  = $resource->getModel()->getKeyName();
            $model    = $resource->getModel();


            if ($resource->treeKey() && $request->str('data')->isNotEmpty()) {

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
        })->name('nestedset');
    }
}
