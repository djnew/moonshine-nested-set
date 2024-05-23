## MoonShine sortable tree resource

> The resource tree of this package is based on the [kalnoy/nestedset](https://github.com/lazychaser/laravel-nestedset)



<p align="center">
<a href="https://moonshine-laravel.com" target="_blank">
<img src="https://github.com/djnew/moonshine-nested-set/blob/main/art/screenshot.png">
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

    protected string $model    = Page::class;

    // Custom child relation name
    public string $treeRelationName = 'children';

    // Use pagination
    protected bool $usePagination = true;

    // Show Up/Down element buttons for sort
    public bool $showUpDownButtons = false;

    // Items per page
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

use Djnew\MoonShineNestedSet\View\Components\NestdSetComponent;
use MoonShine\Pages\Crud\IndexPage;

class CategoryTreePage extends IndexPage
{
    protected function mainLayer(): array
    {
        return [
            ...$this->actionButtons(),
            NestdSetComponent::make($this->getResource()),
        ];
    }
}

```

Add migration to model

```php
use Kalnoy\Nestedset\NestedSet;

Schema::create('table', function (Blueprint $table) {
    ...
    NestedSet::columns($table);
});
```

To drop columns:
```php

...
use Kalnoy\Nestedset\NestedSet;

Schema::table('table', function (Blueprint $table) {
    NestedSet::dropColumns($table);
});
```

Add trait to model
```php
namespace App\Models;

use App\Traits\MoonshineNestedSetTrait;
use Illuminate\Database\Eloquent\Model;


class Page extends Model
{
    use moonshineNestedSetTrait;
    
    // ...
}

```




### Migrating existing data

#### Migrating from other nested set extension

If your previous extension used different set of columns, you just need to override
following methods on your model class:

```php
public function getLftName()
{
    return 'left';
}

public function getRgtName()
{
    return 'right';
}

public function getParentIdName()
{
    return 'parent';
}

// Specify parent id attribute mutator
public function setParentAttribute($value)
{
    $this->setParentIdAttribute($value);
}
```

#### Migrating from basic parentage info

If your tree contains `parent_id` info, you need to add two columns to your schema:

```php
$table->unsignedInteger('_lft');
$table->unsignedInteger('_rgt');
```

After [setting up your model](#the-model) you only need to fix the tree to fill
`_lft` and `_rgt` columns:

```php
MyModel::fixTree();
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

use Djnew\MoonShineNestedSet\View\Components\NestdSetComponent;
use MoonShine\Pages\Crud\IndexPage;

class CategoryTreePage extends IndexPage
{
    protected function mainLayer(): array
    {
        return [
            ...$this->actionButtons(),
            Fragment::make(
                [
                    NestdSetComponent::make($this->getResource())
                        ->setFragmentName('fragment-name')
                ]
            )->name('fragment-name')
            ->updateAsync(['page' => request()->get('page')]),
        ];
    }
}```