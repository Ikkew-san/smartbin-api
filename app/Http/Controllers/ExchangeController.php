<?php

namespace App\Http\Controllers;

use App\Models\Exchange;
use App\Models\Exchangelist;
use App\Models\Pay;
use App\Models\Rewards;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Laravel\Lumen\Routing\Controller as BaseController;

class ExchangeController extends BaseController
{
    private $response = array('status' => 1, 'message' => 'success');

    public function getExchange()
    {
        $today = strtotime(date('Y-m-d H:i:s'));
        $results = Exchange::where('exchange_status', 3)->get(['exchange_id', 'exchange_updated_at']);
        if ($results != '[]') {
            foreach ($results as $i => $val1) {
                $day = ($today - strtotime($val1['exchange_updated_at'])) / (60 * 60 * 24);
                if ($day > 14) {
                    $exchange = Exchange::find($val1['exchange_id']);
                    $exchangelist_id = Exchangelist::where('exchange_id', $exchange['exchange_id'])->get(['exchangelist_id']);
                    if ($exchangelist_id != '[]') {
                        foreach ($exchangelist_id as $n => $val2) {
                            $exchangelist = Exchangelist::find($val2['exchangelist_id']);
                            $exchangelist->exchangelist_status = 0;

                            $rewards = Rewards::find($exchangelist['rewards_id']);
                            $rewards->rewards_amount = $rewards['rewards_amount'] + $exchangelist['exchangelist_amount'];
                            $rewards->save();
                            $exchangelist->save();
                        }
                    }
                    $exchange->exchange_status = 5;
                    $exchange->save();
                }
            }
        }

        $results = Exchange::leftJoin('user', 'exchange.user_id', 'user.user_id')
            ->orderBy('exchange_updated_at', 'desc')
            ->get(['exchange.*', 'user.user_username']);

        for ($i = 0; $i < count($results); $i++) {
            if ($results[$i]['exchange_status'] == 4) {
                $admin = Pay::where('exchange_id', $results[$i]['exchange_id'])
                    ->leftJoin('user', 'pay.user_id', 'user.user_id')
                    ->get(['user.user_username']);
                $results[$i]['admin'] = $admin[0]['user_username'];
            } else {
                $results[$i]['admin'] = "";
            }
        }
        return response()->json($results);
    }

    public function getExchangelist($id)
    {
        $result = Exchangelist::where('exchange_id', $id)
            ->leftJoin('rewards', 'exchangelist.rewards_id', 'rewards.rewards_id')
            ->get([
                'exchangelist.exchangelist_id',
                'exchangelist.exchange_id',
                'rewards.rewards_name',
                'exchangelist.exchangelist_amount',
                'exchangelist.exchangelist_points',
                'exchangelist.exchangelist_status',
            ]);
        $exchange = Exchange::where('exchange_id', $id)->get(['exchange_status']);
        $result[count($result)] = $exchange[0]['exchange_status'];
        return response()->json($result);
    }

    public function getNotFinish()
    {
        $result = Exchange::where([
            ['exchange_status', '!=', 0],
            ['exchange_status', '!=', 4],
        ])
            ->leftJoin('user', 'exchange.user_id', 'user.user_id')
            ->orderBy('exchange_created_at', 'desc')
            ->get(['exchange.*', 'user.user_username']);

        for ($i = 0; $i < count($result); $i++) {
            $exchangelist = Exchangelist::where([
                ['exchange_id', $result[$i]['exchange_id']],
                ['exchangelist_status', 1],
            ])->get(['exchangelist_id']);
            $result[$i]['exchangelist'] = count($exchangelist);
        }
        return response()->json($result);
    }

    public function setExchangeStatus(Request $request)
    {
        $result = Exchange::find($request->exchange);
        if ($result['exchange_status'] == '1') {
            $result->exchange_status = '2';
            $result->save();
            return response()->json($this->response);
        } elseif ($result['exchange_status'] == '2') {
            $exchangelist = Exchangelist::where([
                ['exchange_id', $result['exchange_id']],
                ['exchangelist_status', 1],
            ])->get(['*']);
            if ($exchangelist == '[]') {
                $result->exchange_status = '3';
                $result->save();
                return response()->json($this->response);
            } else {
                return false;
            }
        } elseif ($result['exchange_status'] == '3') {
            $result->exchange_status = '4';
            $result->save();

            $pay = new Pay;
            $pay->exchange_id = $result['exchange_id'];
            $pay->user_id = $request->user;
            $pay->save();
            return response()->json($this->response);
        } else {
            return false;
        }
    }

    public function setExchangelistStatus(Request $request)
    {
        $exchangelist = Exchangelist::find($request->exchangelist);
        if ($exchangelist['exchangelist_status'] == 1) {
            $exchangelist->exchangelist_status = 2;
            $exchangelist->save();
        } else {
            return false;
        }
    }

    public function cancelExchange(Request $request)
    {
        $exchange = Exchange::find($request->id);
        if ($exchange['exchange_status'] != 4 && $exchange['exchange_status'] != 0) {
            
            $exchangelist = Exchangelist::where('exchange_id', $exchange['exchange_id'])->get(['exchangelist_id']);
            $points = 0;
            
            for ($i = 0; $i < count($exchangelist); $i++) {
                $exchangelist_f = Exchangelist::find($exchangelist[$i]['exchangelist_id']);
                $exchangelist_f->exchangelist_status = 0;
                
                $rewards = Rewards::find($exchangelist_f['rewards_id']);
                $rewards->rewards_amount = $rewards['rewards_amount'] + $exchangelist_f['exchangelist_amount'];
                
                $points = $points + $exchangelist_f['exchangelist_points'];
                $exchangelist_f->save();
                $rewards->save();
            }

            $exchange->exchange_status = 0;
            $exchange->save();

            $user = User::find($exchange['user_id']);
            $user->user_points = $user['user_points'] + $points;
            $user->save();
            return response()->json($this->response);
        } else {
            return false;
        }
    }

