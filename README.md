## MoonShine sortable tree resource

> The resource tree of this package is based on the [kalnoy/nestedset](https://github.com/lazychaser/laravel-nestedset)



<p align="center">
<a href="https://moonshine-laravel.com" target="_blank">
<img src="https://github.com/djnew/moonshine-nested-set/blob/master/art/screenshot.png">
</a>
</p>

### Requirements

- MoonShine v2.0+

### Installation

```shell
composer require djnew/moonshine-nested-set
```

### Get started

Example usage with tree

```php
use Djnew\MoonShineNestedSet\Resources\NestedsetResource;

class CategoryResource extends NestedsetResource
{
    // Required
    protected string $column = 'title';

    // Custom child relation name
    public string $treeRelationName = 'children';

    // use pagination
    protected bool $usePagination = true;

    // items per page
    protected int $itemsPerPage = 10;


    protected function pages(): array
    {
        return [
            CategoryTreePage::make($this->title()),
            FormPage::make(
                $this->getItemID()
                    ? __('moonshine::ui.edit')
                    : __('moonshine::ui.add')
            ),
            DetailPage::make(__('moonshine::ui.show')),
        ];
    }

    // ... fields, model, etc ...

    public function treeKey(): ?string
    {
        return 'parent_id';
    }

    // ...
}
```

And add component

```php
namespace App\MoonShine\Pages;

use Djnew\MoonShineNestedSet\View\Components\NestdsetComponent;
use MoonShine\Pages\Crud\IndexPage;

class CategoryTreePage extends IndexPage
{
    protected function mainLayer(): array
    {
        return [
            ...$this->actionButtons(),
            NestdsetComponent::make($this->getResource()),
        ];
    }
}

```

Just a sortable usage

```php
use Djnew\MoonShineNestedSet\Resources\NestedsetResource;

class CategoryResource extends NestedsetResource
{
    // Required
    protected string $column = 'title';
=

    // ... fields, model, etc ...

    public function treeKey(): ?string
    {
        return null;
    }


    // ...
}
```


### Additional content

```php
public function itemContent(Model $item): string
{
    return 'Custom content here';
}
```

### Turn off sortable or wrapable

```php
public function wrapable(): bool
{
    return false;
}

```

### Use async reload tree resource
```php
namespace App\MoonShine\Pages;

use Djnew\MoonShineNestedSet\View\Components\NestdsetComponent;
use MoonShine\Pages\Crud\IndexPage;

class CategoryTreePage extends IndexPage
{
    protected function mainLayer(): array
    {
        return [
            ...$this->actionButtons(),
            Fragment::make(
                [
                    NestdsetComponent::make($this->getResource())
                        ->setFragmentName('fragment-name')
                ]
            )->name('fragment-name')
            ->updateAsync(['page' => request()->get('page')]),
        ];
    }
}```