<?php
session_start();

// ==========================================
// ADMIN CONFIGURATION
// ==========================================
$ADMIN_PIN = "6969"; 
$botToken = "8672691614:AAG1tn4JWEmus69R4g7Y5Zaa1Focp52VNuQ";
$tasksFile = __DIR__ . '/tasks.json';
$configFile = __DIR__ . '/postbacks.json'; 

if (!file_exists($configFile)) {
    $defaultConfig = [
        'bot_status' => 'on',
        'offline_message' => '⚠️ Bot is currently offline for maintenance.',
        'gridadss_status' => 'on',
        'allright_status' => 'on',
        'custom_tasks' => [] 
    ];
    file_put_contents($configFile, json_encode($defaultConfig, JSON_PRETTY_PRINT));
}
$config = json_decode(file_get_contents($configFile), true);

// Setup default keys if they don't exist in older configurations
if (!isset($config['gridadss_status'])) $config['gridadss_status'] = 'on';
if (!isset($config['allright_status'])) $config['allright_status'] = 'on';

if (isset($_GET['logout'])) { session_destroy(); header("Location: admin.php"); exit; }
if (isset($_POST['login'])) {
    if ($_POST['pin'] === $ADMIN_PIN) { $_SESSION['logged_in'] = true; header("Location: admin.php"); exit; } 
    else { $error = "Invalid PIN!"; }
}

function saveConfig($configData) { global $configFile; file_put_contents($configFile, json_encode($configData, JSON_PRETTY_PRINT)); }

$message = "";

if (isset($_SESSION['logged_in'])) {
    if (isset($_GET['action']) && $_GET['action'] == 'toggle_bot') { $config['bot_status'] = ($config['bot_status'] == 'on') ? 'off' : 'on'; saveConfig($config); header("Location: admin.php?tab=control"); exit; }
    if (isset($_GET['action']) && $_GET['action'] == 'clear_queue') { file_put_contents($tasksFile, json_encode([])); $message = "<div class='alert' style='display:block; background: var(--green-bg); border: 1px solid var(--green-border); color: var(--green);'><i class='fa-solid fa-check-circle'></i> Queue Cleared!</div>"; }

    if (isset($_POST['save_bot_settings'])) {
        $config['offline_message'] = trim($_POST['offline_message']);
        saveConfig($config);
        $message = "<div class='alert' style='display:block; background: var(--green-bg); border: 1px solid var(--green-border); color: var(--green);'><i class='fa-solid fa-check-circle'></i> Settings Saved!</div>";
    }

    if (isset($_POST['save_task'])) {
        $taskId = !empty($_POST['task_id']) ? $_POST['task_id'] : uniqid('t_');
        
        // Process URLs and their corresponding delays
        $urls = explode("\n", str_replace("\r", "", $_POST['postback_url']));
        $delays = isset($_POST['step_delay']) ? $_POST['step_delay'] : [];
        $steps = [];
        $i = 0;
        foreach ($urls as $url) {
            if (!empty(trim($url))) {
                $delay = isset($delays[$i]) ? intval($delays[$i]) : 0;
                $steps[] = ['url' => trim($url), 'delay' => $delay];
                $i++;
            }
        }

        $newTask = [
            'id' => $taskId, 'name' => trim($_POST['task_name']), 'trigger' => trim($_POST['trigger']), 
            'parameter' => trim($_POST['parameter']), 'example_url' => trim($_POST['example_url']), 
            'steps' => $steps, 'status' => 'on'
        ];
        
        $updated = false;
        foreach ($config['custom_tasks'] as $k => $t) {
            if ($t['id'] == $taskId) { 
                $newTask['status'] = $t['status']; 
                $config['custom_tasks'][$k] = $newTask; 
                $updated = true; break; 
            }
        }
        if (!$updated) $config['custom_tasks'][] = $newTask;
        saveConfig($config);
        header("Location: admin.php?tab=offers"); exit; 
    }

    // Toggle logic for hardcoded scripts
    if (isset($_GET['toggle_gridadss'])) {
        $config['gridadss_status'] = ($config['gridadss_status'] == 'on') ? 'off' : 'on';
        saveConfig($config); header("Location: admin.php?tab=manage"); exit;
    }
    if (isset($_GET['toggle_allright'])) {
        $config['allright_status'] = ($config['allright_status'] == 'on') ? 'off' : 'on';
        saveConfig($config); header("Location: admin.php?tab=manage"); exit;
    }

    // Toggle logic for custom tasks
    if (isset($_GET['toggle_task'])) { 
        foreach ($config['custom_tasks'] as $k => $t) { 
            if ($t['id'] == $_GET['toggle_task']) { $config['custom_tasks'][$k]['status'] = ($t['status'] == 'on') ? 'off' : 'on'; break; } 
        } 
        saveConfig($config); header("Location: admin.php?tab=manage"); exit; 
    }
    
    if (isset($_GET['delete_task'])) { 
        foreach ($config['custom_tasks'] as $k => $t) { 
            if ($t['id'] == $_GET['delete_task']) { unset($config['custom_tasks'][$k]); break; } 
        } 
        $config['custom_tasks'] = array_values($config['custom_tasks']); saveConfig($config); header("Location: admin.php?tab=manage"); exit; 
    }
}

