<?php

try {
    $lokos = new PDO('firebird:dbname=seuhost:nomedobanco', 'SYSDBA', 'senhasua');
} catch (PDOException $e) {
    echo "Falha na conexão." . $e->getMessage();
    exit;
}

$query = "SELECT  * FROM ACESSO a WHERE STATUS = 0 AND foto <> ''";

$result = $lokos->query($query);

if ($result === false) {
    echo "Erro na execução da consulta: " . $lokos->errorInfo()[2] . "\n";
    exit;
}

$faceList = [];
$userCount = 5; // Contador de usuários adicionados

while ($row = $result->fetch(PDO::FETCH_ASSOC)) {
    $photoData = $row['FOTO'];

    // Verifica se $photoData está vazia ou nula
    if (empty($photoData) || $photoData === null || strlen($photoData) === 0) {
        echo "PhotoData vazia ou nula para UserID: " . $row['USERID'] . ". Pulando para o próximo usuário.<br>";
        continue; // Pula para o próximo usuário
    }

    if (is_resource($photoData)) {
        $photoData = stream_get_contents($photoData);
    }

    $photoData = base64_encode($photoData);

    $faceList[] = [
        "UserID" => $row['USERID'],
        "PhotoData" => [$photoData],
    ];

    $userCount++;

    echo "UserID: " . $row['USERID'] . "<br>";
    echo "PhotoData: " . $photoData . "<br><br>";

    // Adiciona em grupos de 10
    if ($userCount % 1 === 0) {
        sendToAPI($faceList); // Chama a função para enviar para a API
        $faceList = []; // Limpa a lista para o próximo grupo
    }
}

// Verifica se há usuários restantes para enviar
if (!empty($faceList)) {
    sendToAPI($faceList);
}

function sendToAPI($faceList) {
    $apiConfigs = [
        // [
        //     "url" => "http://IP-DA-MAQUINA/cgi-bin/AccessFace.cgi?action=insertMulti",
        //     "username" => "admin",
        //     "password" => "suasenha",
        // ],
        [
            "url" => "http://IP-DA-MAQUINA/cgi-bin/AccessFace.cgi?action=insertMulti",
            "username" => "admin",
            "password" => "suasenha",
        ],
        [
            "url" => "http://IP-DA-MAQUINA/cgi-bin/AccessFace.cgi?action=insertMulti",
            "username" => "admin",
            "password" => "suasenha",
        ],
        [
            "url" => "http://IP-DA-MAQUINA/cgi-bin/AccessFace.cgi?action=insertMulti",
            "username" => "admin",
            "password" => "suasenha",
        ],
        // catraca de saida
        [
            "url" => "http://IP-DA-MAQUINA/cgi-bin/AccessFace.cgi?action=insertMulti",
            "username" => "admin",
            "password" => "suasenha",
        ],
        [
            "url" => "http://IP-DA-MAQUINA/cgi-bin/AccessFace.cgi?action=insertMulti",
            "username" => "admin",
            "password" => "suasenha",
        ],
        [
            "url" => "http://IP-DA-MAQUINA/cgi-bin/AccessFace.cgi?action=insertMulti",
            "username" => "admin",
            "password" => "suasenha",
        ],
        [
            "url" => "http://IP-DA-MAQUINA/cgi-bin/AccessFace.cgi?action=insertMulti",
            "username" => "admin",
            "password" => "suasenha",
        ],
    ];

    foreach ($apiConfigs as $apiConfig) {
        $url = $apiConfig['url'];
        $username = $apiConfig['username'];
        $password = $apiConfig['password'];

        $payload = json_encode([
            "FaceList" => $faceList
        ], JSON_UNESCAPED_UNICODE);

        // Exibe informações para depuração
        echo "Payload enviado: $payload\n<br>";

        $headers = [
            'Content-Type: application/json',
            'Content-Length: ' . strlen($payload),
        ];

        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPAUTH, CURLAUTH_DIGEST);
        curl_setopt($ch, CURLOPT_USERPWD, "$username:$password");
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $payload);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);

        // Exibe informações adicionais
        $response = curl_exec($ch);
        $info = curl_getinfo($ch);
        curl_close($ch);

        echo "Resposta cURL: \n <br>";
        var_dump($info);
        echo "Corpo da resposta: \n<br>";
        var_dump($response);

        if ($response === false) {
            echo 'Erro cURL: <br>' . curl_error($ch);
        } else {
            echo $response;
        }
    }
}
?>