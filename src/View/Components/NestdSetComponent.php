<?php

declare(strict_types=1);

namespace Djnew\MoonShineNestedSet\View\Components;

use Illuminate\Database\Eloquent\Collection;
use Illuminate\Pagination\LengthAwarePaginator;
use MoonShine\ActionButtons\{ActionButton, ActionButtons};
use MoonShine\Buttons\{DeleteButton, DetailButton, EditButton};
use MoonShine\Components\MoonshineComponent;
use MoonShine\Resources\ModelResource;
use MoonShine\Traits\HasResource;

/**
 * @method static static make(ModelResource $resource)
 */
final class NestdSetComponent extends MoonshineComponent
{
    use HasResource;

    protected string $view = 'moonshine-nestedset::components.tree.index';

    protected ?string $fragmentName = '';

    public function __construct(ModelResource $resource)
    {
        $this->setResource($resource);
    }


    public function setFragmentName(string $eventName): static
    {
        $this->fragmentName = 'fragment-updated-' . $eventName;

        return $this;
    }

    protected function items(): Collection|LengthAwarePaginator
    {
        return $this->getResource()->getItems();
    }

    protected function viewData(): array
    {
        $page = (int)request()->input('page', 1);
        $events = $this?->fragmentName ? [$this->fragmentName] : [];
        $upDownButtons = [];
        if($this->getResource()->showUpDownButtons){
            $upDownButtons = [
                ActionButton::make('', $this->getResource()->url())
                    ->icon('heroicons.chevron-up')
                    ->method('nestedsetUp', events: $events, resource: $this->getResource())
                    ->customAttributes([
                        'class' => 'nested-tree-action__up',
                    ]),
                ActionButton::make('', $this->getResource()->url())
                    ->icon('heroicons.chevron-down')
                    ->method('nestedsetDown', events: $events, resource: $this->getResource())
                    ->customAttributes([
                        'class' => 'nested-tree-action__down',
                    ]),
            ];
        }
        return [
            'items'        => $this->items(),
            'page'         => $page,
            'fragmentName' => $this->fragmentName ?? '',
            'resource'     => $this->getResource(),
            'route'        => $this->getResource()->route('nestedset'),
            'buttons'      => function ($item) use($page, $events) {
                $resource = $this->getResource()->setItem($item);

                return ActionButtons::make([
                    ...$resource->getIndexButtons(),
                    ...$upDownButtons,
                    DetailButton::for($resource),
                    EditButton::for($resource, 'tree'),
                    DeleteButton::for($resource, 'tree'),
                ])->fillItem($item);
            }
        ];
    }
}
