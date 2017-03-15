<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class UserController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        //
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        $rand = rand(99, 99999);
       
	$request->request->add(['rand' => $rand ]);
        
        app('App\Http\Controllers\CommonController')->logData($request, json_encode($request->input()), 'info');
		
        app('App\Http\Controllers\GenericController')->validateData($request);
        
        switch ($request->input('type')) {
            case 100:
              // register  
                $this->registerUser($request);
                break;
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
    public function show($id)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function edit($id)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, $id)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy($id)
    {
        //
    }
    
    /*
        for registering the new user either care giver or care recipient 
          */
    
         public function registerUser($request) {
             
         }
         
         public function test($request){
             
             $test = array();
             $test['name'] = 'Vinay';
             $test['mobile'] = '1234567890';
             $test['email'] = 'vinay@mail.com';
             
             $this->respond($test);
             
         }
         
         public function respond($response, $exit = true)
        {
            echo json_encode($response, JSON_UNESCAPED_SLASHES);
            exit();
        }
}
