<?php

namespace App\Http\Controllers;

use App\Models\Alert;
use App\Models\Cumulative;
use App\Models\User;
use App\Models\Smartbin;
use App\Models\System;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use App\Http\Controllers\ImageController;
use Laravel\Lumen\Routing\Controller as BaseController;

class SmartbinController extends BaseController
{
    private $image_path = 'user/';
    private $response = array('status' => 1, 'message' => 'success');

    public function getSmartbin()
    {  
        $results = Smartbin::leftJoin('user', 'smartbin.user_id', 'user.user_id')
            ->get(['smartbin.*', 'user.user_username']);
        return response()->json($results);
    }

    public function findSmartbin($id)
    {
        $result = Smartbin::find($id);
        return response()->json($result);
    }

    public function setSmartbin(Request $request)
    {
        $result = new Smartbin;
        $result->smartbin_hostname = $request->hostname;
        $result->smartbin_ipaddress = $request->ipaddress;
        $result->smartbin_address = $request->address;
        $result->save();
        return response()->json($this->response);
    }

    public function putSmartbinStatus(Request $request)
    {
        $result = Smartbin::find($request->id);
        $result->smartbin_status = $request->status;
        $result->save();
        return response()->json($result['smartbin_status']);
    }

    public function putSmartbin(Request $request)
    {
        $result = Smartbin::find($request->id);
        $result->smartbin_hostname = $request->hostname;
        $result->smartbin_ipaddress = $request->ipaddress;
        $result->smartbin_address = $request->address;
        $result->save();
        return response()->json($this->response);
    }


    public function deleteSmartbin($id)
    {
        $result = Smartbin::find($id);
        if ($result != '') {
            $smartbin = Smartbin::where([['smartbin.smartbin_id', $id],['smartbin.user_id', '!=', null]])
            ->orWhere('cumulative.smartbin_id', $result['smartbin_id'])
            ->leftJoin('cumulative', 'smartbin.smartbin_id', 'cumulative.smartbin_id')
            ->distinct()
            ->get(['smartbin.*']);
            if ($smartbin == '[]') {
                $result->delete();
                return response()->json($this->response);
            } else {
                return response()->json(1);
            }
        } else {
            return false;
        }
    }

    public function qrLogged($hostname, Request $request)
    {
        $smartbin = Smartbin::where('smartbin_hostname', $hostname)->get(['smartbin_id']);
        $smartbin = Smartbin::find($smartbin[0]['smartbin_id']);
        $user = User::find($request->user_id);

        if ($user != '')
        {
            $checkLogged = Smartbin::where('user_id', $user['user_id'])->get();

            if ($checkLogged == '[]') {   
                if ($smartbin != '' && $smartbin['user_id'] == null) {
                    $smartbin->user_id = $user['user_id'];
                    $smartbin->save();
                    return response()->json($this->response);
                } else if ($smartbin != '[]' && $smartbin['user_id'] != null) {
                    # มีผู้เข้าใช้งาน
                    return response()->json(array('status' => 3));
                } else {
                    # ไม่มีในฐานข้อมูล
                    return response()->json(array('status' => 4));
                }
            } else {
                # ผู้ใช้งานได้เข้าใช้งานระบบถังอยู่
                return response()->json(array('status' => 2));
            }
        } else {
            return false;
        }
    }

    public function onPoints($id) 
    {
        $image = new ImageController();
        $system = System::find(1)->get(["system_points"]);
        $results = User::where("user.user_id", $id)
            ->leftJoin('smartbin', 'user.user_id', 'smartbin.user_id')
            ->get(["user.user_id", "user.user_firstname", "user.user_lastname", "user.user_points", "user.user_img","smartbin_id"]);
        $results[0]["image_url"] = $image->getImageUrl($results[0]["user_img"], $this->image_path);
        $results[0]["system_points"] = $system[0]["system_points"];
        return response()->json($results);
    }

    public function getAmountBottle($id) 
    {
        $result = Smartbin::find($id);
        return response()->json($result);
    }

    public function pointsHistory($id)
    {
        $results = Cumulative::where("cumulative.user_id", $id)
            ->leftJoin('smartbin', 'cumulative.smartbin_id', 'smartbin.smartbin_id')
            ->orderBy('cumulative_datetime', 'desc')
            ->get(['cumulative.cumulative_datetime', 
                    'smartbin.smartbin_hostname', 
                    'cumulative.cumulative_amount', 
                    'cumulative.cumulative_points'
                ]);
        return response()->json($results);
    }
    
