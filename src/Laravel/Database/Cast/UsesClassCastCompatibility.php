<?php

namespace Devkit\Laravel\Database\Cast;

use Illuminate\Contracts\Database\Eloquent\CastsAttributes;

trait UsesClassCastCompatibility
{
    public function getAttributeValue($key)
    {
        if (! $this->hasNativeClassCasts() && $this->isDevkitClassCastable($key) && ! $this->hasGetMutator($key)) {
            return $this->resolveDevkitCaster($key)->get(
                $this,
                $key,
                $this->getAttributeFromArray($key),
                $this->attributes
            );
        }

        return parent::getAttributeValue($key);
    }

    public function setAttribute($key, $value)
    {
        if (! $this->hasNativeClassCasts() && $this->isDevkitClassCastable($key) && ! $this->hasSetMutator($key)) {
            foreach ($this->normalizeDevkitCastResponse($key, $this->resolveDevkitCaster($key)->set($this, $key, $value, $this->attributes)) as $attribute => $attributeValue) {
                $this->attributes[$attribute] = $attributeValue;
            }

            return $this;
        }

        return parent::setAttribute($key, $value);
    }

    private function hasNativeClassCasts(): bool
    {
        return method_exists(get_parent_class($this), 'isClassCastable');
    }

    private function isDevkitClassCastable(string $key): bool
    {
        $casts = $this->getCasts();

        if (! isset($casts[$key])) {
            return false;
        }

        $castClass = $this->devkitCastClassName($casts[$key]);

        return class_exists($castClass) && is_subclass_of($castClass, CastsAttributes::class);
    }

    private function resolveDevkitCaster(string $key): CastsAttributes
    {
        $casts = $this->getCasts();
        $castClass = $this->devkitCastClassName($casts[$key]);

        return new $castClass();
    }

    private function devkitCastClassName(string $cast): string
    {
        $segments = explode(':', $cast, 2);

        return $segments[0];
    }

    private function normalizeDevkitCastResponse(string $key, $value): array
    {
        return is_array($value) ? $value : array($key => $value);
    }
}
