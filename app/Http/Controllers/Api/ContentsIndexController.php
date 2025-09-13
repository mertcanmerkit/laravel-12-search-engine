<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\ContentsIndexRequest;
use App\Http\Resources\Api\ContentResource;
use App\Services\ContentSearchService;

class ContentsIndexController extends Controller
{
    public function __invoke(ContentsIndexRequest $request, ContentSearchService $search)
    {
        $paginated = $search->search($request->validated());

        return ContentResource::collection($paginated)->response();
    }
}
