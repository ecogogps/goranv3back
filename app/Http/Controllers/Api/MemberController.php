<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Member;

class MemberController extends Controller
{
    public function index()
    {
        
        $members = Member::orderBy('name')->get();

        
        return response()->json($members);
    }
}
