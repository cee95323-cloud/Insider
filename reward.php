<?php
session_start();
set_time_limit(0);

if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    session_unset();
    session_destroy();
    session_start();
    $_SESSION['step'] = 1;
}

$current_step = isset($_SESSION['step']) ? $_SESSION['step'] : 1;
$error_msg = "";
$success_msg = "";
$already_claimed = false;

class CremicaBot {
    private $BASE_URL = "https://cremicabacktoschool.woohoo.in/api/users";
    private $dataKey = "bqZ9KXzIl63mx1Nz6dKA55";
    private $cookieFile;
    private $botUA;

    public function __construct() {
        $this->cookieFile = sys_get_temp_dir() . '/cremica_cookie_' . session_id() . '.txt';
        
        if (!isset($_SESSION['bot_ua'])) {
            $agents = [
                "Mozilla/5.0 (Linux; Android 14; SM-S918B) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/124.0.6367.113 Mobile Safari/537.36",
                "Mozilla/5.0 (Linux; Android 13; Pixel 7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/123.0.6312.80 Mobile Safari/537.36",
                "Mozilla/5.0 (Linux; Android 14; 23049PCD8G) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.6261.119 Mobile Safari/537.36",
                "Mozilla/5.0 (Linux; Android 12; M2012K11AG) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/121.0.6167.101 Mobile Safari/537.36",
                "Mozilla/5.0 (Linux; Android 13; CPH2451) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/120.0.6099.230 Mobile Safari/537.36"
            ];
            $_SESSION['bot_ua'] = $agents[array_rand($agents)];
        }
        $this->botUA = $_SESSION['bot_ua'];
    }

