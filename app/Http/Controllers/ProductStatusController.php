<?php

namespace App\Http\Controllers;

use App\Models\ProductStatus;
use Illuminate\Http\JsonResponse;

class ProductStatusController extends Controller
{
    public function index(): JsonResponse
    {
        $statuses = ProductStatus::all();
        return response()->json($statuses);
    }
}
