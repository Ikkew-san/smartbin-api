<?php

namespace App\Http\Controllers;
use DB;
use Mpdf\Mpdf;
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
use App\Http\Controllers\ImageController;
use Laravel\Lumen\Routing\Controller as BaseController;

class PdfController extends BaseController
{
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
        $content = "";
        $amount = 0;
        $system = System::find(1);
        $time = '<p style="text-align:right; font-size: 75%;">รายงาน ณ วันที่ '.date("d-m-Y H:i").' น.</p>';
        $head = '
        <p style="text-align:center"><img src="'.'images/system/'.$system["system_logo"].'" width="100"></p>
        <h2 style="text-align:center; margin: 0;">'.$system['system_name'].'</h2>
        <p style="text-align:center; margin-top: -6px;">'.$system['system_address'].'</p>
        <h3 style="text-align:center; margin: 0;">รายงานแจ้งเตือนถังขยะเต็ม</h3>
        <p style="text-align:center; margin-top: -6px">ตั้งแต่วันที่ '. $request->dateFrom.' ถึง '. $request->dateTo.'</p>
        <table width="100%" style="border-collapse: collapse; margin-top: 8px;">
            <thead>
                <tr  style="background-color: #5c968a;">
                    <th style="border:1px solid #000; padding:10px;" >#</th>
                    <th style="border:1px solid #000; padding:10px;" >วันที่</th>
                    <th style="border:1px solid #000; padding:10px;" >โฮสต์เนมถังขยะ</th>
                    <th style="border:1px solid #000; padding:10px; text-align:center;">จำนวน</th>
                </tr>
        <tbody>';
        for ($i=0; $i < count($results); $i++) { 
            $num = $i + 1;
            $amount = $amount + $results[$i]['alert_amount'];
            $content .= '
            <tr style="border:1px solid #000;">
				<td style="border:1px solid #000; padding: 7px; text-align:center;"  >'.$num.'</td>
				<td style="border:1px solid #000; padding: 7px;"  >'.$results[$i]['alert_date'].'</td>
				<td style="border:1px solid #000; padding: 7px;"  >'.$results[$i]['smartbin_hostname'].'</td>
				<td style="border:1px solid #000; padding: 7px; text-align:center;"  >'.number_format($results[$i]['alert_amount']).'</td>
			</tr>';
        }
        $end = '
        </tbody>
            <tfoot>
                <tr>
                    <td colspan="3" style="padding: 7px; text-align: right; font-weight: bold;">รวม</td>
                    <td style="border:1px solid #000; padding: 7px; text-align:center;">'.number_format($amount).'</td>
                </tr>
            </tfoot>
        </table>';
        $mpdf = new \Mpdf\Mpdf([
            'default_font_size' => 8,
            'default_font' => 'Garuda'
        ]);
        $mpdf->WriteHTML($time);
        $mpdf->WriteHTML($head);
        $mpdf->WriteHTML($content);
        $mpdf->WriteHTML($end);
        $mpdf->Output('PDFreportAlertSmartbin.pdf', \Mpdf\Output\Destination::INLINE);
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
        $content = "";
        $amount = 0;
        $points = 0;
        $system = System::find(1);
        $time = '<p style="text-align:right; font-size: 75%;">รายงาน ณ วันที่ '.date("d-m-Y H:i").' น.</p>';
        $head = '
        <p style="text-align:center"><img src="'.'images/system/'.$system["system_logo"].'" width="100"></p>
        <h2 style="text-align:center; margin: 0;">'.$system['system_name'].'</h2>
        <p style="text-align:center; margin-top: -6px;">'.$system['system_address'].'</p>
        <h3 style="text-align:center; margin: 0;">รายงานสะสมคะแนน</h3>
        <p style="text-align:center; margin-top: -6px">ตั้งแต่วันที่ '. $request->dateFrom.' ถึง '. $request->dateTo.'</p>
        <table width="100%" style="border-collapse: collapse; margin-top: 8px;">
            <thead>
                <tr  style="background-color: #5c968a;">
                    <th style="border:1px solid #000; padding:10px;" width="10%">#</th>
                    <th style="border:1px solid #000; padding:10px;">ชื่อผู้สะสมคะแนน</th>
                    <th style="border:1px solid #000; padding:10px;" width="20%">จำนวนขยะ</th>
                    <th style="border:1px solid #000; padding:10px;" width="20%">คะแนนที่ได้รับ</th>
                </tr>
        <tbody>';
        for ($i=0; $i < count($results); $i++) { 
            $num = $i + 1;
            $amount = $amount + $results[$i]['cumulative_amount'];
            $points = $points + $results[$i]['cumulative_points'];
            $content .= '
            <tr style="border:1px solid #000;">
				<td style="border:1px solid #000; padding: 7px; text-align:center;"  >'.$num.'</td>
				<td style="border:1px solid #000; padding: 7px;">'.$results[$i]['cumulative_username'].'</td>
				<td style="border:1px solid #000; padding: 7px; text-align:center;">'.number_format($results[$i]['cumulative_amount']).'</td>
				<td style="border:1px solid #000; padding: 7px; text-align:center;">'.number_format($results[$i]['cumulative_points']).'</td>
            </tr>';
        }
        $end = '
        </tbody>
        <tfoot>
            <tr>
                <td colspan="2" style="padding: 7px; text-align: right; font-weight: bold;">รวม</td>
                <td style="border:1px solid #000; padding:7px; text-align:center;">'.number_format($amount).'</td>
                <td style="border:1px solid #000; padding:7px; text-align:center;">'.number_format($points).'</td>
            </tr>
        </tfoot>
        </table>';
        $mpdf = new \Mpdf\Mpdf([
            'default_font_size' => 8,
            'default_font' => 'Garuda'
        ]);
        $mpdf->WriteHTML($time);
        $mpdf->WriteHTML($head);
        $mpdf->WriteHTML($content);
        $mpdf->WriteHTML($end);
        $mpdf->Output('PDFreportCumulative.pdf', \Mpdf\Output\Destination::INLINE);
    }    

    public function reportSellGarbage(Request $request)
    {
        $results = Sell::whereBetween('sell.sell_date', [ $request->dateFrom,  $request->dateTo])
        ->leftJoin('user', 'sell.user_id', 'user.user_id')
        ->get(['sell.sell_id', 'sell.sell_date', 'sell.sell_weight', 'sell.sell_money', 'user.user_username']);
        $content = "";
        $weight = 0;
        $money = 0;
        $system = System::find(1);
        $time = '<p style="text-align:right; font-size: 75%;">รายงาน ณ วันที่ '.date("d-m-Y H:i").' น.</p>';
        $head = '
        <p style="text-align:center"><img src="'.'images/system/'.$system["system_logo"].'" width="100"></p>
        <h2 style="text-align:center; margin: 0;">'.$system['system_name'].'</h2>
        <p style="text-align:center; margin-top: -6px;">'.$system['system_address'].'</p>
        <h3 style="text-align:center; margin: 0;">รายงานขายขยะ</h3>
        <p style="text-align:center; margin-top: -6px">ตั้งแต่วันที่ '. $request->dateFrom.' ถึง '. $request->dateTo.'</p>
        <table width="100%" style="border-collapse: collapse; margin-top: 8px;">
            <thead>
                <tr  style="background-color: #5c968a;">
                    <th style="border:1px solid #000; padding:10px;" width="10%">#</th>
                    <th style="border:1px solid #000; padding:10px;" width="25%">วันที่</th>
                    <th style="border:1px solid #000; padding:10px;" width="25%">บันทึกโดย</th>
                    <th style="border:1px solid #000; padding:10px;" width="20%">น้ำหนัก / กิโลกรัม</th>
                    <th style="border:1px solid #000; padding:10px;" width="20%">เงินที่ได้รับ</th>
                </tr>
        <tbody>';
        for ($i=0; $i < count($results); $i++) { 
            $num = $i + 1;
            $weight = $weight + $results[$i]['sell_weight'];
            $money = $money + $results[$i]['sell_money'];
            $content .= '
            <tr style="border:1px solid #000;">
				<td style="border:1px solid #000; padding: 7px; text-align:center;"  >'.$num.'</td>
				<td style="border:1px solid #000; padding: 7px;">'.$results[$i]['sell_date'].'</td>
				<td style="border:1px solid #000; padding: 7px;">'.$results[$i]['user_username'].'</td>
				<td style="border:1px solid #000; padding: 7px; text-align:center;">'.number_format($results[$i]['sell_weight'], 2).'</td>
				<td style="border:1px solid #000; padding: 7px; text-align:right;">'.number_format($results[$i]['sell_money'], 2).'</td>
            </tr>';
        }
        $end = '
        </tbody>
        <tfoot>
            <tr>
                <td colspan="3" style="padding: 7px; text-align: right; font-weight: bold;">รวม</td>
                <td style="border:1px solid #000; padding:7px; text-align:center;">'.number_format($weight, 2).'</td>
                <td style="border:1px solid #000; padding:7px; text-align:right;">'.number_format($money, 2).'</td>
            </tr>
        </tfoot>
        </table>';
        $mpdf = new \Mpdf\Mpdf([
            'default_font_size' => 8,
            'default_font' => 'Garuda'
        ]);
        $mpdf->WriteHTML($time);
        $mpdf->WriteHTML($head);
        $mpdf->WriteHTML($content);
        $mpdf->WriteHTML($end);
        $mpdf->Output('PDFreportSellGarbage.pdf', \Mpdf\Output\Destination::INLINE);
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
        $content = "";
        $amount = 0;
        $unit = 0;
        $total = 0;
        $system = System::find(1);
        $time = '<p style="text-align:right; font-size: 75%;">รายงาน ณ วันที่ '.date("d-m-Y H:i").' น.</p>';
        $head = '
        <p style="text-align:center"><img src="'.'images/system/'.$system["system_logo"].'" width="100"></p>
        <h2 style="text-align:center; margin: 0;">'.$system['system_name'].'</h2>
        <p style="text-align:center; margin-top: -6px;">'.$system['system_address'].'</p>
        <h3 style="text-align:center; margin: 0;">รายงานซื้อของรางวัล</h3>
        <p style="text-align:center; margin-top: -6px">ตั้งแต่วันที่ '. $request->dateFrom.' ถึง '. $request->dateTo.'</p>
        <table width="100%" style="border-collapse: collapse; margin-top: 8px;">
            <thead>
                <tr  style="background-color: #5c968a;">
                    <th style="border:1px solid #000; padding:10px;" width="10%">#</th>
                    <th style="border:1px solid #000; padding:10px;">ชื่อของรางวัล</th>
                    <th style="border:1px solid #000; padding:10px;" width="20%">จำนวน</th>
                    <th style="border:1px solid #000; padding:10px;" width="20%">ราคารวม</th>
                </tr>
        <tbody>';
        for ($i=0; $i < count($results); $i++) { 
            $num = $i + 1;
            $amount = $amount + $results[$i]['buy_amount'];
            $unit = $unit + $results[$i]['buy_unit'];
            $total = $total + $results[$i]['buy_total'];
            $content .= '
            <tr style="border:1px solid #000;">
				<td style="border:1px solid #000; padding: 7px; text-align:center;"  >'.$num.'</td>
				<td style="border:1px solid #000; padding: 7px;">'.$results[$i]['buy_name'].'</td>
				<td style="border:1px solid #000; padding: 7px; text-align:center;">'.number_format($results[$i]['buy_amount']).'</td>
				<td style="border:1px solid #000; padding: 7px; text-align:right;">'.number_format($results[$i]['buy_total'], 2).'</td>
            </tr>';
        }
        $end = '
        </tbody>
        <tfoot>
            <tr>
                <td colspan="2"  style="padding: 7px; text-align: right; font-weight: bold;">รวม</td>
                <td style="border:1px solid #000; padding:7px; text-align:center;">'.number_format($amount).'</td>
                <td style="border:1px solid #000; padding:7px; text-align:right;">'.number_format($total, 2).'</td>
            </tr>
        </tfoot>
        </table>';
        $mpdf = new \Mpdf\Mpdf([
            'default_font_size' => 8,
            'default_font' => 'Garuda'
        ]);
        $mpdf->WriteHTML($time);
        $mpdf->WriteHTML($head);
        $mpdf->WriteHTML($content);
        $mpdf->WriteHTML($end);
        $mpdf->Output('PDFreportBuying.pdf', \Mpdf\Output\Destination::INLINE);
    }    

    public function reportPay(Request $request)
    {
        $results = "";
        if ($request->user == "all") {            
            $results = Exchangelist::whereBetween('exchange.exchange_updated_at', [$request->dateFrom, $request->dateTo])
            ->where('exchange.exchange_status', $request->status)
            ->leftJoin('exchange', 'exchangelist.exchange_id', 'exchange.exchange_id')
            ->leftJoin('rewards', 'exchangelist.rewards_id', 'rewards.rewards_id')
            ->leftJoin('user', 'exchange.user_id', 'user.user_id')
            ->select('rewards.rewards_name as exchangelist_rewards', 'exchange.exchange_status', 
            DB::raw("count(exchange.exchange_status) as exchangelist_amount"))
            ->groupBy('exchangelist.rewards_id', 'exchange.exchange_status',  'rewards.rewards_name')
            ->get();

            $results[0]['user_username'] = "ทั้งหมด";
        } else {
            $results = Exchangelist::whereBetween('exchange.exchange_updated_at', [$request->dateFrom, $request->dateTo])
            ->where([['exchange.exchange_status', $request->status], ['exchange.user_id', $request->user]])
            ->leftJoin('exchange', 'exchangelist.exchange_id', 'exchange.exchange_id')
            ->leftJoin('rewards', 'exchangelist.rewards_id', 'rewards.rewards_id')
            ->leftJoin('user', 'exchange.user_id', 'user.user_id')
            ->select('user.user_username', 'rewards.rewards_name as exchangelist_rewards', 'exchange.exchange_status', 
                DB::raw("count(exchange.exchange_status) as exchangelist_amount"))
            ->groupBy('user.user_username','exchangelist.rewards_id', 'exchange.exchange_status',  'rewards.rewards_name')
            ->get();
        }    
        $textStatus = ["3"=>'ยังไม่จ่าย',"4"=>'จ่ายแล้ว'];
        $content = "";
        $amount = 0;
        $system = System::find(1);
        $time = '<p style="text-align:right; font-size: 75%;">รายงาน ณ วันที่ '.date("d-m-Y H:i").' น.</p>';
        $head = '
        <p style="text-align:center"><img src="'.'images/system/'.$system["system_logo"].'" width="100"></p>
        <h2 style="text-align:center; margin: 0;">'.$system['system_name'].'</h2>
        <p style="text-align:center; margin-top: -6px;">'.$system['system_address'].'</p>
        <h3 style="text-align:center; margin: 0;">รายงานจ่ายของรางวัล</h3>
        <p style="text-align:center; margin-top: -6px; padding:0px;"><b>สถานะ: </b>'.$textStatus[$results[0]['exchange_status']].'</p>
        <p style="text-align:center; margin: 0px; padding:0px;">ผู้ใช้ : '.$results[0]['user_username'].'</p>
        <p style="text-align:center; margin: 0px; padding:0px;">ตั้งแต่วันที่ '. $request->dateFrom.' ถึง '. $request->dateTo.'</p>
        <table width="100%" style="border-collapse: collapse; margin-top: 8px;">
            <thead>
                <tr  style="background-color: #5c968a;">
                    <th style="border:1px solid #000; padding:10px;" width="10%">#</th>
                    <th style="border:1px solid #000; padding:10px;">ชื่อของรางวัล</th>
                    <th style="border:1px solid #000; padding:10px;">สถานะ</th>
                    <th style="border:1px solid #000; padding:10px;" width="20%">จำนวน</th>
                </tr>
        <tbody>';
        for ($i=0; $i < count($results); $i++) { 
            $num = $i + 1;
            $amount = $amount + $results[$i]['exchangelist_amount'];
            $content .= '
            <tr style="border:1px solid #000;">
				<td style="border:1px solid #000; padding: 7px; text-align:center;"  >'.$num.'</td>
				<td style="border:1px solid #000; padding: 7px;">'.$results[$i]['exchangelist_rewards'].'</td>
				<td style="border:1px solid #000; padding: 7px;">'.$textStatus[$results[$i]['exchange_status']].'</td>
				<td style="border:1px solid #000; padding: 7px; text-align:center;">'.number_format($results[$i]['exchangelist_amount']).'</td>
            </tr>';
        }
        $end = '
        </tbody>
        <tfoot>
            <tr>
                <td colspan="3" style="padding: 7px; text-align: right; font-weight: bold;">รวม</td>
                <td style="border:1px solid #000; padding:7px; text-align:center;">'.number_format($amount).'</td>
            </tr>
        </tfoot>
        </table>';
        $mpdf = new \Mpdf\Mpdf([
            'default_font_size' => 8,
            'default_font' => 'Garuda'
        ]);
        $mpdf->WriteHTML($time);
        $mpdf->WriteHTML($head);
        $mpdf->WriteHTML($content);
        $mpdf->WriteHTML($end);
        $mpdf->Output('PDFreportPay.pdf', \Mpdf\Output\Destination::INLINE);

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
        $textStatus = ["0" => "ยกเลิก", "1" => "ขอแลก", "2" => "เตรียมของ", "3" => "พร้อมจ่าย", "5" => "ตัดสิทธิ์"];
        $content = "";
        $unit = 0;
        $system = System::find(1);
        $time = '<p style="text-align:right; font-size: 75%;">รายงาน ณ วันที่ '.date("d-m-Y H:i").' น.</p>';
        $head = '
        <p style="text-align:center"><img src="'.'images/system/'.$system["system_logo"].'" width="100"></p>
        <h2 style="text-align:center; margin: 0;">'.$system['system_name'].'</h2>
        <p style="text-align:center; margin-top: -6px;">'.$system['system_address'].'</p>
        <h3 style="text-align:center; margin: 0;">รายงานขอแลกของรางวัล</h3>
        <p style="text-align:center; margin-top: -6px; padding:0px;"><b>สถานะ: </b>'.$textStatus[$results[0]['exchange_status']].'</p>
        <p style="text-align:center; margin-top: -6px">ตั้งแต่วันที่ '. $request->dateFrom.' ถึง '. $request->dateTo.'</p>
        <table width="100%" style="border-collapse: collapse; margin-top: 8px;">
            <thead>
                <tr style="background-color: #5c968a;">
                    <th style="border:1px solid #000; padding:10px;" width="10%">#</th>
                    <th style="border:1px solid #000; padding:10px;">ชื่อผู้แลกของรางวัล</th>
                    <th style="border:1px solid #000; padding:10px;">สถานะ</th>
                    <th style="border:1px solid #000; padding:10px;" width="20%">จำนวน</th>
                </tr>
        <tbody>';
        for ($i=0; $i < count($results); $i++) { 
            $num = $i + 1;
            $unit = $unit + $results[$i]['exchangelist_unit'];
            $content .= '
            <tr style="border:1px solid #000;">
				<td style="border:1px solid #000; padding: 7px; text-align:center;"  >'.$num.'</td>
				<td style="border:1px solid #000; padding: 7px;">'.$results[$i]['exchangelist_username'].'</td>
				<td style="border:1px solid #000; padding: 7px;">'.$textStatus[$results[$i]['exchange_status']].'</td>
				<td style="border:1px solid #000; padding: 7px; text-align:center;">'.number_format($results[$i]['exchangelist_unit']).'</td>
            </tr>';
        }
        $end = '
        </tbody>
        <tfoot>
            <tr>
                <td colspan="3" style="padding: 7px; text-align: right; font-weight: bold;">รวม</td>
                <td style="border:1px solid #000; padding:7px; text-align:center;">'.number_format($unit).'</td>
            </tr>
        </tfoot>
        </table>';
        $mpdf = new \Mpdf\Mpdf([
            'default_font_size' => 8,
            'default_font' => 'Garuda'
        ]);
        $mpdf->WriteHTML($time);
        $mpdf->WriteHTML($head);
        $mpdf->WriteHTML($content);
        $mpdf->WriteHTML($end);
        $mpdf->Output('PDFreportExchange.pdf', \Mpdf\Output\Destination::INLINE);

    }
}