    private function generateRandomName() {
        $firstNamesStr = "Aarav,Vihaan,Vivaan,Ananya,Diya,Advik,Kabir,Anika,Navya,Ayaan,Shaurya,Myra,Ira,Ahaana,Dhruv,Tara,Saanvi,Rohan,Aditya,Arjun,Neha,Priya,Rahul,Amit,Sneha,Pooja,Vikram,Riya,Karan,Simran,Yash,Kriti,Dev,Nisha,Ravi,Ishita,Manoj,Kavya,Siddharth,Meera,Aryan,Tanya,Rishi,Swati,Gaurav,Nidhi,Harsh,Shruti,Varun,Aarti,Akash,Alok,Aman,Anil,Ankit,Ansh,Anurag,Arvind,Ashish,Ashok,Atul,Ayush,Bharat,Bhuvan,Chetan,Chirag,Darshan,Deepak,Dinesh,Divansh,Eshan,Farid,Ganesh,Girish,Gopal,Hari,Hemant,Hitesh,Ishan,Jatin,Jay,Jitendra,Kailash,Kamal,Kapil,Kartik,Kiran,Kishore,Krish,Kunal,Laksh,Lalit,Lokesh,Madhav,Mahesh,Manish,Mayank,Milan,Mohit,Mukesh,Nakul,Naman,Naveen,Neeraj,Nikhil,Nilay,Nitin,Om,Pankaj,Paras,Parth,Pawan,Piyush,Prabhat,Pradeep,Prakash,Pramod,Pranav,Pranay,Prateek,Praveen,Prem,Puneet,Raghav,Raj,Rajan,Rajat,Rakesh,Ram,Ramesh,Ranjit,Ritesh,Riyansh,Roshan,Sachin,Sagar,Sahil,Samir,Sanjay,Sanjeev,Sanket,Sarthak,Satish,Saurabh,Shivam,Shreyas,Shubham,Soham,Somesh,Sourav,Sumeet,Sunil,Suraj,Suresh,Surya,Sushant,Tanmay,Tarun,Tejas,Tushar,Uday,Utkarsh,Vaibhav,Vedant,Veer,Vikas,Vinay,Vineet,Vinod,Vipin,Vishal,Vivek,Yogesh";
        $surnamesStr = "Sharma,Verma,Gupta,Patel,Singh,Kumar,Das,Bose,Chowdhury,Nair,Reddy,Rao,Iyer,Joshi,Mishra,Pandey,Tiwari,Dubey,Yadav,Chauhan,Rajput,Bhatia,Kaur,Mehta,Desai,Shah,Agarwal,Bansal,Garg,Jain,Malhotra,Kapoor,Ahuja,Chawla,Sethi,Bhardwaj,Kashyap,Goyal,Mittal,Sen,Menon,Pillai,Krishnan,Hegde,Shetty,Nadar,Venkatesh,Naidu,Thakur,Bhatt,Acharya,Adhikari,Agrawal,Ahluwalia,Amble,Anand,Apte,Arora,Arya,Asrani,Asthana,Atri,Awasthi,Bagchi,Bahl,Baidya,Bakshi,Balakrishnan,Balasubramanian,Bandyopadhyay,Banerjee,Banik,Barman,Barua,Basu,Batra,Bawa,Bedi,Behera,Bhagat,Bhandari,Bharadwaj,Bhargava,Bhasin,Bhatnagar,Bhattacharya,Bhowmick,Bhuvan,Biswal,Biswas,Bora,Borah,Brahmbhatt,Buch,Chadha,Chakrabarti,Chakraborty,Chakravarty,Chanda,Chandra,Chandran,Chatterjee,Chaturvedi,Chaudhari,Chettri,Chhabra,Chhikara,Chidambaram,Chitre,Chopra,Choudhary,Choudhury,Chowdary,Dahiya,Dalal,Dani,Dar,Dasgupta,Dash,Datt,Datta,Dave,Dayal,De,Deb,Debnath,Deka,Deshmukh,Deshpande,Devan,Dewan,Dey,Dhaliwal,Dhar,Dhawan,Dhillon,Dhingra,Dixit,Dogra,Doshi,Dravid,Duggal,Dutt,Dutta,Dwivedi,Eswaran,Fernandes,Gadgil,Gaikwad,Gandhi,Gangopadhyay,Ganguly,Gawande,Ghosh,Gill,Godbole,Goel,Gokhale,Goswami,Grover,Guha,Gulati,Gurung,Halder,Handa,Hans,Hariharan,Hooda,Jadhav,Jaiswal,Jha,Jindal,Johar,Johri,Kadam,Kahlon,Kak,Kakar,Kakati,Kale,Kalita,Kalyan,Kamath,Kamble,Kandpal,Kannan,Kant,Kapadia,Kapur,Kar,Karmakar,Karnik,Katiyar,Kaushik,Kelkar,Khan,Khanna,Khare,Khatri,Khosla,Khurana,Kini,Kohli,Kothari,Kulkarni,Kumawat,Kurien,Kushwaha,Lahiri,Lall,Lamba,Lodha,Lohar,Lokhande,Luthra,Madan,Mahajan,Mahanti,Mahapatra,Maheshwari,Maiti,Maitra,Majhi,Majumdar,Malakar,Malaviya,Malik,Mallick,Mandal,Mandlik,Mane,Mangal,Mangeshkar,Mani,Marathe,Marwah,Master,Mathur,Maurya,Mehra,Mehrotra,Memon,Merchant,Misra,Mistry,Mitra,Modi,Mohanty,Mohapatra,Mohite,Mondal,Monga,More,Mudaliar,Mukherjee,Mukhopadhyay,Munshi,Murthy,Murugesan,Nadkarni,Nag,Nagar,Nagarajan,Nagpal,Naik,Nanda,Nandi,Narang,Narasimhan,Narayan,Narayanan,Narula,Natarajan,Nath,Nayak,Nayar,Nayyar,Nazir,Negi,Nehra,Nigam,Nikhra,Nimbalkar,Niranjan,Niyogi,Oak,Oberoi,Oza,Pachaury,Padhi,Padmanabhan,Pagare,Pahwa,Pai,Pal,Palan,Palekar,Paliwal,Panchal,Panda,Pande,Pandit,Pandya,Panicker,Panigrahi,Panjwani,Pant,Parakh,Paramshiv,Parashar,Parekh,Parikh,Parmar,Parthasarathy,Paswan,Pathak,Patil,Patnaik,Patra,Pattnaik,Patwardhan,Paul,Pawar,Pendse,Phadke,Phogat,Pinto,Poddar,Poojary,Prabhu,Pradhan,Prajapati,Prakash,Prasad,Prasanna,Pratap,Pugal,Pujari,Punia,Puri,Purohit,Radhakrishnan,Raghavan,Raghunathan,Raha,Raheja,Rahman,Rai,Raj,Rajagopal,Rajagopalan,Rajan,Rajaraman,Rajguru,Rajpurohit,Raju,Ram,Ramachandran,Ramakrishnan,Raman,Ramanathan,Ramaswamy,Ramesh,Rana,Randhawa,Rane,Ranganathan,Rastogi,Rathi,Rathod,Rathore,Raut,Raval,Ravi,Ravindran,Rawat,Ray,Rege,Rekhi,Roy,Sabharwal,Sachdev,Sachdeva,Sadafule,Sadhu,Saha,Sahai,Sahani,Sahay,Sahoo,Sahu,Sai,Saini,Sakpal,Saldanha,Salvi,Samant,Samantaray,Sampath,Samuel,Sandhu,Sane,Sangma,Santhosh,Sanyal,Sapre,Sarangi,Saraswat,Sardar,Sareen,Sarin,Sarkar,Sarma,Sarna,Sasidharan,Sastry,Satam,Sathe,Sathyanarayanan,Satpathy,Savant,Sawant,Saxena,Sehgal,Sekhar,Sengupta,Seshadri,Seth,Shaikh,Shanker,Shanmugam,Shastri,Shekhawat,Shenoy,Shergill,Shinde,Shirke,Shrivastava,Shroff,Shukla,Sibal,Sikka,Singhal,Singhania,Sinha,Sircar,Sitaraman,Sivakumar,Sivan,Sodhi,Solanki,Somani,Soni,Sood,Sridhar,Srinivasan,Srivastava,Subrahmanyam,Subramaniam,Subramanian,Sud,Suman,Sundaram,Sunder,Sur,Suresh,Suri,Suryavanshi,Swami,Swaminathan,Swamy,Syed,Talwar,Tambe,Tandon,Taneja,Tank,Tara,Taraporevala,Tare,Tariq,Tawde,Teli,Tendulkar,Tewari,Thakkar,Thangavel,Thomas,Thota,Tikku,Tomar,Tripathi,Trivedi,Tulpule,Tuteja,Tyagi,Upadhyay,Upreti,Urs,Vaghela,Vaidya,Vaish,Vaishnav,Vajpeyi,Vakil,Varghese,Varma,Varshney,Vartak,Vasan,Vashisht,Vasudevan,Vats,Ved,Veer,Velayudhan,Vengurlekar,Venkataraman,Venkatesan,Vidyarthi,Vig,Vij,Vijay,Vijayakumar,Vora,Vyas,Wadhwa,Wadhwani,Wagle,Walia,Wankhede,Yash,Yellapragada,Yerram,Yohannan,Yonzon,Zacharia,Zaveri,Zeliang,Zutshi";

        $firstNames = explode(',', $firstNamesStr);
        $surnames = explode(',', $surnamesStr);
        
        return $firstNames[array_rand($firstNames)] . ' ' . $surnames[array_rand($surnames)];
    }

