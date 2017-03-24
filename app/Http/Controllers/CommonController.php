<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use App\Model;

class CommonController extends Controller {

    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index() {
        //
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create() {
        //
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request) {
        //
        $rand = rand(99, 99999);

        $request->request->add(['rand' => $rand]);

        app('App\Http\Controllers\CommonController')->logData($request, json_encode($request->input()), 'info');


        switch ($request->input('type')) {
            case 200:
                // register  
                $this->getQuestions($request);
                break;
            default:
                break;
        }
    }

    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function show($id) {
        //
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function edit($id) {
        //
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, $id) {
        //
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy($id) {
        //
    }

    // all user controller related validations will be done here

    public function logData($request, $logMsg, $logType, $return = '') {
        $logMsg = $request->input('rand') . " : " . $logMsg;
        switch ($logType) {
            case 'error':
                Log::error($logMsg);
                break;
            case 'warning':
                Log::warning($logMsg);
                break;
            case 'info':
                Log::info($logMsg);
                break;
            default:
                break;
        }

        if ($return != '') {
            echo json_encode($return, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
            exit;
        }
    }

    public function validateData($request) {

        if ($request->input('type') == NULL) {
            $this->logData($request, "Invalid or missing command value", 'error', Array("empty"));
        }
        $data_set = $request->input();
        switch ($request->input('type')) {
            case 100:
                $this->validateRegister($data_set);
                break;

            default:
                break;
        }
    }

    public function getQuestions($data_set) {


        $questionsObj = new Model\ScreeningQuestions();

        $questions = $questionsObj->getScreeningQuestions($data_set);

//        $result['success'] = TRUE;
//        $result['error'] = '';
//        $result['data'] = $questions;
//        $this->respond($result);
        echo $questions;
    }

    public function respond($response, $exit = true) {
        echo json_encode($response, JSON_UNESCAPED_SLASHES);
        exit();
    }

}
