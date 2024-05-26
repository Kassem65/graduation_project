<?php

namespace App\Http\Controllers\Adminstrator;

use App\Http\Controllers\Controller;
use App\Http\Resources\ExamsSubjectsResource;
use App\Http\Resources\ExamSubjectResource;
use App\Models\Exam;
use App\Models\ExamStudent;
use App\Models\Subject;
use Illuminate\Http\Request;

class ExamController extends Controller
{
    public function index() {
        $exams = Exam::with('subject')->get();
        return ExamsSubjectsResource::collection($exams);
    }
    public function show($id) {
        $exam = Exam::with('subject', 'problem1', 'trueFalseQuestions')->find($id);
        
        return new ExamSubjectResource($exam);
    }
    public function addExamToSubject(Request $request, Subject $subject) {
        $request->validate([
            'exam_date' => 'required|date',
            'exam_name' => 'required',
            'exam_password' => 'required',
            'problem_id' => 'required|integer',
            // 'questions' => 'required|array'
        ]);
        // add all students in this subject to this exam_student
        $students = $subject->students;
        $exam = Exam::create([
            'passwd' => $request->exam_password,
            'administrator_id' => auth()->id(),
            'name' => $request->exam_name,
            'time' => $request->exam_date,
            'subject_id' => $subject->id,
            'problem1_id' => $request->problem_id,
        ]);
        foreach ($students as $student)
        {
            $exam->student()->attach($student->id);   
        }
        return response()->json([
            'success' => 'exam added successfully and students added success',
            'exam' => $exam,
        ]);
    }
}
