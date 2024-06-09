<?php

namespace App\Models;

use App\Rules\Filter;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Validation\Rule;

class Category extends Model
{
    use HasFactory;

    protected $fillable = [
        'name', 'parent_id', 'description', 'image', 'status', 'slug'
    ];

    public function products()
    {
        return $this->hasMany(Product::class, 'category_id', 'id');
    }

    public function parent()
    {
        return $this->belongsTo(Category::class, 'parent_id', 'id')
            ->withDefault([
                'name' => '-'
            ]);
    }

    public function children()
    {
        return $this->hasMany(Category::class, 'parent_id', 'id');
    }

    // local scope
    public function scopeActive(Builder $builder)
    {
        $builder->where('status', '=', 'active');
    }

    // local scope with parameter
    public function scopeFilter(Builder $builder, $filters)
    {
        // when like if statement
        /*
        if ($filters['name'] ?? false) {
            $builder->where('categories.name', 'LIKE', "%{$value}%");
        }
        */

        $builder->when($filters['name'] ?? false, function ($builder, $value) {
            // categories. is a table name
            $builder->where('categories.name', 'LIKE', "%{$value}%");
        });

        $builder->when($filters['status'] ?? false, function ($builder, $value) {
            // categories. is a table name
            $builder->where('categories.status', '=', $value);
        });
    }

    public static function rules($id = 0)
    {
        return [
            'name' => [
                'required',
                'string',
                'min:3',
                'max:255',
                // unique in categories table in name field
                // "unique:categories,name,$id",
                Rule::unique('categories', 'name')->ignore($id),
                /*function($attribute, $value, $fails) {
                    if (strtolower($value) == 'laravel') {
                        $fails('This name is forbidden!');
                    } 
                },*/
                // we created Validator file to execute this in Providers folder in AppServiceProvider
                // video 06.2
                'filter:php,laravel,html',

                // we created Filter file
                //new Filter(['php', 'laravel', 'html']),
            ],
            'parent_id' => [
                // exist in categories table in id field
                'nullable', 'int', 'exists:categories,id'
            ],
            'image' => [
                'image', 'max:1048576', 'dimensions:min_width=100,min_height=100',
            ],
            'status' => 'required|in:active,archived',
        ];
    }
}
