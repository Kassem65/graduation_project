<?php

namespace App\Http\Controllers;

// use Illuminate\Contracts\Cache\Store;

use GuzzleHttp\Client;
use GuzzleHttp\Psr7\Request;
use Illuminate\Support\Facades\Storage;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx\Rels;

class CodeExecutorController extends Controller
{
    public static function runCppCode(array $param):array
    {
        
        // Receive the C++ code and input from the request.
        $cppCode = $param['code'];
        $input = $param['input']; // Default to an empty string if 'input' is not provided.

        // Create a temporary input file and write the input data to it.
        $inputFilePath = tempnam(sys_get_temp_dir(), 'Code/cpp_input_');
        file_put_contents($inputFilePath, $input);

        // Create a temporary error file for compilation or execution errors.
        $errorFilePath = tempnam(sys_get_temp_dir(), 'Code/cpp_error_');
        $salt = random_int(1, 1000000);
        $filename =  "cpp_code_$salt.cpp";

        // Path to the /tmp directory
        $tmpDirectory = "/tmp/";
        // Write the text to a file in the /tmp directory
        file_put_contents($tmpDirectory . $filename, $param['code']);
        // Run the C++ code with input and capture the output and errors.
        $command = "g++ /tmp/cpp_code_$salt.cpp -o /tmp/cpp_code_output_$salt 2> $errorFilePath && cat $inputFilePath | /tmp/cpp_code_output_$salt";
        // return $command ;
        $start_time = microtime(true) ; 
        exec($command, $output, $returnCode);
        $end_time = microtime(true) ;
        if ($returnCode !== 0) {
            // Compilation or execution error occurred.
                // Clean up temporary files.
            $errorOutput = file_get_contents($errorFilePath);
            unlink($inputFilePath);
            unlink($errorFilePath);
            unlink("/tmp/cpp_code_$salt.cpp");
            // Return the output as a response.

            // Return the error message as a response.
            return ['error' => $errorOutput];
        }

        // Clean up temporary files.
        unlink($inputFilePath);
        unlink($errorFilePath);
        unlink("/tmp/cpp_code_$salt.cpp");
        unlink("/tmp/cpp_code_output_$salt");
        // Return the output as a response.
        return ['output' => implode(PHP_EOL, $output), 'time' => $end_time - $start_time];
    }

    public static function runJavaCode(array $param):array
    {
        $start_time = microtime(true) ;
        $salt = random_int(0,1000000) ;
        // Receive the Java code and input from the request.
        $javaCode = $param['code'];
        $input = $param['input']; // Default to an empty string if 'input' is not provided.
        $errorFilePath = tempnam(sys_get_temp_dir(), 'java_error_');
        $inputFilePath = tempnam(sys_get_temp_dir(), 'java_input_');
        file_put_contents($inputFilePath, $input);

        $filename =  "java_code$salt.java";

        // Path to the /tmp directory
        $tmpDirectory = "/tmp/";
        // Write the text to a file in the /tmp directory
        file_put_contents($tmpDirectory . $filename, $param['code']);


        // Compile the Java code and capture the errors, if any.
        
        $compileCommand =  "javac /tmp/java_code$salt.java 2> $errorFilePath ";
        // return $compileCommand ; 

        exec($compileCommand, $compileOutput, $compileReturnCode);
        
        $compileCommand = "javac /tmp/java_code$salt.java 2> $errorFilePath" ;
        exec($compileCommand, $compileOutput, $compileReturnCode);
        if ($compileReturnCode !== 0) {
            // Compilation error occurred.
            $errorOutput = file_get_contents($errorFilePath);
            unlink($inputFilePath);
            unlink($errorFilePath);
            unlink("/tmp/java_code$salt.java");
            // Return the error message as a response.
            return ['error' => $errorOutput];
        }
        // Run the compiled Java code with input and capture the output.
        $executionCommand = "cd /tmp  && cat $inputFilePath | java Main" ;
        $start_time = microtime(true);
        exec($executionCommand, $output, $executionReturnCode);
        $end_time = microtime(true);

        // Clean up temporary files.
        unlink($inputFilePath);
        unlink($errorFilePath);
        unlink("/tmp/java_code$salt.java");
        unlink("/tmp/Main.class"); // Remove compiled .class file

        // Return the output as a response.
        return ['output' => implode(PHP_EOL, $output) , 'time' => $end_time - $start_time];
    }

    Public static  function generateTestCases($model){
        // $salt = random_int(0 , 1000000) ;
        $executionCommand = "cd /home/kassem/Projects/Graduated_Project/code/storage/app/public && echo \"$model\" | java Main" ;
        exec($executionCommand, $output, $executionReturnCode);
        return $output ; 
    }
    public function replaceWordInFile() {
        $filePath = 'Main.java';
        $searchWord = 'Main';
        
        $replaceWord = 'Kasseem';
        $content = Storage::get($filePath);
        return $content;
        $newContent = str_replace($searchWord, $replaceWord, $content);

        Storage::put($filePath, $newContent);
        return response()->json([
            'message' => 'word replaced succfully...'
        ]);

    }
    public static function runCpp($param){
        $client = new Client();
          
        $url = 'http://127.0.0.1:8001/api/run-cpp'; // Replace 8080 with the actual port number

        try {
            $response = $client->request('POST', $url, [
                'json' => [
                    'code' => $param['code'],
                    'input' =>$param['input'],
                ]
            ]);

            // Get the response body
            $body = $response->getBody();
            $contents = $body->getContents();
            // return   $body ;
            // Process the response as needed
            // For example, you can return the response to the client
            return response()->json(json_decode($contents), $response->getStatusCode());
        } catch (\GuzzleHttp\Exception\RequestException $e) {
            // Handle request exceptions (e.g., connection errors, timeouts)
            // Log the error or return an appropriate response
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }
}
