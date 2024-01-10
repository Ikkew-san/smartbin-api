<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use App\Http\Controllers\ImageController;

use Laravel\Lumen\Routing\Controller as BaseController;

class AuthController extends BaseController
{
    private $image_path = 'user/';
    private $response = array('status' => 1, 'message' => 'success');

    # ตรวจสอบสิทธิ์ผู้ใช้แอพพลิเคชั่น
    public function auth(Request $request)
    {
        $result = User::where([
            ['user_username', $request->username],
            ['user_password', $request->password],
        ])->get(['user_id', 'user_username', 'user_password', 'user_status']);
        
        if ($result != '[]') {
            if ($result[0]["user_status"] == 0) {
                return response()->json(0);
            } else {
                return response()->json($result[0]);
            }
        } else {
            return response()->json($result);
        }
    }
    
    # สมัครสมาชิกเข้าใช้แอพพลิเคชั่น
    public function register(Request $request)
    {
        $user = User::where('user_username', $request->username)->get();
        if ($user == '[]') {   
            $user = User::where('user_email', $request->email)->get();
            if ($user == '[]') {   
                $result = new User;
                $result->user_firstname = $request->firstname;
                $result->user_lastname = $request->lastname;
                $result->user_username = $request->username;
                $result->user_password = $request->password;
                $result->user_email = $request->email;
                $result->user_position = 2;
                $result->save();
                return response()->json($this->response);
            } else {
                return response()->json('email');
            }
        } else {
            return response()->json('username');
        }
    }
    
    # ตรวจสอบสิทธิ์ผู้ใช้เว็บ
    public function authAdmin(Request $request)
    {
        $image = new ImageController();
        $result = User::where([
            ['user_username', $request->username],
            ['user_password', $request->password],
            ['user_position', 1],
            ])->get(['user_id', 'user_username', 'user_password', 'user_firstname', 'user_lastname', 'user_status', 'user_img']);   
        $result["0"]["image_url"] = $image->getImageUrl($result["0"]["user_img"], $this->image_path);
        return response()->json($result["0"]);
    }    


    public function user(Request $request)
    {
        $result = User::where('user_position', $request->position)->get();
        return response()->json($result);
    }

    public function find($id)
    {
        $image = new ImageController();
        $result = User::find($id);
        $result["image_url"] = $image->getImageUrl($result["user_img"], $this->image_path);
        return response()->json($result);
    }

    public function create(Request $request)
    {
        $user = User::where('user_username', $request->username)->get();
        if ($user == '[]') {   
            $user = User::where('user_email', $request->email)->get();
            if ($user == '[]') {   
                $result = new User;
                $result->user_firstname = $request->firstname;
                $result->user_lastname = $request->lastname;
                $result->user_birthday = $request->birthday;
                $result->user_gender = $request->gender;
                $result->user_email = $request->email;
                $result->user_telephone = $request->telephone;
                $result->user_username = $request->username;
                $result->user_password = $request->password;
                $result->user_position = $request->position;
                if ($request->hasFile('image')) {
                    $destinationPath = "images/user";
                    $file = $request->file('image');
                    $fileName   = $file->getClientOriginalName();
                    $file->move($destinationPath,  $fileName);
                    $result->user_img = $fileName;
                }
                $result->save();
                return response()->json($this->response);
            } else {
                return response()->json('email');
            }
        } else {
            return response()->json('username');
        }
    }

    public function edit(Request $request)
    {
        $result = User::find($request->id);
        $result->user_firstname = $request->firstname;
        $result->user_lastname = $request->lastname;
        $result->user_gender = $request->gender;
        $result->user_birthday = $request->birthday;
        $result->user_telephone = $request->telephone;
        $result->user_email = $request->email;
        $result->user_password = $request->password;
        if ($request->hasFile('image')) {
            $destinationPath = "images/user";
            $file = $request->file('image');
            $fileName   = $file->getClientOriginalName();
            $file->move($destinationPath,  $fileName);
            $result->user_img = $fileName;
        }
        $result->save();
        return response()->json($this->response);
}

    public function userStatus($id, Request $request)
    {
        $result = User::find($id);
        $result->user_status = $request->status;
        $result->save();
        return response()->json($this->response);
    }

    public function delete($id)
    {
        $result = User::find($id);
        if ($result != '') {
            $user = User::where([
                ['user.user_id', $result['user_id']],
                ['user.user_points', '!=' , 0]
                ])
                ->orWhere('buy.user_id', $result['user_id'])
                ->orWhere('cumulative.user_id', $result['user_id'])
                ->orWhere('exchange.user_id', $result['user_id'])
                ->orWhere('pay.user_id', $result['user_id'])
                ->orWhere('sell.user_id', $result['user_id'])                
                ->leftJoin('buy', 'user.user_id', 'buy.user_id')
                ->leftJoin('cumulative', 'user.user_id', 'cumulative.user_id')
                ->leftJoin('exchange', 'user.user_id', 'exchange.user_id')
                ->leftJoin('pay', 'user.user_id', 'pay.user_id')
                ->leftJoin('sell', 'user.user_id', 'sell.user_id')
                ->distinct()
                ->get(['user.*']);
            if ($user == '[]') {
                $result->delete();
                return response()->json($this->response);
            } else {
                return response()->json(0);
            }
        } else {
            return false;
        }
    }

    public function editImageProfile($id, Request $request)
    {
        $result = User::find($id);
        $fileName = $result['user_username'].'-'.$result['user_id'].'.jpg';
        if ($request->hasFile('image')) {
            $destinationPath = "images/user";
            $file = $request->file('image');
            $file->move($destinationPath,  $fileName);
            $result->user_img = $fileName;
        }
        $result->save();
        return response()->json($result);
    }

    public function editProfile($id, Request $request)
    {
        $result = User::find($id);
        if ($request->action == "name") {
            # แก้ไข ชื่อ-นามสกุล
            $result->user_firstname = $request->firstname;
            $result->user_lastname = $request->lastname;
            $result->save();
        } elseif ($request->action == "birthday") {
            # แก้ไข วันเกิด
            $result->user_birthday = $request->birthday;
            $result->save();
        } elseif ($request->action == "gender") {
            # แก้ไข เพศ
            $result->user_gender = $request->gender;
            $result->save();
        } elseif ($request->action == "email") {
            # แก้ไข อีเมล
            $result->user_email = $request->email;
            $result->save();
        } elseif ($request->action == "tel") {
            # แก้ไข หมายเลขโทรศัพท์
            $result->user_telephone = $request->telephone;
            $result->save();
        } elseif ($request->action == "pwd") {
            # แก้ไข รหัสผ่าน
            $result->user_password = $request->password;
            $result->save();
        } else {
           return false;
        }
        return response()->json($this->response);
    }
}
