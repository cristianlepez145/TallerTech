<?php
/**
 * TallerTech - Módulo de Agendamiento de Citas y Referencias de Diseños
 * Acceso inteligente: abierto a todos los usuarios, enlace automático de cuenta,
 * subida múltiple de imágenes y planos PDF de referencias/diseños, y persistencia PDO.
 */

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once 'includes/conexion.php';
require_once 'includes/auth.php';

$mensaje_exito = '';
$mensaje_error = '';

$tipo_cita  = 'domicilio';
$fecha      = '';
$hora       = '';
$direccion  = '';
$notas      = '';
$nombre     = $_SESSION['nombre'] ?? '';
$correo     = $_SESSION['correo'] ?? '';
$telefono   = '';

// Si el usuario está autenticado, buscamos su teléfono registrado
if (isset($_SESSION['id_usuario'])) {
    try {
        $stmtUser = $pdo->prepare("SELECT telefono FROM usuarios WHERE id_usuario = :id LIMIT 1");
        $stmtUser->execute([':id' => (int)$_SESSION['id_usuario']]);
        $rowU = $stmtUser->fetch();
        if ($rowU && !empty($rowU['telefono'])) {
            $telefono = $rowU['telefono'];
        }
    } catch (Exception $e) {
        // Silencioso
    }
}

