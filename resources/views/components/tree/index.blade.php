@props([
    'resource',
    'item',
    'buttons',
    'page',
    '$fragmentName',
])

@if(!empty($items[0]))
    <div
        @if($resource->wrapable())
        x-data="{tree_show_all: $persist(true).as('tree_resource_all')}"
        @endif
    >
        <ul @if($resource->sortable())
                x-data="nestedset('{{ $route }}', 'nested')"
                data-id=""
                data-handle=".handle"
                data-animation="150"
                data-fallbackOnBody="true"
                data-swapThreshold="0.65"
            @endif
        >
            @foreach($items as $item)
                <x-moonshine-nestedset::tree.item
                    :item="$item"
                    :page="$page"
                    :resource="$resource"
                    :fragment-name="$fragmentName"
                    :buttons="$buttons"
                />
            @endforeach
        </ul>
            @if($resource->isPaginationUsed())
                {{ $items->links(
                    false
                        ? 'moonshine::ui.simple-pagination'
                        : 'moonshine::ui.pagination',
                    ['async' => false]
                ) }}
            @endif
    </div>
@endif
