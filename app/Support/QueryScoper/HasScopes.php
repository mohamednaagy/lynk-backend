<?php

namespace App\Support\QueryScoper;

trait HasScopes
{
    /**
     * Apply given scopes payload
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $builder
     * @param  array  $scopes
     * @return void
     */
    public function scopeToScopes($builder, $scopes)
    {
        if (! is_array($scopes)) {
            $scopes = [$scopes];
        }

        foreach ($scopes as $scopeClass) {
            $builder = (new $scopeClass)->apply($builder);
        }

        return $builder;
    }
}
