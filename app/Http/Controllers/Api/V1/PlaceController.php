<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\StorePlaceRequest;
use App\Http\Resources\PizzaPlaceResource;
use App\Http\Traits\ApiResponds;
use App\Models\PizzaList;
use App\Models\PizzaPlace;
use App\Services\PizzaPlaceService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class PlaceController extends Controller
{
    use ApiResponds;

    public function __construct(public PizzaPlaceService $placeService) {}

    public function store(StorePlaceRequest $request, PizzaList $list): JsonResponse
    {
        $place = $this->placeService->addToList(
            $list,
            $request->user(),
            $request->google_place_id,
            $request->name,
            $request->address,
            $request->lat,
            $request->lng,
            $request->currency,
            $request->google_rating,
        );

        return $this->created(new PizzaPlaceResource($place));
    }

    public function destroy(Request $request, PizzaList $list, PizzaPlace $place): JsonResponse
    {
        abort_if($list->user_id !== $request->user()->id, 403, 'Forbidden.');

        $this->placeService->removeFromList($list, $place);

        return $this->noContent();
    }
}
