<!DOCTYPE html>

<html lang="es">

<head>
    
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Confirmación de Asistencia</title>

    <link rel="stylesheet"
        href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">

    <style>

        *{
            margin:0;
            padding:0;
            box-sizing:border-box;
        }

        body{
            font-family:Arial;
            background:#0f172a;
            color:white;
            min-height:100vh;
            padding:20px;
        }

        .container{
            max-width:1400px;
            margin:auto;
        }

        .card{
            background:#1e293b;
            border-radius:20px;
            padding:25px;
            box-shadow:0 10px 30px rgba(0,0,0,.3);
        }

        h1{
            margin-bottom:20px;
            color:#22c55e;
        }

        select{
            width:100%;
            padding:14px;
            border:none;
            border-radius:12px;
            margin-bottom:25px;
            background:#334155;
            color:white;
            font-size:15px;
        }

        table{
            width:100%;
            border-collapse:collapse;
        }

        thead{
            background:#334155;
        }

        th{
            padding:15px;
            text-align:left;
            font-size:14px;
        }

        td{
            padding:15px;
            border-bottom:1px solid rgba(255,255,255,.08);
            vertical-align:middle;
        }

        tr:hover{
            background:rgba(255,255,255,.03);
        }

        .foto{
            width:65px;
            height:65px;
            border-radius:50%;
            object-fit:cover;
            border:3px solid #22c55e;
        }

        .presente{
            color:#22c55e;
            font-weight:bold;
        }

        .ausente{
            color:#ef4444;
            font-weight:bold;
        }

        .estado-box{
            display:inline-block;
            padding:8px 14px;
            border-radius:20px;
            font-size:13px;
        }

        .info-nombre{
            display:flex;
            flex-direction:column;
        }

        .info-nombre small{
            color:#94a3b8;
            margin-top:5px;
        }

        .hora{
            color:#cbd5e1;
            font-size:14px;
        }

        .checkbox{
            transform:scale(1.4);
            cursor:pointer;
        }

        .vacio{
            text-align:center;
            padding:40px;
            color:#94a3b8;
        }

        .botones{
            margin-top:25px;
            display:flex;
            gap:15px;
        }

        button{
            border:none;
            padding:14px 22px;
            border-radius:12px;
            font-weight:bold;
            cursor:pointer;
            transition:.2s;
            color:white;
        }

        button:hover{
            transform:scale(1.03);
        }

        .btn-confirmar{
            background:#22c55e;
        }

        .btn-pdf{
            background:#3b82f6;
        }

        #mensaje{
            margin-top:25px;
            padding:16px;
            border-radius:12px;
            display:none;
            font-weight:bold;
        }

        .ok{
            background:#166534;
            color:white;
        }

        .error{
            background:#991b1b;
            color:white;
        }

    </style>
</head>

<body>

    <div class="container">

        <div class="card">

            <h1>
                <i class="fas fa-clipboard-check"></i>
                Confirmación de Asistencia
            </h1>

            <select id="curso">
                <option value="">
                    Seleccionar curso
                </option>
            </select>

            <div id="tabla">

                <div class="vacio">
                    Selecciona un curso
                </div>

            </div>

            <div class="botones">

                <button
                    class="btn-confirmar"
                    onclick="guardarAsistencia()">

                    <i class="fas fa-check-circle"></i>
                    Confirmar Asistencia

                </button>

                <button
                    class="btn-pdf"
                    onclick="enviarPDF()">

                    <i class="fas fa-file-pdf"></i>
                    Enviar PDF al Correo

                </button>

            </div>

            <div id="mensaje"></div>

        </div>

    </div>

<script>

window.onload = function () {

    cargarCursos();

    const curso =
        document.getElementById('curso');

    if (curso) {

        curso.addEventListener(
            'change',
            cargarEstudiantes
        );
    }
};

// ======================================================
// CARGAR CURSOS
// ======================================================
async function cargarCursos() {

    try {

        const res = await fetch(
            'asistencia_api.php?action=get_cursos'
        );

        const cursos = await res.json();

        console.log(cursos);

        const select =
            document.getElementById('curso');

        if (!select) return;

        cursos.forEach(curso => {

            const option =
                document.createElement('option');

            option.value = curso.id;

            option.textContent =
                curso.nombre + ' - Salón ' + curso.salon;

            select.appendChild(option);
        });

    } catch (error) {

        console.error(error);

        mostrarMensaje(
            'Error cargando cursos',
            false
        );
    }
}

