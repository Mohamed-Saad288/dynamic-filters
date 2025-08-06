All Code Examples for Laravel Dynamic Filters
Installation
Install the package via Composer:
composer require mohamedsaad/dynamic-filters

Publish the configuration file:
php artisan vendor:publish --tag=dynamic-filters-config

Configuration File
The configuration file will be published to config/dynamic-filters.php:
<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Allowed Operators
    |--------------------------------------------------------------------------
    | Operators allowed for filtering.
    */
    'allowed_operators' => ['=', 'like', 'in', 'between', '>', '<', '>=', '<='],

    /*
    |--------------------------------------------------------------------------
    | Default Relation Column
    |--------------------------------------------------------------------------
    | When filtering by relation directly (e.g., ?filters[organization]=3),
    | this column will be used.
    */
    'default_relation_column' => 'id',

    /*
    |--------------------------------------------------------------------------
    | Allowed Sorting Columns
    |--------------------------------------------------------------------------
    | Restrict sorting to specific columns. Leave empty to allow all.
    */
    'allowed_sorting_columns' => [],
];

Model Setup
Add the HasDynamicFilters trait to your Eloquent model and optionally define searchable columns:
<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use MohamedSaad\DynamicFilters\Traits\HasDynamicFilters;

class User extends Model
{
    use HasDynamicFilters;

    // Optional: Define searchable columns for the "search" feature
    protected array $searchable = ['name', 'email', 'profile.city'];
}

Controller Setup
Apply filters, search, sorting, and column selection dynamically in your controller:
<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\User;

class UserController extends Controller
{
    public function index(Request $request)
    {
        // Apply filters, search, sorting, and columns dynamically
        $users = User::applyFilters($request)->paginate(15);

        return response()->json($users);
    }
}

API Examples
Apply Filters to Specific Columns
GET /users?filters[status]=active&filters[age][operator]=>&filters[age][value]=25

Filtering Relations
GET /users?filters[organization]=2
GET /users?filters[organization.name]=CompanyX

Global Search
Search across all predefined searchable columns:
GET /users?search=ahmed

Column Selection
Select only the columns you need:
GET /users?columns[]=id&columns[]=name

Multi-level Sorting
Sort by multiple columns (prefix with - for descending):
GET /users?sort=-created_at,name,profile.city

Combined Example
Combine everything in one request:
GET /users?search=ahmed&filters[status]=active&filters[organization.id]=3&columns[]=id&columns[]=name&sort=-created_at,email

Using with API Resources
Integrate with Laravel API Resources:
<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\User;
use App\Http\Resources\UserResource;

class UserController extends Controller
{
    public function index(Request $request)
    {
        $users = User::applyFilters($request)->paginate();
        return UserResource::collection($users);
    }
}
