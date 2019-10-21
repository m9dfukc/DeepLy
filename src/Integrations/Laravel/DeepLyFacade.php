<?php

declare(strict_types=1);

namespace Octfx\DeepLy\Integrations\Laravel;

use Illuminate\Support\Facades\Facade;

class DeepLyFacade extends Facade
{
    /**
     * Get the registered name of the component.
     *
     * @return string
     */
    protected static function getFacadeAccessor(): string
    {
        return 'deeply';
    }
}
