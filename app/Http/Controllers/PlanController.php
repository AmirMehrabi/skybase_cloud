<?php

namespace App\Http\Controllers;

class PlanController extends Controller
{
    public function index()
    {
        return view('plans.index');
    }

    public function create()
    {
        return view('plans.create');
    }

    public function show($id)
    {
        return view('plans.show', ['id' => $id]);
    }

    public function edit($id)
    {
        return view('plans.edit', ['id' => $id]);
    }
}
