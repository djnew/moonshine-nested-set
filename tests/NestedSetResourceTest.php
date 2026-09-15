<?php

declare(strict_types=1);

namespace Tests;

use Illuminate\Container\Container;
use Illuminate\Contracts\Routing\ResponseFactory;
use Illuminate\Database\Capsule\Manager as Capsule;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Events\Dispatcher;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Facade;
use PHPUnit\Framework\TestCase;
use ReflectionClass;
use Tests\Support\FakeResponseFactory;
use Tests\Support\Models\TraitCategory;
use Tests\Support\Resources\DefaultTreeKeyResource;

final class NestedSetResourceTest extends TestCase
{
    private static Capsule $database;

    public static function setUpBeforeClass(): void
    {
        $container = new Container();
        Container::setInstance($container);
        Facade::setFacadeApplication($container);
        $container->instance(ResponseFactory::class, new FakeResponseFactory());

        self::$database = new Capsule($container);
        self::$database->addConnection([
            'driver' => 'sqlite',
            'database' => ':memory:',
            'prefix' => '',
        ]);
        self::$database->setEventDispatcher(new Dispatcher($container));
        self::$database->setAsGlobal();
        self::$database->bootEloquent();
    }

    protected function setUp(): void
    {
        $schema = self::$database->schema();

        if ($schema->hasTable('trait_categories')) {
            $schema->drop('trait_categories');
        }

        $schema->create('trait_categories', static function (Blueprint $table): void {
            $table->increments('id');
            $table->unsignedInteger('parent_id')->nullable();
            $table->unsignedInteger('_lft');
            $table->unsignedInteger('_rgt');
            $table->string('title');
        });

        TraitCategory::clearBootedModels();
    }

    public function testTreeKeyDefaultsToNestedSetParentColumn(): void
    {
        self::assertSame('parent_id', $this->resource()->treeKey());
    }

    public function testSortColumnDefaultsToNestedSetLeftColumn(): void
    {
        self::assertSame('_lft', $this->resource()->getSortColumn());
    }

    public function testSortDirectionFollowsNestedSetLeftOrder(): void
    {
        self::assertSame('asc', $this->resource()->getSortDirection());
    }

    public function testNestedsetMoveKeepsTreeConsistentWithoutFixTree(): void
    {
        self::$database->table('trait_categories')->insert([
            ['id' => 1, 'title' => 'A', 'parent_id' => null, '_lft' => 1, '_rgt' => 6],
            ['id' => 3, 'title' => 'A1', 'parent_id' => 1, '_lft' => 2, '_rgt' => 3],
            ['id' => 4, 'title' => 'A2', 'parent_id' => 1, '_lft' => 4, '_rgt' => 5],
            ['id' => 2, 'title' => 'B', 'parent_id' => null, '_lft' => 7, '_rgt' => 10],
            ['id' => 5, 'title' => 'B1', 'parent_id' => 2, '_lft' => 8, '_rgt' => 9],
        ]);

        $resource = $this->resource();

        $this->bindRequest([
            'id' => '4',
            'parent' => '',
            'index' => '2',
            'data' => '1,2,4',
        ]);
        $response = $resource->nestedset();

        $this->bindRequest([
            'id' => '5',
            'parent' => '4',
            'index' => '0',
            'data' => '5',
        ]);
        $response2 = $resource->nestedset();

        self::assertSame(204, $response->getStatusCode());
        self::assertSame(204, $response2->getStatusCode());
        self::assertSame([1, 2, 4], $this->rootIds());
        self::assertNull(TraitCategory::query()->findOrFail(4)->parent_id);
        self::assertSame([5], $this->childrenIds(4));
        $this->assertTreeHasNoErrors();
    }

    public function testNestedsetReordersSiblingsInsideSameParent(): void
    {
        self::$database->table('trait_categories')->insert([
            ['id' => 1, 'title' => 'Root', 'parent_id' => null, '_lft' => 1, '_rgt' => 8],
            ['id' => 2, 'title' => 'A1', 'parent_id' => 1, '_lft' => 2, '_rgt' => 3],
            ['id' => 3, 'title' => 'A2', 'parent_id' => 1, '_lft' => 4, '_rgt' => 5],
            ['id' => 4, 'title' => 'A3', 'parent_id' => 1, '_lft' => 6, '_rgt' => 7],
        ]);

        $this->bindRequest([
            'id' => '4',
            'parent' => '1',
            'index' => '0',
            'data' => '4,2,3',
        ]);

        $response = $this->resource()->nestedset();

        self::assertSame(204, $response->getStatusCode());
        self::assertSame([4, 2, 3], $this->childrenIds(1));
        $this->assertTreeHasNoErrors();
    }

