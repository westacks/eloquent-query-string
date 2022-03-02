<?php

namespace WeStacks\EloquentQueryString\Traits;

use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Request;
use Illuminate\Support\Str;
use Throwable;

trait QueryFilterable
{
    private $queryFilters = [
        'select',
        'distinct',
        'with',
        'with_count',
        'with_sum',
        'with_min',
        'with_max',
        'with_avg',
        'with_exists',
        'without',
        'where',
        'where_in',
        'where_integer_in_raw',
        'where_integer_not_in_raw',
        'where_null',
        'where_between',
        'where_between_columns',
        'where_date',
        'where_time',
        'where_day',
        'where_month',
        'where_year',
        'where_json_contains',
        'where_json_length',
        'where_full_text',
        'having',
        'having_null',
        'having_between',
        'group_by',
        'order_by',
        'offset',
        'limit',
        'for_page',
    ];

    private $queryGetters = [
        'get',
        'find',
        'value',
        'pluck',
        'implode',
        'count',
        'min',
        'max',
        'sum',
        'avg',
        'paginate',
        'simple_paginate',
        'cursor_paginate'
    ];

    private $queryPostLoad = [
        'append'
    ];

    public function scopeFromQueryString(Builder $query)
    {
        foreach ($this->queryFilters as $filter) {
            $query = $this->applyFilter($query, $filter, Request::query($filter));
        }

        $getter = array_intersect(array_keys(Request::query()), $this->queryGetters);
        $getter = empty($getter) ? 'get' : $getter[0];
        $query = $this->applyFilter($query, $getter, Request::query($getter, ['*']));

        foreach ($this->queryPostLoad as $method) {
            $query = $this->applyFilter($query, $method, Request::query($method));
        }

        return $query;
    }

    private function applyFilter(&$query, string $method, array $args)
    {
        if (!$args || !$this->canBeFilteredByQuery($method, $args, Auth::user())) return $query;

        try {
            if (method_exists($this, $custom = Str::camel("apply_$method"))) {
                return $this->{$custom}($query, ...$args);
            }
            return $query->{Str::camel($method)}(...$args);
        } catch (Throwable $e) {
            Log::warning($e->getMessage(), $e->getTrace());
        }

        return $query;
    }

    public function canBeFilteredByQuery(string $method, array $args, ?Authenticatable $user)
    {
        return false;
    }
}