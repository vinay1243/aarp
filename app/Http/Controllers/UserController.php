<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use App\Helpers\Common;
use App\Model;
use Intervention\Image\ImageManagerStatic as Image;
use Illuminate\Support\Facades\Storage;
use Aws\S3\S3Client;
use League\Flysystem\AwsS3v3\AwsS3Adapter;
use League\Flysystem\Filesystem;

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
        $data_set = $request->input();
        $commonObj = new Common();
        if (isset($data_set['user_id_encrypt']) && $data_set['user_id_encrypt'] != '') {

            $decrypted_value = $commonObj->decryptId($data_set['user_id_encrypt']);

            $data_set['user_id'] = $decrypted_value;
        }

        switch ($request->input('type')) {
            case 100:
                // register  
                $this->registerUser($data_set);
                break;
            case 101:
                // login  
                $this->login($data_set);
                break;
            case 102:
                // to get user details  
                $this->getUserDetails($data_set);
                break;
            case 103:
                // to update profile
                $this->updateProfile($data_set);
                break;
            case 104:
                // to update or get last seen of a user
                $this->lastSeen($data_set);
                break;
            case 105:
                // for forgot password
                $this->forgotPassword($data_set);
                break;
            case 106:
                // for saving profile image
                $this->saveUserProfileImage($data_set);
                break;
            case 107;
                $this->deleteUserProfileImages($data_set);
            case 999;
                $this->test($data_set);
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

    public function registerUser($data_set) {

        // to validate the registeration fields in hepler called common 
        $commonObj = new Common();
        $validate = $commonObj->validateRegister($request);

        // if it returns not valid then it returns error 
        if (!$validate['isValid']) {
            app('App\Http\Controllers\CommonController')->logData($data_set, json_encode($data_set) . 'required fields are missing', 'error');
            $result['success'] = FALSE;
            $result['error'] = 'Required Fields are missing';
            $this->respond($result);
        }

        // else it will continue for further process
        // initialising the users model
        $usersObj = new Model\Users();
        // check whether user exists or not
        $checkUser = $usersObj->getDetailsByMobile($data_set['mobile']);

        if (!empty($checkUser)) {
            $result['success'] = FALSE;
            $result['error'] = 'Account exists with the given mobile number';
            $result['data'] = NULL;
            $this->respond($result);
        }
        // saving the user in users model and returns the user id 
        $saveUser = $usersObj->saveUser($request);
        // $save User variable has user id of the record which is inserted
        if ($saveUser) {
            // if saveUser exists then it will return user_id with success as true   
            app('App\Http\Controllers\CommonController')->logData($data_set, json_encode($data_set), 'info');

            // $data['user_id'] = $encrypted_user_id;
            $result['success'] = TRUE;
            $result['error'] = NULL;
            $result['user_id'] = $saveUser;
        } else {
            app('App\Http\Controllers\CommonController')->logData($data_set, json_encode($data_set) . 'some technical error', 'error');
            $result['success'] = FALSE;
            $result['error'] = 'Unable to register';
            $result['user_id'] = NULL;
        }
        // returns the result in json format  
        $this->respond($result);
    }

    // login function

    public function login($data_set) {


        //  print_r($data);die();
        if ((isset($data_set['mobile']) && $data_set['mobile'] == '') || (isset($data_set['password']) && $data_set['password'] == '')) {
            app('App\Http\Controllers\CommonController')->logData($data_set, json_encode($data_set) . 'some technical error', 'error');
            $result['success'] = FALSE;
            $result['error'] = 'Required fields are missing';
            $result['data'] = NULL;
            // returns the result in json format  
            $this->respond($result);
        }

        $usersObj = new Model\Users();

        $checkLogin = $usersObj->checkLogin($data_set);
        if ($checkLogin != FALSE) {
            unset($checkLogin->user_id);
            // $dataArray = (array)$checkLogin;
            $result['success'] = TRUE;
            $result['error'] = NULL;
            $result['data'] = $checkLogin;
            $this->respond($result);
        } else {
            $result['success'] = FALSE;
            $result['error'] = 'Mobile number and password dont match';
            $result['data'] = NULL;
            $this->respond($result);
        }
    }

    public function getUserDetails($data_set) {

        $usersObj = new Model\Users();
        $getDetails = $usersObj->getUserDetails($data_set['user_id']);
        if ($getDetails == FALSE) {
            $result['success'] = FALSE;
            $result['error'] = 'Invalid User Id';
            $result['data'] = NULL;
            $this->respond($result);
        }
        unset($getDetails->user_id);
        $result['success'] = TRUE;
        $result['error'] = NULL;
        $result['data'] = $getDetails;
        $this->respond($result);
    }

    public function updateProfile($data_set) {
        $usersObj = new Model\Users();
        $checkforValidUser = $usersObj->getUserDetails($data_set['user_id']);
        if ($checkforValidUser == FALSE) {
            $result['success'] = FALSE;
            $result['error'] = 'Invalid User Id';
            $result['data'] = NULL;
            $this->respond($result);
        }

        // check whether the record exists with the particular mobile number
        $checkForRecordExists = $usersObj->checkRecordWithMobile($data_set['mobile']);

        if ($checkForRecordExists == FALSE) {
            $result['success'] = FALSE;
            $result['error'] = 'Record exists with the given mobile number';
            $result['data'] = NULL;
            $this->respond($result);
        }


        $update_data = array();
        $update_data = $data_set;
        unset($update_data['user_id'], $update_data['rand'], $update_data['type']);

        if (isset($update_data['password']) && $update_data['password'] != '') {
            $update_data['password'] = password_hash($update_data['password'], PASSWORD_DEFAULT);
        }
        $whereArray = array('user_id' => $data_set['user_id']);
        $updatedArray = $update_data;
        $updateProfile = $usersObj->updateProfile($whereArray, $updatedArray);

        if ($updateProfile == FALSE) {
            $result['success'] = FALSE;
            $result['error'] = 'Technical Error';
            $result['data'] = NULL;
        } else {
            $getUpdatedDetails = $usersObj->getUserDetails($data_set['user_id']);
            unset($getUpdatedDetails->user_id);
            $result['success'] = TRUE;
            $result['error'] = NULL;
            $result['data'] = $getUpdatedDetails;
        }
        $this->respond($result);
    }

    public function lastSeen($data_set) {

        $usersObj = new Model\Users();
        $checkforValidUser = $usersObj->getUserDetails($data_set['user_id']);
        if ($checkforValidUser == FALSE) {
            $result['success'] = FALSE;
            $result['error'] = 'Invalid User Id';
            $result['data'] = NULL;
            $this->respond($result);
        }
        $update_data = array();
        $update_data = $data_set;
        $whereArray = array('user_id' => $data_set['user_id']);
        if (isset($update_data['last_seen']) && $update_data['last_seen'] != '') {

            unset($update_data['user_id'], $update_data['rand'], $update_data['type']);

            $updatedArray = $update_data;
            $updateLastSeen = $usersObj->updateLastSeen($whereArray, $updatedArray);
            if ($updateLastSeen == FALSE) {
                $result['success'] = FALSE;
                $result['error'] = 'Technical Error';
                $result['data'] = NULL;
            } else {
                $getLastSeen = $usersObj->getLastSeen($whereArray);
                $result['success'] = TRUE;
                $result['error'] = NULL;
                $result['data'] = $getLastSeen;
            }
        } else {

            $getLastSeen = $usersObj->getLastSeen($whereArray);

            if ($getLastSeen) {
                $result['success'] = TRUE;
                $result['error'] = NULL;
                $result['data'] = $getLastSeen;
            } else {

                $result['success'] = FALSE;
                $result['error'] = 'Technical Error';
                $result['data'] = NULL;
            }
        }
        $this->respond($result);
    }

    public function forgotPassword($request) {

        $usersObj = new Model\Users();

        $getDetails = $usersObj->getDetailsByMobile($data_set['mobile']);
        if ($getDetails) {
            $changePassword = $usersObj->changePassword($data_set);
            if ($changePassword == FALSE) {
                $result['success'] = FALSE;
                $result['error'] = 'Technical Error';
                $result['data'] = NULL;
            } else {
                $result['success'] = TRUE;
                $result['error'] = NULL;
                $result['data'] = NULL;
            }
        } else {
            $result['success'] = FALSE;
            $result['error'] = 'Incorrect mobile number';
            $result['data'] = NULL;
        }
        $this->respond($result);
    }

    public function sendContactsList($data_set) {

        $usersObj = new Model\Users();
        $checkforValidUser = $usersObj->getUserDetails($data_set['user_id']);
        if ($checkforValidUser == FALSE) {
            $result['success'] = FALSE;
            $result['error'] = 'Invalid User Id';
            $result['data'] = NULL;
            $this->respond($result);
        }

        $contacts = $request->input();

        $getContactsInSys = $usersObj->getSytemContacts();
    }

    public function saveUserProfileImage($data_set) {

        $usersObj = new Model\Users();
        $checkforValidUser = $usersObj->getUserDetails($data_set['user_id']);
        if ($checkforValidUser == FALSE) {
            $result['success'] = FALSE;
            $result['error'] = 'Invalid User Id';
            $result['data'] = NULL;
            $this->respond($result);
        }

        if (isset($data_set['profile_image']) && $data_set['profile_image'] != '') {
            $image = $data_set['profile_image'];
            $userId = $data_set['user_id'];
//            $pos  = strpos($image, ';');
//            $type_explode = explode(':', substr($image, 0, $pos))[1];
//            $type = explode('/',$type);			
//            $pathForS3 = '/userfiles/' . $userId . '/images';
//            $s3 = Storage::disk('s3');
//            $s3->makeDirectory($pathForS3);
//            $filePath = '/userfiles/' . $userId . '/images/original.'.$type;
//            $s3->put($filePath, file_get_contents($image), 'public');


            $path = public_path() . '/userfiles/' . $userId . '/images';
            $base64string = $data_set['profile_image'];

            // amazon keys for accessing s3
            $s3 = Storage::disk('s3');
            $bucket = config('filesystems')['disks']['s3']['bucket'];

            if (!file_exists($path)) {
                $mask = umask(0);
                if (!mkdir($path, 0755, true)) {
                    $result = Array('status' => '152',
                        'error' => 'Some error while file upload',
                        'userId' => '');
                    Log::warning($rnd . ': Unable to create directory @' . __CLASS__ . ':' . __METHOD__ . ':' . __LINE__);
                    $this->respond($result);
                }
                umask($mask);
            }

            $rawDataSplit = explode(",", $base64string);
            $rawData = (is_array($rawDataSplit) && sizeof($rawDataSplit) > 1) ? $rawDataSplit[1] : $base64string;
            $image = base64_decode($rawData);

            $f = finfo_open();
            $mime_type = finfo_buffer($f, $image, FILEINFO_MIME_TYPE);
            $type = explode("/", $mime_type)[1];

            //before saving, lets delete all the files inside images  
            $files = glob($path . '/*'); // get all file names
            foreach ($files as $file) { // iterate files
                if (is_file($file)) {
                    unlink($file); // delete file
                }
            }

            $ifp = fopen($path . '/original.' . $type, "a+");

            if (!fwrite($ifp, $image)) {
                $result['status'] = '153';      //Invalid user data
                $result['error'] = 'Error in uploading image';
                $result['path'] = '';
                Log::warning($rnd . ":  Error in uploading image @" . __CLASS__ . ":" . __METHOD__ . ":" . __LINE__);
                $this->respond($result);
            }

            // file path for original
            $s3 = Storage::disk('s3');
            $pathForS3 = '/userfiles/' . $userId . '/images';
            $s3->makeDirectory($pathForS3);
            $s3->put($pathForS3 . '/original.' . $type, file_get_contents($path . '/original.' . $type), 'public');


            // usage inside a laravel route

            $imgObj = Image::make($path . '/original.' . $type);
            $imgObj->resize(500, null, function ($constraint) {
                $constraint->aspectRatio();
                $constraint->upsize();
            })->save($path . '/small.' . $type);
            $s3->put($pathForS3 . '/small.' . $type, file_get_contents($path . '/small.' . $type), 'public');

            $originalUrl = "https://" . $bucket . ".s3.amazonaws.com" . $pathForS3 . '/original.' . $type;
            $smallUrl = "https://" . $bucket . ".s3.amazonaws.com" . $pathForS3 . '/small.' . $type;
            $insertArray = array('user_id' => $userId, 'original' => $originalUrl, 'thumbnail' => $smallUrl, 'created_by' => $userId);

            $saveImage = $usersObj->saveUserProfileImage($insertArray);

            if ($saveImage == FALSE) {
                $result['success'] = FALSE;
                $result['error'] = 'Error while saving image';
                $result['data'] = NULL;
                $this->respond($result);
            } else {
                $deleteLocalFolder = public_path() .'/userfiles/' . $userId;
                unlink($deleteLocalFolder);
                $result['success'] = TRUE;
                $result['error'] = 'Successfully saved Images';
                $result['data'] = $saveImage;
                $this->respond($result);
            }
        } else {
            $result['success'] = FALSE;
            $result['error'] = 'Image not found';
            $result['data'] = NULL;
            $this->respond($result);
        }
    }

    public function deleteUserProfileImages($data_set) {

        $usersObj = new Model\Users();
        $checkforValidUser = $usersObj->getUserDetails($data_set['user_id']);
        if ($checkforValidUser == FALSE) {
            $result['success'] = FALSE;
            $result['error'] = 'Invalid User Id';
            $result['data'] = NULL;
            $this->respond($result);
        }

        $userId = $data_set['user_id'];
        $pathForS3 = '/userfiles/' . $userId;

        $bucket = config('filesystems')['disks']['s3']['bucket'];


        $dbresponse = $usersObj->deleteUserProfileImages($data_set['user_id']);

        if ($dbresponse == TRUE) {
            if (count(Storage::disk('s3')->exists($pathForS3)) > 0) {
                $response = Storage::disk('s3')->deleteDirectory($pathForS3);
                Log::info($response . " @" . __CLASS__ . ":" . __METHOD__ . ":" . __LINE__);
            }
            $result['success'] = TRUE;
            $result['error'] = 'Successfully deleted Images';
            $result['data'] = NULL;
            $this->respond($result);
        } else {
            $result['success'] = FALSE;
            $result['error'] = 'Unable to delete user pic';
            $result['data'] = NULL;
            $this->respond($result);
        }
    }

    public function test($request) {

        $variable = str_random(60);
      //  echo $variable;die();
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
