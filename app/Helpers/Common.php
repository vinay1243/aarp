<?php

namespace App\Helpers;

use Illuminate\Support\Facades\Log;
use App\Model;
use Hashids\Hashids;

class Common {
    /*
     * To change this license header, choose License Headers in Project Properties.
     * To change this template file, choose Tools | Templates
     * and open the template in the editor.
     */

    public function validateRegister($request) {

        $data = Array('isValid' => true, 'invalidData' => array());
        $fields = $request->input();
        $data = Array('isValid' => true, 'invalidData' => array());
        if ((!isset($fields['first_name']) || empty($fields['first_name'])) && (!isset($fields['last_name']) || empty($fields['last_name']))) {
            $data['isValid'] = false;
            $data['invalidData'][] = 'Name';
            return $data;
        }
        foreach ($fields as $key => $value) {

            switch ($key) {
                case 'email':
                    if (!$this->validateInput($value, 'email')) {
                        $data['isValid'] = false;
                        $data['invalidData'][] = $key;
                    }
                    break;
                case 'first_name':

                    if (!$this->validateInput($value, 'nonvulnerable')) {
                        $data['isValid'] = false;
                        $data['invalidData'][] = 'first_name';
                    }
                    break;
                case 'last_name':
                    if (!$this->validateInput($value, 'nonMandatory')) {
                        $data['isValid'] = false;
                        $data['invalidData'][] = $key;
                    }
                    break;
                case 'gender':
                    if (!$this->validateInput($value, 'nonMandatory')) {
                        $data['isValid'] = false;
                        $data['invalidData'][] = $key;
                    }
                    break;
                case 'dob':
                    if (!$this->validateInput($value, 'nonMandatoryDOB')) {
                        $data['isValid'] = false;
                        $data['invalidData'][] = $key;
                    }
                    break;
                case 'mobile':
                case 'country':
                    if (!$this->validateInput($value, 'nonMandatory')) {
                        $data['isValid'] = false;
                        $data['invalidData'][] = $key;
                    }
                    break;
            }
        }

        return $data;
    }

    function validateInput($value, $type) {
        switch ($type) {
            case 'email':
                if (empty($value) || filter_var($value, FILTER_VALIDATE_EMAIL) === false) {
                    return false;
                }
                break;
            case 'password':
                break;
            case 'withoutSplChar':
                if (empty($value) || !preg_match("/^[a-zA-Z0-9 ]*$/", $value)) {
                    return false;
                }
                break;
            case 'number':
                if (!is_numeric($value)) {
                    return false;
                }
                break;
            case 'notEmpty':
                if (empty($value)) {
                    return false;
                }
                break;
            case 'gender':
                if (empty($value) || !in_array(strtolower($value), array('m', 'f'))) {
                    return false;
                }
                break;
            case 'dob':
                if (!empty($value)) {
                    $dateArray = explode("-", $value);
                    if (count($dateArray) != 3 || !checkdate($dateArray[0], $dateArray[1], $dateArray[2]) || !in_array($dateArray[2], range(1920, date("Y")))) {
                        return false;
                    }
                } else {
                    return false;
                }
                break;
            case 'nonMandatory':
                if (!preg_match("/^[a-zA-Z0-9 ]*$/", $value)) {
                    return false;
                }
                break;
            case 'nonMandatoryDOB':
                if (!empty($value)) {
                    $dateArray = explode("-", $value);
                    if (count($dateArray) != 3 || !checkdate($dateArray[0], $dateArray[1], $dateArray[2]) || !in_array($dateArray[2], range(1920, date("Y")))) {
                        return false;
                    }
                }
                break;
            case 'nonvulnerable':
                if (empty($value) || preg_match("/<|>|%|&/", $value)) {
                    return false;
                }
                break;
            case 'nonVulnerableNonMandatory':
                if (preg_match("/<|>|%|&/", $value)) {
                    return false;
                }
                break;
        }
        return true;
    }

    public function encryptId($value) {
        $key = getenv('APP_HASH_KEY');
        $hashids = new Hashids($key, 10);
        $encrypted_id = $hashids->encode($value);

        return $encrypted_id;
    }

    public function decryptId($value) {
        $key = getenv('APP_HASH_KEY');
        $hashids = new Hashids($key, 10);
        $decrypted_id = $hashids->decode($value);

        return $decrypted_id[0];
    }

}
