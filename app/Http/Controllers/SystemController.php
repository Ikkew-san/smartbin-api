<?php

namespace App\Http\Controllers;
use DB;

use App\Http\Controllers\ImageController;
use App\Models\Buy;
use App\Models\Buylist;
use App\Models\Alert;
use App\Models\Cumulative;
use App\Models\Exchange;
use App\Models\Exchangelist;
use App\Models\Rewards;
use App\Models\Sell;
use App\Models\Pay;
use App\Models\User;
use App\Models\Smartbin;
use App\Models\System;

use Illuminate\Http\Request;
use Illuminate\Http\Response; 
use Laravel\Lumen\Routing\Controller as BaseController;

class SystemController extends BaseController
{
    private $image_path = 'system/';
    private $response = array('status' => 1, 'message' => 'success');

    public function getSystem()
    {
        $image = new ImageController();
        $result = System::all();
        $result[0]["image_url"] = $image->getImageUrl($result[0]["system_logo"], $this->image_path);
        return response()->json($result[0]);
    }
    
    public function putSystem(Request $request)
    {
        $result = System::find(1);
        $result->system_name = $request->name;
        $result->system_address = $request->address;
        $result->system_telephone = $request->telephone;
        $result->system_points = $request->points;
        if ($request->hasFile('image')) {
            $destinationPath = "images/system";
            $file = $request->file('image');
            $fileName = $file->getClientOriginalName();
            $file->move($destinationPath,  $fileName);
            $result->system_logo = $fileName;
         }
        $result->save();
        return response()->json($this->response);
    }
    
    public function calculatePoints(Request $request)
    {
        $user = User::select('user_points')->where('user_id', $request->user)->get();
        $rewards = Rewards::select('rewards_points')->where('rewards_id', $request->rewards)->get();
        $result = $user[0]['user_points'] - $rewards[0]['rewards_points'];
        return response()->json($result);
    }

    public function reportExchange(Request $request)
    {
        $results = Exchangelist::whereBetween('exchange.exchange_updated_at', [$request->dateFrom, $request->dateTo])
        ->where([['exchange.exchange_status', $request->status], ['exchangelist.rewards_id', $request->rewards]])
        ->leftJoin('exchange', 'exchangelist.exchange_id', 'exchange.exchange_id')
        ->leftJoin('user', 'exchange.user_id', 'user.user_id')
        ->select('user.user_username as exchangelist_username', 'exchange.exchange_status', DB::raw("SUM(exchangelist.exchangelist_amount) as exchangelist_unit"))
        ->groupBy('user.user_username', 'exchange.exchange_status')
        ->get();
        return response()->json($results);
    }
    
