<?php

namespace App\Http\Controllers;

use App\Models\Buy;
use App\Models\Buylist;
use App\Models\Rewards;
use App\Http\Controllers\ImageController;

use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Laravel\Lumen\Routing\Controller as BaseController;

class RewardsController extends BaseController
{
    private $image_path = 'rewards/';
    private $response = array('status' => 1, 'message' => 'success');

    public function getRewards()
    {
        $image = new ImageController();
        $results = Rewards::all();
        $results = $image->getImagesUrl($results, $this->image_path, "rewards_img");
        return response()->json($results);
    }

    public function getRewardsApp()
    {
        $image = new ImageController();
        $result = Rewards::where([['rewards_status', 1],['rewards_amount', '>', 0]])
                ->orderBy('rewards_points', 'desc')
                ->get();
        $result = $image->getImagesUrl($result, $this->image_path, "rewards_img");
        return response()->json($result);
    }
    
    public function findRewards($id)
    {
        $image = new ImageController();
        $result = Rewards::find($id);
        $result["image_url"] = $image->getImageUrl($result["rewards_img"], $this->image_path);
        return response()->json($result);
    }

    public function checkRewards($id)
    {
        $result = Rewards::where('rewards_id', $id)
                        ->where('rewards_amount', '>' , 0)
                        ->where('rewards_status', '=' , 1)
                        ->get(['rewards_id']);
        if($result != []) {
            return response()->json($result);
        } else {
            return false;
        }
    }

    public function inBasket(Request $request) 
    {
        $image = new ImageController();
        $data = $request->all();
        $result;
        for ($i=0; $i < count($data); $i++) { 
            $rewards = Rewards::where('rewards_id', $data[$i]["rewards_id"])
                            ->get(['rewards_id', 'rewards_name', 'rewards_points', 'rewards_img']);
            $result[$i] = $rewards[0];              
            $result[$i]["amount"] = $data[$i]["amount"];         
        }
        $result = $image->getImagesUrl($result, $this->image_path, "rewards_img");
        return response()->json($result);
    }

    
    public function addRewards(Request $request)
    {
        $results = new Rewards;
        $results->rewards_name = $request->name;
        $results->rewards_points = $request->points;
        $results->rewards_price = $request->price;
        if ($request->hasFile('image')) {
            $destinationPath = "images/rewards";
            $file = $request->file('image');
            $fileName   = $file->getClientOriginalName();
            $file->move($destinationPath,  $fileName);
            $results->rewards_img = $fileName;
        }
        $results->save();
       return response()->json($this->response);
    }
    
    public function edit($id, Request $request)
    {
        $result = Rewards::find($id);
        $result->rewards_price = $request->price;
        $result->rewards_points = $request->points;
        if ($request->hasFile('image')) {
            $destinationPath = "images/rewards";
            $file = $request->file('image');
            $fileName   = $file->getClientOriginalName();
            $file->move($destinationPath,  $fileName);
            $result->rewards_img = $fileName;
        }
        $result->save();
        return response()->json($this->response);
    }
    
    public function putStatusRewards(Request $request)
    {
        $result = Rewards::find($request->id);
        if($result["rewards_amount"] != "0"){
            if($result["rewards_status"] == "0"){
                $result->rewards_status = "1";
            } else {
                $result->rewards_status = "0";
            }
            $response = $this->response;
        } else {
            $result->rewards_status = "0";
            $$response = "amount";
        }
        $result->save();
        return response()->json($this->response);
    }
    
    public function delete($id)
    {
        $result = Rewards::find($id);
        if ($result != '') {
            $rewards = Rewards::where([['rewards.rewards_id', $id], ['rewards.rewards_amount', '!=', 0]])
            ->orWhere('buylist.rewards_id', $result['rewards_id'])
            ->orWhere('exchangelist.rewards_id', $result['rewards_id'])
            ->leftJoin('buylist', 'rewards.rewards_id', 'buylist.rewards_id')
            ->leftJoin('exchangelist', 'rewards.rewards_id', 'exchangelist.rewards_id')
            ->distinct()
            ->get();
            if ($rewards == '[]') {
                $result->delete();
                return response()->json($this->response);
            } else {
                return response()->json(1);
            }
        } else {
            return false;
        }
    }
    
    public function getBuying()
    {
        $result = Buy::orderBy('buy_date', 'desc')
                    ->leftJoin('user', 'buy.user_id', 'user.user_id')
                    ->get(['buy.buy_id','buy.buy_date','buy.buy_net', 'user.user_username']);
        return response()->json($result);
    }

    public function getBuylist($id)
    {
        $results;
        $results = Buylist::where('buylist.buy_id', $id)
                    ->leftJoin('buy', 'buylist.buy_id', 'buy.buy_id')
                    ->leftJoin('user', 'buy.user_id', 'user.user_id')
                    ->leftJoin('rewards', 'buylist.rewards_id', 'rewards.rewards_id')
                    ->get(["rewards.rewards_name","buylist.buylist_amount","buylist.buylist_price","buylist.buylist_total","buy.buy_date", "user_username"]);
        return response()->json($results);
    }

    public function getRewardsName()
    {
        $result = Rewards::select('rewards_id', 'rewards_name')->get();
        return response()->json($result);
    }
    
    public function addBuying($id, Request $request)
    {
        $data = $request->all();
        $net = 0;
        
        $buy = new Buy;
        $buy->user_id = $id;
        $buy->save();
        for ($i = 0; $i < count($data); $i++) {
            $value = Rewards::select('rewards_id')->where('rewards_name', $data[$i]["name"])->get();
            
            $buylist = new Buylist;
            $buylist->rewards_id = $value[0]["rewards_id"];
            $buylist->buylist_amount = $data[$i]["amount"];
            $buylist->buylist_price = $data[$i]["price"];
            $total = $data[$i]["price"] * $data[$i]["amount"];
            $buylist->buylist_total = $total;
            $buylist->buy_id = $buy["buy_id"];
            $buylist->save();
            
            $rewards = Rewards::find($value[0]["rewards_id"]);
            $rewards->rewards_amount = $rewards["rewards_amount"] + $data[$i]["amount"];
            $rewards->save();
            $net = $net + $total;
        }
        $buy->buy_net = $net;
        $buy->save();
        return response()->json($this->response);
    }

    public function LatestRewards()
    {
        $image = new ImageController();
        $results = Rewards::where([
            ["rewards_status", "!=" , 0],
            ["rewards_amount", "!=" , 0]
        ])->orderBy('rewards_id', 'desc')->limit(4)->get();
        $results = $image->getImagesUrl($results, $this->image_path, "rewards_img");
        return response()->json($results);
    }
}
