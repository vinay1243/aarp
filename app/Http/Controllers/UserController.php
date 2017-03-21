<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use App\Helpers\Common;
use App\Model;

class UserController extends Controller {

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
        $rand = rand(99, 99999);

        $request->request->add(['rand' => $rand]);

        app('App\Http\Controllers\CommonController')->logData($request, json_encode($request->input()), 'info');


        switch ($request->input('type')) {
            case 100:
                // register  
                $this->registerUser($request);
                break;
            case 101:
                // login  
                $this->login($request);
                break;
            case 102:
                // to get user details  
                $this->getUserDetails($request);
                break;
            case 103:
                // to update profile
                $this->updateProfile($request);
                break;
            case 104:
                // to update or get last seen of a user
                $this->lastSeen($request);
                break;
            case 105:
                // for forgot password
                $this->forgotPassword($request);
            
            case 999;
                $this->test($request);
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

    /*
      for registering the new user either care giver or care recipient
     */

    public function registerUser($request) {

        // to validate the registeration fields in hepler called common 
        $commonObj = new Common();
        $validate = $commonObj->validateRegister($request);

        // if it returns not valid then it returns error 
        if (!$validate['isValid']) {
            app('App\Http\Controllers\CommonController')->logData($request, json_encode($request->input()) . 'required fields are missing', 'error');
            $result['success'] = FALSE;
            $result['error'] = 'Required Fields are missing';
            $this->respond($result);
        }
        // else it will continue for further process
        // initialising the users model
        $usersObj = new Model\Users();
        // saving the user in users model and returns the user id 
        $saveUser = $usersObj->saveUser($request);
        // $save User variable has user id of the record which is inserted
        if ($saveUser) {
            // if saveUser exists then it will return user_id with success as true   
            app('App\Http\Controllers\CommonController')->logData($request, json_encode($request->input()), 'info');

            // $data['user_id'] = $encrypted_user_id;
            $result['success'] = TRUE;
            $result['error'] = '';
            $result['user_id'] = $saveUser;
        } else {
            app('App\Http\Controllers\CommonController')->logData($request, json_encode($request->input()) . 'some technical error', 'error');
            $result['success'] = FALSE;
            $result['error'] = 'Unable to register';
            $result['user_id'] = '';
        }
        // returns the result in json format  
        $this->respond($result);
    }

    // login function

    public function login($request) {

        $data = $request->input();
        //  print_r($data);die();
        if ((isset($data['mobile']) && $data['mobile'] == '') || (isset($data['password']) && $data['password'] == '')) {
            app('App\Http\Controllers\CommonController')->logData($request, json_encode($request->input()) . 'some technical error', 'error');
            $result['success'] = FALSE;
            $result['error'] = 'Required fields are missing';
            $result['data'] = '';
            // returns the result in json format  
            $this->respond($result);
        }

        $usersObj = new Model\Users();

        $checkLogin = $usersObj->checkLogin($request);
        if ($checkLogin != FALSE) {
            // $dataArray = (array)$checkLogin;
            $result['success'] = TRUE;
            $result['error'] = '';
            $result['data'] = $checkLogin;
            $this->respond($result);
        } else {
            $result['success'] = FALSE;
            $result['error'] = 'Mobile number and password dont match';
            $result['data'] = '';
            $this->respond($result);
        }
    }

    public function getUserDetails($request) {

        $usersObj = new Model\Users();
        $getDetails = $usersObj->getUserDetails($request->input('user_id'));
        if ($getDetails == FALSE) {
            $result['success'] = FALSE;
            $result['error'] = 'Invalid User Id';
            $result['data'] = '';
            $this->respond($result);
        }

        $result['success'] = TRUE;
        $result['error'] = '';
        $result['data'] = $getDetails;
        $this->respond($result);
    }

    public function updateProfile($request) {
        $usersObj = new Model\Users();
        $checkforValidUser = $usersObj->getUserDetails($request->input('user_id'));
        if ($checkforValidUser == FALSE) {
            $result['success'] = FALSE;
            $result['error'] = 'Invalid User Id';
            $result['data'] = '';
            $this->respond($result);
        }

        $update_data = array();
        $update_data = $request->input();
        unset($update_data['user_id'], $update_data['rand'], $update_data['type']);
        
        if(isset($update_data['password']) && $update_data['password'] != ''){
            $update_data['password'] = password_hash($update_data['password'], PASSWORD_DEFAULT);
        }
        $whereArray = array('user_id' => $request->input('user_id'));
        $updatedArray = $update_data;
        $updateProfile = $usersObj->updateProfile($whereArray, $updatedArray);

        if ($updateProfile == FALSE) {
            $result['success'] = FALSE;
            $result['error'] = 'Technical Error';
            $result['data'] = '';
        } else {
            $getUpdatedDetails = $usersObj->getUserDetails($request->input('user_id'));
            $result['success'] = TRUE;
            $result['error'] = '';
            $result['data'] = $getUpdatedDetails;
        }
        $this->respond($result);
    }

    public function lastSeen($request) {

        $usersObj = new Model\Users();
        $checkforValidUser = $usersObj->getUserDetails($request->input('user_id'));
        if ($checkforValidUser == FALSE) {
            $result['success'] = FALSE;
            $result['error'] = 'Invalid User Id';
            $result['data'] = '';
            $this->respond($result);
        }
        $update_data = array();
        $update_data = $request->input();
        $whereArray = array('user_id' => $request->input('user_id'));
        if (isset($update_data['last_seen']) && $update_data['last_seen'] != '') {

            unset($update_data['user_id'], $update_data['rand'], $update_data['type']);

            $updatedArray = $update_data;
            $updateLastSeen = $usersObj->updateLastSeen($whereArray, $updatedArray);
            if ($updateLastSeen == FALSE) {
                $result['success'] = FALSE;
                $result['error'] = 'Technical Error';
                $result['data'] = '';
            } else {
                $getLastSeen = $usersObj->getLastSeen($whereArray);
                $result['success'] = TRUE;
                $result['error'] = '';
                $result['data'] = $getLastSeen;
            }
        } else {

            $getLastSeen = $usersObj->getLastSeen($whereArray);

            if ($getLastSeen) {
                $result['success'] = TRUE;
                $result['error'] = '';
                $result['data'] = $getLastSeen;
            } else {

                $result['success'] = FALSE;
                $result['error'] = 'Technical Error';
                $result['data'] = '';
            }
        }
        $this->respond($result);
    }

    public function forgotPassword($request) {

        $usersObj = new Model\Users();

        $getDetails = $usersObj->getDetailsByMobile($request);
        if ($getDetails) {
            $changePassword = $usersObj->changePassword($request);
            if($changePassword == FALSE){
                $result['success'] = FALSE;
                $result['error'] = 'Technical Error';
                $result['data'] = '';
            }else{
                $result['success'] = TRUE;
                $result['error'] = '';
                $result['data'] = '';                
            }
        } else {
            $result['success'] = FALSE;
            $result['error'] = 'Incorrect mobile number';
            $result['data'] = '';
        }
        $this->respond($result);
    }
    

    public function test($request) {

        $test = array();
        $test['name'] = 'Vinay';
        $test['mobile'] = '1234567890';
        $test['email'] = 'vinay@mail.com';

        $this->respond($test);
    }

    public function respond($response, $exit = true) {

        echo json_encode($response, JSON_UNESCAPED_SLASHES);
        exit();
    }

}
