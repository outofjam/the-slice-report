<?php

namespace App\Models;

use Database\Factories\PizzaRatingFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * @method static Model|static create(array $attributes = [])
 * @method static Builder|static query()
 *
 * @mixin Builder
 */
#[Fillable(['user_id', 'pizza_place_id', 'list_id', 'price', 'currency', 'rating', 'note', 'is_active'])]
class PizzaRating extends Model
{
    /** @use HasFactory<PizzaRatingFactory> */
    use HasFactory, HasUuids, SoftDeletes;

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'price' => 'decimal:2',
            'rating' => 'decimal:1',
            'is_active' => 'boolean',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function pizzaPlace(): BelongsTo
    {
        return $this->belongsTo(PizzaPlace::class);
    }

    public function list(): BelongsTo
    {
        return $this->belongsTo(PizzaList::class, 'list_id');
    }
}
