<!DOCTYPE html>
<html>
<head>
  <title>Identificación Facial</title>
</head>
<body>
  <h2>Capturar rostro</h2>
  <video id="video" width="320" height="240" autoplay></video>
  <button id="captureBtn">Capturar Imagen</button>
  <canvas id="canvas" width="320" height="240" style="display:none"></canvas>
  <p id="status">Esperando...</p>

  <script>
    const video = document.getElementById('video');
    const canvas = document.getElementById('canvas');
    const context = canvas.getContext('2d');
    const captureBtn = document.getElementById('captureBtn');

    // Acceder a la cámara del usuario
    navigator.mediaDevices.getUserMedia({ video: true })
      .then(stream => {
        video.srcObject = stream;
      })
      .catch(err => {
        console.error("Error al acceder a la cámara:", err);
      });

    // Capturar una imagen del video
    captureBtn.addEventListener('click', () => {
      context.drawImage(video, 0, 0, canvas.width, canvas.height);
      const imageData = canvas.toDataURL('image/png'); // Convertir a base64

      // Enviar la imagen al servidor PHP para el reconocimiento facial
      fetch('procesar_rosto.php', {
        method: 'POST',
        body: JSON.stringify({ image: imageData }),
        headers: { 'Content-Type': 'application/json' }
      })
      .then(response => response.json())
      .then(data => {
        document.getElementById('status').textContent = data.message;
      })
      .catch(err => console.error("Error al procesar la imagen:", err));
    });
  </script>
</body>
</html>
