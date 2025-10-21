<?php

declare(strict_types=1);

namespace Oneduo\NovaFileManager\Casts;

use Illuminate\Contracts\Database\Eloquent\CastsAttributes;
use Illuminate\Support\Facades\Storage;
use InvalidArgumentException;
use Oneduo\NovaFileManager\Support\Asset as AssetObject;

class Asset implements CastsAttributes
{
    /**
     * @param  \Illuminate\Database\Eloquent\Model  $model
     * @param  string|null  $value
     *
     * @throws \JsonException
     */
    public function get($model, string $key, $value, array $attributes): AssetObject|string|null
    {
        if ($value === null) {
            return null;
        }

        if (is_string($value)) {
            $value = json_decode($value, true, 512, JSON_THROW_ON_ERROR);
        } elseif (is_a($value, \stdClass::class)) {
            $value = (array) $value;
        }

        if (static::shouldTransformToUrl()) {
            return static::transformArrayToUrl($value);
        }

        return new AssetObject(...$value);
    }

    /**
     * @param  \Illuminate\Database\Eloquent\Model  $model
     * @param  array|AssetObject|null  $value
     *
     * @throws \JsonException
     */
    public function set($model, string $key, $value, array $attributes): ?string
    {
        if ($value === null) {
            return null;
        }

        if (is_array($value) || $value instanceof AssetObject) {
            return json_encode($value, JSON_THROW_ON_ERROR);
        }

        throw new InvalidArgumentException('Invalid value for Asset cast');
    }

    /**
     * @return bool
     */
    public static function shouldTransformToUrl(): bool
    {
        if (! $routes = config('nova-file-manager.array_to_url_routes')) {
            return false;
        }

        foreach ($routes as $route) {
            if (request()->routeIs($route) || request()->is($route)) {
                return true;
            }
        }

        return false;
    }

    /**
     * @param array $data
     * @return string
     */
    public static function transformArrayToUrl(array $data): string
    {
        try {
            return Storage::disk($data['disk'])->url($data['path']);
        } catch (\Exception $e) {
            return '';
        }
    }
}
