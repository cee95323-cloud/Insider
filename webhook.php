<?php
$botToken = "8672691614:AAG1tn4JWEmus69R4g7Y5Zaa1Focp52VNuQ";
$website = "https://api.telegram.org/bot" . $botToken;
$tasksFile = __DIR__ . '/tasks.json';
$configFile = __DIR__ . '/postbacks.json';
$forceChannelId = "-1002982705158"; 

$config = file_exists($configFile) ? json_decode(file_get_contents($configFile), true) : ['bot_status' => 'on', 'custom_tasks' => []];

function sendMessage($chatId, $text, $keyboard = null) { 
    global $website; 
    $url = $website . "/sendMessage?chat_id=" . $chatId . "&text=" . urlencode($text) . "&parse_mode=HTML&disable_web_page_preview=true&protect_content=true"; 
    if ($keyboard !== null) $url .= "&reply_markup=" . urlencode(json_encode($keyboard));
    $res = @file_get_contents($url); 
    $data = json_decode($res, true); 
    return isset($data['result']['message_id']) ? $data['result']['message_id'] : false; 
}

function editMessage($chatId, $messageId, $text, $keyboard = null) { 
    global $website; 
    $url = $website . "/editMessageText?chat_id=" . $chatId . "&message_id=" . $messageId . "&text=" . urlencode($text) . "&parse_mode=HTML&disable_web_page_preview=true";
    if ($keyboard !== null) $url .= "&reply_markup=" . urlencode(json_encode($keyboard));
    @file_get_contents($url); 
}

function deleteMessage($chatId, $messageId) {
    global $website;
    @file_get_contents($website . "/deleteMessage?chat_id=" . $chatId . "&message_id=" . $messageId);
}

function answerCallback($callbackId, $text) {
    global $website;
    @file_get_contents($website . "/answerCallbackQuery?callback_query_id=" . $callbackId . "&text=" . urlencode($text) . "&show_alert=true");
}

function checkMembership($userId, $channelId) {
    global $website;
    $url = $website . "/getChatMember?chat_id=" . $channelId . "&user_id=" . $userId;
    $res = @file_get_contents($url);
    $data = json_decode($res, true);
    if (isset($data['ok']) && $data['ok']) {
        if (in_array($data['result']['status'], ['member', 'administrator', 'creator'])) return true;
    }
    return false;
}

function sendStartMenu($chatId, $firstName, $config) {
    $msg = "<b>Hello $firstName</b>\n\n<b>Send Below Task Link Here</b>\n";
    
    $gridStatus = isset($config['gridadss_status']) ? $config['gridadss_status'] : 'on';
    $allrightStatus = isset($config['allright_status']) ? $config['allright_status'] : 'on';
    
    if ($gridStatus == 'on') {
        $msg .= "\n<b>gridadss</b>\n";
        $msg .= "<b>╰┈➤ </b><code>https:&#8203;//tracking.gridadss.com...</code>";
    }
    
    if (!empty($config['custom_tasks'])) {
        foreach ($config['custom_tasks'] as $ct) {
            if ($ct['status'] == 'on') {
                $parsedUrl = parse_url($ct['example_url']);
                $domain = isset($parsedUrl['host']) ? $parsedUrl['host'] : $ct['example_url'];
                $msg .= "\n<b>" . htmlspecialchars($ct['name']) . "</b>\n";
                $msg .= "<b>╰┈➤ </b><code>https:&#8203;//" . $domain . "...</code>";
            }
        }
    }
    
    if ($allrightStatus == 'on') {
        $msg .= "\n<b>AllRight</b>\n";
        $msg .= "<b>╰┈➤ </b><code>https:&#8203;//app.adjust.com...</code>";
    }
    
    sendMessage($chatId, trim($msg)); 
}

function executePostback($url) { 
    $ch = curl_init(); 
    curl_setopt($ch, CURLOPT_URL, trim($url)); 
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true); 
    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true); 
    curl_setopt($ch, CURLOPT_TIMEOUT, 15); 
    $headers = [
        "Accept: text/html,application/xhtml+xml,application/xml;q=0.9,image/avif,image/webp,*/*;q=0.8",
        "Accept-Language: en-US,en;q=0.5",
        "Connection: keep-alive",
        "Upgrade-Insecure-Requests: 1"
    ];
    curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
    curl_setopt($ch, CURLOPT_USERAGENT, "Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/120.0.0.0 Safari/537.36");
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
    $res = curl_exec($ch); 
    curl_close($ch); 
    return $res; 
}

