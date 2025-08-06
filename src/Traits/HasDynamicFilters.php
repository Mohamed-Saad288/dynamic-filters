<?php

namespace MohamedSaad\DynamicFilters\Traits;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;

trait HasDynamicFilters
{
    /**
     * Apply dynamic filters (search + filters + columns + sorting) to query.
     */
    public function scopeApplyFilters(Builder $query, array|Request $data): Builder
    {
        $allowedOperators = config('dynamic-filters.allowed_operators', []);
        $defaultRelationColumn = config('dynamic-filters.default_relation_column', 'id');
        $allowedSorting = config('dynamic-filters.allowed_sorting_columns', []);

        // Handle Request or array
        if ($data instanceof Request) {
            $search = $data->get('search');
            $filters = $data->get('filters', []);
            $columns = $data->get('columns', []);
            $sort = $data->get('sort');
        } else {
            $search = $data['search'] ?? null;
            $filters = $data['filters'] ?? [];
            $columns = $data['columns'] ?? [];
            $sort = $data['sort'] ?? null;
        }

        // 1- Select specific columns if provided
        if (!empty($columns)) {
            $query->select($columns);
        }

        // 2- Apply global search
        if (!empty($search)) {
            $this->applySearch($query, $search);
        }

        // 3- Apply filters
        foreach ($filters as $field => $filter) {
            [$operator, $value, $boolean] = $this->smartParseFilter($field, $filter);

            if ($this->isEmptyValue($value)) continue;
            if (!in_array(strtolower($operator), $allowedOperators)) continue;

            if ($this->isRelation($field)) {
                // Filter by relation without column (default to id)
                $query->whereHas($field, function ($q) use ($value, $defaultRelationColumn) {
                    $q->where($defaultRelationColumn, $value);
                });
            } elseif (str_contains($field, '.')) {
                // Filter by nested relation field
                $this->applyRelationFilter($query, $field, $operator, $value, $boolean);
            } else {
                // Normal field filter
                $this->applyWhere($query, $field, $operator, $value, $boolean);
            }
        }

        // 4- Apply sorting (if provided)
        if (!empty($sort)) {
            $this->applySortingString($query, $sort, $allowedSorting);
        }

        return $query;
    }

    /**
     * Apply unified search across default columns (supports dot notation for relations).
     */
    protected function applySearch(Builder $query, string $term): void
    {
        $searchable = property_exists($this, 'searchable') ? $this->searchable : ['name', 'email'];

        $query->where(function ($q) use ($searchable, $term) {
            foreach ($searchable as $column) {
                if (str_contains($column, '.')) {
                    // Search in relation
                    [$relation, $relColumn] = explode('.', $column, 2);
                    $q->orWhereHas($relation, function ($relQ) use ($relColumn, $term) {
                        $relQ->where($relColumn, 'LIKE', "%{$term}%");
                    });
                } else {
                    // Search in model field
                    $q->orWhere($column, 'LIKE', "%{$term}%");
                }
            }
        });
    }

    /**
     * Parse filter data into [operator, value, boolean].
     */
    protected function smartParseFilter(string $field, $filter): array
    {
        $boolean = 'and';

        if (is_array($filter) && isset($filter['operator'], $filter['value'])) {
            return [$filter['operator'], $filter['value'], strtolower($filter['boolean'] ?? 'and')];
        }

        if (is_array($filter)) return ['in', $filter, $boolean];
        if (is_numeric($filter)) return ['=', $filter, $boolean];

        return ['like', $filter, $boolean];
    }

    /**
     * Apply relation filter using dot notation.
     */
    protected function applyRelationFilter(Builder $query, string $field, string $operator, $value, string $boolean): void
    {
        [$relation, $column] = explode('.', $field, 2);

        $query->whereHas($relation, function ($q) use ($column, $operator, $value, $boolean) {
            $this->applyWhere($q, $column, $operator, $value, $boolean);
        });
    }

    /**
     * Apply basic where conditions.
     */
    protected function applyWhere(Builder $query, string $field, string $operator, $value, string $boolean): void
    {
        $operator = strtolower($operator);

        switch ($operator) {
            case 'like':
                $query->where($field, 'LIKE', "%{$value}%", $boolean);
                break;

            case 'in':
                $query->whereIn($field, (array)$value, $boolean);
                break;

            case 'between':
                if (is_array($value) && count($value) === 2) {
                    $query->whereBetween($field, $value, $boolean);
                }
                break;

            default:
                $query->where($field, $operator, $value, $boolean);
        }
    }

    /**
     * Apply sorting using a single string (e.g., "-name,created_at,profile.city").
     */
    protected function applySortingString(Builder $query, string $sortString, array $allowed = []): void
    {
        $fields = explode(',', $sortString);

        foreach ($fields as $field) {
            $direction = 'asc';

            // Handle descending
            if (str_starts_with($field, '-')) {
                $direction = 'desc';
                $field = ltrim($field, '-');
            }

            // Skip if sorting restricted
            if (!empty($allowed) && !in_array($field, $allowed)) {
                continue;
            }

            // Relation sorting
            if (str_contains($field, '.')) {
                [$relation, $column] = explode('.', $field, 2);
                $query->whereHas($relation, function ($q) use ($column, $direction) {
                    $q->orderBy($column, $direction);
                });
            } else {
                $query->orderBy($field, $direction);
            }
        }
    }

    /**
     * Check if a value is empty.
     */
    protected function isEmptyValue($value): bool
    {
        return is_null($value) || (is_string($value) && trim($value) === '');
    }

    /**
     * Check if a field is a relation.
     */
    protected function isRelation(string $key): bool
    {
        return method_exists($this, $key);
    }
}
