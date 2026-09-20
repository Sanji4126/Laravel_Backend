<?php

namespace App\Http\Controllers\Brands;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Brand;
use App\Models\User;
use Illuminate\Validation\ValidationException;
use Exception;

class BrandController extends Controller
{
    private function getAuthenticatedUserId(): int
    {
        $userId = auth('api')->id() ?: auth()->id();
        if (!$userId) {
            $firstUser = User::first();
            $userId = $firstUser ? $firstUser->user_id : 1;
        }
        return (int) $userId;
    }

    public function brand() {
        $brand = Brand::with('category')->orderBy('brand_id', 'asc')->get();
        return response()->json([
            'message' => 'get data successfully',
            'brand' => $brand,
        ], 200);
    }

    public function createBrand(Request $request) {
        try {
            $data = $request->validate([
                'brand_name' => 'required|string|max:50',
                'cate_id' => 'required|exists:categories,cate_id',
            ]);
            $brand = Brand::create([
                'brand_name' => $data['brand_name'],
                'cate_id' => $data['cate_id'],
                'user_id' => $this->getAuthenticatedUserId(),
            ]);
            $brand->load('category');
            return response()->json([
                'message' => 'Brand created successfully',
                'brand' => $brand,
            ], 201);
        } catch (ValidationException $e) {
            return response()->json([
                'message' => $e->validator->errors()->first(),
                'error' => $e->validator->errors()->first(),
                'errors' => $e->validator->errors(),
            ], 422);
        } catch (Exception $e) {
            return response()->json([
                'message' => 'Brand created failed',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    public function updateBrand(Request $request, $id) {
        try {
            $data = $request->validate([
                'brand_name' => 'required|string|max:50',
                'cate_id' => 'required|exists:categories,cate_id',
            ]);
            $brand = Brand::findOrFail($id);
            $brand->update([
                'brand_name' => $data['brand_name'],
                'cate_id' => $data['cate_id'],
                'user_id' => $this->getAuthenticatedUserId(),
            ]);
            $brand->load('category');
            return response()->json([
                'message' => 'Brand updated successfully',
                'brand' => $brand,
            ], 200);
        } catch (ValidationException $e) {
            return response()->json([
                'message' => $e->validator->errors()->first(),
                'error' => $e->validator->errors()->first(),
                'errors' => $e->validator->errors(),
            ], 422);
        } catch (Exception $e) {
            return response()->json([
                'message' => 'Brand updated failed',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    public function deleteBrand($id) {
        try {
            $brand = Brand::findOrFail($id);
            $brand->delete();
            return response()->json([
                'message' => 'Brand deleted successfully',
            ], 200);
        } catch (Exception $e) {
            return response()->json([
                'message' => 'Brand deletion failed',
                'error' => $e->getMessage(),
            ], 500);
        }
    }
}