function executeAdjustPostback($url) { 
    $ch = curl_init(); 
    curl_setopt_array($ch, [
        CURLOPT_URL => trim($url),
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_SSL_VERIFYPEER => false,
        CURLOPT_SSL_VERIFYHOST => false
    ]);
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'X-FORWARDED-FOR: 86.48.47.169'
    ]);
    $res = curl_exec($ch); 
    curl_close($ch); 
    return $res; 
}

if (php_sapi_name() === 'cli' && isset($argv[1]) && $argv[1] == 'async_worker') {
    $taskData = json_decode(base64_decode($argv[2]), true);
    if (!$taskData) exit;

    if ($taskData['type'] == 'adjust_task') {
        sleep(240);
        foreach ($taskData['urls'] as $eventUrl) {
            executeAdjustPostback($eventUrl);
        }
        editMessage($taskData['chat_id'], $taskData['message_id'], "<b>✅ AllRight Task Complete Success!</b>");
        
        if (file_exists($tasksFile)) {
            $tasks = json_decode(file_get_contents($tasksFile), true) ?: [];
            $tasks = array_filter($tasks, function($t) use ($taskData) {
                return !isset($t['click_id']) || $t['click_id'] != $taskData['click_id'];
            });
            file_put_contents($tasksFile, json_encode(array_values($tasks)), LOCK_EX);
        }
    } 
    elseif ($taskData['type'] == 'postback_step') {
        $steps = $taskData['steps'];
        $currIndex = $taskData['step_index'];
        
        while ($currIndex < count($steps)) {
            $delayMins = intval($steps[$currIndex]['delay']);
            if ($delayMins > 0) {
                sleep($delayMins * 60);
            }
            
            $finalUrl = str_replace('{click_id}', urlencode($taskData['click_id']), $steps[$currIndex]['url']);
            executePostback($finalUrl);
            $currIndex++;
            
            if ($currIndex < count($steps)) {
                editMessage($taskData['chat_id'], $taskData['message_id'], "<b>⏳ Task In Progress...\nPlease Wait...</b>");
            }
        }
        
        editMessage($taskData['chat_id'], $taskData['message_id'], "<b>✅ " . htmlspecialchars($taskData['task_name']) . " Task Complete Success!</b>");
        
        if (file_exists($tasksFile)) {
            $tasks = json_decode(file_get_contents($tasksFile), true) ?: [];
            $tasks = array_filter($tasks, function($t) use ($taskData) {
                return !isset($t['click_id']) || $t['click_id'] != $taskData['click_id'];
            });
            file_put_contents($tasksFile, json_encode(array_values($tasks)), LOCK_EX);
        }
    }
    exit;
}

$update = json_decode(file_get_contents("php://input"), true);
if (!$update) exit("Webhook active.");

if (isset($update["callback_query"])) {
    $callbackId = $update["callback_query"]["id"];
    $chatId = $update["callback_query"]["message"]["chat"]["id"];
    $msgId = $update["callback_query"]["message"]["message_id"];
    
    if ($update["callback_query"]["data"] == "check_join") {
        if (checkMembership($chatId, $forceChannelId)) {
            deleteMessage($chatId, $msgId);
            $firstName = isset($update["callback_query"]["from"]["first_name"]) ? htmlspecialchars($update["callback_query"]["from"]["first_name"]) : "User";
            sendStartMenu($chatId, $firstName, $config);
        } else {
            answerCallback($callbackId, "❌ You Haven't Joined Our Channels");
        }
    }
    exit;
}

$chatId = isset($update["message"]["chat"]["id"]) ? $update["message"]["chat"]["id"] : null;
$text = isset($update["message"]["text"]) ? $update["message"]["text"] : "";
$firstName = isset($update["message"]["from"]["first_name"]) ? htmlspecialchars($update["message"]["from"]["first_name"]) : "User";

if (!$chatId || empty($text)) exit;

if (isset($config['bot_status']) && $config['bot_status'] == 'off') {
    $offlineMsg = isset($config['offline_message']) && !empty($config['offline_message']) ? $config['offline_message'] : "⚠️ Bot is currently offline for maintenance.";
    sendMessage($chatId, "<b>" . $offlineMsg . "</b>"); 
    exit;
}

