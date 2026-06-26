<?php

namespace App\Models;

use Database\Factories\PizzaPlaceFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * @method static Model|static create(array $attributes = [])
 * @method static Builder|static query()
 *
 * @mixin Builder
 */
#[Fillable(['google_place_id', 'name', 'address', 'lat', 'lng', 'currency', 'is_active'])]
class PizzaPlace extends Model
{
    /** @use HasFactory<PizzaPlaceFactory> */
    use HasFactory, HasUuids, SoftDeletes;

    public function getRouteKeyName(): string
    {
        return 'google_place_id';
    }

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'lat' => 'decimal:7',
            'lng' => 'decimal:7',
            'is_active' => 'boolean',
        ];
    }

    public function lists(): BelongsToMany
    {
        return $this->belongsToMany(PizzaList::class, 'list_pizza_place', 'pizza_place_id', 'list_id')
            ->withPivot('id', 'added_by')
            ->withTimestamps();
    }

    public function ratings(): HasMany
    {
        return $this->hasMany(PizzaRating::class);
    }
}
