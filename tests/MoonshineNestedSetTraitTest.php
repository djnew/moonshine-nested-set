<?php

declare(strict_types=1);

namespace Tests;

use Illuminate\Database\Capsule\Manager as Capsule;
use Illuminate\Database\Schema\Blueprint;
use PHPUnit\Framework\TestCase;
use Tests\Support\Models\CustomParentCategory;
use Tests\Support\Models\TraitCategory;

final class MoonshineNestedSetTraitTest extends TestCase
{
    private static Capsule $database;

    public static function setUpBeforeClass(): void
    {
        self::$database = new Capsule();
        self::$database->addConnection([
            'driver' => 'sqlite',
            'database' => ':memory:',
            'prefix' => '',
        ]);
        self::$database->setAsGlobal();
        self::$database->bootEloquent();
    }

    protected function setUp(): void
    {
        $schema = self::$database->schema();

        foreach (['trait_categories', 'custom_parent_categories'] as $table) {
            if ($schema->hasTable($table)) {
                $schema->drop($table);
            }
        }

        $schema->create('trait_categories', static function (Blueprint $table): void {
            $table->increments('id');
            $table->unsignedInteger('parent_id')->nullable();
            $table->unsignedInteger('_lft');
            $table->unsignedInteger('_rgt');
            $table->string('title');
        });

        $schema->create('custom_parent_categories', static function (Blueprint $table): void {
            $table->increments('id');
            $table->unsignedInteger('parent_ref')->nullable();
            $table->unsignedInteger('_lft');
            $table->unsignedInteger('_rgt');
            $table->string('title');
        });
    }

    public function testChildrenRelationDoesNotRequireActiveColumn(): void
    {
        self::$database->table('trait_categories')->insert([
            ['id' => 1, 'title' => 'Root', 'parent_id' => null, '_lft' => 1, '_rgt' => 4],
            ['id' => 2, 'title' => 'Child', 'parent_id' => 1, '_lft' => 2, '_rgt' => 3],
        ]);

        $root = TraitCategory::query()->findOrFail(1);

        self::assertSame(['Child'], $root->childrenNestedset()->pluck('title')->all());
    }

    public function testChildrenRelationUsesModelParentColumnName(): void
    {
        self::$database->table('custom_parent_categories')->insert([
            ['id' => 1, 'title' => 'Root', 'parent_ref' => null, '_lft' => 1, '_rgt' => 4],
            ['id' => 2, 'title' => 'Child', 'parent_ref' => 1, '_lft' => 2, '_rgt' => 3],
        ]);

        $root = CustomParentCategory::query()->findOrFail(1);

        self::assertSame(['Child'], $root->childrenNestedset()->pluck('title')->all());
    }
}