    public function checkSmartbin(Request $request)
    {
        $smartbin = Smartbin::where([
            ['smartbin_hostname', $request->hostname],
            ['smartbin_ipaddress', $request->ipaddress]
            ])->get(['smartbin_id']);
        $result = Smartbin::find($smartbin[0]['smartbin_id']);
        if ($result['smartbin_status'] == 0) {
            $result->smartbin_status = 1;
            $result->save();
        }
        // if ($result != '[]') {
        //     $result = Smartbin::where('smartbin_ipaddress', $request->ipaddr)->get();
        //     if ($result != '[]') {
        //         $smartbin = new Smartbin;
        //         $smartbin->smartbin_hostname = $request->hostname;
        //         $smartbin->smartbin_ipaddress = $request->ipaddr;
        //         $smartbin->save();
        //     }
        // }
        // else {
        //     $smartbin = new Smartbin;
        //     $smartbin->smartbin_hostname = $request->hostname;
        //     $smartbin->smartbin_ipaddress = $request->ipaddr;
        //     $smartbin->save();
        // }
        return response()->json($result);
    }

    public function checkStatus($id)
    {
        $result = Smartbin::where('smartbin.smartbin_id', $id)
            ->leftJoin('user', 'smartbin.user_id', 'user.user_id')
            ->get(['smartbin.smartbin_status', 'user.user_id', 'user.user_username']);
        return response()->json($result[0]);
    }

    public function checkLogin($id)
    {
        $result = Smartbin::find($id);
        return response()->json($result);
    }

    public function updatePoints(Request $request) 
    {
        $result = Smartbin::find($request->id);
        $result->smartbin_bottle = $result['smartbin_bottle'] + 1;
        $result->save();
        return response()->json($this->response);
    }

    public function checkAlertSmartbin(Request $request) 
    {
        $results = Alert::where([
            ['smartbin_id', $request->id], 
            ['alert_status', 0]
            ])->get(['alert_id']);
        if ($results == '[]') {
            return response()->json(0);
        } else {
            return response()->json($results[0]['alert_id']);
        }
        
    }

    public function getAlertSmartbin(Request $request) 
    {
        $results = Alert::where([['alert.alert_status', 0],['smartbin.smartbin_status', 2]])
            ->leftJoin('smartbin', 'alert.smartbin_id', 'smartbin.smartbin_id')
            ->get(['alert_date', 'smartbin.smartbin_hostname']);
        return response()->json($results);
    }

    public function setAlertSmartbin(Request $request) 
    {
        $alert = Alert::find($request->id);
        $alert->alert_status = 1;
        $alert->save();
        return response()->json($this->response);
    }

    public function alertSmartbin(Request $request) 
    {
        $smartbin = Smartbin::find($request->id);
        $alert = Alert::where([
            ['smartbin_id', $smartbin['smartbin_id']],
            ['alert_status', 0]
            ])->get();
        if ($alert == '[]') {
            $alert = new Alert;
            $alert->smartbin_id = $smartbin['smartbin_id'];
            $alert->save();
        }

        $smartbin->smartbin_status = 2;
        $smartbin->save();
        return response()->json($alert);
    }
    
    public function checkStatusLogin(Request $request)
    {
        $result = Smartbin::where([['smartbin_id', $request->id], ['user_id', $request->user_id]])->get();
        if ($result != '[]') {
            return response()->json('1');
        } else {
            return response()->json('0');
        }
        
    }

    public function logout(Request $request) 
    {
        $system = System::find(1);
        $smartbin = Smartbin::find($request->id);
        if ($smartbin['smartbin_bottle'] != 0) {
            $user = User::find($smartbin['user_id']);
            $cumulative = new Cumulative;
            
            $cumulative->cumulative_amount = $smartbin['smartbin_bottle'];
            $cumulative->cumulative_points = $cumulative['cumulative_amount'] * $system['system_points'];
            $cumulative->user_id = $user['user_id'];
            $cumulative->smartbin_id = $smartbin['smartbin_id'];
            
            $user->user_points = $user['user_points'] + $cumulative['cumulative_points'];
            
            $smartbin->smartbin_bottle = 0;
            
            $cumulative->save();
            $user->save();
        }
        $smartbin->user_id = null;
        $smartbin->save();
        return response()->json($this->response);
    }
}