    public function reportCumulative(Request $request)
    {
        $results = Cumulative::whereBetween('cumulative.cumulative_datetime', [$request->dateFrom, $request->dateTo])
            ->leftJoin('user', 'cumulative.user_id', 'user.user_id')
            ->select(
                'user.user_username as cumulative_username', 
                DB::raw("SUM(cumulative.cumulative_amount) as cumulative_amount,
                SUM(cumulative.cumulative_points) as cumulative_points"),
                )
            ->groupBy('cumulative.user_id','user.user_username')
            ->get();
        return response()->json($results);
    }

    public function reportAlertSmartbin(Request $request)
    {
        $results = Alert::whereBetween('alert.alert_date', [$request->dateFrom, $request->dateTo])
            ->leftJoin('smartbin', 'alert.smartbin_id', 'smartbin.smartbin_id')
            ->get(['alert.alert_date', 'smartbin.smartbin_hostname','alert.alert_id','smartbin.smartbin_id']);
            for ($i=0; $i < count($results); $i++) { 
                if ($i == 0) {
                    $alert_dateForm = Alert::where([
                        ['alert_date', "<", $results[$i]['alert_date']],
                        ['smartbin_id', $results[$i]['smartbin_id']]
                    ])->orderBy('alert_date', 'desc')
                    ->get(['alert_date']);
                    if ($alert_dateForm == '[]') {
                        $amount = Cumulative::where([
                            ['cumulative_datetime', "<", $results[$i]['alert_date']],
                            ['smartbin_id', $results[$i]['smartbin_id']]
                            ])
                            ->select(DB::raw("SUM(cumulative_amount) as alert_amount"))
                            ->get();
                        $results[$i]['alert_amount'] = $amount[0]['alert_amount'];
                    } else {
                        $amount = Cumulative::whereBetween('cumulative_datetime', [$alert_dateForm[0]['alert_date'], $results[$i]['alert_date']])
                        ->select(DB::raw("SUM(cumulative_amount) as alert_amount"))
                        ->get();
                        $results[$i]['alert_amount'] = $amount[0]['alert_amount'];
                    }
                }
                else {
                    $amount = Cumulative::whereBetween('cumulative_datetime', [$results[$i - 1]['alert_date'], $results[$i]['alert_date']])
                    ->select(DB::raw("SUM(cumulative_amount) as alert_amount"))
                    ->get();
                    $results[$i]['alert_amount'] = $amount[0]['alert_amount'];
                }
            }
        return response()->json($results);
    }

    public function reportPay(Request $request)
    {
        if ($request->user == "all") {            
            $results = Exchangelist::whereBetween('exchange.exchange_updated_at', [$request->dateFrom, $request->dateTo])
            ->where('exchange.exchange_status', $request->status)
            ->leftJoin('exchange', 'exchangelist.exchange_id', 'exchange.exchange_id')
            ->leftJoin('rewards', 'exchangelist.rewards_id', 'rewards.rewards_id')
            ->select('rewards.rewards_name as exchangelist_rewards', 'exchange.exchange_status', 
            DB::raw("count(exchange.exchange_status) as exchangelist_amount"))
            ->groupBy('exchangelist.rewards_id', 'exchange.exchange_status',  'rewards.rewards_name')
            ->get();
            return response()->json($results);
        } else {
            $results = Exchangelist::whereBetween('exchange.exchange_updated_at', [$request->dateFrom, $request->dateTo])
            ->where([['exchange.exchange_status', $request->status], ['exchange.user_id', $request->user]])
            ->leftJoin('exchange', 'exchangelist.exchange_id', 'exchange.exchange_id')
            ->leftJoin('rewards', 'exchangelist.rewards_id', 'rewards.rewards_id')
            ->select('rewards.rewards_name as exchangelist_rewards', 'exchange.exchange_status', 
                DB::raw("count(exchange.exchange_status) as exchangelist_amount"))
            ->groupBy('exchangelist.rewards_id', 'exchange.exchange_status',  'rewards.rewards_name')
            ->get();
            return response()->json($results);
        }        
    }

    public function getUsernameInReportPay(Request $request)
    {
        $results = Exchangelist::whereBetween('exchange.exchange_updated_at', [$request->dateFrom, $request->dateTo])
        ->where('exchange.exchange_status', $request->status)
        ->leftJoin('exchange', 'exchangelist.exchange_id', 'exchange.exchange_id')
        ->leftJoin('user', 'exchange.user_id', 'user.user_id')
        ->select('exchange.user_id', 'user.user_username')
        ->groupBy('exchange.user_id','user.user_username')
        ->get();
        return response()->json($results);
    }

    public function reportSellGarbage(Request $request)
    {
        $results = Sell::whereBetween('sell.sell_date', [$request->dateFrom, $request->dateTo])
        ->leftJoin('user', 'sell.user_id', 'user.user_id')
        ->get(['sell.sell_id', 'sell.sell_date', 'sell.sell_weight', 'sell.sell_money', 'user.user_username']);
        return response()->json($results);
    }

    public function reportBuy(Request $request)
    {
       $results = Buylist::whereBetween('buy.buy_date', [$request->dateFrom, $request->dateTo])
            ->leftJoin('buy', 'buylist.buy_id', 'buy.buy_id')
            ->leftJoin('rewards', 'buylist.rewards_id', 'rewards.rewards_id')
            ->select(
                'rewards.rewards_name as buy_name', 
                DB::raw("SUM(buylist.buylist_amount) as buy_amount,
                SUM(buylist.buylist_total) as buy_total"),
                )
            ->groupBy('buylist.rewards_id','rewards.rewards_name')
            ->get();
        return response()->json($results);
    }

    private $dest = 'files/';
    private $link_name = 'default.png';
  
    public function setImageMobile(Request $request)
    {
      // return response()->json($request);
      $dest_path = $this->dest . 'user_images';
      if ($request->hasFile('image')) {
        $image = $request->file('image');
        $this->link_name = $this->setUniqidImageName($image);
  
        $image->move($dest_path, $this->link_name);
      }
  
      $image_url = 'http://' . $_SERVER['SERVER_NAME'] . ':' . $_SERVER['SERVER_PORT'] . '/' . $dest_path . '/' . $this->link_name;
      return $image_url;
    }
}
