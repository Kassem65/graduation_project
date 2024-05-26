<?php

namespace App\Http\Controllers\Teacher;

use App\Http\Controllers\Controller;
use App\Http\Resources\AssessmentStudentsResource;
use App\Models\Assessment;
use App\Models\Category;
use App\Models\CategoryStudent;
use Illuminate\Http\Client\ResponseSequence;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class AssessmentController extends Controller
{
    public function index(Category $category) {
        return $category->assessments->map(function($assessment){
            return [
                'problem_name' =>$assessment->problem->name ,
                'problem_id' => $assessment->problem_id ,
                'id' => $assessment->id ,
                'name' => $assessment->name ,
                'date' =>  $assessment->created_at ,
            ];
        });
    }

    public function store(Request $request){
        $request->validate([
            'category_id' => 'required|exists:categories,id',
            'name' => 'string' ,
            'problem_id' => 'required|exists:problems,id'
        ]);
        $request['teacher_id'] = auth()->user()->teacher->id;
        Assessment::create($request->all());
        return [
            'message' => 'assessment added successfully' 
        ];

    }
    public function stopAssessment(Assessment $assessment , Request $request ) {
        $request->validate([
            'students' => 'required|array'
        ]);
        $this->checkPermission($assessment) ;
        // return $request ;
        $category = $assessment->category ;
        $assessment->active = 0 ; 
        $assessment->problem->active = 1;
        $category = $assessment->category ;
        foreach($request->students as $student){
            $cat_stu = CategoryStudent::where('student_id' , $student['id'])
                ->where('category_id' , $category->id)
                ->first();
            if ($student['mark']!=0)
            // return $cat_stu ;
                $cat_stu->assessment_marks = ($cat_stu->assessment_marks + $student['mark'])/($cat_stu->number_of_assessment+1) ;
            $cat_stu->number_of_assessment = $cat_stu->number_of_assessment + 1 ;
            $cat_stu->attendance_marks ++;
            $cat_stu->save();
        }
        return ['mesage' => 'assessment finishing successfully'] ;
    }
    public function active(Assessment $assessment , Request $request ) {
        $this->checkPermission($assessment) ;
        $request->validate([
            'students' => 'required|array'
        ]);
        $assessment->active = 1 ;
        $assessment->save() ;
        foreach($request->students as $student){
            $assessment->students()->attach($student) ;
        }
        return [ 'message' => 'activeated successfully ']; 
        //AssessmentStudentsResource::collection($assessment->students()->get());
    }
    public function details(Assessment $assessment){
        $data['student'] =  AssessmentStudentsResource::collection($assessment->students()->get());
        $problem = $assessment->problem ;
        $problem->testCases;
        $problem->tags ;
        $problem['solve'] = $problem->teacher_code_solve ;
        $data['problem'] = $problem ;
        return $data ;

    }
    public function delete(Assessment $assessment){
        $assessment->delete() ;
        return response()->json([
            'message' => 'deleted sucessfully'
        ] ,200) ;
    }

    protected function checkPermission(Assessment $assessment){
        if ($assessment->teacher_id != auth()->user()->teacher->id)
            abort(403 , 'this assessment dont belongs to you') ;
    }
    
    
}