    private function getTimestamp() { 
        return round(microtime(true) * 1000); 
    }

    private function generateSignatureData($payload, $userKey, $dataKey) {
        $payloadStr = str_replace(['": ', '", '], ['":', '",'], json_encode($payload));
        $a = base64_encode($payloadStr);
        $ts = (string)$payload['t'];
        $u = base64_encode($ts);
        
        $hmacKey = substr($dataKey, 4, 14);
        $message = "$u.$a";
        
        $hexSig = hash_hmac('sha256', $message, $hmacKey);
        $f = base64_encode($hexSig);
        
        $m = mt_rand(1, 6); 
        $k = mt_rand(2, 8);
        $alphabet = 'ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz0123456789';
        $hRand = substr(str_shuffle($alphabet), 0, $k);
        
        $g = $k . $m . substr($f, 0, $m) . $hRand . substr($f, $m);
        
        return "userKey=" . urlencode($userKey) . "&data=" . urlencode($u) . "." . urlencode($a) . "." . urlencode($g);
    }

    private function decryptResponse($encryptedResp) {
        try { 
            return json_decode(base64_decode($encryptedResp), true); 
        } catch (Exception $e) { 
            return []; 
        }
    }

    private function apiRequest($method, $path, $body, $headers = []) {
        $ch = curl_init($this->BASE_URL . $path);
        
        $defaultHeaders = [
            "accept: application/json, text/plain, */*",
            "accept-language: en-US,en;q=0.9,hi;q=0.8",
            "accept-encoding: gzip, deflate, br",
            "sec-ch-ua: \"Not A(Brand\";v=\"8\", \"Chromium\";v=\"132\", \"Google Chrome\";v=\"132\"",
            "sec-ch-ua-mobile: ?1",
            "sec-ch-ua-platform: \"Android\"",
            "sec-fetch-dest: empty",
            "sec-fetch-mode: cors",
            "sec-fetch-site: same-origin",
            "origin: https://cremicabacktoschool.woohoo.in",
            "user-agent: " . $this->botUA
        ];
        
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, array_merge($defaultHeaders, $headers));
        
