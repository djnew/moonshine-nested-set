<?php

declare(strict_types=1);

namespace Djnew\MoonShineNestedSet\Traits;


use Illuminate\Database\Eloquent\Relations\HasMany;
use Kalnoy\Nestedset\NodeTrait;

trait MoonshineNestedSetTrait
{
    use NodeTrait;
    public function childrenNestedset(): HasMany
    {
        return $this->hasMany(__CLASS__, 'parent_id')->defaultOrder()->where('active', true);
    }
}

