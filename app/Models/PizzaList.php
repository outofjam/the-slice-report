<?php

namespace App\Models;

use Database\Factories\PizzaListFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * @method static Model|static create(array $attributes = [])
 * @method static Builder|static query()
 *
 * @mixin Builder
 */
#[Fillable(['user_id', 'name', 'city', 'is_public', 'slug'])]
class PizzaList extends Model
{
    /** @use HasFactory<PizzaListFactory> */
    use HasFactory, HasUuids, SoftDeletes;

    protected $table = 'lists';

    public function getRouteKeyName(): string
    {
        return 'slug';
    }

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'is_public' => 'boolean',
        ];
    }

    public function owner(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function pizzaPlaces(): BelongsToMany
    {
        return $this->belongsToMany(PizzaPlace::class, 'list_pizza_place', 'list_id', 'pizza_place_id')
            ->withPivot('id', 'added_by')
            ->withTimestamps();
    }

    public function ratings(): HasMany
    {
        return $this->hasMany(PizzaRating::class, 'list_id');
    }
}
