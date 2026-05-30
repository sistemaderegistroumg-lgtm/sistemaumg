<?php

ob_start();

header('Content-Type: application/json');

require_once 'config.php';

try{

    if($_SERVER['REQUEST_METHOD'] !== 'POST'){

        throw new Exception(
            'Método no permitido'
        );
    }

    if(!isset($_FILES['pdf'])){

        throw new Exception(
            'Debe subir un PDF'
        );
    }

    $tmp = $_FILES['pdf']['tmp_name'];

    if(!file_exists($tmp)){

        throw new Exception(
            'Archivo inválido'
        );
    }

    // LEER PDF
    $contenido = file_get_contents($tmp);

    // ==========================================
    // EXTRAER HASH DESDE SUBJECT
    // ==========================================

    preg_match(
        '/HASH:([A-Fa-f0-9]{64})/',
        $contenido,
        $matches
    );

    if(empty($matches[1])){

        // ==========================================
        // EXTRAER HASH GENERAL
        // ==========================================

        preg_match(
            '/([A-Fa-f0-9]{64})/',
            $contenido,
            $matches
        );
    }

    if(empty($matches[1])){

        throw new Exception(
            'No se encontró hash dentro del PDF'
        );
    }

    $hash = $matches[1];

    // ==========================================
    // BUSCAR EN BASE DE DATOS
    // ==========================================

    $pdo = getDB();

    $stmt = $pdo->prepare("
        SELECT
            c.hash_certificado,
            c.estado,
            u.nombre,
            u.apellidos,
            u.correo,
            u.carrera,
            u.semestre,
            u.seccion
        FROM certificados c

        INNER JOIN usuarios u
            ON u.id = c.usuario_id

        WHERE c.hash_certificado = ?

        LIMIT 1
    ");

    $stmt->execute([$hash]);

    $cert = $stmt->fetch(PDO::FETCH_ASSOC);

    if(!$cert){

        throw new Exception(
            'El hash no existe en la base de datos'
        );
    }

    ob_clean();

    echo json_encode([

        'success' => true,

        'message' =>
            'Certificado válido',

        'hash' => $hash,

        'estado' =>
            $cert['estado'],

        'usuario' => [

            'nombre' =>
                $cert['nombre'] . ' ' .
                $cert['apellidos'],

            'correo' =>
                $cert['correo'],

            'carrera' =>
                $cert['carrera'],

            'semestre' =>
                $cert['semestre'],

            'seccion' =>
                $cert['seccion']
        ]
    ]);

}catch(Throwable $e){

    ob_clean();

    echo json_encode([

        'success' => false,

        'message' => $e->getMessage()
    ]);
}