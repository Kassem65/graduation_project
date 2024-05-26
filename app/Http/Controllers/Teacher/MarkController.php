<?php

namespace App\Http\Controllers\Teacher;

use App\Http\Controllers\Controller;
use App\Models\Category;
use Illuminate\Http\Request;

class MarkController extends Controller
{
    public function addMarks(Request $request){
        $request->validate([
            'attendance_mark' => 'required|integer',
            'category_id' => 'required|exists:categories,id',
        ]);
        $category = Category::find($request->category_id);
        if ($category->teacher_id != auth()->user()->teacher->id)
        abort(403 , 'this category dont belon to you ');
        $exam_mark = $category->subject->exam_mark;
        $total_mark  = $category->subject->total_mark;
        $category->mark_of_commings = $request->attendance_mark;
        $category->mark_of_ratings = $total_mark - $category->mark_of_commings - $exam_mark ;
        $category->save();
        return response()->json([
            'message'=> 'mark added sucssessfully'
        ],200);
    }
    public function showMarks(Category $category) {
        $students = $category->students()->with('user:id,name')->get();
        $response = $students->map(function ($student) {
            return [
                'id' => $student->user->id,
                'name' => $student->user->name,
                'pivot' => $student->pivot
            ];
        });
        return $response;
    }
}
