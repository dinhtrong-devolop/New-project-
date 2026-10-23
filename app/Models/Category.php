<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Category extends Model
{
    use HasFactory;

    protected $fillable = [
        'parent_id',
        'name',
        'slug',
        'description',
        'image_path',
        'is_active',
        'sort_order',
    ];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
            'sort_order' => 'integer',
        ];
    }

    public function parent(): BelongsTo
    {
        return $this->belongsTo(self::class, 'parent_id');
    }

    public function children(): HasMany
    {
        return $this->hasMany(self::class, 'parent_id')->orderBy('sort_order');
    }

    public function products(): HasMany
    {
        return $this->hasMany(Product::class);
    }

    /** @return list<int> */
    public function descendantIds(): array
    {
        if (! $this->exists) {
            return [];
        }

        $descendants = [];
        $pending = [(int) $this->getKey()];

        while ($pending !== []) {
            $children = self::query()
                ->whereIn('parent_id', $pending)
                ->whereNotIn('id', $descendants)
                ->pluck('id')
                ->map(fn ($id): int => (int) $id)
                ->all();

            $children = array_values(array_diff($children, $descendants, [(int) $this->getKey()]));
            $descendants = array_values(array_unique([...$descendants, ...$children]));
            $pending = $children;
        }

        return $descendants;
    }
}
