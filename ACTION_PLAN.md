# 🚀 Plan de Acción - Sistema Requiem

**Fecha de Creación:** 2025-12-01  
**Responsable:** Equipo de Desarrollo  
**Estado:** 📋 PENDIENTE

---

## 📊 Resumen de Issues

| Prioridad | Total | Completados | Pendientes |
|-----------|-------|-------------|------------|
| 🔴 ALTA | 4 | 0 | 4 |
| 🟡 MEDIA | 3 | 0 | 3 |
| 🟢 BAJA | 0 | 0 | 0 |
| **TOTAL** | **7** | **0** | **7** |

---

## 🔴 Prioridad ALTA (Implementar Antes de Producción)

### Issue #1: Actualizar Schema SQL
**Descripción:** `database/schema.sql` no refleja la estructura real de la tabla `nres`

**Impacto:** 🔴 CRÍTICO - Nuevas instalaciones fallarán

**Esfuerzo:** ⏱️ 30 minutos

**Asignado a:** Backend Developer

**Pasos:**
1. Abrir `database/schema.sql`
2. Agregar campos faltantes en la definición de tabla `nres`:
   ```sql
   operation VARCHAR(50) AFTER item_code,
   customizer VARCHAR(100) AFTER operation,
   brand VARCHAR(100) AFTER customizer,
   model VARCHAR(100) AFTER brand,
   new_or_replace VARCHAR(20) AFTER model,
   approved_by INT UNSIGNED AFTER status,
   approved_at DATETIME AFTER approved_by,
   ```
3. Agregar foreign key para `approved_by`:
   ```sql
   FOREIGN KEY (approved_by) REFERENCES users(id) ON DELETE SET NULL
   ```
4. Probar instalación limpia en ambiente de desarrollo
5. Verificar que todos los campos coincidan con producción

**Criterios de Aceptación:**
- [ ] Schema SQL refleja estructura real de BD
- [ ] Instalación limpia funciona sin errores
- [ ] Todos los campos tienen tipos correctos

**Estado:** ⏳ PENDIENTE

---

### Issue #2: Corregir Race Condition en Generación de NRE
**Descripción:** Dos usuarios creando NREs simultáneamente pueden obtener el mismo número

**Impacto:** 🟡 MEDIO - Probabilidad baja pero existente

**Esfuerzo:** ⏱️ 2 horas

**Asignado a:** Backend Developer

**Pasos:**

**Opción A: Bloqueo de Tabla (Más Simple)**
1. Modificar `src/models/Nre.php` línea 62-78:
   ```php
   public static function generateNextNreNumber(): string {
       $prefix = 'XY';
       $today = date('Ymd');
       
       $database = Database::getInstance();
       $db = $database->getConnection();
       
       // Bloquear tabla para evitar race conditions
       $db->query("LOCK TABLES nres WRITE");
       
       try {
           $stmt = $db->prepare("SELECT COUNT(*) AS count FROM nres WHERE nre_number LIKE ?");
           $pattern = $prefix . $today . '%';
           $stmt->bind_param('s', $pattern);
           $stmt->execute();
           $result = $stmt->get_result();
           $row = $result->fetch_assoc();
           $nextSeq = (int)$row['count'] + 1;
           
           $nreNumber = $prefix . $today . str_pad($nextSeq, 2, '0', STR_PAD_LEFT);
           
           return $nreNumber;
       } finally {
           $db->query("UNLOCK TABLES");
       }
   }
   ```

**Opción B: Secuencia en BD (Más Robusto)**
1. Crear tabla de secuencias:
   ```sql
   CREATE TABLE nre_sequences (
       date_key VARCHAR(8) PRIMARY KEY,
       last_sequence INT NOT NULL DEFAULT 0
   ) ENGINE=InnoDB;
   ```

2. Modificar `generateNextNreNumber()`:
   ```php
   public static function generateNextNreNumber(): string {
       $prefix = 'XY';
       $today = date('Ymd');
       
       $database = Database::getInstance();
       $db = $database->getConnection();
       
       // Insertar o actualizar secuencia
       $stmt = $db->prepare("
           INSERT INTO nre_sequences (date_key, last_sequence)
           VALUES (?, 1)
           ON DUPLICATE KEY UPDATE last_sequence = last_sequence + 1
       ");
       $stmt->bind_param('s', $today);
       $stmt->execute();
       
       // Obtener secuencia actual
       $stmt = $db->prepare("SELECT last_sequence FROM nre_sequences WHERE date_key = ?");
       $stmt->bind_param('s', $today);
       $stmt->execute();
       $result = $stmt->get_result();
       $row = $result->fetch_assoc();
       
       return $prefix . $today . str_pad($row['last_sequence'], 2, '0', STR_PAD_LEFT);
   }
   ```

