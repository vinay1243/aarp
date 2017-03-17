<?php

namespace App\Model;

use Illuminate\Database\Eloquent\Model;

class ScreeningQuestions extends Model
{
    protected $table = 'screening_questions';
    
    public function getScreeningQuestions($request) {
         
        $result = \DB::table($this->table)->where('type','1')->first();
     
        return $result->json_questions;
    }
}