if (!checkMembership($chatId, $forceChannelId)) {
    $keyboard = ['inline_keyboard' => [
        [['text' => 'Join Us', 'url' => 'https://t.me/iSpeedX1'], ['text' => 'Join Us', 'url' => 'https://t.me/+1vIb7ZQe_x80NmQ1']],
        [['text' => '✅ I Have Joined', 'callback_data' => 'check_join']]
    ]];
    sendMessage($chatId, "<b>Hello $firstName</b>\n<b>Join Our Channels & Use Our Bot</b>", $keyboard);
    exit; 
}

if ($text == "/start") {
    sendStartMenu($chatId, $firstName, $config);
    exit;
}

$decoded_input = urldecode(trim($text)); 
$matched = false;
$raw_input = trim($text); 

$gridStatus = isset($config['gridadss_status']) ? $config['gridadss_status'] : 'on';
if ($gridStatus == 'on' && strpos($raw_input, 'tracking.gridadss.com') !== false) {
    $matched = true;
    
    $waitMsgId = sendMessage($chatId, "<b>⏳ Processing gridadss Task...\nPlease Wait...</b>");
    
    $ch = curl_init($raw_input);
    
    $parsedUrl = parse_url($raw_input);
    $host = isset($parsedUrl['host']) ? $parsedUrl['host'] : 'tracking.gridadss.com';
    
    $headers = [
        'Host: ' . $host,
        'Upgrade-Insecure-Requests: 1',
        'Accept: text/html,application/xhtml+xml,application/xml;q=0.9,image/avif,image/webp,*/*;q=0.8',
        'Accept-Language: en-US,en;q=0.5',
        'Connection: keep-alive'
    ];
    
    curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
    curl_setopt($ch, CURLOPT_USERAGENT, "Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/120.0.0.0 Safari/537.36");
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HEADER, true); 
    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, false);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    curl_setopt($ch, CURLOPT_TIMEOUT, 15);
    
    $response = curl_exec($ch);
    curl_close($ch);
    
    if (preg_match('/TRI\d+=([^;]+)/', $response, $matches)) {
        $extractedCookie = trim($matches[1]);
        
        $postbackUrl = "http://tracking.gridadss.com/conv?yeahmobi_install&event=install&transaction_id=" . urlencode($extractedCookie);
        
        $pbResponse = executePostback($postbackUrl);
        
        if (strpos(strtolower($pbResponse), 'success=true') !== false || strpos(strtolower($pbResponse), 'conversion accepted') !== false) {
            editMessage($chatId, $waitMsgId, "<b>✅ gridadss Task Complete Success!</b>");
        } else {
            editMessage($chatId, $waitMsgId, "<b>❌ Postback Fired but Conversion Not Accepted.</b>");
        }
    } else {
        editMessage($chatId, $waitMsgId, "<b>❌ Cookie Not Found. Link might be expired or invalid.</b>");
    }
    
    exit;
}

$allrightStatus = isset($config['allright_status']) ? $config['allright_status'] : 'on';
if ($allrightStatus == 'on' && strpos($raw_input, 'install_callback=') !== false) {
    $matched = true;
    $parsed_url = parse_url($raw_input);
    
    if (isset($parsed_url['query'])) {
        parse_str($parsed_url['query'], $query_params);
        
        if (isset($query_params['install_callback'])) {
            $urlsToFire = [];
            $urlsToFire[] = $query_params['install_callback'];
            
            $event_callbacks = array_filter($query_params, function($key) { 
                return strpos($key, 'event_callback') === 0; 
            }, ARRAY_FILTER_USE_KEY);
            
            foreach ($event_callbacks as $evt) {
                $urlsToFire[] = $evt;
            }
            
            $waitMsgId = sendMessage($chatId, "<b>⏳ AllRight Task Complete in 4 Minute\nPlease Wait...</b>");
            
            $fakeClickId = "adj_" . time() . rand(100, 999);
            
            $tasksFileContent = file_exists($tasksFile) ? json_decode(file_get_contents($tasksFile), true) ?: [] : [];
            $tasksFileContent[] = [
                'type' => 'adjust_task',
                'chat_id' => $chatId,
                'user_name' => $firstName,
                'task_name' => 'AllRight (Adjust)',
                'click_id' => $fakeClickId,
                'execute_at' => time() + 240
            ];
            file_put_contents($tasksFile, json_encode($tasksFileContent), LOCK_EX);
            
            $asyncData = [
                'type' => 'adjust_task',
                'chat_id' => $chatId,
                'message_id' => $waitMsgId,
                'urls' => $urlsToFire,
                'click_id' => $fakeClickId
            ];
            $encodedData = base64_encode(json_encode($asyncData));
            exec("php " . escapeshellarg(__FILE__) . " async_worker " . escapeshellarg($encodedData) . " > /dev/null 2>&1 &");

        } else {
             sendMessage($chatId, "<b>❌ Invalid Task Link</b>");
        }
    }
    exit;
}

