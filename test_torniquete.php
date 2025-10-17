<!DOCTYPE html>
<html>
<head>
  <title>Controlar Torniquete desde el Navegador</title>
</head>
<body>
  <h2>Controlar Torniquete</h2>
  <button id="connectBtn">Conectar Torniquete</button>
  <button id="openBtn" disabled>Abrir Torniquete</button>
  <p id="status">Estado: desconectado</p>

  <script>
    let port;
    let writer;

    document.getElementById('connectBtn').addEventListener('click', async () => {
      try {
        port = await navigator.serial.requestPort();
        await port.open({ baudRate: 9600 });

        writer = port.writable.getWriter();

        document.getElementById('status').textContent = "Estado: conectado";
        document.getElementById('openBtn').disabled = false;
      } catch (err) {
        alert("Error al conectar con el torniquete: " + err);
      }
    });

    document.getElementById('openBtn').addEventListener('click', async () => {
      if (!writer) return;

      const encoder = new TextEncoder();
      const data = encoder.encode("ABRIR\n"); // Usa el comando que el torniquete entienda
      await writer.write(data);

      document.getElementById('status').textContent = "Estado: comando enviado";
    });
  </script>
</body>
</html>
