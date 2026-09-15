<?php

declare(strict_types=1);

namespace Tests\Support\Resources;

use Djnew\MoonShineNestedSet\Resources\NestedSetResource;
use Tests\Support\Models\TraitCategory;

final class DefaultTreeKeyResource extends NestedSetResource
{
    protected string $model = TraitCategory::class;

    protected string $column = 'title';
}
