<?php

namespace App\Services;

use App\Models\PizzaList;
use App\Models\User;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Str;

class PizzaListService
{
    public function forUser(User $user): Collection
    {
        return $user->lists()->with('owner')->latest()->get();
    }

    public function create(User $user, string $name, ?string $city, bool $isPublic): PizzaList
    {
        $list = $user->lists()->create([
            'name' => $name,
            'city' => $city,
            'is_public' => $isPublic,
            'slug' => Str::slug($name).'-'.Str::lower(Str::random(6)),
        ]);

        return $list->load('owner');
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function update(PizzaList $list, array $data): PizzaList
    {
        $list->update($data);

        return $list->load('owner');
    }

    public function delete(PizzaList $list): void
    {
        $list->delete();
    }
}
