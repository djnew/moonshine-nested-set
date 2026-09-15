<?php

declare(strict_types=1);

namespace Tests\Support\Models;

use Djnew\MoonShineNestedSet\Traits\MoonshineNestedSetTrait;
use Illuminate\Database\Eloquent\Model;

final class CustomParentCategory extends Model
{
    use MoonshineNestedSetTrait;

    protected $table = 'custom_parent_categories';

    public $timestamps = false;

    protected $guarded = [];

    public function getParentIdName(): string
    {
        return 'parent_ref';
    }
}
