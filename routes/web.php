<?php

/*
|--------------------------------------------------------------------------
| Application Routes
|--------------------------------------------------------------------------
|
| Here is where you can register all of the routes for an application.
| It is a breeze. Simply tell Lumen the URIs it should respond to
| and give it the Closure to call when that URI is requested.
|
*/

$router->get('/', function () use ($router) {
    return $router->app->version();
});

$router->get('/key', function () use ($router) {
    return str_random(32);
});

# AuthController (ผู้ใช้งาน, สมัครสมาชิก)
$router->post('/auth', 'AuthController@auth');       # App      # ตรวจสอบสิทธิ์ผู้ใช้แอพพลิเคชั่น
$router->post('/authAdmin', 'AuthController@authAdmin');        # Web      # ตรวจสอบสิทธิ์ผู้ใช้เว็บ
$router->post('/register', 'AuthController@register');       # App    # สมัครสมาชิกเข้าใช้แอพพลิเคชั่น 
$router->get('/findUser/{id}', 'AuthController@find');       # App  # Web       # ดึงข้อมูลของผู้ใช้ตาม ID
$router->post('/getUser', 'AuthController@user');       # Web       # ดึงข้อมูลของผู้ใช้ทั้งหมด
$router->post('/setUser', 'AuthController@create');     # Web       # เพิ่มผู้ใช้งาน
$router->post('/putUser', 'AuthController@edit');       # Web       # แก้ไขข้อมูลผู้ใช้งาน
$router->put('/putUserstatus/{id}', 'AuthController@userStatus');     # Web       # แก้ไขสถานะผู้ใช้งาน
$router->delete('/deleteUser/{id}', 'AuthController@delete');     # Web       # เพิ่มผู้ใช้งาน
$router->post('/editProfile/{id}', 'AuthController@editProfile');       # App       # แก้ไขโปรไฟล์ผู้ใช้งาน
$router->post('/editImageProfile/{id}', 'AuthController@editImageProfile');       # App       # แก้ไขโปรไฟล์ผู้ใช้งาน
/*--------------------------------------------------------------------------------------------------------------------------------*/

# RewardsController (ของรางวัล, ซื้อของรางวัล)
$router->get('/getRewards', 'RewardsController@getRewards');       # Web       # ดึงข้อมูลของรางวัลทั้งหมด
$router->get('/findRewards/{id}', 'RewardsController@findRewards');        # App  # Web        # ดึงข้อมูลของรางวัลตาม ID
$router->post('/setRewards', 'RewardsController@addRewards');       # Web       # เพิ่มของรางวัล
$router->put('/putRewardsStatus', 'RewardsController@putStatusRewards');        # Web       # แก้ไขสถานะของรางวัล
$router->post('/putRewards/{id}', 'RewardsController@edit');        # Web       # แก้ไขของรางวัล
$router->delete('/deleteRewards/{id}', 'RewardsController@delete');     # Web       # ลบของรางวัล
$router->get('/getRewardsApp', 'RewardsController@getRewardsApp');      # App       # ดึงข้อมูลของรางวัลในแอพ
$router->get('/checkRewards/{id}', 'RewardsController@checkRewards');      # App       # เช็คของรางวัล
$router->post('/inBasket', 'RewardsController@inBasket');        # App       # ดึงข้อมูลของรางวัลที่อยู่ในตะกร้า
$router->get('/latestRewards', 'RewardsController@LatestRewards');      #App        # ดึงข้อมูลของรางวัล 5 รายการ

$router->get('/getBuying', 'RewardsController@getBuying');       # Web       # ดึงข้อมูลซื้อของรางวัลทั้งหมด
$router->get('/getBuylist/{id}', 'RewardsController@getBuylist');        # Web       # ดึงข้อมูลรายการซื้อของรางวัลตาม ID ข้อมูลซื้อของรางวัล
$router->get('/getRewardsName', 'RewardsController@getRewardsName');       # Web       # ดึงข้อมูลชื่อของรางวัลทั้งหมด
$router->post('/setBuying/{id}', 'RewardsController@addBuying');     # Web     # เพื่อข้อมูลการซื้อของรางวัล
/*--------------------------------------------------------------------------------------------------------------------------------*/

# ExchangeController (ขอแลกของรางวัล, จ่ายของรางวัล)
$router->post('/exchangeRewards/{id}', 'ExchangeController@exchangeRewards');
$router->get('/getExchangeUser/{id}', 'ExchangeController@getExchangeUser');
$router->post('/getExchangeUser_Status/{id}', 'ExchangeController@getExchangeUser_Status');