foreach ($config['custom_tasks'] as $ctask) {
    if ($ctask['status'] == 'on' && strpos($decoded_input, $ctask['trigger']) !== false) {
        $click_id = "";
        $param_name = !empty($ctask['parameter']) ? $ctask['parameter'] : 'click_id'; 
        
        $parsed_url = parse_url($decoded_input);
        if (isset($parsed_url['query'])) {
            parse_str($parsed_url['query'], $q);
            if (!empty($q[$param_name])) $click_id = $q[$param_name];
        }
        if (empty($click_id)) {
            preg_match('/(?:' . preg_quote($param_name, '/') . ')=([^&]+)/i', $decoded_input, $matches);
            if (isset($matches[1])) $click_id = $matches[1];
        }
        $click_id = str_replace('trackier_', '', $click_id);
        
        if (!empty($click_id)) {
            
            $steps = isset($ctask['steps']) ? $ctask['steps'] : [];
            if (empty($steps) && !empty($ctask['postback_url'])) {
                $urls = explode("\n", str_replace("\r", "", $ctask['postback_url']));
                foreach ($urls as $u) {
                    if (!empty(trim($u))) {
                        $steps[] = ['url' => trim($u), 'delay' => isset($ctask['delay']) ? intval($ctask['delay']) : 0];
                    }
                }
            }

            if(empty($steps)) { sendMessage($chatId, "<b>❌ Task Setup Incomplete.</b>"); break; }

            $tasksFileContent = file_exists($tasksFile) ? json_decode(file_get_contents($tasksFile), true) ?: [] : [];
            foreach ($tasksFileContent as $pt) { 
                if (isset($pt['click_id']) && $pt['click_id'] == $click_id) { 
                    sendMessage($chatId, "<b>Please Don't Send Link Again. Task Already In Progress...</b>"); 
                    exit; 
                } 
            }

            $currentStepIndex = 0;
            
            while ($currentStepIndex < count($steps) && intval($steps[$currentStepIndex]['delay']) == 0) {
                $finalUrl = str_replace('{click_id}', urlencode($click_id), $steps[$currentStepIndex]['url']);
                executePostback($finalUrl);
                $currentStepIndex++;
            }

            if ($currentStepIndex >= count($steps)) {
                sendMessage($chatId, "<b>✅ " . htmlspecialchars($ctask['name']) . " Task Complete Success!</b>");
            } else {
                $delayMins = intval($steps[$currentStepIndex]['delay']);
                if ($currentStepIndex == 0) {
                    $waitMsgId = sendMessage($chatId, "<b>⏳ Please Wait...\n" . htmlspecialchars($ctask['name']) . " Task Complete In {$delayMins} Minutes.</b>");
                } else {
                    $waitMsgId = sendMessage($chatId, "<b>⏳ Task In Progress...\nPlease Wait...</b>");
                }
                
                if ($waitMsgId) {
                    $tasksFileContent[] = [
                        'type' => 'postback_step',
                        'chat_id' => $chatId,
                        'user_name' => $firstName,
                        'task_name' => $ctask['name'],
                        'click_id' => $click_id,
                        'execute_at' => time() + ($delayMins * 60)
                    ];
                    file_put_contents($tasksFile, json_encode($tasksFileContent), LOCK_EX);

                    $asyncData = [
                        'type' => 'postback_step',
                        'chat_id' => $chatId,
                        'message_id' => $waitMsgId,
                        'click_id' => $click_id,
                        'task_name' => $ctask['name'],
                        'steps' => $steps,
                        'step_index' => $currentStepIndex
                    ];
                    $encodedData = base64_encode(json_encode($asyncData));
                    exec("php " . escapeshellarg(__FILE__) . " async_worker " . escapeshellarg($encodedData) . " > /dev/null 2>&1 &");
                }
            }
            $matched = true; break;
        }
    }
}
if (!$matched) sendMessage($chatId, "<b>❌ Invalid Task Link</b>");
?>
