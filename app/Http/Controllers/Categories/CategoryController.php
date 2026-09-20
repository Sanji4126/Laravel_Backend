<?php

namespace App\Http\Controllers\Categories;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Category;
use Exception;

class CategoryController extends Controller
{
    public function category(){
        $data=Category::all();
        return response()->json([
            'msg' => "get data success",
            'data' => $data,
        ],200);
    }
    public function createCategory(Request $request){
        try {
            $validated = $request->validate([
                'name' => 'required|string|max:255|unique:categories,cate_name',
            ]);
            $category = Category::create([
                'cate_name' => $validated['name'],
                'user_id' => auth('api')->id(),
            ]);

            return response()->json([
                'message' => 'Category created successfully',
                'category' => $category,
            ], 201);
        } catch (Exception $e) {
            return response()->json([
                'message' => 'Category created failed',
                'error' => $e->getMessage(),
            ], 500);
        }
    }
    public function updateCategory($id,Request $request){
        try {
            $validated = $request->validate([
                'name' => 'required|string|max:255|unique:categories,cate_name,'.$id.',cate_id',
            ]);
            $category=Category::findOrFail($id);
            if($category){
                $category->update([
                    'cate_name' => $validated['name'],
                    'user_id' => auth('api')->id(),
                ]);
                return response()->json([
                    'message' => 'Category updated successfully',
                    'category' => $category,
                ], 200);
            }
            return response()->json([
                'message' => 'Category not found',
            ], 404);
        } catch (Exception $e) {
            return response()->json([
                'message' => 'Category updated failed',
                'error' => $e->getMessage(),
            ], 500);
        }
    }
    public function deleteCategory($id){
        try {
            $category=Category::findOrFail($id);
            if($category){
                $category->delete();
                return response()->json([
                    'message' => 'Category deleted successfully',
                ], 200);
            }
            return response()->json([
                'message' => 'Category not found',
            ], 404);
        } catch (Exception $e) {
            return response()->json([
                'message' => 'Category deleted failed',
                'error' => $e->getMessage(),
            ], 500);
        }
    }
}
