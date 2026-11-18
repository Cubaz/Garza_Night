# Guía técnica para la presentación de Garza Night

Esta guía contiene respuestas breves y verificables para preguntas sobre funcionamiento, tecnologías, seguridad y decisiones de programación.

## Resumen en 30 segundos

Garza Night es una aplicación web desarrollada con PHP, MySQL/MariaDB, HTML, CSS y JavaScript. Permite a estudiantes de la UAEH publicar eventos, registrarse, consultar asistencia, guardar favoritos, comentar y administrar el sistema. PHP genera las páginas y ejecuta la lógica; PDO accede a MySQL; JavaScript usa Fetch para actualizar acciones sin recargar.

## Preguntas frecuentes

### ¿Qué problema resuelve el proyecto?

Centraliza eventos universitarios y permite saber qué actividades existen, dónde serán, cuándo ocurrirán, quién las organiza y cuántas personas confirmaron asistencia.

### ¿Qué lenguaje de programación usa el backend?

PHP. Se ejecuta dentro de Apache, normalmente desde XAMPP.

### ¿Qué se usa para la base de datos?

MySQL o MariaDB. El esquema usa el motor InnoDB y está diseñado para mantener integridad referencial.

### ¿Qué tecnología conecta PHP con MySQL?

PDO, la interfaz orientada a objetos de PHP para bases de datos. Se configura en `includes/db.php` y usa consultas preparadas reales.

### ¿Por qué se usan consultas preparadas?

Los datos externos pasan como parámetros separados de la SQL. Esto evita que texto introducido por el usuario sea interpretado como una instrucción SQL.

### ¿Qué hace la capa `includes/`?

Contiene funciones compartidas: configuración, conexión, sesiones, permisos, CSRF, fechas, formularios, consultas de eventos y utilidades de comentarios.

### ¿Qué hace la carpeta `acciones/`?

Contiene endpoints que reciben operaciones desde JavaScript y devuelven JSON. Son utilizados por asistencia, favoritos y comentarios.

### ¿La aplicación usa un framework?

No. Está hecha con PHP nativo y JavaScript nativo. El objetivo es comprender el funcionamiento del servidor, las sesiones, las consultas y el DOM sin una abstracción adicional.

### ¿Qué hace JavaScript?

JavaScript mejora la experiencia: filtra al cambiar una opción, muestra la vista previa de una imagen, valida contraseñas y actualiza botones, contadores y comentarios mediante Fetch API.

### ¿Qué es Fetch API?

Una API moderna del navegador para realizar peticiones HTTP. `assets/js/app.js` contiene `enviarPost()`, que convierte datos a JSON y añade el token CSRF.

### ¿Cómo viajan los filtros del feed?

Por la URL con el método GET. Búsqueda, temática, fecha, costo y orden se convierten en parámetros. PHP los lee, los valida y construye una consulta segura.

### ¿Qué diferencia existe entre el feed y el histórico?

El feed consulta eventos con fecha igual o posterior a hoy. El histórico consulta eventos anteriores y activos.

### ¿Cómo se calcula si un evento está finalizado?

Se compara su fecha con la fecha actual del servidor. Si la fecha ya pasó, se considera finalizado.

### ¿Cómo se valida el registro?

Se valida en HTML y JavaScript como ayuda visual, pero PHP realiza nuevamente todas las comprobaciones. Los datos no se confían al navegador.

### ¿Qué formato deben tener los correos institucionales?

El proyecto acepta el patrón `[dos letras][seis números]@uaeh.edu.mx`, por ejemplo `ab123456@uaeh.edu.mx`.

### ¿Cómo se almacenan las contraseñas?

Con `password_hash()` mediante el algoritmo seguro que determine PHP. Nunca se guarda la contraseña original. El inicio de sesión usa `password_verify()`.

### ¿Qué requisitos tiene una contraseña?

Debe tener al menos ocho caracteres y contener letras y números.

### ¿Cómo funciona el inicio de sesión?

Se busca el correo en la tabla `usuarios`, se verifica el hash y se guarda en sesión un conjunto reducido de datos: id, nombre, correo y rol.

### ¿Qué es una sesión PHP?

Es un estado asociado al usuario mientras navega. PHP conserva un identificador en una cookie y los datos del usuario en `$_SESSION`.

### ¿Por qué se regenera el ID de sesión?

`session_regenerate_id(true)` se ejecuta después del inicio de sesión para reducir el riesgo de fijación de sesión.

