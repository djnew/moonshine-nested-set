<?php

declare(strict_types=1);

namespace Djnew\MoonShineNestedSet\View\Components;

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
final class NestedSetComponent extends MoonShineComponent
{
    use HasResource;

    protected string $view = 'moonshine-nestedset::components.tree.index';

    protected ?string $fragmentName = null;

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

    protected function items(): iterable
    {
        $resource = $this->getResource();

        $resource->setQueryParams(
            request()->only($resource->getQueryParamsKeys()),
        );

        return $resource->getItems();
    }

    /**
     * @throws Throwable
     */
    protected function viewData(): array
    {
        $page = max(1, (int) request()->input('page', 1));
        $events = $this->fragmentName ? [$this->fragmentName] : [];
        $upDownButtons = [];
        $route = $this->getResource()->getAsyncMethodUrl(
            'nestedset',
            page: $this->getResource()->getPages()->first()
        );

        if ($this->getResource()->showUpDownButtons) {
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
            'items' => $this->items(),
            'page' => $page,
            'fragmentName' => $this->fragmentName ?? '',
            'resource' => $this->getResource(),
            'route' => $route,
            'buttons' => function ($item) use ($upDownButtons): ActionButtons {
                /** @var CrudResource $resource */
                $resource = $this->getResource()->setItem($item);
                $indexButtons = $resource->getIndexPage()
                    ?->getButtons()
                    ->withoutBulk()
                    ->toArray() ?? [];

                return ActionButtons::make([
                    ...$indexButtons,
                    ...$upDownButtons,
                ])->fill($resource->getCastedData());
            },
        ];
    }
}
