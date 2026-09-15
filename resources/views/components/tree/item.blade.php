@props([
    'resource',
    'item',
    'buttons',
    'page',
    'fragmentName',
    'route',
])

<li class="nested-element"
    data-id="{{ $item->getKey() }}"
    @if($fragmentName)
        data-fragment-event="{{ $fragmentName }}"
    @endif
    @if($resource->wrapable())
        x-data="{tree_show_{{ $item->getKey() }}: $persist(true).as('tree_resource_{{ $item->getKey() }}')}"
    @endif
>
    <div class="nested-element__data handle">
        <div class="nested-element__data-item">
            @if($resource->sortable())
                <x-moonshine::icon icon="bars-3-bottom-right" />
            @endif

            <div class="font-bold">
                <x-moonshine::badge color="purple">{{ $item->getKey() }}</x-moonshine::badge>
                {{ $item->{$resource->getColumn()} }}
            </div>

            {!! $resource->itemContent($item) !!}
        </div>

        <div class="nested-element__data-buttons @if($page > 1) show-up @endif">
            <x-moonshine::action-group :actions="$buttons($item)" />
        </div>
    </div>

    <ul
        @if($resource->sortable())
            x-data="nestedset('{{ $route }}', 'nested')"
            data-id="{{ $item->getKey() }}"
            data-handle=".handle"
            data-animation="150"
            data-fallback-on-body="true"
            data-swap-threshold="0.65"
            data-empty-insert-threshold="16"
            data-nested-set-list
            data-success-message="{{ __('moonshine-nestedset::ui.order_saved') }}"
            data-error-message="{{ __('moonshine-nestedset::ui.order_save_failed') }}"
        @endif
    >
        @foreach($item->{$resource->treeRelationName}->all() as $inner)
            <x-moonshine-nestedset::tree.item
                :item="$inner"
                :page="$page"
                :resource="$resource"
                :fragment-name="$fragmentName"
                :route="$route"
                :buttons="$buttons"
            />
        @endforeach
    </ul>
</li>
