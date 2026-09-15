<?php

declare(strict_types=1);

namespace Djnew\MoonShineNestedSet\Providers;

use Illuminate\Foundation\Vite;
use Illuminate\Support\Facades\Blade;
use Illuminate\Support\ServiceProvider;
use MoonShine\AssetManager\Css;
use MoonShine\AssetManager\Js;

final class MoonShineNestedSetServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        $this->loadViewsFrom(__DIR__ . '/../../resources/views', 'moonshine-nestedset');
        $this->loadTranslationsFrom(__DIR__ . '/../../resources/lang', 'moonshine-nestedset');

        $this->publishes([
            __DIR__ . '/../../public' => public_path('vendor/djnew/moonshine-nestedset'),
        ], ['moonshine-nestedset', 'laravel-assets']);

        $buildPath = 'vendor/djnew/moonshine-nestedset';

        if (is_dir(public_path($buildPath))) {
            $vite = (new Vite())->createAssetPathsUsing(
                static fn (string $path, ?bool $secure): string => '/' . ltrim($path, '/')
            );

            moonShineAssets()->add([
                Css::make(
                    $vite->asset('resources/css/nested-set.css', $buildPath)
                ),
                Js::make(
                    $vite->asset('resources/js/app.js', $buildPath)
                ),
            ]);
        }

        Blade::componentNamespace('Djnew\\MoonShineNestedSet\\View\\Components', 'moonshine-nestedset');
    }
}
