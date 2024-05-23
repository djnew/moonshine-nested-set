<?php

declare(strict_types=1);

namespace Djnew\MoonShineNestedSet\Providers;

use Illuminate\Support\Facades\{Blade, Vite};
use Illuminate\Support\ServiceProvider;

final class MoonShineNestedsetServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        $this->loadViewsFrom(__DIR__ . '/../../resources/views', 'moonshine-nestedset');

        $this->publishes([
            __DIR__ . '/../../public' => public_path('vendor/djnew/moonshine-nestedset'),
        ], ['moonshine-nestedset', 'laravel-assets']);


        if(file_exists(public_path() . '/vendor/djnew/moonshine-nestedset/')) {
            moonShineAssets()->add([
                Vite::createAssetPathsUsing(function (string $path, ?bool $secure) { // Customize the backend path generation for built assets...
                    return "{$path}";
                })
                    ->asset('resources/css/nested-set.css', 'vendor/djnew/moonshine-nestedset'),

                Vite::createAssetPathsUsing(function (string $path, ?bool $secure) { // Customize the backend path generation for built assets...
                    return "{$path}";
                })
                    ->asset('resources/js/app.js', 'vendor/djnew/moonshine-nestedset')
            ]);
        }

        Blade::withoutDoubleEncoding();
        Blade::componentNamespace('Djnew\MoonShineNestedset\View\Components', 'moonshine-nestedset');

        $this->commands([]);
    }
}