    public function testNestedsetMovesNodeWithDescendantsToNewParent(): void
    {
        self::$database->table('trait_categories')->insert([
            ['id' => 1, 'title' => 'A', 'parent_id' => null, '_lft' => 1, '_rgt' => 6],
            ['id' => 3, 'title' => 'A1', 'parent_id' => 1, '_lft' => 2, '_rgt' => 5],
            ['id' => 4, 'title' => 'A1a', 'parent_id' => 3, '_lft' => 3, '_rgt' => 4],
            ['id' => 2, 'title' => 'B', 'parent_id' => null, '_lft' => 7, '_rgt' => 8],
        ]);

        $this->bindRequest([
            'id' => '3',
            'parent' => '2',
            'index' => '0',
            'data' => '3',
        ]);

        $response = $this->resource()->nestedset();

        self::assertSame(204, $response->getStatusCode());
        self::assertSame([1, 2], $this->rootIds());
        self::assertSame([3], $this->childrenIds(2));
        self::assertSame([4], $this->childrenIds(3));
        $this->assertTreeHasNoErrors();
    }

    public function testUpAndDownButtonsReorderSiblingsAndIgnoreEdges(): void
    {
        self::$database->table('trait_categories')->insert([
            ['id' => 1, 'title' => 'Root', 'parent_id' => null, '_lft' => 1, '_rgt' => 8],
            ['id' => 2, 'title' => 'A1', 'parent_id' => 1, '_lft' => 2, '_rgt' => 3],
            ['id' => 3, 'title' => 'A2', 'parent_id' => 1, '_lft' => 4, '_rgt' => 5],
            ['id' => 4, 'title' => 'A3', 'parent_id' => 1, '_lft' => 6, '_rgt' => 7],
        ]);

        $resource = $this->resource();

        $this->setResourceItemId($resource, 3);
        $resource->nestedsetUp();
        self::assertSame([3, 2, 4], $this->childrenIds(1));

        $resource->nestedsetDown();
        self::assertSame([2, 3, 4], $this->childrenIds(1));

        $this->setResourceItemId($resource, 2);
        $resource->nestedsetUp();
        self::assertSame([2, 3, 4], $this->childrenIds(1));

        $this->setResourceItemId($resource, 4);
        $resource->nestedsetDown();
        self::assertSame([2, 3, 4], $this->childrenIds(1));
        $this->assertTreeHasNoErrors();
    }

    public function testDeprecatedMisspelledComponentAliasStillResolves(): void
    {
        self::assertTrue(class_exists(\Djnew\MoonShineNestedSet\View\Components\NestedSetComponent::class));
        self::assertTrue(class_exists(\Djnew\MoonShineNestedSet\View\Components\NestdSetComponent::class));
        self::assertTrue(is_a(
            \Djnew\MoonShineNestedSet\View\Components\NestdSetComponent::class,
            \Djnew\MoonShineNestedSet\View\Components\NestedSetComponent::class,
            true
        ));
    }

    private function resource(): DefaultTreeKeyResource
    {
        return (new ReflectionClass(DefaultTreeKeyResource::class))->newInstanceWithoutConstructor();
    }

    /**
     * @param array<string, string> $payload
     */
    private function bindRequest(array $payload): void
    {
        Container::getInstance()->instance('request', Request::create('/nestedset', 'POST', $payload));
    }

    /**
     * @return list<int>
     */
    private function rootIds(): array
    {
        return TraitCategory::query()->whereNull('parent_id')->orderBy('_lft')->pluck('id')->all();
    }

    /**
     * @return list<int>
     */
    private function childrenIds(int $parentId): array
    {
        return TraitCategory::query()->findOrFail($parentId)->childrenNestedset()->pluck('id')->all();
    }

    private function assertTreeHasNoErrors(): void
    {
        self::assertSame([
            'oddness' => 0,
            'duplicates' => 0,
            'wrong_parent' => 0,
            'missing_parent' => 0,
        ], TraitCategory::query()->countErrors());
    }

    private function setResourceItemId(DefaultTreeKeyResource $resource, int|string $itemId): void
    {
        $class = new ReflectionClass($resource);

        while (! $class->hasProperty('itemID')) {
            $parent = $class->getParentClass();
            self::assertNotFalse($parent, 'MoonShine resource itemID property was not found.');
            $class = $parent;
        }

        $property = $class->getProperty('itemID');
        $property->setValue($resource, $itemId);
    }
}
