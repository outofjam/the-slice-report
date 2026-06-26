<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreRatingRequest;
use App\Http\Resources\PizzaRatingResource;
use App\Http\Traits\ApiResponds;
use App\Models\PizzaPlace;
use App\Services\PizzaRatingService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class RatingController extends Controller
{
    use ApiResponds;

    public function __construct(public PizzaRatingService $ratingService) {}

    public function index(PizzaPlace $place): JsonResponse
    {
        $ratings = $this->ratingService->forPlace($place);

        return $this->success(PizzaRatingResource::collection($ratings));
    }

    public function store(StoreRatingRequest $request, PizzaPlace $place): JsonResponse
    {
        $rating = $this->ratingService->upsert(
            $request->user(),
            $place,
            $request->list_id,
            $request->price,
            $request->rating,
            $request->note,
        );

        return $this->created(new PizzaRatingResource($rating));
    }

    public function destroy(Request $request, PizzaPlace $place): JsonResponse
    {
        $this->ratingService->deleteForUser($request->user(), $place);

        return $this->noContent();
    }
}
