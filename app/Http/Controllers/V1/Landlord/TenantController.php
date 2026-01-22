<?php

namespace App\Http\Controllers\V1\Landlord;

use App\Http\Controllers\Controller;
use App\Models\Tenant;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class TenantController extends Controller
{
    public function index(): JsonResponse
    {
        $tenants = Tenant::all();
        return response()->json([
            'data' => $tenants
        ]);
    }
}