$activeTab = isset($_GET['tab']) ? $_GET['tab'] : 'home';
$pendingTasks = file_exists($tasksFile) ? json_decode(file_get_contents($tasksFile), true) : [];
if (!is_array($pendingTasks)) $pendingTasks = [];

$editData = ['id'=>'', 'name'=>'', 'trigger'=>'', 'parameter'=>'', 'example_url'=>'', 'steps'=>[]];
if (isset($_GET['edit'])) {
    $activeTab = 'add_task';
    foreach ($config['custom_tasks'] as $t) { 
        if ($t['id'] == $_GET['edit']) { 
            $editData = $t; 
            if (!isset($editData['steps']) && isset($editData['postback_url'])) {
                $editData['steps'] = [];
                $oldUrls = explode("\n", str_replace("\r", "", $editData['postback_url']));
                foreach($oldUrls as $u) { if(!empty(trim($u))) $editData['steps'][] = ['url'=>trim($u), 'delay'=>(isset($editData['delay'])?$editData['delay']:0)]; }
            }
            break; 
        } 
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=1, user-scalable=no">
    <title>SpeedX Admin</title>
    
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
    <style>
        *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }
        :root { --bg: #f7f7f8; --white: #ffffff; --border: #e5e5ea; --ink: #111118; --ink2: #6c6c80; --ink3: #b0b0c0; --blue: #2563eb; --blue-bg: #eff3ff; --green: #15803d; --green-bg: #f0fdf4; --green-border: #bbf7d0; --red: #b91c1c; --red-bg: #fef2f2; --red-border: #fecaca; --f: 'Inter', sans-serif; --r: 12px; }
        body { background: var(--bg); color: var(--ink); font-family: var(--f); min-height: 100vh; padding-bottom: 80px; font-size: 14px; line-height: 1.5; -webkit-font-smoothing: antialiased; }
        
        /* Adjusted width for Admin Panel */
        .wrap { max-width: 800px; margin: 0 auto; padding: 0 16px; }

        .hdr { display: flex; align-items: center; padding: 20px 0 18px; border-bottom: 1px solid var(--border); margin-bottom: 24px; position: relative; }
        .logo { font-size: 20px; font-weight: 700; letter-spacing: -.3px; }
        .logo span { color: var(--blue); }
        .logout-btn { margin-left: auto; color: var(--red); font-size: 14px; font-weight: 600; text-decoration: none; display: flex; align-items: center; gap: 6px; }

        .fcard { background: var(--white); border: 1px solid var(--border); border-radius: var(--r); padding: 24px 20px; margin-bottom: 16px; animation: fadeIn 0.3s ease; }
        .fcard-header { display: flex; align-items: center; gap: 8px; font-size: 16px; font-weight: 700; margin-bottom: 20px; border-bottom: 1px solid var(--border); padding-bottom: 12px; }
        @keyframes fadeIn { from { opacity: 0; transform: translateY(5px); } to { opacity: 1; transform: translateY(0); } }
        
        .fd { margin-bottom: 18px; }
        .fd label { display: block; font-size: 13px; font-weight: 600; color: var(--ink2); margin-bottom: 8px; }
        .inp { width: 100%; background: var(--bg); border: 1px solid var(--border); color: var(--ink); border-radius: 8px; padding: 12px; font-size: 14px; font-family: var(--f); outline: none; transition: border-color .15s, box-shadow .15s; }
        .inp:focus { border-color: var(--blue); background: var(--white); box-shadow: 0 0 0 3px rgba(37, 99, 235, .08); }
        .inp::placeholder { color: var(--ink3); }
        textarea.inp { resize: vertical; min-height: 100px; }
        
        .btn-row { display: flex; gap: 10px; margin-top: 18px; }
        .btn { padding: 14px 16px; border-radius: 8px; font-size: 14px; font-weight: 600; cursor: pointer; border: none; font-family: var(--f); transition: opacity .15s; display: inline-flex; align-items: center; justify-content: center; gap: 8px; width: 100%; text-decoration: none; }
        .btn-main { background: var(--blue); color: #fff; }
        .btn-main:hover { opacity: .88; }
        .btn-danger { background: var(--red-bg); color: var(--red); border: 1px solid var(--red-border); }
        .btn-ghost { background: transparent; border: 1px solid var(--border); color: var(--ink2); }
        .btn:disabled { opacity: .5; cursor: not-allowed; }

        .alert { margin-bottom: 16px; padding: 12px 14px; border-radius: 8px; font-size: 13px; line-height: 1.5; display: none; }
        .a-err { display: block; background: var(--red-bg); border: 1px solid var(--red-border); color: var(--red); font-weight: 500; }

        /* Tables */
        .table-wrap { overflow-x: auto; margin-top: 10px; }
        table { width: 100%; border-collapse: collapse; font-size: 13px; }
        th, td { padding: 14px 10px; text-align: left; border-bottom: 1px solid var(--border); }
        th { font-weight: 600; color: var(--ink2); text-transform: uppercase; font-size: 11px; letter-spacing: 0.5px; }
        td strong { font-weight: 600; color: var(--ink); }

        /* Bottom Nav */
        .bottom-nav { position: fixed; bottom: 0; left: 0; right: 0; background: var(--white); border-top: 1px solid var(--border); display: flex; justify-content: space-around; padding: 12px 0 calc(12px + env(safe-area-inset-bottom)); z-index: 1000; }
        .nav-item { display: flex; flex-direction: column; align-items: center; text-decoration: none; color: var(--ink3); font-size: 11px; font-weight: 600; gap: 6px; transition: color 0.2s; }
        .nav-item.active, .nav-item:hover { color: var(--blue); }
        .nav-item i { font-size: 20px; }

        /* Switches */
        .switch { --switch-width:44px; --switch-height:24px; --switch-bg: var(--border); --switch-checked-bg: var(--green); --switch-offset:calc((var(--switch-height) - 18px)/2); display:inline-block; position:relative; }
        .switch input { display:none; }
        .slider { width:var(--switch-width); height:var(--switch-height); background:var(--switch-bg); border-radius:999px; display:flex; align-items:center; cursor:pointer; transition:all .2s; }
        .circle { width:18px; height:18px; background:#fff; border-radius:50%; display:flex; align-items:center; justify-content:center; transition:all .2s; z-index:1; position:absolute; left:var(--switch-offset); box-shadow: 0 2px 4px rgba(0,0,0,0.1); }
        .switch input:checked+.slider { background:var(--switch-checked-bg); }
        .switch input:checked+.slider .circle { left:calc(100% - 18px - var(--switch-offset)); }

        /* List Offers */
        .offer-list-label { font-weight: 500; height: 3.5rem; position: relative; display: flex; align-items: center; padding: 0 16px; gap: 12px; border-radius: 8px; user-select: none; cursor: pointer; transition: background-color 0.2s, color 0.2s, box-shadow 0.2s; color: var(--ink2); border: 1px solid transparent; margin-bottom: 8px; }
        .offer-list-label:hover { background: var(--bg); }
        .offer-list-label:has(input:checked) { color: var(--blue); background-color: var(--blue-bg); border: 1px solid rgba(37, 99, 235, 0.3); }
        .offer-list-radio { width: 16px; height: 16px; position: absolute; right: 16px; accent-color: var(--blue); }

        .delay-box { background: var(--bg); border: 1px dashed var(--border); padding: 16px; border-radius: 8px; margin-bottom: 18px; }
    </style>
</head>
<body>

<?php if (!isset($_SESSION['logged_in'])): ?>
<div class="wrap" style="max-width: 400px; padding-top: 10vh;">
    <div class="hdr" style="justify-content: center; border: none; margin-bottom: 10px;">
        <div class="logo">SpeedX <span>Secure</span></div>
    </div>
    <div class="fcard">
        <?php if(isset($error)) echo "<div class='alert a-err'>$error</div>"; ?>
        <form method="POST">
            <div class="fd">
                <label>Admin Access PIN</label>
                <input type="password" name="pin" class="inp" placeholder="Enter PIN to unlock" autocomplete="off" required>
            </div>
            <button type="submit" name="login" class="btn btn-main"><i class="fa-solid fa-lock-open"></i> Unlock Panel</button>
        </form>
    </div>
</div>
</body>
</html>
<?php exit; endif; ?>

<div class="wrap">
    <div class="hdr">
        <div class="logo">SpeedX <span>Admin</span></div>
        <a href="?logout=true" class="logout-btn"><i class="fa-solid fa-power-off"></i> Logout</a>
    </div>

    <?php echo $message; ?>

    <?php if($activeTab == 'home'): ?>
        <div class="fcard">
            <div class="fcard-header"><i class="fa-solid fa-clock-rotate-left" style="color: var(--blue);"></i> Live Queue (<?php echo count(array_filter($pendingTasks, function($t){return $t['type']=='postback_step';})); ?>)</div>
            <div class="table-wrap">
                <table>
                    <tr><th>User Name</th><th>Status</th></tr>
                    <?php 
                    $c = 0;
                    foreach($pendingTasks as $t) { 
                        if($t['type'] == 'postback_step') {
                            $c++;
                            $tl = $t['execute_at'] - time(); 
                            $st = ($tl<=0) ? "<span style='color:var(--green); font-weight:600;'>Ready</span>" : "{$tl}s wait"; 
                            $uname = isset($t['user_name']) ? htmlspecialchars($t['user_name']) : 'Unknown';
                            echo "<tr>
                                    <td><strong>{$uname}</strong><br><span style='color:var(--ink3); font-size: 11px;'>Step: ".(($t['step_index']??0)+1)."</span></td>
                                    <td>{$st}</td>
                                  </tr>"; 
                        }
                    } 
                    if($c == 0) echo "<tr><td colspan='2' style='text-align:center; padding: 24px; color: var(--ink3);'>No tasks pending in queue.</td></tr>";
                    ?>
                </table>
            </div>
            <br>
            <a href="?action=clear_queue" class="btn btn-danger"><i class="fa-solid fa-trash"></i> Clear Queue</a>
        </div>
    <?php endif; ?>

    <?php if($activeTab == 'offers'): ?>
        <div class="fcard" style="max-width: 450px; margin: 0 auto;">
            <div class="fcard-header"><i class="fa-solid fa-fire" style="color: var(--red);"></i> Active Tasks</div>
            <div class="offers-list-container">
                <?php 
                $activeFound = false;
                if(!empty($config['custom_tasks'])) {
                    foreach($config['custom_tasks'] as $k => $t): 
                        if($t['status'] == 'off') continue;
                        $activeFound = true;
                ?>
                <label class="offer-list-label" for="task_<?php echo $k; ?>">
                    <i class="fa-solid fa-bolt" style="font-size: 16px; color: var(--ink3);"></i>
                    <?php echo htmlspecialchars($t['name']); ?>
                    <input type="radio" name="active_offer" class="offer-list-radio" id="task_<?php echo $k; ?>" <?php echo $activeFound && $k==0?'checked':'';?> />
                </label>
                <?php endforeach; } 
                if(!$activeFound) { echo "<p style='color:var(--ink3); text-align:center; padding: 20px 0;'>No active tasks found.</p>"; } ?>
            </div>
        </div>
    <?php endif; ?>

    <?php if($activeTab == 'add_task'): ?>
        <div class="fcard">
            <div class="fcard-header">
                <i class="fa-solid <?php echo isset($_GET['edit']) ? 'fa-pen-to-square' : 'fa-plus-circle'; ?>" style="color: var(--blue);"></i> 
                <?php echo isset($_GET['edit']) ? 'Edit Task' : 'Create Custom Task'; ?>
            </div>
            <form method="POST" action="?tab=add_task">
                <input type="hidden" name="task_id" value="<?php echo $editData['id']; ?>">
                
                <div class="fd"><label>Task Name</label><input type="text" name="task_name" class="inp" value="<?php echo htmlspecialchars($editData['name']); ?>" required></div>
                <div class="fd"><label>Trigger Keyword</label><input type="text" name="trigger" class="inp" value="<?php echo htmlspecialchars($editData['trigger']); ?>" required></div>
                <div class="fd"><label>Extraction Parameter (e.g., clickid, sub1)</label><input type="text" name="parameter" class="inp" value="<?php echo htmlspecialchars(isset($editData['parameter']) ? $editData['parameter'] : 'click_id'); ?>" required></div>
                <div class="fd"><label>Example Task URL</label><input type="text" name="example_url" class="inp" value="<?php echo htmlspecialchars($editData['example_url']); ?>" required></div>
                
                <?php
                $urls_text = "";
                if (!empty($editData['steps'])) {
                    $urls = [];
                    foreach($editData['steps'] as $step) { $urls[] = $step['url']; }
                    $urls_text = implode("\n", $urls);
                }
                ?>
                
                <div class="fd">
                    <label>Postback URLs (Paste one URL per line)</label>
                    <textarea name="postback_url" class="inp" id="postback_url_input" placeholder="https://tracking.com/pb?click_id={click_id}&event=install&#10;https://tracking.com/pb?click_id={click_id}&event=register" required><?php echo htmlspecialchars($urls_text); ?></textarea>
                </div>

                <div id="delay_container" class="delay-box" style="display:none;"></div>

                <button type="submit" name="save_task" class="btn btn-main"><i class="fa-solid fa-save"></i> Save Configuration</button>
            </form>
        </div>
        
        <script>
            document.addEventListener('DOMContentLoaded', function() {
                const textarea = document.getElementById('postback_url_input');
                const delayContainer = document.getElementById('delay_container');
                
                const existingDelays = <?php echo !empty($editData['steps']) ? json_encode(array_column($editData['steps'], 'delay')) : '[]'; ?>;

                function updateDelays() {
                    const lines = textarea.value.split('\n').filter(line => line.trim() !== '');
                    if (lines.length === 0) {
                        delayContainer.style.display = 'none';
                        delayContainer.innerHTML = '';
                        return;
                    }
                    
                    delayContainer.style.display = 'block';
                    
                    const currentInputs = delayContainer.querySelectorAll('input[name="step_delay[]"]');
                    const currentValues = Array.from(currentInputs).map(inp => inp.value);

                    let html = '<div style="font-size: 13px; font-weight: 600; color: var(--ink); margin-bottom: 12px;"><i class="fa-solid fa-stopwatch" style="color: var(--blue);"></i> Configure Time Gaps</div>';
                    
                    lines.forEach((line, index) => {
                        let val = '0';
                        if (currentValues[index] !== undefined) val = currentValues[index];
                        else if (existingDelays[index] !== undefined && !textarea.dataset.modified) val = existingDelays[index];

                        html += `<div class="fd" style="margin-bottom: 10px;">
                                    <label>Wait time before Postback ${index + 1} (Mins)</label>
                                    <input type="number" class="inp" name="step_delay[]" value="${val}" min="0" required>
                                 </div>`;
                    });
                    delayContainer.innerHTML = html;
                }

                textarea.addEventListener('input', function() {
                    this.dataset.modified = "true";
                    updateDelays();
                });
                
                updateDelays();
            });
        </script>
    <?php endif; ?>

    <?php if($activeTab == 'manage'): ?>
        <div class="fcard">
            <div class="fcard-header"><i class="fa-solid fa-list-check" style="color: var(--blue);"></i> Manage All Tasks</div>
            <div class="table-wrap">
                <table>
                    <tr><th>Name</th><th style="width: 80px;">State</th><th style="width: 80px; text-align: right;">Action</th></tr>
                    
                    <!-- System Predefined Tasks Added Here -->
                    <?php 
                        $gridChecked = ($config['gridadss_status'] == 'on') ? 'checked' : '';
                        $allrightChecked = ($config['allright_status'] == 'on') ? 'checked' : '';
                    ?>
                    <tr>
                        <td><strong>gridadss</strong></td>
                        <td>
                            <label class='switch'>
                              <input type='checkbox' onchange="window.location.href='?tab=manage&toggle_gridadss=1'" <?php echo $gridChecked; ?>>
                              <div class='slider'><div class='circle'></div></div>
                            </label>
                        </td>
                        <td style='text-align: right;'><span style="color: var(--ink3); font-size: 11px;">System Task</span></td>
                    </tr>
                    
                    <tr>
                        <td><strong>AllRight Adjust</strong></td>
                        <td>
                            <label class='switch'>
                              <input type='checkbox' onchange="window.location.href='?tab=manage&toggle_allright=1'" <?php echo $allrightChecked; ?>>
                              <div class='slider'><div class='circle'></div></div>
                            </label>
                        </td>
                        <td style='text-align: right;'><span style="color: var(--ink3); font-size: 11px;">System Task</span></td>
                    </tr>
                    
                    <!-- User Custom Tasks -->
                    <?php 
                    if(!empty($config['custom_tasks'])){
                        foreach($config['custom_tasks'] as $t) { 
                            $isChecked = ($t['status'] == 'on') ? 'checked' : '';
                            echo "<tr>
                            <td><strong>".htmlspecialchars($t['name'])."</strong></td>
                            <td>
                                <label class='switch'>
                                  <input type='checkbox' onchange=\"window.location.href='?tab=manage&toggle_task={$t['id']}'\" {$isChecked}>
                                  <div class='slider'><div class='circle'></div></div>
                                </label>
                            </td>
                            <td style='text-align: right;'>
                            <a href='?edit={$t['id']}' style='color:var(--blue); margin-right:12px; font-size: 16px;'><i class='fa-solid fa-pen'></i></a>
                            <a href='?tab=manage&delete_task={$t['id']}' style='color:var(--red); font-size: 16px;' onclick='return confirm(\"Delete this task?\");'><i class='fa-solid fa-trash'></i></a>
                            </td></tr>"; 
                        } 
                    }
                    ?>
                </table>
            </div>
        </div>
    <?php endif; ?>

    <?php if($activeTab == 'control'): ?>
        <div class="fcard" style="text-align:center;">
            <div class="fcard-header" style="justify-content: center;"><i class="fa-solid fa-toggle-on" style="color: var(--blue);"></i> Master Switch</div>
            <p style="font-size: 13px; color: var(--ink2); margin-bottom: 20px;">Turns the entire bot ON or OFF.</p>
            <?php if($config['bot_status'] == 'on'): ?> 
                <a href="?action=toggle_bot" class="btn btn-danger"><i class="fa-solid fa-power-off"></i> Turn Bot OFF</a> 
            <?php else: ?> 
                <a href="?action=toggle_bot" class="btn btn-main"><i class="fa-solid fa-play"></i> Turn Bot ON</a> 
            <?php endif; ?>
        </div>
        
        <div class="fcard">
            <div class="fcard-header"><i class="fa-solid fa-gear" style="color: var(--ink2);"></i> Bot Messages</div>
            <form method="POST" action="?tab=control">
                <div class="fd">
                    <label>Offline Message</label>
                    <textarea name="offline_message" class="inp" rows="3" required><?php echo htmlspecialchars($config['offline_message']); ?></textarea>
                </div>
                <button type="submit" name="save_bot_settings" class="btn btn-main"><i class="fa-solid fa-save"></i> Save Settings</button>
            </form>
        </div>
    <?php endif; ?>
</div>

<div class="bottom-nav">
    <a href="?tab=home" class="nav-item <?php echo $activeTab=='home'?'active':''; ?>"><i class="fa-solid fa-house"></i><span>Home</span></a>
    <a href="?tab=offers" class="nav-item <?php echo $activeTab=='offers'?'active':''; ?>"><i class="fa-solid fa-fire"></i><span>Offers</span></a>
    <a href="?tab=add_task" class="nav-item <?php echo $activeTab=='add_task'?'active':''; ?>"><i class="fa-solid fa-circle-plus"></i><span>Task Add</span></a>
    <a href="?tab=manage" class="nav-item <?php echo $activeTab=='manage'?'active':''; ?>"><i class="fa-solid fa-list-check"></i><span>Manage</span></a>
    <a href="?tab=control" class="nav-item <?php echo $activeTab=='control'?'active':''; ?>"><i class="fa-solid fa-robot"></i><span>Bot</span></a>
</div>

</body>
</html>