### ¿Qué hace el token CSRF?

Un token aleatorio impide que un sitio externo haga peticiones POST fingiendo ser el usuario autenticado. Los formularios y los endpoints Fetch lo requieren.

### ¿Qué es CSRF?

Cross-Site Request Forgery. Es un ataque en el que un sitio engaña al navegador para que envíe una petición a otro sitio donde el usuario tiene sesión activa.

### ¿Cómo se protegen los formularios?

Con `csrf_campo()` y `csrf_exigir()`. Además, las operaciones POST requieren el método correcto.

### ¿Qué consultas se usan para leer datos?

Consultas preparadas mediante `prepare()` y `execute()`. Las claves vienen de la base de datos y los resultados se solicitan con el modo asociativo configurado en PDO.

### ¿Cómo se evita la inyección SQL?

No se inserta directamente el texto del usuario dentro del SQL. Se utilizan parámetros representados por `?`.

### ¿Cómo se evita XSS?

Los datos mostrados en HTML pasan por `e()`, que usa `htmlspecialchars()`. En comentarios dinámicos JavaScript usa `textContent` en lugar de `innerHTML`.

### ¿Qué es XSS?

Cross-Site Scripting: una vulnerabilidad que permite insertar código HTML o JavaScript mediante datos que no fueron escapados.

### ¿Cómo funciona la asistencia?

El usuario confirma una acción. JavaScript envía el id del evento y `apuntar` o `cancelar`. PHP inicia una transacción, consulta el estado y modifica `asistencias`.

### ¿Cómo se evita que dos usuarios rebasen el cupo?

El servidor bloquea temporalmente la fila del evento con `FOR UPDATE` y vuelve a calcular asistentes y capacidad dentro de la transacción.

### ¿Qué hace una transacción?

Agrupa operaciones para que se confirmen juntas. Si ocurre un error, `rollBack()` restaura el estado anterior.

### ¿Por qué `FOR UPDATE`?

Hace que solicitudes concurrentes esperen y evita que varias personas lean simultáneamente el mismo cupo disponible.

### ¿Qué ocurre si un usuario intenta apuntarse dos veces?

El servidor verifica si ya existe. Además, la tabla tiene una clave única compuesta por evento y usuario.

### ¿Qué es una clave única compuesta?

Es una regla que impide repetir la combinación `(evento_id, usuario_id)`.

### ¿Por qué el organizador no puede asistir?

El código considera que su rol ya cuenta como responsable del evento. Además, puede consultar directamente la lista de asistentes.

### ¿Cómo se agregan favoritos?

PHP usa `INSERT IGNORE` contra la clave única del par evento-usuario. Así, duplicados no generan registros adicionales.

### ¿Cómo se muestran los comentarios?

Se consultan por evento y se ordenan por fecha. JavaScript crea un elemento `<li>` y asigna cada dato con propiedades DOM o `textContent`.

### ¿Qué pasa si un comentario supera 200 caracteres?

El servidor devuelve 422 con un mensaje. También existe `maxlength="200"` en el formulario.

### ¿Por qué se valida un dato dos veces?

La validación del navegador mejora la experiencia y la del servidor establece la seguridad real, porque las solicitudes HTTP pueden modificarse.

### ¿Cómo se cancelan eventos?

El organizador o administrador envía un POST. El sistema cambia el estado a `cancelado` y elimina todas las asistencias en una transacción.

### ¿Qué diferencia hay entre cancelar y eliminar?

Cancelar conserva el evento para mostrar su estado y quitar asistentes. Eliminar elimina la fila y los datos relacionados por cascada.

### ¿Qué son las cascadas?

Reglas `ON DELETE CASCADE` que hacen que MySQL elimine automáticamente registros dependientes al borrar un usuario o evento.

### ¿Por qué una temática no se puede eliminar si está en uso?

La llave foránea y `ON DELETE RESTRICT` protegen la integridad. Si se permite borrar la temática, quedaría sin clasificación algún evento.

### ¿Cómo se distinguen los roles?

La tabla `usuarios` tiene un `ENUM('usuario','admin')`. PHP usa `es_admin()` y `requerir_admin()`.

### ¿Qué ocurre si un usuario normal intenta entrar al panel?

`requerir_admin()` envía una respuesta HTTP 403 y detiene la ejecución.

### ¿Cómo están organizadas las páginas?

