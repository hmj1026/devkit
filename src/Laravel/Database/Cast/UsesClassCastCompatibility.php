<?php

namespace Devkit\Laravel\Database\Cast;

use Illuminate\Contracts\Database\Eloquent\CastsAttributes;
use Illuminate\Contracts\Support\Arrayable;
use JsonSerializable;

trait UsesClassCastCompatibility
{
    private $devkitClassCastCache = array();

    private $devkitCasterCache = array();

    public function getAttributeValue($key)
    {
        if (! $this->hasNativeClassCasts() && $this->isDevkitClassCastable($key) && ! $this->hasGetMutator($key)) {
            return $this->getDevkitClassCastableValue($key);
        }

        return parent::getAttributeValue($key);
    }

    public function setAttribute($key, $value)
    {
        if (! $this->hasNativeClassCasts() && $this->isDevkitClassCastable($key) && ! $this->hasSetMutator($key)) {
            unset($this->devkitClassCastCache[$key]);
            foreach ($this->normalizeDevkitCastResponse($key, $this->resolveDevkitCaster($key)->set($this, $key, $value, $this->attributes)) as $attribute => $attributeValue) {
                $this->attributes[$attribute] = $attributeValue;
                unset($this->devkitClassCastCache[$attribute]);
            }

            return $this;
        }

        return parent::setAttribute($key, $value);
    }

    public function setRawAttributes(array $attributes, $sync = false)
    {
        $this->devkitClassCastCache = array();

        return parent::setRawAttributes($attributes, $sync);
    }

    public function attributesToArray()
    {
        $attributes = parent::attributesToArray();

        if ($this->hasNativeClassCasts()) {
            return $attributes;
        }

        foreach ($this->getCasts() as $key => $_) {
            if (! array_key_exists($key, $attributes)) {
                continue;
            }
            if ($this->hasGetMutator($key)) {
                continue;
            }
            if (! $this->isDevkitClassCastable($key)) {
                continue;
            }

            $attributes[$key] = $this->serializeDevkitCastValue(
                $this->getDevkitClassCastableValue($key)
            );
        }

        return $attributes;
    }

    public function getDirty()
    {
        $dirty = parent::getDirty();

        if ($this->hasNativeClassCasts()) {
            return $dirty;
        }

        foreach ($dirty as $key => $current) {
            if (! $this->isDevkitClassCastable($key)) {
                continue;
            }
            if (! array_key_exists($key, $this->original)) {
                continue;
            }

            $original = $this->original[$key];
            if ($current === null || $original === null) {
                continue;
            }

            $caster = $this->resolveDevkitCaster($key);
            if ($caster->get($this, $key, $current, $this->attributes) === $caster->get($this, $key, $original, $this->attributes)) {
                unset($dirty[$key]);
            }
        }

        return $dirty;
    }

    private function hasNativeClassCasts(): bool
    {
        return interface_exists(CastsAttributes::class, false)
            && ! defined('DEVKIT_CASTS_ATTRIBUTES_POLYFILLED');
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

        if (isset($this->devkitCasterCache[$castClass])) {
            return $this->devkitCasterCache[$castClass];
        }

        return $this->devkitCasterCache[$castClass] = new $castClass();
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

    private function getDevkitClassCastableValue(string $key)
    {
        if (array_key_exists($key, $this->devkitClassCastCache)) {
            return $this->devkitClassCastCache[$key];
        }

        return $this->devkitClassCastCache[$key] = $this->resolveDevkitCaster($key)->get(
            $this,
            $key,
            $this->getAttributeFromArray($key),
            $this->attributes
        );
    }

    private function serializeDevkitCastValue($value)
    {
        if ($value instanceof JsonSerializable) {
            return $value->jsonSerialize();
        }

        if ($value instanceof Arrayable) {
            return $value->toArray();
        }

        return $value;
    }
}
