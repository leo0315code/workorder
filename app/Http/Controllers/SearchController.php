<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Services\SearchService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class SearchController extends Controller
{
    /**
     * 全局搜索页
     */
    public function index(Request $request): View
    {
        $q = trim((string) $request->input('q', ''));
        $user = $request->user();

        $results = $q !== ''
            ? SearchService::search($user, $q)
            : ['tickets' => null, 'customers' => null, 'products' => null];

        return view('search.index', array_merge(['q' => $q], $results));
    }

    /**
     * 下拉建议接口（AJAX）
     */
    public function suggest(Request $request): JsonResponse
    {
        $q = trim((string) $request->input('q', ''));
        if (mb_strlen($q) < 1) {
            return response()->json(['items' => []]);
        }

        return response()->json(['items' => SearchService::suggest($request->user(), $q)]);
    }
}
