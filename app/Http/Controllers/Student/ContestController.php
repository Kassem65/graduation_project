<?php

namespace App\Http\Controllers\Student;

use App\Http\Controllers\Controller;
use App\Http\Resources\StudentDispalyResource;
use App\Models\Contest;
use App\Models\ContestStudent;
use App\Models\Problem;
use App\Models\Student;
use Illuminate\Http\Request;

class ContestController extends Controller
{
    public function index() {
        $contests = Contest::all();
        $contests = $contests->map(function($contest) {
            $contest['owner'] = auth()->user()->student->user->name;
            return $contest;
        });
        return $contests;
    }
    public function allStudents() {
        return StudentDispalyResource::collection(Student::with('user')->get());

    }
    public function myContests() {
        $student = auth()->user()->student;
        $contests = $student->contests->map(function($contest){
            $contest['owner'] = $contest->owner() ;
            return $contest ;
        });
        
        return $contests ;
    }
    public function create(Request $request){
        $student = auth()->user()->student ;
        $request->validate([
            'name' => 'required|string',
            'duration' => 'integer',
            'start_at' => 'required|date_format:Y-m-d',
            'min_level' => 'required|integer|min:1|max:10',
            'max_level' => 'required|integer|min:0|max:10',
            'students' => 'array' 
        ]);
        $scour = 0 ;
        $contest = $student->contests()->create($request->all());
        $problems = Problem::whereBetween('level', [$request->min_level, $request->max_level])
                    ->inRandomOrder()
                    ->take(5)   
                    ->get();
        foreach($problems as $problem){
            $scour +=$problem->level ;
            $contest->problems()->attach($problem->id);
        }
        $contest->scoure = $scour ;
        if ($request->students !== NULL)
            foreach($request->students as $student_id)
                if ($student_id !== $student->id)
                   $contest->students()->attach($student_id) ;
        $contest->save() ;
        return response()->json([
            'message' => "contest will start at $request->start_at",
        ],200);
    }
    public function solve(Contest $contest , Problem $problem,Request $request){
        $student =  auth()->user()->student;
        $exisit = ContestStudent::where('contest_id', $contest->id)
            ->where('student_id', $student->id)
            ->first();
        
        $this->checkContestTime($contest) ;
        if ($exisit == NULL)
            abort(403 ,'join before try to solve');
        $solvedProblems = $exisit->solvedProblems()->get();
        foreach ($solvedProblems as $solvedProblem) {
            if ($solvedProblem->problem == $problem->id)
                abort (403, 'you are already solved');
        }
        $solve = ProblemController::solveProlem($problem , $request); 
        if ($solve['approved'] == true){
            $exisit->solvedProblems()->create(['problem' => $problem->id]);
            $currentRank = $student->contests()->wherePivot('contest_id', $contest->id)->value('rank');
            $newRank  = $currentRank + $problem->level ;
            $student->contests()->updateExistingPivot($contest->id , ['rank' => $newRank]) ;
       }
       return $solve ;
    }
    public function join(Contest $contest,Request $request){
        if($contest->students()->exists(auth()->user()->student->id))
        return response()->json([
            'messsage' => 'allready exists',
        ]);
        $this->checkContestTime($contest) ;
        if ($contest->password == NULL){
            $contest->students()->attach(auth()->user()->student->id);
        }else {
            $request->validate([
                'password' => 'required'
            ]);
            if ($contest->password != $request->password)
                return response()->json([
                    'message' => 'wrong password'
                ],401); 
            $contest->students()->attach(auth()->user()->student->id);
        }
        return response()->json([
            'message' => 'joined successfully'
        ]);
    }
    public function displayRanks(Contest $contest){
        return $contest->students()->withPivot('rank');
    }
    protected function checkContestTime(Contest $contest){
        $currentDateTime = now();
        if ($currentDateTime < $contest->start_at)
            abort(403,'contest not start yet');
        else if ($currentDateTime->subHours($contest->duration) > $contest->start_at)
            abort(403,'contest is over') ; 
    }
    public function show (Contest $contest){
        $students = $contest->students()->with('user:id,name')->get();
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
