<!doctype html>
<html>
<head>
<meta charset="utf-8">
<title>Documento sin título</title>
</head>

<body>
	
	
<audio id="welcomeAudio"
       src="happy_birthday.mp3"
       preload="auto" 
       playsinline
       ></audio>

<script>
document.addEventListener('DOMContentLoaded', () => {
  const audio = document.getElementById('welcomeAudio');

  // 2️⃣  Intentar reproducir al cargar
  const intento = audio.play();

  // 3️⃣  Si el navegador lo bloquea, intento será una promesa rechazada
  if (intento !== undefined) {
    intento.catch(() => {
      // 4️⃣  Creamos un botón discreto para que el usuario lo habilite
      const btn = Object.assign(
        document.createElement('button'),
        { textContent: '🔊 Escuchar bienvenida' }
      );
      Object.assign(btn.style, {
        position: 'fixed', right: '1rem', bottom: '1rem',
        padding: '.75rem 1rem', fontSize: '1rem',
        background: '#00A859', color: '#fff', border: 'none',
        borderRadius: '8px', cursor: 'pointer', boxShadow: '0 2px 4px rgba(0,0,0,.2)'
      });

      btn.addEventListener('click', () => {
        audio.play();    // ahora sí hay gesto ➜ se permite el sonido
        btn.remove();    // ocultamos el botón
      });

      document.body.appendChild(btn);
    });
  }
});
</script>

	
</body>
</html>