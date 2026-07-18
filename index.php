<?php

/* 
 * Bot Name: কী রাখাল্লা
 * Function: লিংক এবং বোট মেনশন রিমুভ + মেম্বার মিউট (৬০ সেকেন্ড)
 */

$botToken = "8987597708:AAHnK1jDm0gDQ-eo3hbeXWuMQZZE0ETmVLY";
$website = "https://api.telegram.org/bot" . $botToken;

$content = file_get_contents("php://input");
$update = json_decode($content, true);

if (!$update || !isset($update['message'])) {
    exit;
}

$message = $update['message'];
$chatId = $message['chat']['id'];
$messageId = $message['message_id'];
$userId = $message['from']['id'];
$nickname = isset($message['from']['first_name']) ? $message['from']['first_name'] : "বন্ধু";
$text = isset($message['text']) ? $message['text'] : "";

// চেক করা হচ্ছে ইউজার অ্যাডমিন কি না
function isExempted($botToken, $chatId, $userId, $message) {
    if (isset($message['sender_chat'])) {
        return true;
    }
    $checkUrl = "https://api.telegram.org/bot$botToken/getChatMember?chat_id=$chatId&user_id=$userId";
    $response = file_get_contents($checkUrl);
    $data = json_decode($response, true);
    if (isset($data['result']['status'])) {
        $status = $data['result']['status'];
        return ($status == 'administrator' || $status == 'creator');
    }
    return false;
}

if (isExempted($botToken, $chatId, $userId, $message)) {
    exit;
}

$isViolation = false;

// লিংক চেক
if (isset($message['entities'])) {
    foreach ($message['entities'] as $entity) {
        if ($entity['type'] == 'url' || $entity['type'] == 'text_link') {
            $isViolation = true;
            break;
        }
    }
}

// বোট মেনশন চেক
if (!$isViolation && preg_match('/\B@\w+bot\b/i', $text)) {
    $isViolation = true;
}

if ($isViolation) {
    // মেসেজ ডিলিট
    file_get_contents($website . "/deleteMessage?chat_id=$chatId&message_id=$messageId");

    // ৬০ সেকেন্ড মিউট
    $muteTime = time() + 60;
    $permissions = json_encode([
        'can_send_messages' => false,
        'can_send_media_messages' => false,
        'can_send_polls' => false,
        'can_send_other_messages' => false,
        'can_add_web_page_previews' => false
    ]);
    file_get_contents($website . "/restrictChatMember?chat_id=$chatId&user_id=$userId&permissions=$permissions&until_date=$muteTime");

    // মজার ওয়ার্নিং
    $warningMsg = "কী রাখাল্লা $nickname, তোমার লিংক রিমুভ করলাম! 🛑\n\nবেশি চালাকি করো কেন? যাও, ৬০ সেকেন্ড ঘাস খেয়ে আসো, তারপর কথা হবে।";
    file_get_contents($website . "/sendMessage?chat_id=$chatId&text=" . urlencode($warningMsg));
}

?>
