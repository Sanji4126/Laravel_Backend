<?php

namespace App\Http\Controllers\Products;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Product;
use App\Models\User;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;
use Exception;

class ProductController extends Controller
{
    private function uploadProductImage($file): string
    {
        // Try S3 first if configured, else public disk
        $disk = config('filesystems.default') === 's3' || !empty(config('filesystems.disks.s3.key')) ? 's3' : 'public';
        try {
            $path = $file->store('products', $disk);
            return Storage::disk($disk)->url($path);
        } catch (\Throwable $e) {
            Log::warning('Primary image upload failed (' . $disk . '), attempting public fallback: ' . $e->getMessage());
            $path = $file->store('products', 'public');
            return Storage::disk('public')->url($path);
        }
    }

    private function getAuthenticatedUserId(): int
    {
        $userId = auth('api')->id() ?: auth()->id();
        if (!$userId) {
            $firstUser = User::first();
            $userId = $firstUser ? $firstUser->user_id : 1;
        }
        return (int) $userId;
    }

    public function product() {
        $product = Product::with(['category', 'brand'])->orderBy('created_at', 'desc')->get();
        return response()->json([
            'message' => 'get data successfully',
            'product' => $product,
        ], 200);
    }

    public function createProduct(Request $request) {
        try {
            $data = $request->validate([
                'pro_name' => 'required|string|max:100',
                'qty' => 'required|integer|min:0',
                'price' => 'required|numeric|min:0',
                'description' => 'required|string',
                'image' => 'required|image|mimes:jpeg,png,jpg,gif,webp|max:5120',
                'cate_id' => 'required|exists:categories,cate_id',
                'brand_id' => 'required|exists:brands,brand_id',
            ]);

            $imageUrl = null;
            if ($request->hasFile('image')) {
                $imageUrl = $this->uploadProductImage($request->file('image'));
            }

            $userId = $this->getAuthenticatedUserId();

            $product = Product::create([
                'pro_name' => $data['pro_name'],
                'qty' => $data['qty'],
                'price' => $data['price'],
                'description' => $data['description'],
                'image' => $imageUrl,
                'cate_id' => $data['cate_id'],
                'brand_id' => $data['brand_id'],
                'user_id' => $userId,
            ]);

            $product->load(['category', 'brand']);

            return response()->json([
                'message' => 'Product created successfully',
                'product' => $product,
            ], 201);
        } catch (ValidationException $e) {
            return response()->json([
                'message' => $e->validator->errors()->first(),
                'error' => $e->validator->errors()->first(),
                'errors' => $e->validator->errors(),
            ], 422);
        } catch (Exception $e) {
            Log::error('Product create failed: ' . $e->getMessage());
            return response()->json([
                'message' => 'Product created failed',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    public function updateProduct(Request $request, $id) {
        try {
            $data = $request->validate([
                'pro_name' => 'required|string|max:100',
                'qty' => 'required|integer|min:0',
                'price' => 'required|numeric|min:0',
                'description' => 'required|string',
                'image' => 'nullable|image|mimes:jpeg,png,jpg,gif,webp|max:5120',
                'cate_id' => 'required|exists:categories,cate_id',
                'brand_id' => 'required|exists:brands,brand_id',
            ]);

            $product = Product::findOrFail($id);

            if ($request->hasFile('image')) {
                $imageUrl = $this->uploadProductImage($request->file('image'));
            } else {
                $imageUrl = $product->image;
            }

            $userId = $this->getAuthenticatedUserId();

            $product->update([
                'pro_name' => $data['pro_name'],
                'qty' => $data['qty'],
                'price' => $data['price'],
                'description' => $data['description'],
                'image' => $imageUrl,
                'cate_id' => $data['cate_id'],
                'brand_id' => $data['brand_id'],
                'user_id' => $userId,
            ]);

            $product->load(['category', 'brand']);

            return response()->json([
                'message' => 'Product updated successfully',
                'product' => $product,
            ], 200);
        } catch (ValidationException $e) {
            return response()->json([
                'message' => $e->validator->errors()->first(),
                'error' => $e->validator->errors()->first(),
                'errors' => $e->validator->errors(),
            ], 422);
        } catch (Exception $e) {
            Log::error('Product update failed: ' . $e->getMessage());
            return response()->json([
                'message' => 'Product update failed',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    public function deleteProduct($id) {
        try {
            $product = Product::findOrFail($id);
            $product->delete();
            return response()->json([
                'message' => 'Product deleted successfully',
            ], 200);
        } catch (Exception $e) {
            return response()->json([
                'message' => 'Product deletion failed',
                'error' => $e->getMessage(),
            ], 500);
        }
    }
}

