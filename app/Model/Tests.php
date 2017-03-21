<?php

namespace App\Model;

use Illuminate\Database\Eloquent\Model;


class Tests extends Model
{
    
    protected $table = 'test_videos';
    //
    
    public function getTestVideo($request) {
        
        try{
            
        \DB::setFetchMode(\PDO::FETCH_ASSOC);
        $getVideos = \DB::table($this->table)->where('test_id', $request->input('test_id'))->get();
        return $getVideos;
        } catch (Exception $ex) {
            return FALSE;
        }
        
         
    }
}
