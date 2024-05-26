<?php

namespace App\Http\Controllers\Teacher;

use App\Http\Controllers\Controller;
use App\Models\Exam;
use App\Models\ExamStudent;
use App\Models\Subject;
use Illuminate\Http\Request;

class ExamController extends Controller
{
    public function showStudentSolve(Request $request){
        $request->validate([
            'student_id' => 'required|integer',
            'exam_id' => 'required|integer'
        ]);
        $solve = ExamStudent::where('student_id' , $request->student_id)
            ->where('exam_id' , $request->exam_id)
            ->first();
        // $solve['problem_1'] = $solve->exam->problem1->description ; 
        // return $solve ;
        $answer = $solve->answers->map(function ($answer) {
            return [
                'question_text' => $answer->trueFalseQuestion()->first()['question_text'],
                'answer' => $answer->answere
            ];
        });
        
        $solve['answers'] = $answer ;
        return $solve;
    }

    public function editMarkStudent(Request $request) {
        $request->validate([
            'exam_id' => 'required|integer' ,
            'student_id' => 'required|integer' ,
            'mark' => 'required|integer'
        ]);
        $exam = ExamStudent::where('student_id' , $request->student_id)
            ->where('exam_id' , $request->exam_id)
            ->first() ;
        $exam->mark = $request->mark ;
        $exam->save();
        return response()->json([
            'message' => 'updated successfully'
        ],200);
    }
    public function examInCategory(Subject $subject){
        return $subject->exam ;
    }
    public function show(Exam $exam){
        $exam->students ;
        $exam->trueFalseQuestions ;
        $exam->problem1 ;
        return $exam ;
    }
    
}