        if ($method === 'POST') { 
            curl_setopt($ch, CURLOPT_POST, true); 
            curl_setopt($ch, CURLOPT_POSTFIELDS, $body); 
        }
        
        curl_setopt($ch, CURLOPT_COOKIEJAR, $this->cookieFile);
        curl_setopt($ch, CURLOPT_COOKIEFILE, $this->cookieFile);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
        curl_setopt($ch, CURLOPT_ENCODING, "gzip, deflate, br");
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        
        return [$httpCode, $response];
    }

    private function apiFormPost($path, $payload, $userKey, $dataKey, $referer = "/", $accessToken = null) {
        $ts = $this->getTimestamp();
        $payload['t'] = $ts; 
        $payload['userKey'] = $userKey;
        
        $body = $this->generateSignatureData($payload, $userKey, $dataKey);
        
        $headers = [
            "content-type: application/x-www-form-urlencoded; charset=UTF-8", 
            "referer: https://cremicabacktoschool.woohoo.in" . $referer
        ];
        
        if ($accessToken) {
            $headers[] = "authorization: Bearer $accessToken";
        }
        
        $fullPath = "$path/$userKey?t=$ts";
        list($status, $resp) = $this->apiRequest('POST', $fullPath, $body, $headers);
        
        $respJson = json_decode($resp, true);
        
        if (isset($respJson['resp'])) {
            $result = $this->decryptResponse($respJson['resp']);
            $result['statusCode'] = $status;
            return $result;
        }

        return $respJson ? array_merge($respJson, ['statusCode' => $status]) : ['statusCode' => $status, 'raw' => $resp];
    }

    public function initAndRegister($mobile) {
        list($status, $initRespRaw) = $this->apiRequest('POST', "", '{"utm_source":"qrcode"}', ["content-type: application/json;charset=UTF-8", "referer: https://cremicabacktoschool.woohoo.in/?utm_source=qrcode"]);
        $initResp = json_decode($initRespRaw, true);
        
        $userKey = isset($initResp['userKey']) ? $initResp['userKey'] : (isset($initResp['data']['userKey']) ? $initResp['data']['userKey'] : (isset($initResp['data']['id']) ? $initResp['data']['id'] : null));
        
        if (!$userKey) {
            $userKey = "693230728";
        }
        
        $_SESSION['userKey'] = (string)$userKey;
        $name = $this->generateRandomName();

        $this->apiFormPost('/clickTrack', ["type" => "GET_STARTED"], $_SESSION['userKey'], $this->dataKey, "/?utm_source=qrcode");
        $reg = $this->apiFormPost('/register', ["name" => $name, "mobile" => $mobile], $_SESSION['userKey'], $this->dataKey, "/registration");
        
        if (isset($reg['statusCode']) && ($reg['statusCode'] == 200 || $reg['statusCode'] == 201)) {
            return true;
        }
        if (isset($reg['status']) && ($reg['status'] === 'SUCCESS' || $reg['status'] === true)) {
            return true;
        }
        return false;
    }

    public function verifyOTP($otp) {
        $userKey = $_SESSION['userKey'];
        $cleanOtp = preg_replace('/[^0-9]/', '', (string)$otp);
        $otpResp = $this->apiFormPost('/verifyOTP', ["otp" => (string)$cleanOtp], $userKey, $this->dataKey, "/registration");
        
        $token = isset($otpResp['accessToken']) ? $otpResp['accessToken'] : (isset($otpResp['data']['accessToken']) ? $otpResp['data']['accessToken'] : (isset($otpResp['token']) ? $otpResp['token'] : null));
        
        if ($token) {
            $_SESSION['bearerToken'] = $token;
            return true;
        }

        if (isset($otpResp['statusCode']) && $otpResp['statusCode'] == 200 && !isset($otpResp['error']) && !isset($otpResp['message'])) {
            return true;
        }

        return false;
    }

    public function playGame($targetScore) {
        $userKey = $_SESSION['userKey'];
        $accessToken = isset($_SESSION['bearerToken']) ? $_SESSION['bearerToken'] : null;
        
        $states = ["Andhra Pradesh", "Arunachal Pradesh", "Assam", "Bihar", "Chhattisgarh", "Delhi", "Goa", "Gujarat", "Haryana", "Himachal Pradesh", "Jharkhand", "Karnataka", "Kerala", "Madhya Pradesh", "Maharashtra", "Manipur", "Meghalaya", "Mizoram", "Nagaland", "Odisha", "Punjab", "Rajasthan", "Sikkim", "Tamil Nadu", "Telangana", "Tripura", "Uttar Pradesh", "Uttarakhand", "West Bengal"];
        $randomState = $states[array_rand($states)];

        $this->apiFormPost('/getBatchCode', ["batchCode" => "CD09G26", "state" => $randomState], $userKey, $this->dataKey, "/registration", $accessToken);
        $startResp = $this->apiFormPost('/startGame', ["gameKey" => null], $userKey, $this->dataKey, "/game/", $accessToken);
        
        $gameKey = isset($startResp['data']['gameKey']) ? $startResp['data']['gameKey'] : (isset($startResp['gameKey']) ? $startResp['gameKey'] : "C1lsxSX3-" . $this->getTimestamp()); 

        $simulatedTime = $targetScore * rand(30, 45);
        $delayTime = min(8, max(3, intval($targetScore / 300)));
        sleep($delayTime);

        $endGamePayload = [
            "gameKey" => $gameKey,
            "score" => (int)$targetScore,
            "time" => $simulatedTime,
            "key1" => "dummy_key1_b1f48df",
            "key2" => "dummy_key2_cd10274",
            "key3" => "dummy_key3_27003ba"
        ];
        
        $endResp = $this->apiFormPost('/endGame', $endGamePayload, $userKey, $this->dataKey, "/game/", $accessToken);
        
        if (isset($endResp['statusCode']) && ($endResp['statusCode'] == 200 || $endResp['statusCode'] == 201)) {
            if (isset($endResp['status']) && ($endResp['status'] === false || $endResp['status'] === 'ERROR' || $endResp['status'] === 'FAILED')) {
                return false;
            }
            return true;
        }
        if (isset($endResp['status']) && ($endResp['status'] === 'SUCCESS' || $endResp['status'] === true)) {
            return true;
        }
        return false;
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $bot = new CremicaBot();

    try {
        if (isset($_POST['action']) && $_POST['action'] === 'register') {
            if ($bot->initAndRegister(trim($_POST['mobile']))) {
                $_SESSION['step'] = 2; 
                $current_step = 2;
                $success_msg = "OTP Sent Successfully!";
            } else {
                $error_msg = "Registration Failed. Please try again.";
            }
        } 
        elseif (isset($_POST['action']) && $_POST['action'] === 'verify_otp') {
            if ($bot->verifyOTP(trim($_POST['otp']))) {
                $randomScore = rand(2000, 2300);
                if ($bot->playGame($randomScore)) {
                    $_SESSION['step'] = 3; 
                    $current_step = 3;
                } else {
                    $already_claimed = true;
                }
            } else {
                $error_msg = "Invalid OTP. Please try again.";
            }
        } 
    } catch (Exception $e) {
        $error_msg = "An error occurred during the process.";
    }
}
?>
<!doctype html>
<html lang="en">
<head> 
    <meta charset="utf-8"> 
    <meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=1"> 
    <title>SpeedX</title> 
    <link rel="preconnect" href="https://fonts.googleapis.com"> 
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet"> 
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet"> 
    <style> 
        *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; } 
        :root { --bg: #f7f7f8; --white: #ffffff; --border: #e5e5ea; --ink: #111118; --ink2: #6c6c80; --ink3: #b0b0c0; --blue: #2563eb; --blue-bg: #eff3ff; --green: #15803d; --green-bg: #f0fdf4; --green-border: #bbf7d0; --red: #b91c1c; --red-bg: #fef2f2; --red-border: #fecaca; --f: 'Inter', sans-serif; --r: 12px; } 
        body { background: var(--bg); color: var(--ink); font-family: var(--f); min-height: 100vh; padding-bottom: 60px; font-size: 14px; line-height: 1.5; -webkit-font-smoothing: antialiased; } 
        .wrap { max-width: 420px; margin: 0 auto; padding: 0 16px; } 
        .hdr { display: flex; align-items: center; justify-content: center; padding: 20px 0 18px; border-bottom: 1px solid var(--border); margin-bottom: 24px; position: relative;} 
        .logo { font-size: 20px; font-weight: 700; letter-spacing: -.3px; } 
        .logo i { color: var(--blue); margin-right: 6px; } 
        .logo span { color: var(--blue); } 
        .fcard { background: var(--white); border: 1px solid var(--border); border-radius: var(--r); padding: 20px 16px; margin-bottom: 12px; animation: fadeIn 0.3s ease; } 
        @keyframes fadeIn { from { opacity: 0; transform: translateY(5px); } to { opacity: 1; transform: translateY(0); } } 
        .fd { margin-bottom: 15px; } 
        .fd label { display: block; font-size: 12px; font-weight: 600; color: var(--ink2); margin-bottom: 6px; } 
        .inp { width: 100%; background: var(--bg); border: 1px solid var(--border); color: var(--ink); border-radius: 8px; padding: 12px; font-size: 14px; font-family: var(--f); outline: none; transition: border-color .15s, box-shadow .15s; } 
        .inp:focus { border-color: var(--blue); box-shadow: 0 0 0 3px rgba(37, 99, 235, .08); } 
        .btn-row { display: flex; gap: 10px; margin-top: 18px; } 
        .btn { padding: 12px 14px; border-radius: 8px; font-size: 14px; font-weight: 600; cursor: pointer; border: none; font-family: var(--f); transition: opacity .15s; display: inline-flex; align-items: center; justify-content: center; gap: 6px; width: 100%; } 
        .btn-main { background: var(--blue); color: #fff; } 
        .btn-main:hover { opacity: .88; } 
        .btn-ghost { background: transparent; border: 1px solid var(--border); color: var(--ink2); } 
        .alert-box { padding: 12px; border-radius: 8px; margin-bottom: 15px; font-size: 13px; font-weight: 600; display: flex; align-items: center; gap: 8px; } 
        .alert-success { background: var(--green-bg); color: var(--green); border: 1px solid var(--green-border); } 
        .alert-error { background: var(--red-bg); color: var(--red); border: 1px solid var(--red-border); } 
        .foot { text-align: center; font-size: 13px; font-weight: 500; color: var(--ink3); padding-top: 30px; margin-top: 20px; } 
    </style>
</head>
<body>
<div class="wrap"> 
    <div class="hdr"> 
        <div class="logo"><i class="fas fa-bolt"></i>Win <span>Rewards</span></div> 
    </div>

    <?php if($already_claimed): ?>
        <div class="fcard" style="text-align: center; padding: 40px 16px;">
            <div style="color: var(--red); font-size: 18px; font-weight: 700;">
                <i class="fas fa-times-circle" style="font-size: 46px; display: block; margin-bottom: 16px;"></i>
                Reward Already Claimed!
            </div>
            <script>
                setTimeout(function() {
                    window.location.href = 'https://t.me/iSpeedX1';
                }, 1500);
            </script>
        </div>

    <?php else: ?>

        <?php if($success_msg && $current_step != 3): ?>
            <div class="alert-box alert-success"><i class="fas fa-check-circle"></i> <?php echo $success_msg; ?></div>
        <?php endif; ?>

        <?php if($error_msg): ?>
            <div class="alert-box alert-error"><i class="fas fa-exclamation-circle"></i> <?php echo $error_msg; ?></div>
        <?php endif; ?>

        <?php if($current_step == 1): ?>
            <div class="fcard"> 
                <form method="POST" onsubmit="this.querySelector('button').innerHTML = '<i class=\'fas fa-spinner fa-spin\'></i> Processing...'; this.querySelector('button').style.opacity = '0.7';"> 
                    <input type="hidden" name="action" value="register">
                    <div class="fd"> 
                        <label>Mobile Number</label> 
                        <input type="number" name="mobile" class="inp" placeholder="Enter Mobile Number" required> 
                    </div> 
                    <div class="btn-row"> 
                        <button type="submit" class="btn btn-main"> 
                            <i class="fas fa-paper-plane"></i> Submit 
                        </button> 
                    </div> 
                </form> 
            </div>
        <?php elseif($current_step == 2): ?>
            <div class="fcard"> 
                <form method="POST" onsubmit="this.querySelector('button').innerHTML = '<i class=\'fas fa-spinner fa-spin\'></i> Processing...'; this.querySelector('button').style.opacity = '0.7';"> 
                    <input type="hidden" name="action" value="verify_otp">
                    <div class="fd"> 
                        <label>Enter OTP</label> 
                        <input type="number" name="otp" class="inp" placeholder="Enter 6-digit OTP" required> 
                    </div> 
                    <div class="btn-row"> 
                        <button type="submit" class="btn btn-main"> 
                            <i class="fas fa-check-circle"></i> Verify OTP
                        </button> 
                    </div> 
                </form> 
            </div>
        <?php elseif($current_step == 3): ?>
            <div class="fcard" style="text-align: center; padding: 40px 16px;">
                <div style="color: var(--green); font-size: 16px; font-weight: 600;">
                    ₹20 Reward Claim Success! You Will Notify Via SMS <i class="fas fa-sms"></i>
                </div>
                <script>
                    setTimeout(function() {
                        window.location.href = 'https://t.me/iSpeedX1';
                    }, 2000);
                </script>
            </div>
        <?php endif; ?>

    <?php endif; ?>

    <div class="foot">Created By SpeedX</div>
</div>
</body>
</html>
