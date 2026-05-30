<?php
session_start();
include 'mascota.php';
?>
<!DOCTYPE html>
<html lang="es">
<head>

<meta charset="UTF-8">

<meta name="viewport" content="width=device-width, initial-scale=1.0">

<title>Verificador de Certificados UMG</title>

<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">

<style>

*{
    margin:0;
    padding:0;
    box-sizing:border-box;
}

body{

    font-family:'Segoe UI', sans-serif;

    min-height:100vh;

    background:
        linear-gradient(
            135deg,
            #0f172a,
            #1e3a8a,
            #2563eb
        );

    display:flex;

    justify-content:center;

    align-items:center;

    padding:30px;
}

/* =========================================
   TARJETA PRINCIPAL
========================================= */

.container{

    width:100%;

    max-width:750px;

    background:rgba(255,255,255,.95);

    backdrop-filter:blur(12px);

    border-radius:24px;

    overflow:hidden;

    box-shadow:
        0 15px 40px rgba(0,0,0,.25);
}

/* =========================================
   HEADER
========================================= */

.header{

    background:
        linear-gradient(
            135deg,
            #0b3d91,
            #2563eb
        );

    padding:35px;

    text-align:center;

    position:relative;
}

.logo{

    width:90px;

    height:90px;

    object-fit:contain;

    background:white;

    border-radius:50%;

    padding:10px;

    box-shadow:0 5px 20px rgba(0,0,0,.2);
}

.header h1{

    color:white;

    margin-top:18px;

    font-size:30px;

    font-weight:700;
}

.header p{

    color:rgba(255,255,255,.9);

    margin-top:8px;

    font-size:15px;
}

/* =========================================
   CONTENIDO
========================================= */

.content{

    padding:40px;
}

/* =========================================
   BOTON MENU
========================================= */

.btn-menu{

    display:inline-flex;

    align-items:center;

    gap:10px;

    background:#0f172a;

    color:white;

    text-decoration:none;

    padding:12px 18px;

    border-radius:12px;

    font-size:14px;

    margin-bottom:25px;

    transition:.3s;
}

.btn-menu:hover{

    background:#1e293b;

    transform:translateY(-2px);
}

/* =========================================
   UPLOAD
========================================= */

.upload-box{

    border:3px dashed #94a3b8;

    border-radius:18px;

    padding:45px;

    text-align:center;

    background:#f8fafc;

    cursor:pointer;

    transition:.3s;
}

.upload-box:hover{

    border-color:#2563eb;

    background:#eff6ff;
}

.upload-box i{

    font-size:55px;

    color:#2563eb;

    margin-bottom:15px;
}

.upload-box p{

    color:#334155;

    font-size:17px;
}

.upload-box small{

    display:block;

    margin-top:10px;

    color:#64748b;
}

input[type=file]{

    display:none;
}

/* =========================================
   BOTON
========================================= */

button{

    width:100%;

    margin-top:25px;

    padding:16px;

    border:none;

    border-radius:14px;

    background:
        linear-gradient(
            135deg,
            #2563eb,
            #1d4ed8
        );

    color:white;

    font-size:17px;

    font-weight:600;

    cursor:pointer;

    transition:.3s;
}

button:hover{

    transform:translateY(-2px);

    box-shadow:0 10px 20px rgba(37,99,235,.35);
}

/* =========================================
   RESULTADO
========================================= */

#resultado{

    margin-top:30px;

    border-radius:18px;

    padding:25px;

    display:none;

    animation:fade .4s ease;
}

@keyframes fade{

    from{
        opacity:0;
        transform:translateY(10px);
    }

    to{
        opacity:1;
        transform:translateY(0);
    }
}

.ok{

    background:#dcfce7;

    border:2px solid #22c55e;

    color:#166534;
}

.bad{

    background:#fee2e2;

    border:2px solid #ef4444;

    color:#991b1b;
}

.result-title{

    font-size:24px;

    margin-bottom:18px;

    font-weight:700;
}

