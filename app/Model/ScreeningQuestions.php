<?php

namespace App\Model;

use Illuminate\Database\Eloquent\Model;

class ScreeningQuestions extends Model
{
    protected $table = 'screening_questions';
    
    public function getScreeningQuestions($data) {
         
        $result = \DB::table($this->table)->where('level',$data['level'])->first();
     
        return $result->json_questions;
    }
}
