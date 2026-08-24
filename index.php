<!DOCTYPE html>
<html lang="en">
<head>
  <meta name="viewport" content="width=device-width">
  <meta charset="UTF-8" />
  <meta http-equiv="X-UA-Compatible" content="IE=edge" />
  <title>Master Script (official)</title>
  <link rel="icon" href="https://4kwallpapers.com/images/walls/thumbs_2t/13681.png" type="image/x-icon" />
  <link href="https://fonts.googleapis.com/css2?family=Bree+Serif&family=Lobster&family=Righteous&display=swap" rel="stylesheet" />
  <style>
    * {
      margin: 0;
      padding: 0;
      box-sizing: border-box;
      font-family: 'Bree Serif', serif;
    }

    body {
      background: url('https://images.unsplash.com/photo-1496309732348-3627f3f040ee') no-repeat center center fixed;
      background-size: cover;
      display: flex;
      align-items: center;
      justify-content: center;
      height: 100vh;
    }

    .main-container {
      background: rgba(255, 255, 255, 0.1);
      backdrop-filter: blur(12px);
      border-radius: 20px;
      padding: 30px;
      width: 90%;
      max-width: 400px;
      box-shadow: 0 0 20px rgba(0, 0, 0, 0.3);
      text-align: center;
    }

    .heading-text {
      font-size: 30px;
      color: #fff;
      font-family: 'Lobster', cursive;
      margin-bottom: 20px;
    }

    .login-head img {
      width: 100%;
      border-radius: 15px;
      margin-bottom: 20px;
    }

    .input-container {
      position: relative;
      margin-bottom: 20px;
      text-align: left;
    }

    .input-container input {
      width: 100%;
      padding: 10px;
      background: transparent;
      border: none;
      border-bottom: 2px solid #fff;
      color: #fff;
      font-size: 16px;
      outline: none;
    }

    .input-container label {
      position: absolute;
      top: 50%;
      left: 10px;
      transform: translateY(-50%);
      color: #ccc;
      pointer-events: none;
      transition: 0.3s ease;
    }

    .input-container input:focus + label,
    .input-container input:not(:placeholder-shown) + label {
      top: -10px;
      font-size: 12px;
      color: #00e6e6;
    }

    .button {
      width: 100%;
      padding: 10px;
      background: #00e6e6;
      border: none;
      border-radius: 25px;
      color: #000;
      font-size: 18px;
      font-weight: bold;
      cursor: pointer;
      transition: background 0.3s;
    }

    .button:hover {
      background: #00cccc;
    }

    .telegram-link {
      display: inline-block;
      margin-top: 20px;
      padding: 10px 20px;
      background: #0088cc;
      color: #fff;
      text-decoration: none;
      border-radius: 20px;
      font-weight: bold;
      transition: background 0.3s;
    }

    .telegram-link:hover {
      background: #0072aa;
    }

    .msg {
      margin-top: 20px;
      padding: 12px;
      border-radius: 8px;
      font-weight: bold;
      font-size: 15px;
      color: white;
    }

    .success {
      background-color: rgba(0, 255, 0, 0.2);
      color: #00ff00;
      border: 1px solid #00ff00;
    }

    .error {
      background-color: rgba(255, 0, 0, 0.2);
      color: #ff4d4d;
      border: 1px solid #ff4d4d;
    }
  </style>
</head>
<body>
  <div class="main-container">
    <div class="heading-text">Just Reward - Anytime Ruppes </div>
    <div class="login-head">
      <!-- Optional image or banner -->
    </div>

    <?php
    error_reporting(0);
    if (!isset($_GET['sub'])) {
      echo '
      <form action="" method="GET" autocomplete="off">
        <div class="input-container">
          

        <div class="input-container">
          <input type="text" name="refer" id="refer" placeholder=" https://t.clickscot.com" required />
          
        </div>

        <button type="submit" class="button" name="sub" value="SUBMIT">Submit</button>
      </form>

      

';
    } else {
    
$refer = $_GET['refer'];
{ $url = $refer;

$nn = explode('p1=', $url)[1];
$clickid = explode(';', $nn)[0];

if (empty($clickid)) {
            
echo "<div class='button'><center>Invalid Task Link.</center></div>";

echo "<meta http-equiv='refresh' content='1;url=https://t.me/masterscript'>";


} else {

$url5 = "http://postback.milengine.com/?clickid=$clickid";  
           
$headers5 = ['User-Agent: HttpCanary/3.3.6',

'in.o18.click',

'Accept: */*'];

$ch77 = curl_init();
curl_setopt($ch77,CURLOPT_URL, $url5);curl_setopt($ch77, CURLOPT_CUSTOMREQUEST, "GET");
curl_setopt($ch77, CURLOPT_RETURNTRANSFER, 1);
curl_setopt($ch77,
CURLOPT_HTTPHEADER,$headers5);
curl_setopt($ch77, CURLOPT_ENCODING, 'deflate,gzip');
curl_setopt($ch77, CURLOPT_SSL_VERIFYPEER, false);
$output1 = curl_exec($ch77);
curl_close($ch77);$json1 = json_decode($output1, true);

$status = $json1["status"];
$message = $json1["message"];
$limit = $json1["limit"];

$url6 = "https://adcounty.vnative.co/acquisition?click_id=$clickid&security_token=d3117dafc420ac09ef6b&revenue={revenue}&goal_value=in_app_purchase_mtha_cnr";

$headers6 = ['User-Agent: HttpCanary/3.3.6',

'in.o18.click',

'Accept: */*'];

$ch77 = curl_init();
curl_setopt($ch77,CURLOPT_URL, $url6);curl_setopt($ch77, CURLOPT_CUSTOMREQUEST, "GET");
curl_setopt($ch77, CURLOPT_RETURNTRANSFER, 1);
curl_setopt($ch77,
CURLOPT_HTTPHEADER,$headers6);
curl_setopt($ch77, CURLOPT_ENCODING, 'deflate,gzip');
curl_setopt($ch77, CURLOPT_SSL_VERIFYPEER, false);
$output1 = curl_exec($ch77);
curl_close($ch77);$json1 = json_decode($output1, true);

$status = $json1["status"];
$message = $json1["message"];
$limit = $json1["limit"];


echo "<div class='button'><font style='font-weight:1000' color='Blue'>Task Bypass Successfully.!! $message</font></div>";
}}}

?>