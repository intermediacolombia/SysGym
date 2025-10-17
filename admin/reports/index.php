<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Mensajes Enviados - ActivGYM</title>
    <!-- DataTables CSS -->
    <link rel="stylesheet" href="https://cdn.datatables.net/1.13.4/css/jquery.dataTables.min.css">
    <!-- jQuery -->
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <!-- DataTables JS -->
    <script src="https://cdn.datatables.net/1.13.4/js/jquery.dataTables.min.js"></script>
    <style>
        body {
            font-family: Arial, sans-serif;
            margin: 20px;
        }
        h1 {
            margin-bottom: 20px;
        }
        table.dataTable thead th {
            background-color: #f2f2f2;
        }
    </style>
</head>
<body>
    <h1>Mensajes Enviados - ActivGYM</h1>
    <table id="mensajesTable" class="display" style="width:100%">
        <thead>
            <tr>
                <th>ID</th>
                <th>Teléfono</th>
                <th>Texto</th>
                <th>Status</th>
                <th>Status Info</th>
                <th>Delivery</th>
                <th>Fecha Creación</th>
                <th>Fecha Ejecución</th>
                <th>URL</th>
            </tr>
        </thead>
    </table>

    <script>
        $(document).ready(function() {
            $('#mensajesTable').DataTable({
                ajax: {
                    url: 'get_all_send.php', // Archivo que genera el JSON
                    dataSrc: 'data.data'     // Ajusta este path según la estructura de tu JSON
                },
                columns: [
                    { data: 'id' },
                    { data: 'phonenumber' },
                    { 
                        data: 'text',
                        render: function(data, type, row) {
                            // Reemplaza saltos de línea por <br> para que se visualice correctamente
                            return data.replace(/\n/g, "<br>");
                        }
                    },
                    { data: 'status' },
                    { data: 'statusInfo' },
                    { data: 'delivery' },
                    { data: 'createdAt' },
                    { data: 'executedAt' },
                    { 
                        data: 'url',
                        render: function(data, type, row) {
                            return data ? '<a href="'+data+'" target="_blank">Ver PDF</a>' : 'Sin URL';
                        }
                    }
                ],
                language: {
                    url: '//cdn.datatables.net/plug-ins/1.13.4/i18n/es-ES.json'
                }
            });
        });
    </script>
</body>
</html>