.info{

    line-height:1.9;

    font-size:15px;
}

.hash{

    margin-top:15px;

    padding:12px;

    background:rgba(255,255,255,.7);

    border-radius:10px;

    word-break:break-all;

    font-size:12px;
}

/* =========================================
   FOOTER
========================================= */

.footer{

    margin-top:25px;

    text-align:center;

    color:#64748b;

    font-size:13px;
}

</style>

</head>
<body>

<div class="container">

    <!-- HEADER -->
    <div class="header">

        <img
            src="logo_umg.png"
            alt="UMG"
            class="logo"
        >

        <h1>
            Verificador de Certificados
        </h1>

        <p>
            Universidad Mariano Gálvez de Guatemala
        </p>

    </div>

    <!-- CONTENT -->
    <div class="content">

        <!-- BOTON MENU -->
        <a
            href="menu_admin.php"
            class="btn-menu"
        >
            <i class="fas fa-arrow-left"></i>
            Regresar al menú
        </a>

        <!-- FORM -->
        <form id="formulario">

            <label class="upload-box">

                <input
                    type="file"
                    id="pdf"
                    name="pdf"
                    accept="application/pdf"
                    required
                >

                <i class="fas fa-file-pdf"></i>

                <p id="textoArchivo">
                    Seleccione un certificado PDF
                </p>

                <small>
                    El sistema verificará automáticamente
                    el hash criptográfico del documento
                </small>

            </label>

            <button type="submit">

                <i class="fas fa-shield-check"></i>

                Verificar Certificado

            </button>

        </form>

        <!-- RESULTADO -->
        <div id="resultado"></div>

        <div class="footer">

            Sistema Oficial de Validación UMG

        </div>

    </div>

</div>

<script>

const inputPDF =
    document.getElementById('pdf');

const textoArchivo =
    document.getElementById('textoArchivo');

inputPDF.addEventListener('change', () => {

    if(inputPDF.files.length > 0){

        textoArchivo.innerHTML =

            '<strong>' +

            inputPDF.files[0].name +

            '</strong>';
    }
});

document
.getElementById('formulario')
.addEventListener('submit', async function(e){

    e.preventDefault();

    const resultado =
        document.getElementById('resultado');

    resultado.style.display = 'block';

    resultado.className = '';

    resultado.innerHTML = `

        <div class="result-title">
            ⏳ Verificando certificado...
        </div>

        Validando hash criptográfico
        y autenticidad del documento.

    `;

    const formData = new FormData(this);

    try{

        const response = await fetch(
            'verificar_pdf.php',
            {
                method:'POST',
                body:formData
            }
        );

        const data = await response.json();

        if(data.success){

            resultado.classList.add('ok');

            resultado.innerHTML = `

                <div class="result-title">

                    ✅ CERTIFICADO VÁLIDO

                </div>

                <div class="info">

                    <strong>Estudiante:</strong>
                    ${data.usuario.nombre}<br>

                    <strong>Correo:</strong>
                    ${data.usuario.correo}<br>

                    <strong>Carrera:</strong>
                    ${data.usuario.carrera}<br>

                    <strong>Semestre:</strong>
                    ${data.usuario.semestre}<br>

                    <strong>Sección:</strong>
                    ${data.usuario.seccion}<br>

                    <strong>Estado:</strong>
                    ${data.estado}

                </div>

                <div class="hash">

                    <strong>HASH SHA256:</strong><br>

                    ${data.hash}

                </div>
            `;

        }else{

            resultado.classList.add('bad');

            resultado.innerHTML = `

                <div class="result-title">

                    ❌ CERTIFICADO INVÁLIDO

                </div>

                ${data.message}
            `;
        }

    }catch(error){

        resultado.classList.add('bad');

        resultado.innerHTML = `

            <div class="result-title">

                ❌ ERROR

            </div>

            ${error.message}

        `;
    }

});

</script>

</body>
</html>