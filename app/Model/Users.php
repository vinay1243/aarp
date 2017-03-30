<?php

namespace App\Model;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Crypt;
use Hashids\Hashids;
use App\Helpers\Common;

class Users extends Model {

    //

    protected $table = 'Users';
    protected $table_user_details = 'user_details';
    protected $table_user_images = 'user_images';

    public function saveUser($data_set) {

        //print_r($request->input);die();
        $random_variable = str_random(60);
        $userArray = array('first_name' => $data_set['first_name'],
            'last_name' => $data_set['last_name'],
            'email' => isset($data_set['email']) ? $data_set['email'] : NULL,
            'mobile' => $data_set['mobile'],
            'password' => isset($data_set['password']) ? password_hash($data_set['password'], PASSWORD_DEFAULT) : '',
            'dob' => isset($data_set['dob']) ? $data_set['dob'] : NULL,
            'gender' => isset($data_set['gender']) ? $data_set['gender'] : NULL,
            'status' => '1',
            'user_type' => $data_set['user_type'],
            'address' => isset($data['address']) ? $data['address'] : NULL,
            'height' => isset($data['height']) ? $data['height'] : NULL,
            'weight' => isset($data['weight']) ? $data['weight'] : NULL,
            'api_token' => isset($random_variable) ? $random_variable : NULL,
            'created_by' => 0,
            'created_dttm' => date("Y-m-d H:i:s", time()),
            'updated_by' => 0
        );


        \DB::beginTransaction();

        try {
            $result = \DB::table($this->table)->insertGetId($userArray);
            /*  $detailsArray = array('user_id'=>$result,
              'address'=>isset($data['address']) ? $data['address'] : NULL,
              'height'=>isset($data['height']) ? $data['height'] : NULL,
              'weight'=>isset($data['weight']) ? $data['weight'] : NULL,
              'created_dttm'=>date("Y-m-d H:i:s", time()));
              $insertDetails = \DB::table($this->table_user_details)->insert($detailsArray); */
            $commonObj = new Common();
            $encrypted_id = $commonObj->encryptId($result);

            $user_id_encrypt = $this->idEncrypt($encrypted_id, $result);

            if ($user_id_encrypt == TRUE) {
                \DB::commit();
                return $user_id_encrypt;
            }
        } catch (Exception $ex) {
            DB::rollback();
            return FALSE;
        }
        //$result = \DB::table($this->table)->insertGetId($detailsArray);
        //  if(!empty($result)){
        //      return $result;
        //   }
    }

    public function checkLogin($data_set) {
        $checkrecord = \DB::table($this->table)->where('mobile', $data_set['mobile'])->first();

        if (!$checkrecord) {
            return FALSE;
        } else {
            if (!password_verify($data_set['password'], $checkrecord->password)) {
                return FALSE;
            }
        }

        return $checkrecord;
    }

    public function getUserDetails($id) {
        $userDetails = \DB::table($this->table)->where('user_id', $id)->first();
        if (!$userDetails) {
            return FALSE;
        } else {
            return $userDetails;
        }
    }

    public function updateProfile($whereArray, $updateArray) {

        $updateUserProfile = \DB::table($this->table)->where($whereArray)->update($updateArray);

        return (boolean) $updateUserProfile;
    }

    public function updateLastSeen($whereArray, $updateArray) {

        $updateUserLastSeen = \DB::table($this->table)->where($whereArray)->update($updateArray);

        return (boolean) $updateUserLastSeen;
    }

    public function getLastSeen($whereArray) {
        $userLastSeen = \DB::table($this->table)->where($whereArray)->pluck('last_seen');
        if (!$userLastSeen) {
            return FALSE;
        } else {
            return $userLastSeen;
        }
    }

    public function getDetailsByMobile($mobile) {

        $userDetails = \DB::table($this->table)->where('mobile', $mobile)->first();

        if (!$userDetails) {
            return FALSE;
        } else {
            return $userDetails;
        }
    }

    public function checkRecordWithMobile($mobile) {

        $userDetails = \DB::table($this->table)->where('mobile', '<>', $mobile)->first();

        if ($userDetails) {
            return FALSE;
        } else {
            return TRUE;
        }
    }

    public function changePassword($request) {

        $passwordHash = password_hash($data_set['password'], PASSWORD_DEFAULT);
        $whereArray = array('mobile' => $data_set['mobile']);
        $updateArray = array('password' => $passwordHash);
        $changePassword = \DB::table($this->table)->where($whereArray)->update($updateArray);
        return (boolean) $changePassword;
    }

    public function idEncrypt($encrypted_id, $user_id) {

        $result = \DB::table($this->table)->where('user_id', $user_id)->update(array('user_id_encrypt' => $encrypted_id));
        return (boolean) $result;
    }

    public function saveUserProfileImage($data) {

        $getImage_urls = $this->getProfileImage($data['user_id']);

        if ($getImage_urls) {
            $result = \DB::table($this->table_user_images)->where('user_id', $data['user_id'])->update($data);
        } else {
            $result = \DB::table($this->table_user_images)->insert($data);
        }
        if (!(boolean) $result) {
            echo $result;
            die();
            return FALSE;
        } else {
            echo 'die';
            die();
            $getImage_urls = $this->getProfileImage($data['user_id']);
            return $getImage_urls;
        }
    }

    public function getProfileImage($id) {

        $getProfileImages = \DB::table($this->table_user_images)->where('user_id', $id)->first();
        return $getProfileImages;
    }

    public function deleteUserProfileImages($id) {
        $deleteImages = \DB::table($this->table_user_images)->where('user_id', $id)->delete();
        if (!(boolean) $deleteImages) {
            return FALSE;
        } else {
            return TRUE;
        }
    }

}
