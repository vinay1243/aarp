<?php namespace App\Model;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;
use App\Helpers\validator;

class FirmwareFiles extends Model {
    
    protected $table = 'firmware_files'; //table name
     
    public function insertData($data) {
        try {
            $id = \DB::table($this->table)->insertGetId($data);
            return (int)filter_var($id);
        }
        catch (\Illuminate\Database\QueryException $e) {
            Log::warning($e.": Unable to create fitness_feed record @".__CLASS__ .":".__METHOD__.":".__LINE__);
            return false;
        }   
    }
    
    public function getDataByWhereClause($whereArray) {
        try {
            $result = \DB::table($this->table)->where($whereArray)
                                              ->orderBy('id', 'desc')
                                              ->get();      
            if($result) {
                return $result[0];
            } else {
                return false;
            }
        } 
        catch (\Illuminate\Database\QueryException $e) {
            return false;
        }
    }
    
}