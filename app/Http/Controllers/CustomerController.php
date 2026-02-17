<?php

namespace App\Http\Controllers;

class CustomerController extends Controller
{
    public function index()
    {
        return view('customers.index');
    }

    public function create()
    {
        return view('customers.create');
    }

    public function show($id)
    {
        return view('customers.show', ['id' => $id]);
    }

    public function edit($id)
    {
        return view('customers.edit', ['id' => $id]);
    }
}
