<?php

namespace App\Model;

use Illuminate\Database\Eloquent\Model;

class Users extends Model {

    //

    protected $table = 'Users';
    protected $encryption_key = 'salt';

    public function saveUser($request) {

        //print_r($request->input);die();
        $data = $request->input();
        $detailsArray = array('first_name' => $data['first_name'],
            'last_name' => $data['last_name'],
            'email' => isset($data['email']) ? $data['email'] : NULL,
            'mobile' => $data['mobile'],
            'password' => isset($data['password']) ? password_hash($data['password'], PASSWORD_DEFAULT) : '',
            'dob' => isset($data['dob']) ? $data['dob'] : NULL,
            'gender' => isset($data['gender']) ? $data['gender'] : NULL,
            'status' => '1',
            'user_type' => $data['user_type'],
            'created_by' => 0,
            'created_dttm' => date("Y-m-d H:i:s", time()),
            'updated_by' => 0
        );
        \DB::beginTransaction();

        try {
            $result = \DB::table($this->table)->insertGetId($detailsArray);
            //  $encrypted_id = $this->encrypt($result, $this->encryption_key);
            //   $user_id_encrypt = $this->idEncrypt($encrypted_id,$result);
            if ($result) {
                \DB::commit();
                return $result;
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

    public function checkLogin($request) {
        $checkrecord = \DB::table($this->table)->where('mobile', $request->input('mobile'))->first();

        if (!$checkrecord) {
            return FALSE;
        } else {
            if (!password_verify($request->input('password'), $checkrecord->password)) {
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
    
    public function updateProfile($whereArray,$updateArray) {
        
        $updateUserProfile = \DB::table($this->table)->where($whereArray)->update($updateArray);
        
        return (boolean)$updateUserProfile;
    }
    
    public function updateLastSeen($whereArray,$updateArray) {
        
        $updateUserLastSeen = \DB::table($this->table)->where($whereArray)->update($updateArray);
        
        return (boolean)$updateUserLastSeen;
    }
    
     public function getLastSeen($whereArray) {
        $userLastSeen = \DB::table($this->table)->where($whereArray)->pluck('last_seen');
        if (!$userLastSeen) {
            return FALSE;
        } else {
            return $userLastSeen;
        }
    }
    
    public function getDetailsByMobile($request){
        
         $userDetails = \DB::table($this->table)->where('mobile', $request->input('mobile'))->first();
         
         if (!$userDetails) {
            return FALSE;
        } else {
            return $userDetails;
        }
    }
    
    public function changePassword($request) {
        
        $passwordHash = password_hash($request->input('password'), PASSWORD_DEFAULT);
        $whereArray = array('mobile'=>$request->input('mobile'));
        $updateArray = array('password'=>$passwordHash);
        $changePassword = \DB::table($this->table)->where($whereArray)->update($updateArray);        
        return (boolean)$changePassword;
    }

    public function idEncrypt($encrypted_id, $user_id) {

        $result = \DB::table($this->table)->where('user_id', $user_id)->update(array('user_id_encrypt' => $encrypted_id));
        return (boolean) $result;
    }

    function encrypt($pure_string, $encryption_key) {
        $iv_size = mcrypt_get_iv_size(MCRYPT_BLOWFISH, MCRYPT_MODE_ECB);
        $iv = mcrypt_create_iv($iv_size, MCRYPT_RAND);
        $encrypted_string = mcrypt_encrypt(MCRYPT_BLOWFISH, $encryption_key, utf8_encode($pure_string), MCRYPT_MODE_ECB, $iv);
        return $encrypted_string;
    }

    /**
     * Returns decrypted original string
     */
    function decrypt($encrypted_string, $encryption_key) {
        $iv_size = mcrypt_get_iv_size(MCRYPT_BLOWFISH, MCRYPT_MODE_ECB);
        $iv = mcrypt_create_iv($iv_size, MCRYPT_RAND);
        $decrypted_string = mcrypt_decrypt(MCRYPT_BLOWFISH, $encryption_key, $encrypted_string, MCRYPT_MODE_ECB, $iv);
        return $decrypted_string;
    }

}
