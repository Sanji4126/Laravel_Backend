<?php

namespace App\Http\Controllers\User;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\User;

class UserController extends Controller
{
    public function user()
    {
        $user = User::all();
        return response()->json([
            'msg' => "get data success",
            'data' => $user,
        ],200);
    }
}