Hay páginas principales en la raíz, módulos compartidos en `includes/`, acciones JSON en `acciones/` y administración en `admin/`.

### ¿Qué patrón arquitectónico usa?

Es una arquitectura procedural modular más que un framework MVC completo. PHP separa por responsabilidad: vistas, validaciones, consultas compartidas, seguridad y endpoints.

### ¿Qué es el patrón post/redirect/get?

El servidor procesa un POST, guarda un mensaje flash y redirige con GET. Así, recargar no repite una operación.

### ¿Qué son los mensajes flash?

Mensajes guardados temporalmente en sesión y mostrados una sola vez después de una redirección.

### ¿Qué tablas existen?

`usuarios`, `tematicas`, `eventos`, `asistencias`, `favoritos` y `comentarios`.

### ¿Qué relaciones existen?

- Un usuario puede organizar muchos eventos.
- Un usuario puede asistir a muchos eventos.
- Un evento puede tener muchos asistentes.
- Un usuario puede guardar muchos eventos como favoritos.
- Un evento puede tener muchos comentarios.

### ¿Qué motor usa la base de datos?

InnoDB, que permite transacciones y llaves foráneas.

### ¿Qué es `utf8mb4`?

Una codificación que permite guardar caracteres Unicode, incluyendo acentos y otros símbolos.

### ¿Qué es una llave primaria?

Es un identificador único por fila, normalmente `id`.

### ¿Qué son las llaves foráneas?

Campos que hacen referencia a la llave primaria de otra tabla y mantienen relaciones válidas.

### ¿Cómo se ordenan los eventos?

Por fecha, popularidad o fecha de creación según el valor `orden`. PHP usa una lista blanca de opciones para impedir SQL arbitrario.

### ¿Cómo se limita el contenido público?

Los visitantes ven hasta `LIMITE_VISITANTES`, definido como seis en `includes/config.php`. Los usuarios autenticados ven el conjunto completo.

### ¿Qué pasa al abrir el detalle sin iniciar sesión?

`evento.php` exige login mediante `requerir_login()` y redirige a `login.php`.

### ¿Qué datos muestra el perfil?

Nombre, correo, carrera, descripción, fecha de registro y tres secciones: publicados, asistiré y favoritos.

### ¿Qué tecnologías CSS utiliza?

CSS3 con variables personalizadas, Grid, Flexbox, media queries y diseño responsivo.

### ¿Qué fuentes utiliza?

Google Fonts descarga Inter para texto y Space Grotesk para títulos.

### ¿Qué conceptos de accesibilidad se implementaron?

Etiquetas, `aria-*`, navegación por teclado, foco visible, textos alternativos parciales, atributos `role=status`, menú adaptable y preferencia de movimiento reducido.

### ¿Cómo se prueba el proyecto?

Creando usuarios, iniciando sesión como usuario y administrador, probando filtros, cupos, favoritos, comentarios, permisos y operaciones de cancelación o eliminación.

### ¿Qué respuestas HTTP son importantes?

- `200`: operación correcta.
- `400`: datos inválidos.
- `401`: falta iniciar sesión.
- `403`: token o permiso insuficiente.
- `404`: recurso inexistente.
- `405`: método HTTP incorrecto.
- `409`: conflicto de estado.
- `422`: contenido inválido.
- `500`: error interno no controlado.

## Explicación de un flujo completo

Ejemplo de asistencia:

1. El usuario pulsa `Apuntarme`.
2. `evento.js` deshabilita el botón y llama `enviarPost()`.
3. `app.js` agrega el token CSRF y usa Fetch API con JSON.
4. `acciones/asistir.php` valida método, sesión y CSRF.
5. PHP inicia una transacción y bloquea el evento.
6. Comprueba existencia, estado, fecha, organizador y cupo.
7. Inserta la asistencia o devuelve el conflicto correspondiente.
8. Confirma la transacción y responde JSON.
9. `evento.js` actualiza contador, cupo, etiqueta, mensaje y botón.

## Ideas de mejora futura

- Cambiar configuración sensible a variables de entorno.
- Habilitar desactivar o confirmar comentarios.
- Añadir una API REST formal.
- Implementar paginación.
- Subir imágenes al servidor en lugar de usar URLs externas.
- Agregar pruebas automatizadas.
- Registrar errores en archivos o un sistema de logs.
- Crear recuperación y cambio de contraseña.
- Agregar un rol adicional o permisos más detallados.
