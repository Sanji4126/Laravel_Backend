<?php

namespace App\Http\Controllers\Brands;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Brand;

class BrandController extends Controller
{
    public function brand() {
        $brand = Brand::all();
        return response()->json([
            'message' => 'get data successfully',
            'brand' => $brand,
        ], 200);
    }
    public function createBrand(Request $request) {
        $data=$request->validate([
            'brand_name' => 'required|string|max:50',
            'cate_id' => 'required|integer',
        ]);
        $brand = Brand::create([
            'brand_name' => $data['brand_name'],
            'cate_id' => $data['cate_id'],
            'user_id' => auth('api')->id(),
        ]);
        return response()->json([
            'message' => 'Brand created successfully',
            'brand' => $brand,
        ], 201);
    }
    public function updateBrand(Request $request, $id) {
        $data=$request->validate([
            'brand_name' => 'required|string|max:50',
            'cate_id' => 'required|integer',
        ]);
        $brand = Brand::findOrFail($id);
        $brand->update([
            'brand_name' => $data['brand_name'],
            'cate_id' => $data['cate_id'],
            'user_id' => auth('api')->id(),
        ]);
        return response()->json([
            'message' => 'Brand updated successfully',
            'brand' => $brand,
        ], 200);
    }
    public function deleteBrand($id) {
        $brand = Brand::findOrFail($id);
        $brand->delete();
        return response()->json([
            'message' => 'Brand deleted successfully',
        ], 200);
    }
}
