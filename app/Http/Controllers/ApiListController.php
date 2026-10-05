<?php

namespace App\Http\Controllers;

class ApiListController extends Controller
{
    public function index()
    {
        $apiBase = url('/api');
        return view('api-list', compact('apiBase'));
    }
}
