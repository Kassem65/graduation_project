<?php

namespace App\Http\Controllers\Teacher;

use App\Http\Controllers\CodeExecutorController;
use App\Http\Controllers\Controller;
use App\Http\Resources\ProblemResource;
use App\Models\Problem;
use Illuminate\Http\Request;

class ProblemController extends Controller
{
    
 
    public function index(){
        return ProblemResource::collection(Problem::where('active' , 0)->get());
    }
    public function showBank(){
        $problems = auth()->user()->teacher->problems()->where('active', 0)->get() ;
        return ProblemResource::collection($problems);
    }
    public function myProblems() {
        $teacher = auth()->user()->teacher;
        $problems = $teacher->problems();
        return response()->json([
            'data' => $problems
        ], 200);
    }
    public function store(Request $request) {
        $teacher = auth()->user()->teacher;
        $request->validate([
            'name' => 'required',
            'description' => 'required',
            'teacher_code_solve' => 'required',
            'language' => 'required|integer',
            'test_cases' => 'required|array',
            'tags' => 'required|array',
        ]);
        $problem = $teacher->problems()->create($request->all());
        // add tags to problem
        $maxTim = 0 ;
        foreach($request->tags as $tag)
            $problem->tags()->attach($tag);
        // add test_cases to problem
        foreach($request->test_cases as $test_case) {
            $input = $test_case;
            if ($request->language == 1){
                $output = CodeExecutorController::runCppCode([
                    'code' => $request->teacher_code_solve,
                    'input' => $test_case,
                ]);
            }
            else{
                $output = CodeExecutorController::runJavaCode([
                    'code' => $request->teacher_code_solve,
                    'input' => $test_case,
                ]);
            }
            if (array_key_exists('error' , $output)) {
                $problem->delete() ;
                return response()->json([
                    'input' => $test_case ,
                    'output' =>'wrong test case ' . $output['error'],
                ]);
            }
            if ($output['time'] > $maxTim)$maxTim = $output['time'] ;
            $problem->testCases()->create([
                'input'=>$input ,
                'output' => $output['output'],
            ]);
        }
        //add the time of the problem and we can make it integer to present seconds . 
        $problem->time_limit_ms = $maxTim + 0.2 ;
        $problem->save() ;
        $problem->testCases ;
        $problem['code']= $problem->teacher_code_solve ;    
        return $problem ;   
    }
    public function show(Problem $problem) {
        
        $problem->tags;
        $problem->testCase ;
        $problem['solve'] = $problem->teacher_code_solve ;
        return $problem ;
    }
    public function delete(Problem $problem){
         if (auth()->user()->teacher->id != $problem->teacher_id){
            abort(403 , 'this problem does not belong to you' ) ;
        } 
        $problem->delete();
        return response()->json([
            'message' => 'deleted successfully' ,
        ],200) ;
    }
    public function activate(Problem $problem){
        
        if (auth()->user()->teacher->id != $problem->teacher_id){
            abort(403 , 'this problem dont belong to you' ) ;
        } 
        $problem->active = 1 ;
        $problem->save();
        return response()->json([
            'message' => 'activeted successfully'
        ],200) ;
    }
    public function generateTestCases(Request $request){
        
        $inputs = CodeExecutorController::generateTestCases($request->model);
        $i = 0 ;
        $res = [];
        foreach ($inputs as $input){
            $res[$i]['input'] = $input ;
            $param['input'] = $input ;
            $param['code'] = $request->code ;
            if ($request->lang == 1)
                $res[$i]['output'] = CodeExecutorController::runCppCode($param)['output'] ;
            else $res[$i]['output'] = CodeExecutorController::runJavaCode($param)['output'];
            $i++;
        }
        return $res ;
    }
  
}
