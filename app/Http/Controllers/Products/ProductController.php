<?php

namespace App\Http\Controllers\Products;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Product;
use Exception;

class ProductController extends Controller
{
    public function product() {
        $product = Product::all();
        return response()->json([
            'message' => 'get data successfully',
            'product' => $product,
        ], 200);
    }
    public function createProduct(Request $request) {
        try {
            $data=$request->validate([
                'pro_name' => 'required|string|max:50',
                'qty' => 'required|integer',
                'price' => 'required|numeric|min:0',
                'description' => 'required|string',
                'image' => 'required|image|mimes:jpeg,png,jpg,gif|max:2048',
                'cate_id' => 'required|exists:categories,cate_id',
                'brand_id' => 'required|exists:brands,brand_id',
            ]);
            
            if($request->hasFile('image')){
                $image = $request->file('image');
                $imageName = time() . '.' . $image->getClientOriginalExtension();
                $image->move(('images'), $imageName);
                $data['image'] =url('images/'.$imageName);
            }
            $product = Product::create([
                'pro_name' => $data['pro_name'],
                'qty' => $data['qty'],
                'price' => $data['price'],
                'description' => $data['description'],
                'image' => $data['image'],
                'cate_id' => $data['cate_id'],
                'brand_id' => $data['brand_id'],
                'user_id' => auth('api')->id()
            ]);
            return response()->json([
                'message' => 'Product created successfully',
                'product' => $product,
            ], 201);
        } catch (Exception $e) {
            return response()->json([
                'message' => 'Product created failed',
                'error' => $e->getMessage(),
            ], 500);
        }
    }
    public function updateProduct(Request $request, $id) {
        $data=$request->validate([
            'pro_name' => 'required|string|max:50',
            'qty' => 'required|integer',
            'price' => 'required|numeric|min:0',
            'description' => 'required|string',
            'image' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048',
            'cate_id' => 'required|exists:categories,cate_id',
            'brand_id' => 'required|exists:brands,brand_id',
        ]);
        $product=Product::findOrFail($id);
        if($request->hasFile('image')){
            $image = $request->file('image');
            $imageName = time() . '.' . $image->getClientOriginalExtension();
            $image->move(('images'), $imageName);
            $data['image'] =url('images/'.$imageName);
        }else{
            $data['image'] = $product->image;
        }
        if($product){
            $product->update([
                'pro_name' => $data['pro_name'],
                'qty' => $data['qty'],
                'price' => $data['price'],
                'description' => $data['description'],
                'image' => $data['image'],
                'cate_id' => $data['cate_id'],
                'brand_id' => $data['brand_id'],
                'user_id' => auth('api')->id()
            ]);
            return response()->json([
                'message' => 'Product updated successfully',
                'product' => $product,
            ], 200);
        }
        return response()->json([
            'message' => 'Product not found',
        ], 404);
    }
    public function deleteProduct($id) {
        $product = Product::findOrFail($id);
        $product->delete();
        return response()->json([
            'message' => 'Product deleted successfully',
        ], 200);
    }
}
