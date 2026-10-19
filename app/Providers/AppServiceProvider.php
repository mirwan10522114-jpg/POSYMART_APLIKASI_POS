<?php

namespace App\Providers;

use Illuminate\Foundation\Vite;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // Aset Vite memakai path relatif (mis. /build/assets/app-xxx.css)
        // agar tetap termuat apa pun host/port/proxy yang dipakai pengguna
        // (localhost:3000, proxy preview, dsb.). Default Laravel memakai
        // helper asset() yang menghasilkan URL absolut mengikuti Host header
        // dan merusak pemuatan CSS/JS di balik proxy.
        $this->app->make(Vite::class)->createAssetPathsUsing(
            fn (string $path, ?bool $secure = null): string => '/'.ltrim($path, '/')
        );
    }
}
