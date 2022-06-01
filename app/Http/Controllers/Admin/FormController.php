<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\FormFieldType;
use Illuminate\Http\Request;

class FormController extends Controller
{
    public function createType()
    {
        return view('admin.form.createType');
    }


    public function createTypePost(Request $request)
    {
        FormFieldType::create([
            'type' => $request->type
        ]);
        return back();
    }

    public function showTypes()
    {
        $types = FormFieldType::all();
        return view('admin.form.showTypes', compact('types'));
    }
}
