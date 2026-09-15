<?php

declare(strict_types=1);

namespace Tests\Support\Models;

use Djnew\MoonShineNestedSet\Traits\MoonshineNestedSetTrait;
use Illuminate\Database\Eloquent\Model;

final class TraitCategory extends Model
{
    use MoonshineNestedSetTrait;

    protected $table = 'trait_categories';

    public $timestamps = false;

    protected $guarded = [];
}
