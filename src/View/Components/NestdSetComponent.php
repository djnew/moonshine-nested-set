<?php

declare(strict_types=1);

namespace Djnew\MoonShineNestedSet\View\Components;

use Illuminate\Database\Eloquent\Collection;
use Illuminate\Pagination\LengthAwarePaginator;
use MoonShine\Core\Traits\HasResource;
use MoonShine\Crud\Resources\CrudResource;
use MoonShine\Laravel\Resources\ModelResource;
use MoonShine\Support\AlpineJs;
use MoonShine\Support\Enums\JsEvent;
use MoonShine\UI\Collections\ActionButtons;
use MoonShine\UI\Components\ActionButton;
use MoonShine\UI\Components\MoonShineComponent;
use Throwable;

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
        parent::__construct();
        $this->setResource($resource);
    }


    public function setFragmentName(string $eventName): static
    {
        $this->fragmentName = AlpineJs::event(JsEvent::FRAGMENT_UPDATED, $eventName);

        return $this;
    }

    protected function items(): Collection|LengthAwarePaginator
    {
        return $this->getResource()->getItems();
    }

    /**
     * @throws Throwable
     */
    protected function viewData(): array
    {
        $page = (int)request()->input('page', 1);
        $events = $this?->fragmentName ? [$this->fragmentName] : [];
        $upDownButtons = [];
        if($this->getResource()->showUpDownButtons){
            $upDownButtons = [
                ActionButton::make('', $this->getResource()->getUrl())
                    ->icon('chevron-up')
                    ->method('nestedsetUp', events: $events, resource: $this->getResource())
                    ->customAttributes([
                        'class' => 'nested-tree-action__up',
                    ]),
                ActionButton::make('', $this->getResource()->getUrl())
                    ->icon('chevron-down')
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
            'route'        => $this->getResource()->getAsyncMethodUrl('nestedset', page: $this->getResource()->getPages()->first()),
            'buttons'      => function ($item) use($page, $events, $upDownButtons) {
                /** @var CrudResource $resource */
                $resource = $this->getResource()->setItem($item);

                return ActionButtons::make([
                    ...$resource->getIndexPage()->getButtons(),
                    ...$upDownButtons,
                ])->fill($resource->getCastedData());
            }
        ];
    }
}
