@props([
    'resource',
    'items',
    'buttons',
    'page',
    'fragmentName',
    'route',
])

@if(count($items) > 0)
    <div
        @if($resource->wrapable())
            x-data="{tree_show_all: $persist(true).as('tree_resource_all')}"
        @endif
    >
        <ul
            @if($resource->sortable())
                x-data="nestedset('{{ $route }}', 'nested')"
                data-id=""
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
            @foreach($items as $item)
                <x-moonshine-nestedset::tree.item
                    :item="$item"
                    :page="$page"
                    :resource="$resource"
                    :fragment-name="$fragmentName"
                    :route="$route"
                    :buttons="$buttons"
                />
            @endforeach
        </ul>

        @if($resource->isPaginationUsed())
            {{ $items->links('moonshine::ui.pagination', ['async' => false]) }}
        @endif
    </div>
@endif