// Procesamiento de reserva de cita
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['agendar_cita'])) {
    $tipo_cita  = trim($_POST['tipo_cita'] ?? 'domicilio');
    $fecha      = trim($_POST['fecha'] ?? '');
    $hora       = trim($_POST['hora'] ?? '');
    $direccion  = trim($_POST['direccion'] ?? '');
    $notas      = trim($_POST['notas'] ?? '');

    $nombre     = trim($_POST['nombre'] ?? $nombre);
    $correo     = trim($_POST['correo'] ?? $correo);
    $telefono   = trim($_POST['telefono'] ?? $telefono);

    // 1. Validar datos de contacto si el usuario no tiene sesión iniciada
    if (!isset($_SESSION['id_usuario'])) {
        if (empty($nombre) || empty($correo) || empty($telefono)) {
            $mensaje_error = 'Por favor, ingresa tu nombre, correo y teléfono de contacto para confirmar tu cita.';
        } elseif (!filter_var($correo, FILTER_VALIDATE_EMAIL)) {
            $mensaje_error = 'Por favor, ingresa un correo electrónico válido.';
        }
    }

    // 2. Validar fecha y horario
    if (empty($mensaje_error)) {
        if (empty($fecha) || empty($hora)) {
            $mensaje_error = 'Por favor, selecciona una fecha y un bloque de horario disponible.';
        } elseif ($tipo_cita === 'domicilio' && empty($direccion)) {
            $mensaje_error = 'Por favor, ingresa la dirección completa donde se realizará la toma de medidas.';
        } else {
            $fechaTimestamp = strtotime($fecha);
            $hoyTimestamp   = strtotime(date('Y-m-d'));

            if ($fechaTimestamp < $hoyTimestamp) {
                $mensaje_error = 'No es posible agendar citas en fechas pasadas.';
            } else {
                $diaSemana = (int)date('N', $fechaTimestamp); // 1 = Lunes, 7 = Domingo
                $horaNum   = (int)substr($hora, 0, 2);
                $esFinDeSemana = ($diaSemana === 6 || $diaSemana === 7);

                if (!$esFinDeSemana && ($horaNum < 18 || $horaNum > 20)) {
                    $mensaje_error = 'Entre semana (Lunes a Viernes), las visitas se realizan a partir de las 6:00 PM.';
                } elseif ($esFinDeSemana && ($horaNum < 9 || $horaNum > 17)) {
                    $mensaje_error = 'En fin de semana (Sábado y Domingo), las citas se agendan entre las 9:00 AM y las 6:00 PM.';
                } else {
                    // 3. Manejo seguro de archivos adjuntos (imágenes o PDF de referencias y diseños)
                    $archivosSubidos = [];

                    $inputFiles = null;
                    if (isset($_FILES['referencias']) && is_array($_FILES['referencias']['name'])) {
                        $inputFiles = $_FILES['referencias'];
                    } elseif (isset($_FILES['croquis'])) {
                        $inputFiles = $_FILES['croquis'];
                    }

                    if ($inputFiles && !empty($inputFiles['name'])) {
                        $nombres  = (array)$inputFiles['name'];
                        $tmpNames = (array)$inputFiles['tmp_name'];
                        $errores  = (array)$inputFiles['error'];
                        $tamanos  = (array)$inputFiles['size'];

                        $directorioDestino = __DIR__ . '/uploads/referencias/';
                        if (!is_dir($directorioDestino)) {
                            mkdir($directorioDestino, 0777, true);
                        }

                        $extPermitida = ['jpg', 'jpeg', 'png', 'webp', 'pdf'];
                        $mimesValidos = [
                            'image/jpeg'      => 'imagen',
                            'image/pjpeg'     => 'imagen',
                            'image/png'       => 'imagen',
                            'image/webp'      => 'imagen',
                            'application/pdf' => 'pdf'
                        ];

                        $totalArchivos = count($nombres);
                        if ($totalArchivos > 10) {
                            $mensaje_error = 'Puedes adjuntar un máximo de 10 archivos de referencia.';
                        }

                        for ($i = 0; $i < $totalArchivos && empty($mensaje_error); $i++) {
                            if ($errores[$i] === UPLOAD_ERR_NO_FILE) {
                                continue;
                            }
                            if ($errores[$i] !== UPLOAD_ERR_OK) {
                                $mensaje_error = 'Error al subir uno de los archivos adjuntos (Código: ' . $errores[$i] . ').';
                                break;
                            }

                            $nombreOrig = $nombres[$i];
                            $tamano     = $tamanos[$i];
                            $tmpPath    = $tmpNames[$i];
                            $ext        = strtolower(pathinfo($nombreOrig, PATHINFO_EXTENSION));

                            if ($tamano > 15 * 1024 * 1024) {
                                $mensaje_error = "El archivo '{$nombreOrig}' supera el límite permitido de 15 MB.";
                                break;
                            }

                            if (!in_array($ext, $extPermitida)) {
                                $mensaje_error = "El archivo '{$nombreOrig}' no tiene una extensión admitida. Solo se aceptan imágenes (PNG, JPG, WEBP) o documentos PDF.";
                                break;
                            }

                            $finfo = finfo_open(FILEINFO_MIME_TYPE);
                            $mimeReal = finfo_file($finfo, $tmpPath);
                            finfo_close($finfo);

                            if (!array_key_exists($mimeReal, $mimesValidos)) {
                                $mensaje_error = "El archivo '{$nombreOrig}' no cuenta con un formato válido de imagen o PDF.";
                                break;
                            }

                            $tipoDoc = $mimesValidos[$mimeReal];
                            $prefijo = ($tipoDoc === 'pdf') ? 'doc_ref_' : 'img_ref_';
                            $nombreSeguro = $prefijo . time() . '_' . bin2hex(random_bytes(6)) . '.' . $ext;
                            $destinoFinal = $directorioDestino . $nombreSeguro;

                            if (move_uploaded_file($tmpPath, $destinoFinal)) {
                                $archivosSubidos[] = [
                                    'ruta'            => 'uploads/referencias/' . $nombreSeguro,
                                    'nombre_original' => $nombreOrig,
                                    'tipo'            => $tipoDoc
                                ];
                            } else {
                                $mensaje_error = "No fue posible almacenar el archivo '{$nombreOrig}' en el servidor.";
                                break;
                            }
                        }
                    }

                    // 4. Inserción Inteligente con PDO
                    if (empty($mensaje_error)) {
                        try {
                            if (isset($_SESSION['id_usuario'])) {
                                $id_usuario = (int)$_SESSION['id_usuario'];
                            } else {
                                $stmtFind = $pdo->prepare("SELECT id_usuario, nombre FROM usuarios WHERE correo = :correo LIMIT 1");
                                $stmtFind->execute([':correo' => $correo]);
                                $userExist = $stmtFind->fetch();

                                if ($userExist) {
                                    $id_usuario = (int)$userExist['id_usuario'];
                                    session_regenerate_id(true);
                                    $_SESSION['id_usuario'] = $id_usuario;
                                    $_SESSION['nombre']     = $userExist['nombre'];
                                    $_SESSION['correo']     = $correo;
                                    $_SESSION['rol']        = 'cliente_prospecto';
                                } else {
                                    $tempPassword = bin2hex(random_bytes(8));
                                    $tempHash     = password_hash($tempPassword, PASSWORD_DEFAULT);
                                    
                                    $stmtCreate = $pdo->prepare("INSERT INTO usuarios (nombre, correo, telefono, password_hash, rol) 
                                                                 VALUES (:nombre, :correo, :telefono, :pass, 'cliente_prospecto')");
                                    $stmtCreate->execute([
                                        ':nombre'   => $nombre,
                                        ':correo'   => $correo,
                                        ':telefono' => $telefono,
                                        ':pass'     => $tempHash
                                    ]);

                                    $id_usuario = (int)$pdo->lastInsertId();

                                    session_regenerate_id(true);
                                    $_SESSION['id_usuario'] = $id_usuario;
                                    $_SESSION['nombre']     = $nombre;
                                    $_SESSION['correo']     = $correo;
                                    $_SESSION['rol']        = 'cliente_prospecto';
                                }
                            }

                            // Insertar cita en MySQL
                            $fecha_hora     = $fecha . ' ' . $hora . ':00';
                            $duracion_max   = 60;
                            $ruta_principal = !empty($archivosSubidos) ? $archivosSubidos[0]['ruta'] : null;

                            $sql = "INSERT INTO citas (id_usuario, fecha_hora, duracion_max, tipo_cita, direccion, ruta_croquis, notas, estado) 
                                    VALUES (:id_usuario, :fecha_hora, :duracion_max, :tipo_cita, :direccion, :ruta_croquis, :notas, 'pendiente')";
                            
                            $stmtCita = $pdo->prepare($sql);
                            $stmtCita->execute([
                                ':id_usuario'   => $id_usuario,
                                ':fecha_hora'   => $fecha_hora,
                                ':duracion_max' => $duracion_max,
                                ':tipo_cita'    => $tipo_cita,
                                ':direccion'    => $direccion,
                                ':ruta_croquis' => $ruta_principal,
                                ':notas'        => $notas
                            ]);

                            $id_cita = (int)$pdo->lastInsertId();

                            // Registrar evento en la bitácora de auditoría
                            registrar_log(
                                $pdo,
                                'NUEVA_CITA',
                                "Cita agendada #CITA-{$id_cita} ({$tipo_cita}) para el {$fecha} a las {$hora}.",
                                $id_usuario
                            );

                            // Insertar cada archivo de referencia en la tabla relacional archivos_cita
                            if (!empty($archivosSubidos)) {
                                $stmtArch = $pdo->prepare("INSERT INTO archivos_cita (id_cita, ruta_archivo, nombre_original, tipo_archivo) 
                                                           VALUES (:id_cita, :ruta, :nombre_orig, :tipo)");
                                foreach ($archivosSubidos as $arc) {
                                    $stmtArch->execute([
                                        ':id_cita'     => $id_cita,
                                        ':ruta'        => $arc['ruta'],
                                        ':nombre_orig' => $arc['nombre_original'],
                                        ':tipo'        => $arc['tipo']
                                    ]);
                                }
                            }

                            $fechaFormateada = date('d/m/Y', strtotime($fecha));
                            $totalSubidos    = count($archivosSubidos);
                            $textoAdjuntos   = ($totalSubidos > 0) ? " Se vincularon {$totalSubidos} archivo(s) de referencia/diseño." : "";
                            
                            $mensaje_exito = "¡Cita reservada con éxito! Folio #{$id_cita} para el {$fechaFormateada} a las {$hora} hrs.{$textoAdjuntos} Tu solicitud quedó registrada y nos comunicaremos contigo para confirmar.";

                        } catch (PDOException $e) {
                            error_log("Error al agendar cita: " . $e->getMessage());
                            $mensaje_error = 'Ocurrió un error en el servidor al registrar la cita. Por favor, intenta de nuevo.';
                        }
                    }
                }
            }
        }
    }
}

// Consultar citas previas del usuario si tiene sesión activa
$misCitas = [];
if (isset($_SESSION['id_usuario'])) {
    try {
        $stmtC = $pdo->prepare("
            SELECT id_cita, fecha_hora, duracion_max, tipo_cita, direccion, ruta_croquis, notas, estado
            FROM citas 
            WHERE id_usuario = :id
            ORDER BY fecha_hora DESC
            LIMIT 6
        ");
        $stmtC->execute([':id' => (int)$_SESSION['id_usuario']]);
        $misCitas = $stmtC->fetchAll(PDO::FETCH_ASSOC);

        if (!empty($misCitas)) {
            $stmtArchList = $pdo->prepare("SELECT * FROM archivos_cita WHERE id_cita = :id_cita");
            foreach ($misCitas as &$mc) {
                $stmtArchList->execute([':id_cita' => $mc['id_cita']]);
                $mc['adjuntos'] = $stmtArchList->fetchAll(PDO::FETCH_ASSOC);
            }
            unset($mc);
        }
    } catch (Exception $e) {
        // Silencioso
    }
}

// Configuración de vista para el layout general
$titulo_pagina = 'Agendamiento de Citas & Referencias | TallerTech';
$pagina_actual = 'citas.php';
$css_adicional = 'assets/css/citas.css';

require_once 'includes/header.php';
?>

<div class="citas-page-wrapper">
    <div class="container">

        <!-- Banners de Notificación -->
        <?php if (!empty($mensaje_exito)): ?>
            <div class="citas-alert-banner success" role="alert">
                <svg xmlns="http://www.w3.org/2000/svg" width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                    <path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"></path>
                    <polyline points="22 4 12 14.01 9 11.01"></polyline>
                </svg>
                <div><?= $mensaje_exito ?></div>
            </div>
        <?php endif; ?>

        <?php if (!empty($mensaje_error)): ?>
            <div class="citas-alert-banner error" role="alert">
                <svg xmlns="http://www.w3.org/2000/svg" width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                    <circle cx="12" cy="12" r="10"></circle>
                    <line x1="12" y1="8" x2="12" y2="12"></line>
                    <line x1="12" y1="16" x2="12.01" y2="16"></line>
                </svg>
                <div><?= $mensaje_error ?></div>
            </div>
        <?php endif; ?>

        <!-- Encabezado de la Sección -->
        <header class="citas-header-box">
            <span class="citas-badge-pill">Calendario &amp; Visitas Técnicas</span>
            <h1 class="citas-main-title">Agendamiento de Citas &amp; Diseños</h1>
            <p class="citas-main-subtitle">
                Reserva una visita presencial para toma de medidas exactas o acude al taller para seleccionar maderas, acabados y revisar tus referencias de diseño.
            </p>
        </header>

        <!-- Layout Principal a 2 Columnas -->
        <div class="citas-layout-grid">

            <!-- Columna Izquierda: Panel Informativo (Reglas de Agenda y Referencias) -->
            <aside class="rules-panel-card" aria-label="Reglas de agenda">
                <div class="rules-panel-header">
                    <h2 class="rules-panel-title">Reglas de Agenda</h2>
                    <p class="rules-panel-desc">
                        Para optimizar los tiempos de maquinado y atención técnica, las citas se rigen bajo los siguientes lineamientos:
                    </p>
                </div>

                <!-- Regla 1: Horarios entre semana -->
                <div class="rule-item-card">
                    <div class="rule-icon-box">
                        <svg xmlns="http://www.w3.org/2000/svg" width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <rect width="18" height="18" x="3" y="4" rx="2" ry="2"/>
                            <line x1="16" y1="2" x2="16" y2="6"/>
                            <line x1="8" y1="2" x2="8" y2="6"/>
                            <line x1="3" y1="10" x2="21" y2="10"/>
                        </svg>
                    </div>
                    <div class="rule-item-content">
                        <h4 class="rule-item-title">Horarios Entre Semana</h4>
                        <p class="rule-item-text">
                            De Lunes a Viernes las visitas se agendan únicamente en horario vespertino (a partir de las <strong>6:00 PM</strong>) para no interrumpir el maquinado en taller.
                        </p>
                    </div>
                </div>

                <!-- Regla 2: Horarios fin de semana -->
                <div class="rule-item-card">
                    <div class="rule-icon-box">
                        <svg xmlns="http://www.w3.org/2000/svg" width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <circle cx="12" cy="12" r="10"/>
                            <polyline points="12 6 12 12 16 14"/>
                        </svg>
                    </div>
                    <div class="rule-item-content">
                        <h4 class="rule-item-title">Horarios Fin de Semana</h4>
                        <p class="rule-item-text">
                            Sábados y Domingos contamos con ventana abierta continua de <strong>9:00 AM a 6:00 PM</strong> para revisiones técnicas y visitas a domicilio.
                        </p>
                    </div>
                </div>

                <!-- Regla 3: Duración parametrizada -->
                <div class="rule-item-card">
                    <div class="rule-icon-box">
                        <svg xmlns="http://www.w3.org/2000/svg" width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <circle cx="12" cy="12" r="10"/>
                            <polyline points="12 6 12 12 14 14"/>
                        </svg>
                    </div>
                    <div class="rule-item-content">
                        <h4 class="rule-item-title">Duración Parametrizada</h4>
                        <p class="rule-item-text">
                            Bloques de tiempo de <strong>máximo 1 hora</strong> enfocados en toma precisa de medidas, chequeo de plomos y asesoría de maderas.
                        </p>
                    </div>
                </div>

                <!-- Regla 4: Referencias, Diseños y Croquis -->
                <div class="rule-item-card">
                    <div class="rule-icon-box">
                        <svg xmlns="http://www.w3.org/2000/svg" width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <rect width="18" height="18" x="3" y="3" rx="2" ry="2"/>
                            <circle cx="9" cy="9" r="2"/>
                            <path d="m21 15-3.086-3.086a2 2 0 0 0-2.828 0L6 21"/>
                        </svg>
                    </div>
                    <div class="rule-item-content">
                        <h4 class="rule-item-title">Referencias, Diseños y Croquis</h4>
                        <p class="rule-item-text">
                            Puedes adjuntar fotos de inspiración (Pinterest, Instagram), planos arquitectónicos en PDF o fotos del espacio para llegar preparados con muestras específicas.
                        </p>
                    </div>
                </div>

            </aside>

            <!-- Columna Derecha: Formulario de Reserva Interactivo -->
            <section class="booking-form-card" aria-label="Formulario de reserva">
                
                <div class="booking-form-header">
                    <div class="d-flex align-items-center justify-content-between flex-wrap gap-2 mb-2">
                        <h3 class="booking-form-title">Reservar Visita o Cita</h3>
                        <?php if (isset($_SESSION['id_usuario'])): ?>
                            <span class="badge rounded-pill bg-dark border border-secondary px-3 py-2 text-secondary">
                                Conectado como: <strong class="text-white"><?= htmlspecialchars($_SESSION['nombre'] ?? '', ENT_QUOTES, 'UTF-8') ?></strong>
                            </span>
                        <?php else: ?>
                            <span class="badge rounded-pill bg-dark border border-secondary px-3 py-2 text-secondary">
                                ¿Ya tienes cuenta? <a href="login.php" class="text-white text-decoration-underline fw-bold">Inicia sesión</a>
                            </span>
                        <?php endif; ?>
                    </div>
                    <p class="booking-form-subtitle">Selecciona tus preferencias, ingresa tus datos y adjunta tus referencias de diseño.</p>
                </div>

                <form method="POST" action="citas.php" enctype="multipart/form-data" id="formCita">
                    <input type="hidden" name="agendar_cita" value="1">

                    <!-- Datos de Contacto (Para usuarios visitantes) -->
                    <?php if (!isset($_SESSION['id_usuario'])): ?>
                        <div class="p-3 mb-4 rounded-3 border border-secondary border-opacity-25" style="background-color: rgba(255,255,255,0.02);">
                            <span class="d-block text-white fw-bold mb-2 small text-uppercase" style="letter-spacing: 0.05em; color: var(--wood-accent) !important;">
                                Datos de Contacto para Confirmación
                            </span>
                            <div class="row g-2">
                                <div class="col-12">
                                    <label for="nombre" class="form-field-label"><small>Nombre completo</small></label>
                                    <input type="text" id="nombre" name="nombre" class="form-field-input" placeholder="Tu nombre y apellido" value="<?= htmlspecialchars($nombre, ENT_QUOTES, 'UTF-8') ?>" required>
                                </div>
                                <div class="col-md-6">
                                    <label for="correo" class="form-field-label"><small>Correo electrónico</small></label>
                                    <input type="email" id="correo" name="correo" class="form-field-input" placeholder="tu@correo.com" value="<?= htmlspecialchars($correo, ENT_QUOTES, 'UTF-8') ?>" required>
                                </div>
                                <div class="col-md-6">
                                    <label for="telefono" class="form-field-label"><small>Teléfono / WhatsApp</small></label>
                                    <input type="tel" id="telefono" name="telefono" class="form-field-input" placeholder="+52 55 1234 5678" value="<?= htmlspecialchars($telefono, ENT_QUOTES, 'UTF-8') ?>" required>
                                </div>
                            </div>
                        </div>
                    <?php endif; ?>

                    <!-- Tipo de Cita -->
                    <div class="form-field-group">
                        <label class="form-field-label">Modalidad de la Cita</label>
                        <div class="appointment-type-selector">
                            <label class="type-pill-label <?= ($tipo_cita === 'domicilio') ? 'active' : '' ?>">
                                <input type="radio" name="tipo_cita" id="tipo_domicilio" value="domicilio" <?= ($tipo_cita === 'domicilio') ? 'checked' : '' ?>>
                                <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                    <path d="m3 9 9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"/>
                                    <polyline points="9 22 9 12 15 12 15 22"/>
                                </svg>
                                <span>Toma de medidas a domicilio</span>
                            </label>
                            <label class="type-pill-label <?= ($tipo_cita === 'taller') ? 'active' : '' ?>">
                                <input type="radio" name="tipo_cita" id="tipo_taller" value="taller" <?= ($tipo_cita === 'taller') ? 'checked' : '' ?>>
                                <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                    <rect width="16" height="20" x="4" y="2" rx="2"/>
                                    <path d="M9 22v-4h6v4"/>
                                </svg>
                                <span>Reunión en taller</span>
                            </label>
                        </div>
                    </div>

                    <!-- Fecha y Hora -->
                    <div class="form-field-group">
                        <div class="date-time-grid">
                            <div>
                                <label for="fecha" class="form-field-label">Fecha de la Cita</label>
                                <input 
                                    type="date" 
                                    id="fecha" 
                                    name="fecha" 
                                    class="form-field-input" 
                                    min="<?= date('Y-m-d') ?>" 
                                    value="<?= htmlspecialchars($fecha, ENT_QUOTES, 'UTF-8') ?>"
                                    required
                                >
                            </div>
                            <div>
                                <label for="hora" class="form-field-label">Bloque de Hora <small>(1 hora máx.)</small></label>
                                <select id="hora" name="hora" class="form-field-select" required disabled>
                                    <option value="">Selecciona primero una fecha</option>
                                </select>
                            </div>
                        </div>
                        <span class="schedule-helper-badge" id="scheduleHint">Selecciona una fecha en el calendario para cargar horarios válidos.</span>
                    </div>

                    <!-- Dirección de la Visita -->
                    <div class="form-field-group" id="direccionGroup">
                        <label for="direccion" class="form-field-label">
                            Dirección de la Visita <small>(Calle, Número, Colonia y CP)</small>
                        </label>
                        <input 
                            type="text" 
                            id="direccion" 
                            name="direccion" 
                            class="form-field-input" 
                            placeholder="Calle, número exterior/interior, colonia y código postal" 
                            value="<?= htmlspecialchars($direccion, ENT_QUOTES, 'UTF-8') ?>"
                            required
                        >
                    </div>

                    <!-- Zona de Carga de Archivos: Referencias de Diseños, Fotos y Documentos PDF -->
                    <div class="form-field-group">
                        <div class="d-flex align-items-center justify-content-between mb-2">
                            <label class="form-field-label mb-0">
                                Referencias de Diseños, Fotos o PDF <small>(Imágenes y Documentos)</small>
                            </label>
                            <span class="badge rounded-pill bg-dark border border-secondary text-secondary small px-2 py-1">
                                Opcional &bull; Múltiples archivos
                            </span>
                        </div>
                        
                        <div class="dropzone-container" id="dropzone">
                            <input 
                                type="file" 
                                id="referencias" 
                                name="referencias[]" 
                                class="dropzone-file-input" 
                                accept=".png,.jpg,.jpeg,.webp,.pdf"
                                multiple
                            >
                            <div class="dropzone-content-box">
                                <div class="dropzone-icon">
                                    <svg xmlns="http://www.w3.org/2000/svg" width="38" height="38" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
                                        <path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/>
                                        <polyline points="17 8 12 3 7 8"/>
                                        <line x1="12" y1="3" x2="12" y2="15"/>
                                    </svg>
                                </div>
                                <p class="dropzone-title">Arrastra aquí imágenes de referencia, diseños o planos PDF</p>
                                <p class="dropzone-subtitle">Fotos de inspiración (Pinterest/Instagram), bocetos o archivos PDF (hasta 10 archivos, 15 MB c/u)</p>
                                <span class="dropzone-browse-btn">Explorar archivos</span>
                            </div>
                        </div>

                        <!-- Galería / Contenedor de Previews de Archivos Seleccionados -->
                        <div class="files-preview-container" id="filesPreviewContainer">
                            <div class="files-preview-header">
                                <span class="files-preview-count" id="filesCountBadge">0 archivos seleccionados</span>
                                <button type="button" class="btn-clear-all-files" id="btnClearAll">Quitar todos</button>
                            </div>
                            <div class="files-preview-list" id="filesListGrid">
                                <!-- Elementos inyectados dinámicamente con JS -->
                            </div>
                        </div>
                    </div>

                    <!-- Notas Adicionales -->
                    <div class="form-field-group">
                        <label for="notas" class="form-field-label">
                            Notas o Detalles del Proyecto <small>(Opcional)</small>
                        </label>
                        <textarea 
                            id="notas" 
                            name="notas" 
                            class="form-field-textarea" 
                            rows="3" 
                            placeholder="Ej. Medidas aproximadas, estilo deseado, maderas de preferencia o detalles de los diseños adjuntos..."
                        ><?= htmlspecialchars($notas, ENT_QUOTES, 'UTF-8') ?></textarea>
                    </div>

                    <!-- Botón Píldora de Envío -->
                    <button type="submit" class="btn-submit-booking">
                        <span>Confirmar y Agendar Cita</span>
                        <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                            <line x1="5" y1="12" x2="19" y2="12"></line>
                            <polyline points="12 5 19 12 12 19"></polyline>
                        </svg>
                    </button>

                </form>

            </section>

        </div>

        <!-- Historial de Citas Programadas del Usuario -->
        <?php if (!empty($misCitas)): ?>
            <section class="user-appointments-section">
                <h3 class="user-appointments-title">
                    <svg xmlns="http://www.w3.org/2000/svg" width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="#c48849" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <rect width="18" height="18" x="3" y="4" rx="2" ry="2"/>
                        <line x1="16" y1="2" x2="16" y2="6"/>
                        <line x1="8" y1="2" x2="8" y2="6"/>
                        <line x1="3" y1="10" x2="21" y2="10"/>
                    </svg>
                    <span>Tus Citas &amp; Diseños Registrados</span>
                </h3>

                <div class="appointments-cards-grid">
                    <?php foreach ($misCitas as $c): ?>
                        <?php 
                            $cTimestamp = strtotime($c['fecha_hora']);
                            $cFecha = date('d/m/Y', $cTimestamp);
                            $cHora  = date('H:i', $cTimestamp);
                            $estado = htmlspecialchars($c['estado'], ENT_QUOTES, 'UTF-8');
                        ?>
                        <div class="appointment-history-card">
                            <div class="d-flex align-items-center justify-content-between mb-2">
                                <span class="text-white fw-bold">Folio #<?= $c['id_cita'] ?></span>
                                <span class="appointment-status-pill <?= $estado ?>"><?= ucfirst($estado) ?></span>
                            </div>

                            <div class="text-secondary small mb-2">
                                <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" class="me-1">
                                    <circle cx="12" cy="12" r="10"/>
                                    <polyline points="12 6 12 12 16 14"/>
                                </svg>
                                <strong class="text-white"><?= $cFecha ?></strong> a las <strong class="text-white"><?= $cHora ?> hrs</strong>
                            </div>

                            <div class="text-secondary small mb-2">
                                <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" class="me-1">
                                    <path d="M21 10c0 7-9 13-9 13s-9-6-9-13a9 9 0 0 1 18 0z"/>
                                    <circle cx="12" cy="10" r="3"/>
                                </svg>
                                <?= htmlspecialchars($c['direccion'] ?? 'Sin dirección especificada', ENT_QUOTES, 'UTF-8') ?>
                            </div>

                            <?php if (!empty($c['notas'])): ?>
                                <p class="text-secondary small fst-italic mb-2" style="border-left: 2px solid var(--wood-accent); padding-left: 0.5rem;">
                                    "<?= htmlspecialchars($c['notas'], ENT_QUOTES, 'UTF-8') ?>"
                                </p>
                            <?php endif; ?>

                            <!-- Referencias y Diseños Adjuntos -->
                            <?php if (!empty($c['adjuntos'])): ?>
                                <div class="mt-3 pt-2 border-top border-secondary border-opacity-25">
                                    <span class="d-block text-white small fw-semibold mb-2">Archivos de Referencia / Diseño:</span>
                                    <div class="appointment-attachments-gallery">
                                        <?php foreach ($c['adjuntos'] as $adj): ?>
                                            <?php 
                                                $rutaAdj = htmlspecialchars($adj['ruta_archivo'], ENT_QUOTES, 'UTF-8');
                                                $nomAdj  = htmlspecialchars($adj['nombre_original'] ?? basename($adj['ruta_archivo']), ENT_QUOTES, 'UTF-8');
                                                $isPdf   = ($adj['tipo_archivo'] === 'pdf' || strtolower(pathinfo($adj['ruta_archivo'], PATHINFO_EXTENSION)) === 'pdf');
                                            ?>
                                            <a href="<?= $rutaAdj ?>" target="_blank" class="attachment-chip" title="Ver <?= $nomAdj ?>">
                                                <?php if ($isPdf): ?>
                                                    <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="#ef4444" stroke-width="2">
                                                        <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/>
                                                        <polyline points="14 2 14 8 20 8"/>
                                                    </svg>
                                                <?php else: ?>
                                                    <img src="<?= $rutaAdj ?>" alt="<?= $nomAdj ?>" class="attachment-thumb-mini">
                                                <?php endif; ?>
                                                <span><?= (mb_strlen($nomAdj) > 16) ? mb_substr($nomAdj, 0, 14) . '...' : $nomAdj ?></span>
                                            </a>
                                        <?php endforeach; ?>
                                    </div>
                                </div>
                            <?php elseif (!empty($c['ruta_croquis'])): ?>
                                <div class="mt-3 pt-2 border-top border-secondary border-opacity-25">
                                    <a href="<?= htmlspecialchars($c['ruta_croquis'], ENT_QUOTES, 'UTF-8') ?>" target="_blank" class="attachment-chip">
                                        <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                            <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/>
                                            <polyline points="14 2 14 8 20 8"/>
                                        </svg>
                                        <span>Ver Croquis Principal</span>
                                    </a>
                                </div>
                            <?php endif; ?>

                        </div>
                    <?php endforeach; ?>
                </div>
            </section>
        <?php endif; ?>

    </div>
</div>

<!-- Script interactivo de reglas de citas y multi-file drag & drop -->
<script src="assets/js/citas.js"></script>

<?php 
require_once 'includes/footer.php'; 
?>
