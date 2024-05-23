@props([
    'resource',
    'item',
    'buttons',
    'page',
    'fragmentName'
])

@if($resource->treeKey())
    <li class="nested-element"
        data-id="{{ $item->getKey() }}"
        @if($fragmentName)
        data-fragmentEvent="{{ $fragmentName }}"
        @endif
        @if($resource->wrapable())
            x-data="{tree_show_{{ $item->getKey() }}: $persist(true).as('tree_resource_{{ $item->getKey() }}')}"
        @endif
    >
    <div class="nested-element__data handle">
        <div class="nested-element__data-item">
            @if($resource->sortable())
                <x-moonshine::icon icon="heroicons.bars-3-bottom-right" />
            @endif

            <div class="font-bold">
                <x-moonshine::badge color="purple">{{ $item->getKey() }}</x-moonshine::badge>
                {{ $item->{$resource->column()} }}
            </div>

            {!! $resource->itemContent($item) !!}
        </div>

        <div class="nested-element__data-buttons @if($page > 1) show-up @endif">
            <x-moonshine::action-group
                :actions="$buttons($item)"
            />
        </div>
    </div>

        <ul
            x-data="nestedset('{{ $resource->route('nestedset') }}', 'nested')"
            data-id="{{ $item->getKey() }}"
            data-handle=".handle"
            data-animation="150"
            data-fallbackOnBody="true"
            data-swapThreshold="0.65"
        >
    @if(!empty($item->{$resource->treeRelationName}))
        @foreach($item->{$resource->treeRelationName}->all() as $inner)
            <x-moonshine-nestedset::tree.item
                :item="$inner"
                :page="$page"
                :resource="$resource"
                @if($fragmentName)
                    :fragment-name="$fragmentName"
                @endif
                :buttons="$buttons"
            />
        @endforeach
    @endif

        </ul>
    </li>
@endif
