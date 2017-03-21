<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use App\Model;

class TestsController extends Controller
{
    //
    
    public function store(Request $request)
    {
        $rand = rand(99, 99999);

        $request->request->add(['rand' => $rand]);

        app('App\Http\Controllers\CommonController')->logData($request, json_encode($request->input()), 'info');

        switch ($request->input('type')) {
            case 100:
              // register  
                $this->getTestVideos($request);
                break;
            default:
                break;
        }
        //
    }
    
    public function getTestVideos($request) {
        
        $testsObj = new Model\Tests();
        
        $getTestVideo = $testsObj->getTestVideo($request);
        
        if($getTestVideo != FALSE){            
            $result['success'] = TRUE;
            $result['error'] = '';
            $result['data'] = $getTestVideo;
        }else{
            $result['success'] = FALSE;
            $result['error'] = 'Technical Error';
            $result['data'] = '';
        }       
            
        $this->respond($result);
    }
    
    public function respond($response, $exit = true) {

        echo json_encode($response, JSON_UNESCAPED_SLASHES);
        exit();
    }
}
