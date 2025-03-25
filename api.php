<?php
header('Content-Type: application/json');

// Enable CORS if needed
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET, POST");
header("Access-Control-Allow-Headers: Content-Type");

$dataFile = 'data.json';

// Initialize data file if not exists
if (!file_exists($dataFile)) {
    file_put_contents($dataFile, json_encode([
        'users' => [],
        'withdrawals' => []
    ]));
}

// Load data
$data = json_decode(file_get_contents($dataFile), true);

// Get action from request
$action = $_REQUEST['action'] ?? '';
$tg_id = $_REQUEST['tg_id'] ?? '';

// Process actions
switch ($action) {
    case 'get_user':
        if (empty($tg_id)) {
            echo json_encode(['status' => 'error', 'message' => 'Telegram ID required']);
            exit;
        }
        
        $isNew = !isset($data['users'][$tg_id]);
        if ($isNew) {
            $data['users'][$tg_id] = ['points' => 1, 'joined' => date('Y-m-d H:i:s')];
            file_put_contents($dataFile, json_encode($data, JSON_PRETTY_PRINT));
        }
        
        echo json_encode([
            'status' => 'success',
            'points' => $data['users'][$tg_id]['points'],
            'is_new' => $isNew
        ]);
        break;
        
    case 'watch_ad':
        if (empty($tg_id)) {
            echo json_encode(['status' => 'error', 'message' => 'Telegram ID required']);
            exit;
        }
        
        if (!isset($data['users'][$tg_id])) {
            $data['users'][$tg_id] = ['points' => 1];
        }
        
        $data['users'][$tg_id]['points'] += 100;
        $data['users'][$tg_id]['last_ad'] = date('Y-m-d H:i:s');
        file_put_contents($dataFile, json_encode($data, JSON_PRETTY_PRINT));
        
        echo json_encode([
            'status' => 'success',
            'points' => $data['users'][$tg_id]['points']
        ]);
        break;
        
    case 'withdraw':
        $wallet = $_POST['wallet'] ?? '';
        $amount = intval($_POST['amount'] ?? 0);
        
        if (empty($tg_id) {
            echo json_encode(['status' => 'error', 'message' => 'Telegram ID required']);
            exit;
        }
        
        if (!isset($data['users'][$tg_id])) {
            echo json_encode(['status' => 'error', 'message' => 'User not found']);
            exit;
        }
        
        if ($amount < 10000) {
            echo json_encode(['status' => 'error', 'message' => 'Minimum withdrawal is 10,000 points']);
            exit;
        }
        
        if (!preg_match('/^EQ[A-Za-z0-9_-]{48}$/', $wallet)) {
            echo json_encode(['status' => 'error', 'message' => 'Invalid TON wallet address']);
            exit;
        }
        
        if ($amount > $data['users'][$tg_id]['points']) {
            echo json_encode(['status' => 'error', 'message' => 'Not enough points']);
            exit;
        }
        
        $data['users'][$tg_id]['points'] -= $amount;
        $data['withdrawals'][] = [
            'tg_id' => $tg_id,
            'wallet' => $wallet,
            'amount' => $amount,
            'date' => date('Y-m-d H:i:s'),
            'status' => 'pending'
        ];
        
        file_put_contents($dataFile, json_encode($data, JSON_PRETTY_PRINT));
        
        echo json_encode([
            'status' => 'success',
            'points' => $data['users'][$tg_id]['points'],
            'message' => 'Withdrawal request submitted'
        ]);
        break;
        
    default:
        echo json_encode(['status' => 'error', 'message' => 'Invalid action']);
        break;
}
?>