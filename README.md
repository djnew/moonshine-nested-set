# MoonShine Nested Set

[![CI](https://github.com/djnew/moonshine-nested-set/actions/workflows/ci.yml/badge.svg)](https://github.com/djnew/moonshine-nested-set/actions/workflows/ci.yml)
[![Latest Stable Version](https://img.shields.io/packagist/v/djnew/moonshine-nested-set.svg)](https://packagist.org/packages/djnew/moonshine-nested-set)
[![PHP Version](https://img.shields.io/packagist/php-v/djnew/moonshine-nested-set.svg)](https://packagist.org/packages/djnew/moonshine-nested-set)
[![License](https://img.shields.io/packagist/l/djnew/moonshine-nested-set.svg)](LICENSE.md)

A sortable nested tree resource for [MoonShine](https://moonshine-laravel.com), powered by [kalnoy/nestedset](https://github.com/lazychaser/laravel-nestedset).

Drag nodes to reorder them or move them between parents. The package persists every change asynchronously and uses native MoonShine notifications for successful and failed saves.

<p align="center">
    <a href="https://moonshine-laravel.com" target="_blank">
        <img src="https://github.com/djnew/moonshine-nested-set/blob/main/art/screenshot.png" alt="Sortable nested tree in MoonShine" width="900">
    </a>
</p>

## Features

- Drag-and-drop sorting at any nesting level
- Moving nodes between parents and back to the root
- Comfortable drop zones for empty child lists
- Native MoonShine success and error toasts
- Automatic rollback when saving fails
- Optional pagination and move up/down actions
- Custom content and standard MoonShine action buttons for every node
- Async fragment refresh support

## Requirements

| Dependency         | Supported version |
| ------------------ | ----------------- |
| PHP                | 8.4 or newer      |
| Laravel            | 13.x              |
| MoonShine          | 4.11 or newer     |
| `kalnoy/nestedset` | 7.x               |

## Installation

Install the package and publish its compiled assets:

```bash
composer require djnew/moonshine-nested-set
php artisan vendor:publish --tag=moonshine-nestedset
```

Laravel discovers the service provider automatically.

> [!IMPORTANT]
> Republish the assets after every package update:
>
> ```bash
> php artisan vendor:publish --tag=moonshine-nestedset --force
> ```

## Quick start

### 1. Prepare the model

Add the nested-set columns to the table:

```php
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Kalnoy\Nestedset\NestedSet;

Schema::create('categories', static function (Blueprint $table): void {
    $table->id();
    $table->string('title');

    NestedSet::columns($table);

    $table->timestamps();
});
```

Then use `MoonshineNestedSetTrait` in the Eloquent model:

```php
namespace App\Models;

use Djnew\MoonShineNestedSet\Traits\MoonshineNestedSetTrait;
use Illuminate\Database\Eloquent\Model;

class Category extends Model
{
    use MoonshineNestedSetTrait;

    protected $fillable = ['title'];
}
```

The trait includes `kalnoy/nestedset`'s `NodeTrait` and adds the recursive `childrenNestedset` relationship used to render the tree.

### 2. Create the resource

Extend `NestedSetResource` instead of MoonShine's regular `ModelResource`:

```php
namespace App\MoonShine\Resources\Category;

use App\Models\Category;
use Djnew\MoonShineNestedSet\Resources\NestedSetResource;

/** @extends NestedSetResource<Category> */
class CategoryResource extends NestedSetResource
{
    protected string $model = Category::class;

    protected string $title = 'Categories';

    protected string $column = 'title';

    public function pages(): array
    {
        return [
            Pages\CategoryTreePage::class,
            Pages\CategoryFormPage::class,
        ];
    }
}
```

`$column` controls the model attribute displayed as the node title.

### 3. Render the tree page

Use `NestedSetComponent` in the index page:

```php
namespace App\MoonShine\Resources\Category\Pages;

use Djnew\MoonShineNestedSet\View\Components\NestedSetComponent;
use MoonShine\Laravel\Pages\Crud\IndexPage;

class CategoryTreePage extends IndexPage
{
    protected function mainLayer(): array
    {
        return [
            NestedSetComponent::make($this->getResource()),
        ];
    }
}
```

That is enough for a sortable tree. The package derives the parent, left and right column names from the model, so the default `parent_id`, `_lft` and `_rgt` columns work without extra configuration.

## Async fragment refresh

Wrap the tree in a MoonShine fragment when other actions on the page should refresh it without a full page reload:

```php
namespace App\MoonShine\Resources\Category\Pages;

use Djnew\MoonShineNestedSet\View\Components\NestedSetComponent;
use MoonShine\Crud\Components\Fragment;
use MoonShine\Laravel\Pages\Crud\IndexPage;

class CategoryTreePage extends IndexPage
{
    protected function mainLayer(): array
    {
        return [
            Fragment::make([
                NestedSetComponent::make($this->getResource())
                    ->setFragmentName('category-tree'),
            ])
                ->name('category-tree')
                ->updateWith(),
        ];
    }
}
```

## Custom node content

Override `itemContent()` to display metadata next to the title:

```php
use Illuminate\Database\Eloquent\Model;

public function itemContent(Model $item): string
{
    return sprintf(
        '<span class="text-xs text-gray-500">%s</span>',
        e($item->slug),
    );
}
```

The returned value is rendered as HTML. Escape all dynamic values with `e()`.

## Configuration

| Option               | Default             | Description                                                          |
| -------------------- | ------------------- | -------------------------------------------------------------------- |
| `$treeRelationName`  | `childrenNestedset` | Recursive relationship used to load child nodes                      |
| `$showUpDownButtons` | `false`             | Adds move up/down buttons to every node                              |
| `$usePagination`     | `false`             | Enables pagination for root nodes                                    |
| `$itemsPerPage`      | `15`                | Number of root nodes per page                                        |
| `sortable()`         | `true`              | Enables drag-and-drop sorting                                        |
| `wrapable()`         | `true`              | Enables the legacy Alpine state scopes around the tree and its nodes |
| `itemContent()`      | empty string        | Adds custom HTML next to a node title                                |

For example:

```php
protected bool $usePagination = true;

protected int $itemsPerPage = 25;

public bool $showUpDownButtons = true;

public function sortable(): bool
{
    return true;
}

public function wrapable(): bool
{
    return true;
}
```

> [!NOTE]
> The `wrapable()` method name and its Alpine state scopes are kept for backward compatibility. This option does not enable or disable drag-and-drop; use `sortable()` for that.

## Existing trees and custom columns

If the table already contains `parent_id` values, add `_lft` and `_rgt`, then rebuild the nested-set indexes once:

```php
use App\Models\Category;

Category::fixTree();
```

For non-standard column names, override the methods provided by `kalnoy/nestedset` in your model:

```php
public function getLftName(): string
{
    return 'left';
}

public function getRgtName(): string
{
    return 'right';
}

public function getParentIdName(): string
{
    return 'parent';
}
```

The resource reads these names directly; there is no separate package configuration to keep in sync.

## How sorting behaves

While a change is being saved, all lists in the tree are temporarily locked to prevent overlapping requests. A successful request shows a MoonShine success toast. If the request fails, the node returns to its previous position and the server message is shown when available.

Each save sends the new destination parent and sibling order. Moving a node into an empty child list is supported by a dedicated drop area beneath every parent.

## Development

```bash
composer install
npm ci

composer test
npm run prettier:check
npm run test:js
npm run build
```

Formatting commands:

```bash
composer format
npm run prettier
```

Compiled assets in `public/` are part of the package and must be rebuilt before publishing a release.

## Releasing

Releases are prepared by [Release Please](https://github.com/googleapis/release-please-action). It reads Conventional Commit messages, updates a dedicated release pull request and creates the Git tag and GitHub Release when that pull request is merged.

Use Conventional Commit prefixes in pull request titles or squash commit messages:

| Change                      | Commit example                               | Version bump                           |
| --------------------------- | -------------------------------------------- | -------------------------------------- |
| Bug fix                     | `fix: restore a node after a failed request` | Patch                                  |
| Backward-compatible feature | `feat: add custom drop zones`                | Minor                                  |
| Breaking change             | `feat!: require Laravel 13`                  | Minor before `1.0.0`, major afterwards |

The release flow is:

1. Open a feature pull request and wait for CI.
2. Squash-merge it into `main` using a Conventional Commit title.
3. Wait for the `Release Please` workflow to create or update the release pull request.
4. Review the generated version and `CHANGELOG.md`.
5. Merge the release pull request when the package is ready to publish.
6. Release Please creates the immutable Git tag and GitHub Release. Packagist discovers the tag through its configured GitHub hook.

Do not create release tags manually. The current released version is tracked in `.release-please-manifest.json`; Composer derives the installable package version from the Git tag rather than a `version` field in `composer.json`.

`CHANGELOG.md` is generated by Release Please and is intentionally excluded from Prettier checks.

For the first automated run, allow GitHub Actions to create pull requests under **Settings → Actions → General → Workflow permissions**. The workflow uses the built-in `GITHUB_TOKEN` by default. If CI must also run directly on pull requests created by Release Please, add a repository secret named `RELEASE_PLEASE_TOKEN` containing a fine-grained token with repository contents, issues and pull-request write access.

The release workflow can also be rerun from **Actions → Release Please → Run workflow** if a transient GitHub API failure occurs.

To force a specific next version, add a footer to a Conventional Commit:

```text
Release-As: 0.3.0
```

Published versions are immutable. If a release needs a correction, merge a fix and publish a new patch version instead of moving or replacing an existing tag.

## License

MoonShine Nested Set is open-source software licensed under the [MIT license](LICENSE.md).
