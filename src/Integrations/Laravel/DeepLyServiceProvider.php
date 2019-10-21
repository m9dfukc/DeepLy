<?php

declare(strict_types=1);

namespace Octfx\DeepLy\Integrations\Laravel;

use Octfx\DeepLy\DeepLy;
use Illuminate\Support\ServiceProvider;

class DeepLyServiceProvider extends ServiceProvider
{
    /**
     * Register the Service Provider.
     */
    public function register(): void
    {
        $this->app->bind(
            'deeply',
            static function () {
                $apiKey = config('services.deepl.auth_key', '');

                return new DeepLy($apiKey);
            }
        );
    }
}
