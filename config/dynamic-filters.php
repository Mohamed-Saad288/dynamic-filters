<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Allowed Operators
    |--------------------------------------------------------------------------
    | These operators can be used in filters.
    | You can extend or remove operators based on your app's needs.
    */
    'allowed_operators' => ['=', 'like', 'in', 'between', '>', '<', '>=', '<='],

    /*
    |--------------------------------------------------------------------------
    | Default Relation Column
    |--------------------------------------------------------------------------
    | This column will be used when filtering by a relation directly
    | (e.g., ?filters[organization]=3). Default is "id".
    */
    'default_relation_column' => 'id',

    /*
    |--------------------------------------------------------------------------
    | Allowed Sorting Columns
    |--------------------------------------------------------------------------
    | If you want to restrict which columns can be sorted,
    | list them here. Leave empty to allow all columns.
    | Example: ['name', 'email', 'created_at']
    */
    'allowed_sorting_columns' => [],
];
