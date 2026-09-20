<?php

namespace App\Http\Controllers\Categories;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Category;
use App\Models\User;
use Illuminate\Validation\ValidationException;
use Exception;

class CategoryController extends Controller
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

    public function category(){
        $data = Category::orderBy('cate_id', 'asc')->get();
        return response()->json([
            'msg' => "get data success",
            'data' => $data,
        ], 200);
    }

    public function createCategory(Request $request){
        try {
            $validated = $request->validate([
                'name' => 'required|string|max:255|unique:categories,cate_name',
            ]);
            $category = Category::create([
                'cate_name' => $validated['name'],
                'user_id' => $this->getAuthenticatedUserId(),
            ]);

            return response()->json([
                'message' => 'Category created successfully',
                'category' => $category,
            ], 201);
        } catch (ValidationException $e) {
            return response()->json([
                'message' => $e->validator->errors()->first(),
                'error' => $e->validator->errors()->first(),
                'errors' => $e->validator->errors(),
            ], 422);
        } catch (Exception $e) {
            return response()->json([
                'message' => 'Category created failed',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    public function updateCategory($id, Request $request){
        try {
            $validated = $request->validate([
                'name' => 'required|string|max:255|unique:categories,cate_name,'.$id.',cate_id',
            ]);
            $category = Category::findOrFail($id);
            $category->update([
                'cate_name' => $validated['name'],
                'user_id' => $this->getAuthenticatedUserId(),
            ]);
            return response()->json([
                'message' => 'Category updated successfully',
                'category' => $category,
            ], 200);
        } catch (ValidationException $e) {
            return response()->json([
                'message' => $e->validator->errors()->first(),
                'error' => $e->validator->errors()->first(),
                'errors' => $e->validator->errors(),
            ], 422);
        } catch (Exception $e) {
            return response()->json([
                'message' => 'Category updated failed',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    public function deleteCategory($id){
        try {
            $category = Category::findOrFail($id);
            $category->delete();
            return response()->json([
                'message' => 'Category deleted successfully',
            ], 200);
        } catch (Exception $e) {
            return response()->json([
                'message' => 'Category deleted failed',
                'error' => $e->getMessage(),
            ], 500);
        }
    }
}