$router->get('/getExchange', 'ExchangeController@getExchange');
$router->get('/getExchangeNotFinish', 'ExchangeController@getNotFinish');
$router->get('/getExchangelist/{id}', 'ExchangeController@getExchangelist');
$router->put('/putExchangeStatus', 'ExchangeController@setExchangeStatus');
$router->put('/putExchangelistStatus', 'ExchangeController@setExchangelistStatus');
$router->put('/cancelExchange', 'ExchangeController@cancelExchange');
$router->put('/cancelExchangelist', 'ExchangeController@cancelExchangelist');
/*--------------------------------------------------------------------------------------------------------------------------------*/

# SellGerbageController (, )
$router->get('/getSellGarbage', 'SellGarbageController@getSellGarbage');        # Web       # ดึงข้อมูลขายขยะ
$router->post('/setSellGarbage/{id}', 'SellGarbageController@setSellGarbage');      # Web       # เพิ่มข้อมูลขายขยะ
/*--------------------------------------------------------------------------------------------------------------------------------*/

# SmartbinController (, )
$router->get('/getSmartbin', 'SmartbinController@getSmartbin');
$router->get('/findSmartbin/{id}', 'SmartbinController@findSmartbin');
$router->post('/setSmartbin', 'SmartbinController@setSmartbin');
$router->put('/putSmartbinStatus', 'SmartbinController@putSmartbinStatus');
$router->put('/putSmartbin', 'SmartbinController@putSmartbin');
$router->delete('/deleteSmartbin/{id}', 'SmartbinController@deleteSmartbin');

$router->post('/qrLogged/{hostname}', 'SmartbinController@qrLogged');
$router->get('/onPoints/{id}', 'SmartbinController@onPoints');
$router->get('/getAmountBottle/{id}', 'SmartbinController@getAmountBottle');
$router->post('/addPoints/{id}', 'SmartbinController@addPoints');
$router->get('/pointsHistory/{id}', 'SmartbinController@pointsHistory');

$router->post('/checkSmartbin', 'SmartbinController@checkSmartbin');
$router->get('/checkStatus/{id}', 'SmartbinController@checkStatus');
$router->get('/checkLogin/{id}', 'SmartbinController@checkLogin');
$router->get('/checkStatusLogin', 'SmartbinController@checkStatusLogin');
$router->post('/updatePoints', 'SmartbinController@updatePoints');
$router->post('/logoutSmartbin', 'SmartbinController@logout');

$router->get('/getAlertSmartbin', 'SmartbinController@getAlertSmartbin');
$router->post('/checkAlertSmartbin', 'SmartbinController@checkAlertSmartbin');
$router->post('/setAlertSmartbin', 'SmartbinController@setAlertSmartbin');
$router->post('/alertSmartbin', 'SmartbinController@alertSmartbin');
/*--------------------------------------------------------------------------------------------------------------------------------*/

# SystemController (ข้อมูลระบบ, การทำงานระบบ)
$router->get('/getSystem', 'SystemController@getSystem');
$router->post('/putSystem', 'SystemController@putSystem');
$router->post('/reportExchange', 'SystemController@reportExchange');
$router->post('/reportPay', 'SystemController@reportPay');
$router->post('/getUsernameInReportPay', 'SystemController@getUsernameInReportPay');
$router->post('/reportCumulative', 'SystemController@reportCumulative');
$router->post('/reportAlertSmartbin', 'SystemController@reportAlertSmartbin');
$router->post('/reportSellGarbage', 'SystemController@reportSellGarbage');
$router->post('/reportBuy', 'SystemController@reportBuy');
/*--------------------------------------------------------------------------------------------------------------------------------*/

route::get('/PDFreportAlertSmartbin', 'PdfController@reportAlertSmartbin');
route::get('/PDFreportBuy', 'PdfController@reportBuy');
route::get('/PDFreportPay', 'PdfController@reportPay');
route::get('/PDFreportExchange', 'PdfController@reportExchange');
route::get('/PDFreportSellGarbage', 'PdfController@reportSellGarbage');
route::get('/PDFreportCumulative', 'PdfController@reportCumulative');
/*--------------------------------------------------------------------------------------------------------------------------------*/
$router->post('/upload/image/mobile', 'SystemController@setImageMobile');