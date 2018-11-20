<?php

class FacebookBot
{
    private $_validationToken;
    private $_pageAccessToken;
    private $_receivedMessages;
    private $_event;
    private $_entryId;
    public function __construct($validationToken, $pageAccessToken)
    {
        $this->_validationToken = $validationToken;
        $this->_pageAccessToken = $pageAccessToken;
        $this->setupWebhook();
    }
    public function getReceivedMessages()
    {
        $this->run();
        $messaging = $this->_event;
        $messages = [];
        $message = new stdClass();
        $message->entryId = isset($this->_entryId) ? $this->_entryId : null;
        $message->senderId = isset($messaging->sender->id) ? $messaging->sender->id : null;
        $message->recipientId = isset($messaging->recipient->id) ? $messaging->recipient->id : null;
        $message->timestamp = isset($messaging->timestamp) ? $messaging->timestamp : null;
        $message->messageId = isset($messaging->message->mid) ? $messaging->message->mid : null;
        $message->sequenceNumber = isset($messaging->message->seq) ? $messaging->message->seq : null;
        $message->text = isset($messaging->message->text) ? $messaging->message->text : null;
        $message->attachments = isset($messaging->message->attachments) ? $messaging->message->attachments : null;
        $messages[] = $message;
//        return $this->_receivedMessages;
        return $messages;
    }

    public function getGamePlayMessages()
    {
        $this->run();
        $messaging = $this->_event;
        $messages = [];
        $message = new stdClass();
        $message->entryId = isset($this->_entryId) ? $this->_entryId : null;
        $message->senderId = isset($messaging->sender->id) ? $messaging->sender->id : null;
        $message->recipientId = isset($messaging->recipient->id) ? $messaging->recipient->id : null;
        $message->timestamp = isset($messaging->timestamp) ? $messaging->timestamp : null;
        $message->gameId = isset($messaging->game_play->game_id) ? $messaging->game_play->game_id : null;
        $message->playerId = isset($messaging->game_play->player_id) ? $messaging->game_play->player_id : null;
        $message->contextType = isset($messaging->game_play->context_type) ? $messaging->game_play->context_type : null;
        $message->contextId = isset($messaging->game_play->context_id) ? $messaging->game_play->context_id : null;
        $message->score = isset($messaging->game_play->score) ? $messaging->game_play->score : null;
        $message->payload = isset($messaging->game_play->payload) ? $messaging->game_play->payload : null;
        $messages[] = $message;
//        return $this->_receivedMessages;
        return $messages;
    }

    public function getEvent()
    {
        return $this->_event;
    }
    public function getPageAccessToken()
    {
        return $this->_pageAccessToken;
    }
    public function getValidationToken()
    {
        return $this->_validationToken;
    }
    private function setupWebhook()
    {
        if(isset($_REQUEST['hub_challenge']) && isset($_REQUEST['hub_verify_token']) && $this->getValidationToken()==$_REQUEST['hub_verify_token'])
        {
            echo $_REQUEST['hub_challenge'];
            exit;
        }
    }
    public function sendTextMessage($recipientId, $text)
    {
        $url = "https://graph.facebook.com/v2.6/me/messages?access_token=%s";
        $url = sprintf($url, $this->_pageAccessToken);
        $recipient = new stdClass();
        $recipient->id = $recipientId;
        $message = new stdClass();
        $message->text = $text;
        $parameters = ['recipient' => $recipient, 'message' => $message];
        $response = self::executePost($url, $parameters, true);
        if($response)
        {
            $responseObject = json_decode($response);
            return is_object($responseObject) && isset($responseObject->recipient_id) && isset($responseObject->message_id);
        }
        return false;
    }
    public function sendButtonMessage($recipientId, $contextId, $title, $button_title)
    {
        $url = "https://graph.facebook.com/v2.6/me/messages?access_token=%s";
        $url = sprintf($url, $this->_pageAccessToken);
        $recipient = new stdClass();
        $recipient->id = $recipientId;
        $message = new stdClass();
        $message->attachment = [
            "type" => "template",
            "payload" => [
                "template_type" => "generic",
                "elements" => [
                    [
                        "title" => $title,
                        "buttons" => [
                            [
                                "type" => "game_play",
                                "title" => $button_title,
                                "payload" => '{}',
                                "game_metadata" => ["context_id"=>$contextId]
                            ]
                        ]
                    ],
                ]
            ]
        ];
        $parameters = [
            "messaging_type" => "UPDATE",
            'recipient' => $recipient,
            'message' => $message];
        $response = self::executePost($url, $parameters, true);
        if($response)
        {
            $responseObject = json_decode($response);
            return is_object($responseObject) && isset($responseObject->recipient_id) && isset($responseObject->message_id);
        }
        return false;
    }

    public function run()
    {
        $request = self::getJsonRequest();
        //var_dump($request);
        if(!$request) return;
        $entries = isset($request->entry) ? $request->entry : null;
        if(!$entries) return;
//        $messages = [];
        foreach ($entries as $entry)
        {
            $messagingList = isset($entry->messaging) ? $entry->messaging : null;
            if(!$messagingList) continue;
            $this->_entryId = isset($entry->id) ? $entry->id : null;
            foreach ($messagingList as $messaging)
            {
                $this->_event = $messaging;
//                $message = new stdClass();
//                $message->entryId = isset($entry->id) ? $entry->id : null;
//                $message->senderId = isset($messaging->sender->id) ? $messaging->sender->id : null;
//                $message->recipientId = isset($messaging->recipient->id) ? $messaging->recipient->id : null;
//                $message->timestamp = isset($messaging->timestamp) ? $messaging->timestamp : null;
//                $message->messageId = isset($messaging->message->mid) ? $messaging->message->mid : null;
//                $message->sequenceNumber = isset($messaging->message->seq) ? $messaging->message->seq : null;
//                $message->text = isset($messaging->message->text) ? $messaging->message->text : null;
//                $message->attachments = isset($messaging->message->attachments) ? $messaging->message->attachments : null;
//                $messages[] = $message;
            }
        }
//        $this->_receivedMessages = $messages;
    }

    private static function getJsonRequest()
    {
        $content = file_get_contents("php://input",true);
        $return=json_decode($content, false, 512, JSON_BIGINT_AS_STRING);
        return $return;
    }
    private static function executePost($url, $parameters, $json = false)
    {
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        if($json)
        {
            $data = json_encode($parameters);
            curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "POST");
            curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
            curl_setopt($ch, CURLOPT_HTTPHEADER, array('Content-Type: application/json', 'Content-Length: ' . strlen($data)));
        }
        else
        {
            curl_setopt($ch, CURLOPT_POST, count($parameters));
            curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($parameters));
        }
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
        $response = curl_exec($ch);
        curl_close($ch);
        return $response;
    }
}