    public function cancelExchangelist(Request $request)
    {
        $exchangelist = Exchangelist::find($request->id);
        $rewards = Rewards::find($exchangelist['rewards_id']);
        $exchange = Exchange::find($exchangelist['exchange_id']);
        $user = User::find($exchange['user_id']);
        
        $exchangelist->exchangelist_status = 0;
        $rewards->rewards_amount = $rewards['rewards_amount'] + $exchangelist['exchangelist_amount'];
        $user->user_points = $user['user_points'] + $exchangelist['exchangelist_points'];
        
        $exchangelist->save();
        $user->save();
        $rewards->save();
        
        $checklist = Exchangelist::where([['exchange_id', $exchange['exchange_id']],['exchangelist_status', "!=", 0]])->get();
        if ($checklist == '[]') {
            $exchange->exchange_status = 0;
            $exchange->save();
        }
        return response()->json($this->response);
    }

    public function exchangeRewards($id, Request $request)
    {
        $data = $request->all();
        $points = 0;
        for ($i = 0; $i < count($data); $i++) {
            $rewards = Rewards::where([
                ['rewards_id', $data[$i]['rewards_id']],
                ['rewards_status', '!=', 0],
            ])->get(['rewards_name', 'rewards_points', 'rewards_amount']);
            if ($rewards != [] && $rewards[0]['rewards_amount'] >= $data[$i]['amount']) {
                $points = $points + ($rewards[0]['rewards_points'] * $data[$i]['amount']);
            } elseif ($rewards[0]['rewards_amount'] < $data[$i]['amount']) {
                $result['name'] = $rewards[0]['rewards_name'];
                $result['message'] = "2";
                return response()->json($result);
                exit();
            } else {
                return response()->json($result['message'] = "0");
                exit();
            }
        }
        $user = User::find($id);
        $points = $user['user_points'] - $points;
        $user->user_points = $points;
        if ($points >= 0) {
            $exchange = new Exchange;
            $exchange->user_id = $id;
            $exchange->save();
            for ($i = 0; $i < count($data); $i++) {
                $rewards = Rewards::find($data[$i]['rewards_id']);
                $rewards->rewards_amount = $rewards['rewards_amount'] - $data[$i]['amount'];
                $rewards->save();

                $exchangelist = new Exchangelist;
                $exchangelist->exchange_id = $exchange['exchange_id'];
                $exchangelist->rewards_id = $rewards['rewards_id'];
                $exchangelist->exchangelist_amount = $data[$i]['amount'];
                $exchangelist->exchangelist_points = $rewards['rewards_points'] * $data[$i]['amount'];
                $exchangelist->save();
            }
            $user->save();
            return response()->json($this->response);
        } else {
            return response()->json($result['message'] = "1");
        }
    }

    public function getExchangeUser($id)
    {
        $results = Exchange::where([['user_id', $id],['exchange_status', '!=', 0],['exchange_status', '!=', 4]])
        ->orderBy('exchange_created_at', 'desc')
        ->get(['exchange_id', 'exchange_created_at', 'exchange_status']);
        for ($i = 0; $i < count($results); $i++) {
            $exchangelist = Exchangelist::where('exchange_id', $results[$i]['exchange_id'])
                ->leftJoin('rewards', 'exchangelist.rewards_id', 'rewards.rewards_id')
                ->get(['rewards.rewards_name', 'exchangelist.exchangelist_amount', 'exchangelist.exchangelist_points', 'exchangelist.exchangelist_status']);
            $results[$i]['exchangelist'] = $exchangelist;
        }
        return response()->json($results);
    }

    public function getExchangeUser_Status($id, Request $request)
    {
        if ($request->status == 0) {
            $results = Exchange::where([['user_id', $id],['exchange_status', [0, 5]]])
                    ->orderBy('exchange_created_at', 'desc')
                    ->get(['exchange_id', 'exchange_created_at', 'exchange_status']);
            for ($i = 0; $i < count($results); $i++) {
                $exchangelist = Exchangelist::where('exchange_id', $results[$i]['exchange_id'])
                    ->leftJoin('rewards', 'exchangelist.rewards_id', 'rewards.rewards_id')
                    ->get(['rewards.rewards_name', 'exchangelist.exchangelist_amount', 'exchangelist.exchangelist_points', 'exchangelist.exchangelist_status']);
                $results[$i]['exchangelist'] = $exchangelist;
            }
            return response()->json($results);
        } else {
            $results = Exchange::where([['user_id', $id],['exchange_status', $request->status]])
                    ->orderBy('exchange_created_at', 'desc')
                    ->get(['exchange_id', 'exchange_created_at', 'exchange_status']);
            for ($i = 0; $i < count($results); $i++) {
                $exchangelist = Exchangelist::where('exchange_id', $results[$i]['exchange_id'])
                    ->leftJoin('rewards', 'exchangelist.rewards_id', 'rewards.rewards_id')
                    ->get(['rewards.rewards_name', 'exchangelist.exchangelist_amount', 'exchangelist.exchangelist_points', 'exchangelist.exchangelist_status']);
                $results[$i]['exchangelist'] = $exchangelist;
            }
            return response()->json($results);
        }
    }
}
