<?php

namespace App\Http\Controllers\Student;

use App\Http\Controllers\Controller;
use App\Http\Resources\CategoryResource;
use App\Http\Resources\CategorySubjectResource;
use App\Http\Resources\ExamsDisplay;
use App\Models\Category;
use App\Models\ChangeCategoryRequest;
use Illuminate\Http\Request;

class CategoryController extends Controller
{
    public function index(){
        return CategoryResource::collection(Category::all());
    }
    public function myCategories(){
        // $data = [];
        $categories = auth()->user()->student->categories; 
        $data['categories'] = CategorySubjectResource::collection($categories);
       
        return $data;
    }
    public function changeCategory(Request $request){
        $request->validate([
            'old_category' => 'required|exists:categories,id',
            'new_category' => 'required|exists:categories,id',
            'reason' => 'required'
        ]);
        
        $student = auth()->user()->student;
        
        $data = ChangeCategoryRequest::where('student_id', $student->id)
            ->where('new_category', $request->new_category)
            ->first();
        if ($data != null) {
            return response()->json([
                'error' => 'you are already in this category'
            ]);
        }
        $student->requests()->create($request->all());
        return ['message' => 'category changed successfully'] ; 

    }
    public function show (Category $category){
        $data = [] ;
        $data['teacher_name'] = $category->teacher->user->name ;
        $data['subject'] = $category->subject->name ;
        $data['assessments'] = $category->assessments ;
        return $data ;
   }

    
}
