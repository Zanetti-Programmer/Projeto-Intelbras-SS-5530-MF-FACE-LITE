<?php

$file = fopen('cadastrarusulog', 'a');

if (!$file) {
    exit('Erro ao abrir o arquivo.');
}

function writeToLogFile($file, $message)
{
    fwrite($file, date('d-m-Y H:i:s') . " - " . $message . "\n");
    echo $message . "<br>";
}

fwrite($file, "COMEÇO DO PROCESSO " . date('d-m-Y H:i:s') . "\n");

echo date('d-m-Y H:i:s') . '<br>';

try {
    $lokos = new PDO(firebird:localhost=yourlocalhost 0;dbname=ja sei   também",$user,$pass);
    $lokos->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    writeToLogFile($file, "Conexão com o banco de dados estabelecida.");

    $lokos->query("EXECUTE PROCEDURE CADASTRAR_ACESSO_CATRACA");
    writeToLogFile($file, "Execução da primeira stored procedure realizada com sucesso.");

    // Executar a segunda stored procedure
    $lokos->query("EXECUTE PROCEDURE CADASTRAR_ACESSO_CATRACA_PROF");
    writeToLogFile($file, "Execução da segunda stored procedure realizada com sucesso.");

    $lokos->query("EXECUTE PROCEDURE CADASTRAR_ACESSO_CATRACA_FUNC");
    writeToLogFile($file, "Execução da terceira stored procedure realizada com sucesso.");

    $device_ips = array(
      //Coloque os iP abaixo use , para separar os id e contatene com ' ' como no exemplo abaixo
      //recomendo tambem que crie um padrão de ip isso pode ser configurado direto no dispositivo
        '192.0.0.0',

    );

    $query = "SELECT * FROM ACESSO a WHERE STATUS = 0 AND foto <> ''";
    //use esse esse select para cadastrar usuario no dispostivo Status = significa que ainda não foram adicionado ao banco no final da pagina faça um update para 1
    $result = $lokos->query($query);

    if (!$result) {
        echo "Erro na execução da consulta. Mensagem de erro: " . $lokos->errorInfo()[2];
        exit;
    }
    $userList = [];
    $userCount = 0;

    while ($row = $result->fetch(PDO::FETCH_ASSOC)) {
        $photoData = $row['FOTO'];
        if (empty($photoData) || $photoData === null || strlen($photoData) === 0) {
            continue;
        }
        $photoData = base64_encode($photoData);

        $userList[] = [
            "UserID" => $row['USERID'],
            "UserName" => $row['USERNAME'],
            "UserType" => $row['USERTYPE'],
            "Authority" => $row['AUTHORITY'],
            "Password" => $row['PASSWORD'],
            "Doors" => [$row['DOORS']],
            "TimeSections" => [$row['TIMESECTIONS']],
            "ValidFrom" => $row['VALIDFROM'],
            "ValidTo" => $row['VALIDTO'],
            "PhotoData" => $photoData
        ];
    }

    $username = 'nome do seu dispostivo';
    $password = 'sua senha do dispostivo';
    
//recomendo que todos seus dispositivos tenham mesmo nome

    foreach ($userList as $user) {
        $userID = $user['UserID'];

        foreach ($device_ips as $device_ip) {
            $urlRemove = "http://{$device_ip}/cgi-bin/AccessUser.cgi?action=removeMulti&UserIDList[0]={$userID}";
            $chRemove = curl_init();
            curl_setopt($chRemove, CURLOPT_URL, $urlRemove);
            curl_setopt($chRemove, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($chRemove, CURLOPT_HTTPAUTH, CURLAUTH_DIGEST);
            curl_setopt($chRemove, CURLOPT_USERPWD, "{$username}:{$password}");
            curl_setopt($chRemove, CURLOPT_SSL_VERIFYPEER, false);
            $responseRemove = curl_exec($chRemove);
            curl_close($chRemove);
            // Verificar se a solicitação foi bem-sucedida
            if ($responseRemove === false) {
                echo "falha ao remover o usuario";
                // Lidar com erro de solicitação
            }

            $urlInsert = "http://{$device_ip}/cgi-bin/AccessUser.cgi?action=insertMulti";
            $chInsert = curl_init();
            curl_setopt($chInsert, CURLOPT_URL, $urlInsert);
            curl_setopt($chInsert, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($chInsert, CURLOPT_HTTPAUTH, CURLAUTH_DIGEST);
            curl_setopt($chInsert, CURLOPT_USERPWD, "{$username}:{$password}");
            curl_setopt($chInsert, CURLOPT_POST, true);
            curl_setopt($chInsert, CURLOPT_POSTFIELDS, json_encode(["UserList" => [$user]]));
            curl_setopt($chInsert, CURLOPT_HTTPHEADER, array('Content-Type: application/json'));
            $responseInsert = curl_exec($chInsert);
            curl_close($chInsert);

            // Verificar se a solicitação foi bem-sucedida
            if ($responseInsert === false) {
                echo "falha ao cadastrar o usuario";
                // Lidar com erro de solicitação
            }

            $payload = json_encode([
                "FaceList" => [
                    [
                        "UserID" => $user['UserID'],
                        "PhotoData" => [$user['PhotoData']],
                    ]
                ]
            ], JSON_UNESCAPED_UNICODE);

            $headers = [
                'Content-Type: application/json',
                'Content-Length: ' . strlen($payload),
            ];

            $urlFace = "http://{$device_ip}/cgi-bin/AccessFace.cgi?action=insertMulti";
            $chFace = curl_init();
            curl_setopt($chFace, CURLOPT_URL, $urlFace);
            curl_setopt($chFace, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($chFace, CURLOPT_HTTPAUTH, CURLAUTH_DIGEST);
            curl_setopt($chFace, CURLOPT_USERPWD, "{$username}:{$password}");
            curl_setopt($chFace, CURLOPT_SSL_VERIFYPEER, false);
            curl_setopt($chFace, CURLOPT_POSTFIELDS, $payload);
            curl_setopt($chFace, CURLOPT_HTTPHEADER, $headers);
            $responseFace = curl_exec($chFace);
            curl_close($chFace);

            // Verificar se a solicitação foi bem-sucedida
            if ($responseFace === false) {
                echo "Falha ao inserir Foto";
            }

            writeToLogFile($file, "Remoção de usuário ID: {$userID} - Dispositivo: {$device_ip} - Resposta: {$responseRemove}");
            writeToLogFile($file, "Inserção de usuário ID: {$userID} - Dispositivo: {$device_ip} - Resposta: {$responseInsert}");
            writeToLogFile($file, "Inserção de foto ID: {$userID} - Dispositivo: {$device_ip} - Resposta: {$responseFace}");
        }
    }

    
    $httpStatusRemove = curl_getinfo($chRemove, CURLINFO_HTTP_CODE);
    $httpStatusInsert = curl_getinfo($chInsert, CURLINFO_HTTP_CODE);
    $httpStatusFace = curl_getinfo($chFace, CURLINFO_HTTP_CODE);
    
    if ($httpStatusRemove == 200 && $httpStatusInsert == 200 && $httpStatusFace == 200) {
        $updateQuery = "faça um update para atualizar os que ja foram cadastrados";
        $stmt = $lokos->prepare($updateQuery);
        if ($stmt->execute()) {
            writeToLogFile($file, "Atualização no banco de dados realizada com sucesso");
        } else {
            writeToLogFile($file, "Erro ao atualizar o banco de dados");
        }
    } else {
        writeToLogFile($file, "Uma ou mais solicitações falharam");
    }
    
    
    $lokos = null;
} catch (PDOException $e) {
    error_log("Falha na conexão: " . $e->getMessage(), 0);
    writeToLogFile($file, "Falha na conexão: Por favor, contate o administrador.");
    exit("Falha na conexão: Por favor, contate o administrador.");
}

    fwrite($file, "FIM DO PROCESSO " . date('d-m-Y H:i:s') . "\n");

    fclose($file);
