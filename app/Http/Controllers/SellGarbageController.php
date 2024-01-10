<?php

namespace App\Http\Controllers;
use App\Models\Sell;
use App\Models\System;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Http\Response; 
use Laravel\Lumen\Routing\Controller as BaseController;

class SellGarbageController extends BaseController
{
    private $response = array('status' => 1, 'message' => 'success');

    public function getSellGarbage()
    {
        $results = Sell::leftJoin('user', 'sell.user_id', 'user.user_id')->orderBy('sell_date', 'desc')->get(['sell.*','user.user_username']);
        return response()->json($results);
    }

    public function setSellGarbage($id, Request $request)
    {
        $results = new Sell;
        $system = System::find(1);
        $results ->sell_weight = $request->weight;
        $results ->sell_money = $request->money;
        $results ->user_id = $id;
        $system ->system_net = $system['system_net'] + $request->money;
        $results ->save();
        $system ->save();
        return response()->json($this->response); 
    }
}
