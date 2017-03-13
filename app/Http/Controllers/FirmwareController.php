<?php namespace App\Http\Controllers;

use App\Http\Requests;
use App\Http\Controllers\Controller;
use App\Model;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Input;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\File;

use Illuminate\Http\Request;
use Aws\S3\S3Client;
use League\Flysystem\AwsS3v3\AwsS3Adapter;
use League\Flysystem\Filesystem;

class FirmwareController extends Controller {

	/**
	 * Display a listing of the resource.
	 *
	 * @return Response
	 */
	public function index()
	{
            $ip = '';
            if (!empty($_SERVER['HTTP_CLIENT_IP'])) {
                $ip = $_SERVER['HTTP_CLIENT_IP'];
            } elseif (!empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {
                $ip = $_SERVER['HTTP_X_FORWARDED_FOR'];
            } else {
                $ip = $_SERVER['REMOTE_ADDR'];
            }
           // if($ip != '183.82.104.162') {
          //      exit('Restricted access');
         //   }
            $rnd = rand(200000,1000000);
            Log::info($rnd.': request data: '.json_encode($_POST).' @'.__CLASS__ .':'.__METHOD__.':'.__LINE__);
            $data = Array();
            $data['maxVersion'] = '';
            //get the latest essential version from db
            $dbObj = new model\FirmwareFiles();
            $whereArray = Array('type' => 'es');            
            $result = $dbObj->getDataByWhereClause($whereArray);
            
            if($result) {
                $data['maxVersion'] = $result->version;
            }

            return view('checkFirmware', $data);
	}

	/**
	 * Show the form for creating a new resource.
	 *
	 * @return Response
	 */
	public function create()
	{
		//
	}

	/**
	 * Store a newly created resource in storage.
	 *
	 * @return Response
	 */
	public function store(Request $request)
	{
            $rnd = rand(200000,1000000);
            Log::info($rnd.': request data: '.json_encode($_POST).' @'.__CLASS__ .':'.__METHOD__.':'.__LINE__);
            Log::info($rnd.': request data: '.json_encode($_FILES).' @'.__CLASS__ .':'.__METHOD__.':'.__LINE__);
            $fileMS = isset($_FILES['ms']) ? $_FILES['ms'] : '';
            $fileQTR = isset($_FILES['qtr']) ? $_FILES['qtr'] : '';
            $fileLog = isset($_FILES['log']) ? $_FILES['log'] : '';
            
            $s3 = Storage::disk('s3');
            $bucket = config('filesystems')['disks']['s3']['bucket'];
          
            $inputJSON = file_get_contents('php://input');
            
            $jsonPost = Array();
            if($inputJSON) {
                Log::info($rnd.': Json data in body : '.$inputJSON.' @'.__CLASS__ .':'.__METHOD__.':'.__LINE__);
                $jsonPost = json_decode($inputJSON, TRUE);
                if (json_last_error() != JSON_ERROR_NONE) {
                    $result['status'] = '151';      //151 is when user enters invalid data
                    $result['error'] = 'invalid Json Object to parse: '. $inputJSON;
                    $result['userId'] = '';
                    Log::warning($rnd. ":invalid Json Object to parse: ". $inputJSON ." @".__CLASS__ .":".__METHOD__.":".__LINE__);
                    //$this->respond($result);
                } else {
                    //merge to $_POST array or assing if it is empty
                    $_POST = $jsonPost;
                }
            }

            switch ($_POST['type']) {
                case '101':
                   
                    //Check if newVersion is a valid double value and check if a folder exists already with that version
                    //if everything is fine then create a folder with that version and save the ms, qtr files there
                    if(isset($_POST['newVersion']) && !$fileMS['error'] && !$fileQTR['error']) {
                        
                        $path = public_path().'/aarpfiles/firmware/'.$_POST['newVersion'];
                       
                        $pathForS3 = '/aarpfiles/firmware/'.$_POST['newVersion'];
                        
                        if (!file_exists($path)) {
                            $mask=umask(0);
                            if(!mkdir($path, 0755, true)) {
                                Log::warning($rnd.': Unable to create directory : '. $path .' @'.__CLASS__ .':'.__METHOD__.':'.__LINE__);
                                exit("Unable to create directory : " . $path);
                                umask($mask);
                            }
                        }                        
                        // new lets move the uploded files to new folder
                        if(!move_uploaded_file($fileMS['tmp_name'], $path.'/ms.bin') || !move_uploaded_file($fileQTR['tmp_name'], $path.'/qtr.bin')) {
                            echo "Unable to move file to path " . $path;
                        } else {                        
                                  
                            //upload same files to s3
                            $s3->makeDirectory($pathForS3);
                            $s3->put($pathForS3.'/ms.bin', file_get_contents($path.'/ms.bin'), 'public');
                            $s3->put($pathForS3.'/qtr.bin', file_get_contents($path.'/qtr.bin'), 'public');

                            $msUrl = "https://". $bucket . ".s3-us-west-1.amazonaws.com". $pathForS3 . "/ms.bin";
                            $qtrUrl = "https://". $bucket . ".s3-us-west-1.amazonaws.com". $pathForS3 . "/qtr.bin";
                            
                            $insertArray = Array('ms' => $msUrl,
                                                'qtr' => $qtrUrl,
                                                'version' => $_POST['newVersion'],
                                                'type'  => 'es',
                                                'created_at' => date("Y-m-d H:i:s"),
                                                'updated_at' => date("Y-m-d H:i:s")
                                                );
                            //If log file is also uploaded then move that file as well
                            if($fileLog['size'] > 0) {
                                move_uploaded_file($fileLog['tmp_name'], $path.'/changeLog.txt');
                                $s3->put($pathForS3.'/changeLog.txt', file_get_contents($path.'/changeLog.txt'), 'public');
                                $insertArray['changeLog'] =  "https://". $bucket . ".s3.amazonaws.com". $pathForS3 . "/changeLog.txt";
                            }

                            
                           $dbObj = new model\FirmwareFiles();
                            if(!$dbObj->insertData($insertArray)) {
                                echo "Unable to save S3 file paths in DB";
                            }
 /*
                            //notify firmware update to users
                            $this->notifyFirmwareUpdate($rnd);*/
                            exit("succesfully moved file to : " . $path);
                        }
                    } else {
                        Log::warning($rnd.': Unexpected version input format :  @'.__CLASS__ .':'.__METHOD__.':'.__LINE__);
                        exit(" Unexpected version input format ");
                        umask($mask);
                    }
                    break;
               case '102':
                   // $validatorobj = new Validator();
                    $post = $_POST;
                   /* if(!$validatorobj->convert_original_id($post['userId'])) {
                        $result['status'] = '151';      //151 is when user enters invalid data
                        $result['error'] = 'invalid user id : '. $post['userId'];
                        $result['result'] = '';
                        Log::warning($rnd. ": invalid user id : ".  $post['userId']." @".__CLASS__ .":".__METHOD__.":".__LINE__);
                        $this->respond($result);
                    }*/
                    //get the latest essential version from db
                    $dbObj = new model\FirmwareFiles();
                    $whereArray = Array('type' => 'es');            
                    $response = $dbObj->getDataByWhereClause($whereArray);
                    if(!$response) {
                        $result = Array('status' => '152',
                                    'error' =>  'No firmware files available',
                                    'result' => '');
                        Log::info($rnd. ":  response is : ". json_encode($result) ." @".__CLASS__ .":".__METHOD__.":".__LINE__);
                        $this->respond($result);
                    } 

                    $data['version'] = $response->version;
                    $data['ms'] = $response->ms;
                    $data['qtr'] = $response->qtr;
                    $data['changeLog'] = $response->changeLog;

                    $result = Array('status' => '150',
                                    'error' =>  '',
                                    'result' => $data);
                    Log::info($rnd. ":  response is : ". json_encode($result) ." @".__CLASS__ .":".__METHOD__.":".__LINE__);
                    $this->respond($result);

                    break;
            }
	}

	/**
	 * Display the specified resource.
	 *
	 * @param  int  $id
	 * @return Response
	 */
	public function show($id)
	{
		//
	}

	/**
	 * Show the form for editing the specified resource.
	 *
	 * @param  int  $id
	 * @return Response
	 */
	public function edit($id)
	{
		//
	}

	/**
	 * Update the specified resource in storage.
	 *
	 * @param  int  $id
	 * @return Response
	 */
	public function update($id)
	{
		//
	}

	/**
	 * Remove the specified resource from storage.
	 *
	 * @param  int  $id
	 * @return Response
	 */
	public function destroy($id)
	{
		//
	}
        
        public function notifyFirmwareUpdate($rnd) {
            //now let's make a notification to all users about the new firmware version
            //get all user's, loop them and send notifications
            $pushObj = new model\PushData();
            $allPushData = $pushObj->getAllPushData();
            
            if(!$allPushData) {
                return;
            }
            
            $msg = Array();
            $msg['type'] = ExternalCommunication::$notifyFirmwareUpdate;
            
            foreach ($allPushData as $eachRecord) {
                $msg['content'] = Array('message' => 'A new firmware update is available.');
                $externalApi = new ExternalCommunication();
                $res = $externalApi->sendPushNotification(Array($eachRecord->pushId), json_encode($msg), $eachRecord->mobile);
                if(!$res) {
                    Log::warning($rnd.': push notification failed while sedning firmware update notification @'.__CLASS__ .':'.__METHOD__.':'.__LINE__);
                }
            }
            return;
        }
                /**
         * 
         * @param type $postData 
         * retur userId with zero errors OR empty userId with error details 
         */
        public function respond($response, $exit = true)
        {
            echo json_encode($response, JSON_UNESCAPED_SLASHES);
            exit();
        }

}