// ======================================================
// CARGAR ESTUDIANTES
// ======================================================
async function cargarEstudiantes() {

    const curso =
        document.getElementById('curso');

    if (!curso) return;

    const cursoId = curso.value;

    if (!cursoId) return;

    const tabla =
        document.getElementById('tabla');

    if (!tabla) return;

    tabla.innerHTML =
        '<div class="vacio">Cargando estudiantes...</div>';

    try {

        const res = await fetch(
            'asistencia_api.php?action=get_estudiantes&curso_id='
            + cursoId
        );

        const estudiantes =
            await res.json();

        console.log(estudiantes);

        let html = '';

        html += '<table>';

        html += `
            <thead>
                <tr>
                    <th>Foto</th>
                    <th>Nombre</th>
                    <th>Correo</th>
                    <th>Estado</th>
                    <th>Hora</th>
                    <th>Confirmar</th>
                </tr>
            </thead>
        `;

        html += '<tbody>';

        estudiantes.forEach(est => {

            console.log(est);

            let presente = false;

            if (
                est.presente == 1 ||
                est.presente === true ||
                est.presente === 'true' ||
                est.presente === 't'
            ) {
                presente = true;
            }

            let checked =
                presente ? 'checked' : '';

            let foto =
                est.foto
                ? est.foto
                : 'https://i.imgur.com/6VBx3io.png';

            let hora =
                est.hora_registro
                ? est.hora_registro
                : '--:--';

            html += `
                <tr>

                    <td>
                        <img
                            src="${foto}"
                            class="foto"
                        >
                    </td>

                    <td>
                        <div class="info-nombre">

                            <strong>
                                ${est.nombre}
                                ${est.apellidos}
                            </strong>

                            <small>
                                ID: ${est.id}
                            </small>

                        </div>
                    </td>

                    <td>
                        ${est.correo}
                    </td>

                    <td>
                        ${
                            presente
                            ? '<span class="estado-box presente">PRESENTE</span>'
                            : '<span class="estado-box ausente">AUSENTE</span>'
                        }
                    </td>

                    <td class="hora">
                        ${hora}
                    </td>

                    <td>
                        <input
                            type="checkbox"
                            class="checkbox chk"
                            value="${est.id}"
                            ${checked}
                        >
                    </td>

                </tr>
            `;
        });

        html += '</tbody>';
        html += '</table>';

        tabla.innerHTML = html;

    } catch (error) {

        console.error(error);

        tabla.innerHTML =
            '<div class="vacio">Error cargando estudiantes</div>';
    }
}

// ======================================================
// GUARDAR ASISTENCIA
// ======================================================
async function guardarAsistencia() {

    const curso =
        document.getElementById('curso');

    if (!curso || !curso.value) {

        mostrarMensaje(
            'Selecciona un curso',
            false
        );

        return;
    }

    let estudiantes = [];

    document
        .querySelectorAll('.chk')
        .forEach(chk => {

            estudiantes.push({

                estudiante_id: chk.value,

                presente:
                    chk.checked ? 1 : 0
            });
        });

    try {

        const res = await fetch(
            'confirmar_asistencia_cursos.php',
            {
                method: 'POST',

                headers: {
                    'Content-Type':
                        'application/json'
                },

                body: JSON.stringify({

                    curso_id:
                        curso.value,

                    estudiantes:
                        estudiantes
                })
            }
        );

        const data =
            await res.json();

        console.log(data);

        if (data.success) {

            mostrarMensaje(
                '✅ Asistencia confirmada correctamente',
                true
            );

            cargarEstudiantes();

        } else {

            mostrarMensaje(
                data.message,
                false
            );
        }

    } catch (error) {

        console.error(error);

        mostrarMensaje(
            'Error del servidor',
            false
        );
    }
}

// ======================================================
// ENVIAR PDF
// ======================================================
async function enviarPDF() {

    const curso =
        document.getElementById('curso');

    if (!curso || !curso.value) {

        mostrarMensaje(
            'Selecciona un curso',
            false
        );

        return;
    }

    try {

        const res = await fetch(
            'generar_pdf.php',
            {
                method: 'POST',

                headers: {
                    'Content-Type':
                        'application/json'
                },

                body: JSON.stringify({

                    curso_id:
                        curso.value
                })
            }
        );

        const data =
            await res.json();

        if (data.success) {

            mostrarMensaje(
                '✅ PDF enviado correctamente',
                true
            );

        } else {

            mostrarMensaje(
                data.message,
                false
            );
        }

    } catch (error) {

        console.error(error);

        mostrarMensaje(
            'Error enviando PDF',
            false
        );
    }
}

// ======================================================
// MOSTRAR MENSAJE
// ======================================================
function mostrarMensaje(texto, ok) {

    const div =
        document.getElementById('mensaje');

    if (!div) return;

    div.style.display = 'block';

    div.className =
        ok ? 'ok' : 'error';

    div.innerHTML = texto;
}

</script>

</body>
<?php include 'mascota.php'; ?>
</html>