3. Actualizar `getNextNreNumbers()` para usar la misma lógica

**Pruebas:**
1. Crear script de prueba de concurrencia:
   ```php
   // test_race_condition.php
   for ($i = 0; $i < 10; $i++) {
       $pid = pcntl_fork();
       if ($pid == 0) {
           $nre = Nre::generateNextNreNumber();
           echo "Proceso $i: $nre\n";
           exit(0);
       }
   }
   ```
2. Verificar que no haya números duplicados

**Criterios de Aceptación:**
- [ ] No se generan números duplicados en pruebas de concurrencia
- [ ] Función `generateNextNreNumber()` es thread-safe
- [ ] Función `getNextNreNumbers()` es thread-safe
- [ ] Pruebas automatizadas pasan

**Estado:** ⏳ PENDIENTE

---

### Issue #3: Implementar HTTPS
**Descripción:** Credenciales y datos sensibles viajan sin cifrar

**Impacto:** 🔴 CRÍTICO - Vulnerable a man-in-the-middle

**Esfuerzo:** ⏱️ 1 hora (si ya se tiene certificado SSL)

**Asignado a:** DevOps / SysAdmin

**Pasos:**
1. Obtener certificado SSL (Let's Encrypt recomendado):
   ```bash
   sudo apt install certbot python3-certbot-apache
   sudo certbot --apache -d requiem.xinya-la.com
   ```

2. Configurar VirtualHost HTTPS en Apache:
   ```apache
   # /etc/apache2/sites-available/requiem-ssl.conf
   <VirtualHost *:443>
       ServerName requiem.xinya-la.com
       DocumentRoot /var/www/html/requiem/public
       
       SSLEngine on
       SSLCertificateFile /etc/letsencrypt/live/requiem.xinya-la.com/fullchain.pem
       SSLCertificateKeyFile /etc/letsencrypt/live/requiem.xinya-la.com/privkey.pem
       
       <Directory /var/www/html/requiem/public>
           Options Indexes FollowSymLinks
           AllowOverride All
           Require all granted
       </Directory>
       
       ErrorLog ${APACHE_LOG_DIR}/requiem_ssl_error.log
       CustomLog ${APACHE_LOG_DIR}/requiem_ssl_access.log combined
   </VirtualHost>
   ```

3. Redirigir HTTP → HTTPS:
   ```apache
   # /etc/apache2/sites-available/requiem.conf
   <VirtualHost *:80>
       ServerName requiem.xinya-la.com
       Redirect permanent / https://requiem.xinya-la.com/
   </VirtualHost>
   ```

4. Habilitar módulos SSL:
   ```bash
   sudo a2enmod ssl
   sudo a2ensite requiem-ssl
   sudo systemctl restart apache2
   ```

5. Agregar headers de seguridad:
   ```apache
   Header always set Strict-Transport-Security "max-age=31536000; includeSubDomains"
   Header always set X-Content-Type-Options "nosniff"
   Header always set X-Frame-Options "SAMEORIGIN"
   Header always set X-XSS-Protection "1; mode=block"
   ```

**Criterios de Aceptación:**
- [ ] Certificado SSL válido instalado
- [ ] HTTP redirige a HTTPS
- [ ] Headers de seguridad configurados
- [ ] Prueba SSL Labs: Grado A o superior

**Estado:** ⏳ PENDIENTE

---

### Issue #4: Implementar Autenticación Robusta
**Descripción:** Solo valida IDs hardcodeados sin contraseñas

**Impacto:** 🔴 CRÍTICO - Vulnerable a suplantación de identidad

**Esfuerzo:** ⏱️ 4 horas

**Asignado a:** Backend Developer

**Pasos:**

1. **Actualizar tabla `users`:**
   ```sql
   ALTER TABLE users ADD COLUMN password_hash VARCHAR(255) NOT NULL AFTER email;
   ALTER TABLE users ADD COLUMN last_login TIMESTAMP NULL;
   ALTER TABLE users ADD COLUMN login_attempts INT DEFAULT 0;
   ALTER TABLE users ADD COLUMN lockout_until TIMESTAMP NULL;
   ```

2. **Crear página de login:**
   ```php
   // public/login.php
   <?php
   session_start();
   
   if ($_SERVER['REQUEST_METHOD'] === 'POST') {
       $email = $_POST['email'] ?? '';
       $password = $_POST['password'] ?? '';
       
       require_once __DIR__ . '/../src/models/User.php';
       
       $user = User::findByEmail($email);
       
       if ($user && $user->verifyPassword($password)) {
           $_SESSION['user_id'] = $user->getId();
           $_SESSION['user_token'] = bin2hex(random_bytes(32));
           $_SESSION['login_time'] = time();
           
           header('Location: index.php');
           exit;
       } else {
           $error = 'Credenciales inválidas';
       }
   }
   ?>
   <!DOCTYPE html>
   <html>
   <head>
       <title>Login - Sistema Requiem</title>
       <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
   </head>
   <body class="bg-light">
       <div class="container">
           <div class="row justify-content-center mt-5">
               <div class="col-md-4">
                   <div class="card">
                       <div class="card-body">
                           <h3 class="text-center mb-4">Sistema Requiem</h3>
                           <?php if (isset($error)): ?>
                               <div class="alert alert-danger"><?= htmlspecialchars($error) ?></div>
                           <?php endif; ?>
                           <form method="POST">
                               <div class="mb-3">
                                   <label class="form-label">Email</label>
                                   <input type="email" name="email" class="form-control" required>
                               </div>
                               <div class="mb-3">
                                   <label class="form-label">Contraseña</label>
                                   <input type="password" name="password" class="form-control" required>
                               </div>
                               <button type="submit" class="btn btn-primary w-100">Iniciar Sesión</button>
                           </form>
                       </div>
                   </div>
               </div>
           </div>
       </div>
   </body>
   </html>
   ```

3. **Actualizar modelo `User`:**
   ```php
   // src/models/User.php
   public function verifyPassword(string $password): bool {
       $db = Database::getInstance();
       $conn = $db->getConnection();
       
       $stmt = $conn->prepare("SELECT password_hash FROM users WHERE id = ?");
       $stmt->bind_param("i", $this->id);
       $stmt->execute();
       $result = $stmt->get_result();
       $row = $result->fetch_assoc();
       
       return password_verify($password, $row['password_hash']);
   }
   
   public static function createUser(string $email, string $password, string $fullName): int {
       $db = Database::getInstance();
       $conn = $db->getConnection();
       
       $passwordHash = password_hash($password, PASSWORD_BCRYPT);
       $username = explode('@', $email)[0];
       
       $stmt = $conn->prepare("
           INSERT INTO users (username, email, password_hash, full_name)
           VALUES (?, ?, ?, ?)
       ");
       $stmt->bind_param("ssss", $username, $email, $passwordHash, $fullName);
       $stmt->execute();
       
       return $conn->insert_id;
   }
   ```

4. **Actualizar middleware:**
   ```php
   // src/middleware/AuthMiddleware.php
   function requireAuth() {
       session_start();
       
       if (!isset($_SESSION['user_id']) || !isset($_SESSION['user_token'])) {
           header('Location: login.php');
           exit();
       }
       
       // Verificar timeout de sesión (30 minutos)
       if (isset($_SESSION['login_time']) && (time() - $_SESSION['login_time']) > 1800) {
           session_destroy();
           header('Location: login.php?timeout=1');
           exit();
       }
       
       // Actualizar tiempo de actividad
       $_SESSION['login_time'] = time();
   }
   ```

5. **Crear script de inicialización de usuarios:**
   ```php
   // scripts/create_users.php
   <?php
   require_once __DIR__ . '/../src/models/User.php';
   
   $users = [
       ['email' => 'jesus.muro@xinya-la.com', 'password' => 'ChangeMe123!', 'name' => 'Jesús Muro'],
       ['email' => 'user2@xinya-la.com', 'password' => 'ChangeMe123!', 'name' => 'Usuario 2'],
       ['email' => 'user3@xinya-la.com', 'password' => 'ChangeMe123!', 'name' => 'Usuario 3'],
   ];
   
   foreach ($users as $userData) {
       $id = User::createUser($userData['email'], $userData['password'], $userData['name']);
       echo "Usuario creado: {$userData['email']} (ID: $id)\n";
   }
   ```

**Criterios de Aceptación:**
- [ ] Contraseñas hasheadas con bcrypt
- [ ] Login funcional con validación
- [ ] Timeout de sesión (30 minutos)
- [ ] Rate limiting en login (máx 5 intentos)
- [ ] Página de logout funcional

**Estado:** ⏳ PENDIENTE

---

## 🟡 Prioridad MEDIA (Implementar en Sprint Siguiente)

### Issue #5: Agregar Protección CSRF
**Descripción:** Formularios vulnerables a Cross-Site Request Forgery

**Impacto:** 🟡 MEDIO - Vulnerable a ataques CSRF

**Esfuerzo:** ⏱️ 2 horas

**Asignado a:** Backend Developer

**Pasos:**
1. Crear helper de CSRF:
   ```php
   // src/utils/CsrfHelper.php
   <?php
   class CsrfHelper {
       public static function generateToken(): string {
           if (!isset($_SESSION['csrf_token'])) {
               $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
           }
           return $_SESSION['csrf_token'];
       }
       
       public static function validateToken(string $token): bool {
           return isset($_SESSION['csrf_token']) && hash_equals($_SESSION['csrf_token'], $token);
       }
       
       public static function getTokenField(): string {
           $token = self::generateToken();
           return "<input type='hidden' name='csrf_token' value='$token'>";
       }
   }
   ```

2. Agregar token a formularios:
   ```php
   // templates/nre/create.php
   <form action="index.php?action=preview" method="POST">
       <?= CsrfHelper::getTokenField() ?>
       <!-- resto del formulario -->
   </form>
   ```

3. Validar token en controladores:
   ```php
   // public/index.php
   if ($_SERVER['REQUEST_METHOD'] === 'POST') {
       if (!CsrfHelper::validateToken($_POST['csrf_token'] ?? '')) {
           die('Token CSRF inválido');
       }
       // procesar formulario
   }
   ```

**Criterios de Aceptación:**
- [ ] Todos los formularios tienen token CSRF
- [ ] Validación de token en todas las acciones POST
- [ ] Tokens regenerados después de login

**Estado:** ⏳ PENDIENTE

---

### Issue #6: Insertar Tipos de Cambio Faltantes
**Descripción:** No hay tipo de cambio para noviembre 2024

**Impacto:** 🟡 MEDIO - Usuarios no pueden crear NREs

**Esfuerzo:** ⏱️ 15 minutos

**Asignado a:** Backend Developer

**Pasos:**
1. Crear script de seed:
   ```sql
   -- database/seed_exchange_rates_2024.sql
   INSERT INTO exchange_rates (period, rate_mxn_per_usd) VALUES
   ('202401', 17.0234),
   ('202402', 17.1456),
   ('202403', 16.9876),
   ('202404', 17.2345),
   ('202405', 17.3456),
   ('202406', 18.1234),
   ('202407', 18.4567),
   ('202408', 19.2345),
   ('202409', 19.8765),
   ('202410', 20.0123),
   ('202411', 20.1234),
   ('202412', 20.2345);
   ```

2. Ejecutar script:
   ```bash
   mysql -u jmuro -p'Monday.03' requiem < database/seed_exchange_rates_2024.sql
   ```

3. Verificar:
   ```sql
   SELECT * FROM exchange_rates WHERE period >= '202401' ORDER BY period;
   ```

**Criterios de Aceptación:**
- [ ] Tipos de cambio para todos los meses de 2024
- [ ] Prueba de creación de NRE exitosa

**Estado:** ⏳ PENDIENTE

---

### Issue #7: Implementar Rate Limiting
**Descripción:** Vulnerable a ataques de fuerza bruta

**Impacto:** 🟡 MEDIO - Vulnerable a brute force

**Esfuerzo:** ⏱️ 3 horas

**Asignado a:** Backend Developer

**Pasos:**
1. Crear tabla de rate limiting:
   ```sql
   CREATE TABLE login_attempts (
       id INT AUTO_INCREMENT PRIMARY KEY,
       ip_address VARCHAR(45) NOT NULL,
       email VARCHAR(100),
       attempted_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
       INDEX idx_ip (ip_address),
       INDEX idx_email (email)
   );
   ```

2. Implementar rate limiter:
   ```php
   // src/utils/RateLimiter.php
   <?php
   class RateLimiter {
       public static function checkLoginAttempts(string $email, string $ip): bool {
           $db = Database::getInstance();
           $conn = $db->getConnection();
           
           // Limpiar intentos antiguos (>15 minutos)
           $conn->query("DELETE FROM login_attempts WHERE attempted_at < DATE_SUB(NOW(), INTERVAL 15 MINUTE)");
           
           // Contar intentos recientes
           $stmt = $conn->prepare("
               SELECT COUNT(*) as attempts 
               FROM login_attempts 
               WHERE (email = ? OR ip_address = ?) 
               AND attempted_at > DATE_SUB(NOW(), INTERVAL 15 MINUTE)
           ");
           $stmt->bind_param("ss", $email, $ip);
           $stmt->execute();
           $result = $stmt->get_result();
           $row = $result->fetch_assoc();
           
           return $row['attempts'] < 5;
       }
       
       public static function recordAttempt(string $email, string $ip): void {
           $db = Database::getInstance();
           $conn = $db->getConnection();
           
           $stmt = $conn->prepare("INSERT INTO login_attempts (email, ip_address) VALUES (?, ?)");
           $stmt->bind_param("ss", $email, $ip);
           $stmt->execute();
       }
   }
   ```

3. Integrar en login:
   ```php
   // public/login.php
   $ip = $_SERVER['REMOTE_ADDR'];
   
   if (!RateLimiter::checkLoginAttempts($email, $ip)) {
       $error = 'Demasiados intentos. Intenta en 15 minutos.';
   } else {
       // procesar login
       if (!$user || !$user->verifyPassword($password)) {
           RateLimiter::recordAttempt($email, $ip);
           $error = 'Credenciales inválidas';
       }
   }
   ```

**Criterios de Aceptación:**
- [ ] Máximo 5 intentos en 15 minutos
- [ ] Bloqueo por IP y por email
- [ ] Limpieza automática de intentos antiguos

**Estado:** ⏳ PENDIENTE

---

## 📅 Cronograma

### Semana 1 (2025-12-02 a 2025-12-08)
- [ ] Issue #1: Actualizar Schema SQL (30 min)
- [ ] Issue #6: Insertar Tipos de Cambio (15 min)
- [ ] Issue #2: Corregir Race Condition (2 horas)
- [ ] Issue #3: Implementar HTTPS (1 hora)

**Total:** ~4 horas

### Semana 2 (2025-12-09 a 2025-12-15)
- [ ] Issue #4: Autenticación Robusta (4 horas)
- [ ] Issue #5: Protección CSRF (2 horas)
- [ ] Issue #7: Rate Limiting (3 horas)

**Total:** ~9 horas

### Semana 3 (2025-12-16 a 2025-12-22)
- [ ] Pruebas de integración
- [ ] Pruebas de seguridad
- [ ] Documentación actualizada
- [ ] Despliegue a staging

**Total:** ~8 horas

### Semana 4 (2025-12-23 a 2025-12-29)
- [ ] Validación en staging
- [ ] Corrección de bugs
- [ ] Despliegue a producción
- [ ] Monitoreo post-despliegue

**Total:** ~4 horas

**TOTAL ESTIMADO:** ~25 horas

---

## ✅ Criterios de Éxito

### Antes de Producción:
- [ ] Todas las pruebas automatizadas pasan (17/17)
- [ ] No hay issues de prioridad ALTA pendientes
- [ ] HTTPS configurado y funcional
- [ ] Autenticación robusta implementada
- [ ] Schema SQL sincronizado
- [ ] Tipos de cambio completos

### Post-Producción:
- [ ] Cero errores críticos en logs (primeras 48 horas)
- [ ] Tiempo de respuesta <2 segundos
- [ ] Disponibilidad >99.5%
- [ ] Usuarios satisfechos (feedback positivo)

---

## 📞 Contactos

**Product Owner:** Jesús Muro (jesus.muro@xinya-la.com)  
**Backend Developer:** TBD  
**DevOps/SysAdmin:** TBD  
**QA Tester:** TBD

---

## 📝 Notas

- Este plan asume disponibilidad de 1 desarrollador fullstack
- Tiempos estimados son conservadores (incluyen testing)
- Se recomienda revisión diaria de progreso
- Cualquier blocker debe escalarse inmediatamente

---

**Última Actualización:** 2025-12-01  
**Próxima Revisión:** 2025-12-08

---

© 2025 Xinya Latinamerica - Sistema Requiem v1.0
