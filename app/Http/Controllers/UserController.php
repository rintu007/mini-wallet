<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;

class UserController extends Controller
{
    public function index(Request $request)
    {
        // Exclude current user from the list
        $users = User::where('id', '!=', $request->user()->id)
            ->select('id', 'name', 'email')
            ->get();

        return response()->json($users);
    }
}