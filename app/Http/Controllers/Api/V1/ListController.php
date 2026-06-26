<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreListRequest;
use App\Http\Requests\UpdateListRequest;
use App\Http\Resources\PizzaListResource;
use App\Http\Traits\ApiResponds;
use App\Models\PizzaList;
use App\Services\PizzaListService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ListController extends Controller
{
    use ApiResponds;

    public function __construct(public PizzaListService $listService) {}

    public function index(Request $request): JsonResponse
    {
        $lists = $this->listService->forUser($request->user());

        return $this->success(PizzaListResource::collection($lists));
    }

    public function store(StoreListRequest $request): JsonResponse
    {
        $list = $this->listService->create(
            $request->user(),
            $request->name,
            $request->city,
            $request->boolean('is_public', false),
        );

        return $this->created(new PizzaListResource($list));
    }

    public function show(PizzaList $list): JsonResponse
    {
        $list->load(['owner', 'pizzaPlaces.ratings' => fn ($q) => $q->where('is_active', true)]);

        return $this->success(new PizzaListResource($list));
    }

    public function update(UpdateListRequest $request, PizzaList $list): JsonResponse
    {
        abort_if($list->user_id !== $request->user()->id, 403, 'Forbidden.');

        $list = $this->listService->update($list, $request->validated());

        return $this->success(new PizzaListResource($list));
    }

    public function destroy(Request $request, PizzaList $list): JsonResponse
    {
        abort_if($list->user_id !== $request->user()->id, 403, 'Forbidden.');

        $this->listService->delete($list);

        return $this->noContent();
    }
}
