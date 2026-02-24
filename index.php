<?php
// Configuración inicial
session_start();

// Variables para resultados
$sueldo = isset($_POST['sueldo']) ? $_POST['sueldo'] : '';
$periodo = isset($_POST['periodo']) ? $_POST['periodo'] : '';
$calcular = isset($_POST['calcular']);
$limpiar = isset($_POST['limpiar']);

// Tablas de tramos ISR
$tramosISR = [
    // ANUAL
    ["tipo" => "ANUAL", "desde" => 0, "hasta" => 6600.00, "cuotaFija" => 0, "tasa" => 0, "excedente" => 0],
    ["tipo" => "ANUAL", "desde" => 6600.01, "hasta" => 10742.88, "cuotaFija" => 212.04, "tasa" => 10, "excedente" => 6600.00],
    ["tipo" => "ANUAL", "desde" => 10742.89, "hasta" => 24457.20, "cuotaFija" => 720.00, "tasa" => 20, "excedente" => 10742.88],
    ["tipo" => "ANUAL", "desde" => 24457.21, "hasta" => 1000000000.00, "cuotaFija" => 3462.84, "tasa" => 30, "excedente" => 24457.20],
    
    // SEMANAL
    ["tipo" => "SEMANAL", "desde" => 0.01, "hasta" => 137.50, "cuotaFija" => 0, "tasa" => 0, "excedente" => 0],
    ["tipo" => "SEMANAL", "desde" => 137.51, "hasta" => 223.81, "cuotaFija" => 4.42, "tasa" => 10, "excedente" => 137.50],
    ["tipo" => "SEMANAL", "desde" => 223.82, "hasta" => 509.52, "cuotaFija" => 15.00, "tasa" => 20, "excedente" => 223.81],
    ["tipo" => "SEMANAL", "desde" => 509.53, "hasta" => 1000000000.00, "cuotaFija" => 72.14, "tasa" => 30, "excedente" => 509.53],
    
    // QUINCENAL
    ["tipo" => "QUINCENAL", "desde" => 0, "hasta" => 275.00, "cuotaFija" => 0, "tasa" => 0, "excedente" => 0],
    ["tipo" => "QUINCENAL", "desde" => 275.01, "hasta" => 447.62, "cuotaFija" => 8.83, "tasa" => 10, "excedente" => 275.00],
    ["tipo" => "QUINCENAL", "desde" => 447.63, "hasta" => 1019.05, "cuotaFija" => 30.00, "tasa" => 20, "excedente" => 447.62],
    ["tipo" => "QUINCENAL", "desde" => 1019.06, "hasta" => 1000000000.00, "cuotaFija" => 144.28, "tasa" => 30, "excedente" => 1019.05],
    
    // MENSUAL
    ["tipo" => "MENSUAL", "desde" => 0, "hasta" => 550.00, "cuotaFija" => 0, "tasa" => 0, "excedente" => 0],
    ["tipo" => "MENSUAL", "desde" => 550.01, "hasta" => 895.24, "cuotaFija" => 17.67, "tasa" => 10, "excedente" => 550.00],
    ["tipo" => "MENSUAL", "desde" => 895.25, "hasta" => 2038.10, "cuotaFija" => 60.00, "tasa" => 20, "excedente" => 895.24],
    ["tipo" => "MENSUAL", "desde" => 2038.11, "hasta" => 1000000000.00, "cuotaFija" => 288.57, "tasa" => 30, "excedente" => 2038.10]
];

// Límites ISSS
$limitesISSS = [
    "ANUAL" => 12000.00,
    "MENSUAL" => 1000.00,
    "QUINCENAL" => 500.00,
    "SEMANAL" => 250.00
];

// Variables para resultados
$tdSueldo = '$ 0.00';
$tdTipo = 'ANUAL';
$tdIsss = '$ 0.00';
$tdAfp = '$ 0.00';
$tdRentaGravada = '$ 0.00';
$tdRenta = '$ 0.00';
$tdTotal = '$ 0.00';
$tablaDescuentos = '';
$mostrarResultados = false;

// Función para formatear números
function formatoNumero($numero) {
    return number_format($numero, 2, '.', ',');
}

// Función para convertir texto a número
function convertirTextoANumero($texto) {
    if (empty($texto)) return 0;
    $limpio = str_replace(',', '', $texto);
    $numero = floatval($limpio);
    return is_nan($numero) ? 0 : $numero;
}

// Función para formatear sueldo mientras se escribe (para JavaScript)
function formatearSueldoParaInput($valor) {
    if (empty($valor)) return '';
    $limpio = str_replace(',', '', $valor);
    if (!is_numeric($limpio)) return $valor;
    return number_format(floatval($limpio), 2, '.', ',');
}

// Procesar limpieza
if ($limpiar) {
    $sueldo = '';
    $periodo = '';
    $mostrarResultados = false;
    // Redirigir para limpiar POST
    header('Location: ' . $_SERVER['PHP_SELF']);
    exit();
}

// Procesar cálculo
if ($calcular && !empty($sueldo) && !empty($periodo)) {
    $sueldoBruto = convertirTextoANumero($sueldo);
    
    if ($sueldoBruto > 0) {
        $limiteISSS = $limitesISSS[$periodo];
        $isss = min($sueldoBruto, $limiteISSS) * 0.03;
        $afp = $sueldoBruto * 0.0725;
        $rentaGravada = $sueldoBruto - $isss - $afp;
        
        // Filtrar tramos del periodo seleccionado
        $tramosDelPeriodo = array_filter($tramosISR, function($t) use ($periodo) {
            return $t['tipo'] === $periodo;
        });
        
        $renta = 0;
        foreach ($tramosDelPeriodo as $tramo) {
            if ($rentaGravada >= $tramo['desde'] && $rentaGravada <= $tramo['hasta']) {
                $excedente = $rentaGravada - $tramo['excedente'];
                $renta = $tramo['cuotaFija'] + ($excedente * ($tramo['tasa'] / 100));
                break;
            }
        }
        
        $total = $rentaGravada - $renta;
        $totalDescuentos = $isss + $afp + $renta;
        $porcentajeDescuento = ($totalDescuentos / $sueldoBruto) * 100;
        $porcentajeLiquido = 100 - $porcentajeDescuento;
        
        // Asignar valores para mostrar
        $tdSueldo = '$ ' . formatoNumero($sueldoBruto);
        $tdTipo = $periodo;
        $tdIsss = '$ ' . formatoNumero($isss);
        $tdAfp = '$ ' . formatoNumero($afp);
        $tdRentaGravada = '$ ' . formatoNumero($rentaGravada);
        $tdRenta = '$ ' . formatoNumero($renta);
        $tdTotal = '$ ' . formatoNumero($total);
        
        $periodoText = $periodo === 'ANUAL' ? 'Anual' : 
                      ($periodo === 'MENSUAL' ? 'Mensual' : 
                      ($periodo === 'QUINCENAL' ? 'Quincenal' : 'Semanal'));
        
        $tablaDescuentos = '
            <tr><td>Sueldo Bruto ' . $periodoText . '</td><td>100%</td><td>$' . formatoNumero($sueldoBruto) . '</td></tr>
            <tr><td>ISSS (Seguro Social)</td><td>3%</td><td>$' . formatoNumero($isss) . '</td></tr>
            <tr><td>AFP (Pensión)</td><td>7.25%</td><td>$' . formatoNumero($afp) . '</td></tr>
            <tr><td>ISR (Impuesto Renta)</td><td>' . number_format(($renta/$sueldoBruto)*100, 2) . '%</td><td>$' . formatoNumero($renta) . '</td></tr>
            <tr style="background: rgba(26, 86, 219, 0.1);">
                <td><strong>Total Descuentos ' . $periodoText . '</strong></td>
                <td><strong>' . number_format($porcentajeDescuento, 2) . '%</strong></td>
                <td><strong>$' . formatoNumero($totalDescuentos) . '</strong></td>
            </tr>
            <tr style="background: rgba(5, 150, 105, 0.1);">
                <td><strong>Sueldo Líquido ' . $periodoText . '</strong></td>
                <td><strong>' . number_format($porcentajeLiquido, 2) . '%</strong></td>
                <td><strong>$' . formatoNumero($total) . '</strong></td>
            </tr>
        ';
        
        $mostrarResultados = true;
    }
}

// Formatear sueldo para mostrar en input
$sueldoFormateado = formatearSueldoParaInput($sueldo);
?>
<!DOCTYPE html>
<html lang="es-SV">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no, viewport-fit=cover">
    <meta name="theme-color" content="#111827">
    <title>ISR | El Salvador</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        /* Optimizado para móviles - CSS reducido y optimizado */
        :root {
            --primary: #1a56db;
            --dark: #111827;
            --light: #f9fafb;
            --secondary: #059669;
            --accent: #7c3aed;
            --error: #dc2626;
            --safe-top: env(safe-area-inset-top, 0px);
            --safe-bottom: env(safe-area-inset-bottom, 0px);
            --safe-left: env(safe-area-inset-left, 0px);
            --safe-right: env(safe-area-inset-right, 0px);
        }

        * { 
            margin: 0; 
            padding: 0; 
            box-sizing: border-box;
            -webkit-tap-highlight-color: rgba(0,0,0,0.1);
        }

        html {
            font-size: 16px;
            -webkit-text-size-adjust: 100%;
            -moz-text-size-adjust: 100%;
            text-size-adjust: 100%;
        }

        body {
            background: linear-gradient(135deg, #030712 0%, #111827 100%);
            color: var(--light);
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Oxygen, Ubuntu, sans-serif;
            line-height: 1.4;
            min-height: 100vh;
            min-height: -webkit-fill-available;
            padding: 12px;
            overflow-x: hidden;
            -webkit-font-smoothing: antialiased;
            -moz-osx-font-smoothing: grayscale;
            touch-action: manipulation;
        }

        @supports (padding: max(0px)) {
            body {
                padding: max(12px, var(--safe-top)) max(12px, var(--safe-right)) 
                         max(12px, var(--safe-bottom)) max(12px, var(--safe-left));
            }
        }

        .container {
            max-width: 100%;
            margin: 0 auto;
            width: 100%;
        }

        .header {
            text-align: center;
            padding: 20px 0 24px;
        }

        .isr-title {
            font-size: clamp(2.5rem, 8vw, 3.5rem);
            font-weight: 900;
            background: linear-gradient(135deg, #3b82f6 0%, var(--accent) 100%);
            -webkit-background-clip: text;
            background-clip: text;
            color: transparent;
            margin-bottom: 4px;
            letter-spacing: -0.02em;
            line-height: 1;
        }

        .signature {
            font-family: 'Times New Roman', serif;
            font-size: clamp(1.2rem, 4vw, 1.5rem);
            font-style: italic;
            margin-top: 8px;
            color: var(--light);
        }

        .main-content {
            display: flex;
            flex-direction: column;
            gap: 20px;
            margin-bottom: 24px;
        }

        @media (min-width: 768px) {
            .main-content {
                display: grid;
                grid-template-columns: repeat(2, 1fr);
                gap: 24px;
            }
        }

        .card {
            background: rgba(17, 24, 39, 0.9);
            backdrop-filter: blur(20px);
            -webkit-backdrop-filter: blur(20px);
            border-radius: 16px;
            border: 1px solid rgba(255, 255, 255, 0.1);
            padding: 20px;
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.2);
        }

        @media (min-width: 768px) {
            .card {
                padding: 24px;
                border-radius: 20px;
            }
        }

        .card-header {
            display: flex;
            align-items: center;
            gap: 12px;
            margin-bottom: 20px;
        }

        @media (max-width: 360px) {
            .card-header {
                gap: 10px;
                margin-bottom: 16px;
            }
        }

        .card-icon {
            width: 40px;
            height: 40px;
            min-width: 40px;
            background: linear-gradient(135deg, var(--primary), var(--accent));
            border-radius: 10px;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-size: 1.1rem;
        }

        @media (max-width: 360px) {
            .card-icon {
                width: 36px;
                height: 36px;
                min-width: 36px;
                font-size: 1rem;
            }
        }

        .card-title {
            font-size: 1.1rem;
            font-weight: 700;
            color: var(--light);
            line-height: 1.3;
        }

        @media (min-width: 768px) {
            .card-title {
                font-size: 1.25rem;
            }
        }

        .input-group {
            margin-bottom: 20px;
            position: relative;
        }

        .input-label {
            display: block;
            margin-bottom: 6px;
            color: #9ca3af;
            font-size: 0.85rem;
            font-weight: 500;
        }

        .input-container {
            position: relative;
        }

        .input-icon {
            position: absolute;
            left: 14px;
            top: 50%;
            transform: translateY(-50%);
            color: #6b7280;
            font-size: 0.95rem;
            pointer-events: none;
            z-index: 2;
        }

        @media (max-width: 360px) {
            .input-icon {
                left: 12px;
                font-size: 0.9rem;
            }
        }

        .input {
            width: 100%;
            padding: 14px 14px 14px 44px;
            background: rgba(30, 41, 59, 0.8);
            border: 2px solid rgba(255, 255, 255, 0.15);
            border-radius: 12px;
            color: var(--light);
            font-size: 1rem;
            font-weight: 500;
            transition: all 0.2s ease;
            -webkit-appearance: none;
            appearance: none;
            touch-action: manipulation;
            position: relative;
            z-index: 1;
        }

        @media (max-width: 360px) {
            .input {
                padding: 12px 12px 12px 40px;
                font-size: 0.95rem;
            }
        }

        .input:focus {
            outline: none;
            border-color: var(--primary);
            background: rgba(30, 41, 59, 0.9);
            box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.15);
            font-size: 16px !important;
        }

        .input.error {
            border-color: var(--error);
        }

        .currency {
            position: absolute;
            right: 14px;
            top: 50%;
            transform: translateY(-50%);
            color: var(--secondary);
            font-weight: 600;
            font-size: 0.9rem;
            z-index: 2;
        }

        @media (max-width: 360px) {
            .currency {
                right: 12px;
                font-size: 0.85rem;
            }
        }

        /* Select de periodo optimizado para móviles */
        .periodo-select-wrapper {
            margin-bottom: 20px;
        }

        .periodo-select {
            width: 100%;
            padding: 14px;
            background: rgba(30, 41, 59, 0.8);
            border: 2px solid rgba(255, 255, 255, 0.15);
            border-radius: 12px;
            color: var(--light);
            font-size: 1rem;
            font-weight: 500;
            cursor: pointer;
            transition: all 0.2s ease;
            -webkit-appearance: none;
            appearance: none;
            background-image: url("data:image/svg+xml;charset=UTF-8,%3csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 24 24' fill='none' stroke='%233b82f6' stroke-width='2' stroke-linecap='round' stroke-linejoin='round'%3e%3cpolyline points='6 9 12 15 18 9'%3e%3c/polyline%3e%3c/svg%3e");
            background-repeat: no-repeat;
            background-position: right 14px center;
            background-size: 16px;
            padding-right: 40px;
        }

        .periodo-select:focus {
            outline: none;
            border-color: var(--primary);
            background-color: rgba(30, 41, 59, 0.9);
            box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.15);
        }

        @media (max-width: 360px) {
            .periodo-select {
                padding: 12px;
                font-size: 0.95rem;
                padding-right: 36px;
                background-position: right 12px center;
            }
        }

        /* Botones optimizados para toque */
        .button-group {
            display: flex;
            flex-direction: column;
            gap: 12px;
            margin-top: 24px;
        }

        @media (min-width: 480px) {
            .button-group {
                flex-direction: row;
            }
        }

        .btn {
            padding: 16px 20px;
            border-radius: 12px;
            font-weight: 600;
            font-size: 0.95rem;
            cursor: pointer;
            border: none;
            transition: all 0.2s ease;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 10px;
            flex: 1;
            min-height: 50px;
            -webkit-user-select: none;
            user-select: none;
            touch-action: manipulation;
            position: relative;
            overflow: hidden;
            min-width: 120px;
        }

        .btn:active {
            transform: scale(0.98);
        }

        @media (max-width: 360px) {
            .btn {
                padding: 14px 16px;
                font-size: 0.9rem;
                min-height: 46px;
                min-width: 110px;
            }
        }

        .btn-primary {
            background: linear-gradient(135deg, var(--primary), #1e40af);
            color: white;
            box-shadow: 0 2px 10px rgba(26, 86, 219, 0.3);
        }

        .btn-primary:active {
            background: linear-gradient(135deg, #1e40af, var(--primary));
            box-shadow: 0 1px 6px rgba(26, 86, 219, 0.3);
        }

        .btn-secondary {
            background: rgba(255, 255, 255, 0.08);
            color: var(--light);
            border: 1px solid rgba(255, 255, 255, 0.1);
        }

        .btn-secondary:active {
            background: rgba(255, 255, 255, 0.12);
        }

        .btn i {
            font-size: 0.95rem;
        }

        @media (max-width: 360px) {
            .btn i {
                font-size: 0.85rem;
            }
        }

        /* Tablas optimizadas para móviles */
        .table-container {
            margin-top: 20px;
            overflow-x: auto;
            -webkit-overflow-scrolling: touch;
            border-radius: 14px;
            border: 1px solid rgba(255, 255, 255, 0.1);
            background: rgba(30, 41, 59, 0.5);
        }

        table {
            width: 100%;
            border-collapse: collapse;
            min-width: 280px;
        }

        th, td {
            padding: 14px 16px;
            text-align: left;
            border-bottom: 1px solid rgba(255, 255, 255, 0.08);
            font-size: 0.9rem;
            white-space: nowrap;
        }

        @media (max-width: 360px) {
            th, td {
                padding: 12px 14px;
                font-size: 0.85rem;
            }
        }

        th {
            background: rgba(26, 86, 219, 0.2);
            font-weight: 600;
            color: var(--light);
        }

        td {
            color: var(--light);
            font-weight: 500;
        }

        tr:last-child td {
            border-bottom: none;
        }

        .highlight-row {
            background: rgba(5, 150, 105, 0.1);
            font-weight: 700 !important;
            color: var(--secondary) !important;
        }

        .hidden {
            display: none;
        }

        .visible {
            display: block;
        }

        /* Footer optimizado */
        .footer {
            text-align: center;
            padding: 24px 0 20px;
            color: #9ca3af;
            font-size: 0.85rem;
            border-top: 1px solid rgba(255, 255, 255, 0.1);
            margin-top: 24px;
            position: relative;
        }

        /* Ajustes específicos para iOS */
        @supports (-webkit-touch-callout: none) {
            .card {
                backdrop-filter: saturate(180%) blur(20px);
                -webkit-backdrop-filter: saturate(180%) blur(20px);
            }
            
            body {
                min-height: -webkit-fill-available;
            }
            
            input, select {
                -webkit-user-select: auto !important;
                user-select: auto !important;
            }
            
            .btn {
                -webkit-tap-highlight-color: rgba(0,0,0,0.1);
            }
        }

        /* Android Chrome overscroll fix */
        body {
            overscroll-behavior-y: contain;
        }

        /* Fix para teclado en iOS */
        @media screen and (max-width: 768px) {
            input, select, textarea {
                font-size: 16px !important;
            }
        }

        /* Ajustes para pantallas muy pequeñas */
        @media (max-width: 320px) {
            body {
                padding: 10px;
            }
            
            .card {
                padding: 16px;
            }
            
            .isr-title {
                font-size: 2rem;
            }
            
            .signature {
                font-size: 1.1rem;
            }
            
            .card-icon {
                width: 32px;
                height: 32px;
                min-width: 32px;
                font-size: 0.9rem;
            }
            
            .btn {
                padding: 12px 14px;
                min-height: 42px;
                font-size: 0.85rem;
            }
            
            th, td {
                padding: 10px 12px;
                font-size: 0.8rem;
            }
        }

        /* Ajustes para modo landscape en móviles */
        @media (max-height: 500px) and (orientation: landscape) {
            .header {
                padding: 12px 0 16px;
            }
            
            .isr-title {
                font-size: 2rem;
            }
            
            .signature {
                font-size: 1.1rem;
                margin-top: 4px;
            }
            
            .card {
                padding: 16px;
            }
            
            .input-group {
                margin-bottom: 16px;
            }
            
            .button-group {
                margin-top: 20px;
            }
        }

        /* Mejora de accesibilidad para botones */
        .btn:focus-visible {
            outline: 2px solid var(--primary);
            outline-offset: 2px;
        }

        /* Prevenir zoom en inputs en iOS */
        @media screen and (max-width: 768px) {
            input[type="text"], select {
                font-size: 16px !important;
            }
        }

        /* Scroll suave en iOS */
        .table-container {
            -webkit-overflow-scrolling: touch;
            scrollbar-width: none; /* Firefox */
        }

        .table-container::-webkit-scrollbar {
            display: none; /* Chrome, Safari, Edge */
        }
    </style>
</head>
<body>
    <div class="container">
        <header class="header">
            <div class="isr-title">ISR</div>
            <div class="signature">El Salvador</div>
        </header>

        <main class="main-content">
            <section class="card">
                <div class="card-header">
                    <div class="card-icon"><i class="fas fa-edit"></i></div>
                    <h2 class="card-title">Calculadora de Sueldo</h2>
                </div>

                <form method="POST" action="" id="calculadoraForm">
                    <div class="input-group">
                        <label class="input-label">Sueldo (USD)</label>
                        <div class="input-container">
                            <i class="fas fa-dollar-sign input-icon"></i>
                            <input type="text" 
                                   name="sueldo" 
                                   id="sueldo" 
                                   class="input" 
                                   placeholder="0.00"
                                   inputmode="decimal"
                                   autocomplete="off"
                                   maxlength="15"
                                   value="<?php echo htmlspecialchars($sueldoFormateado); ?>"
                                   required>
                            <span class="currency">USD</span>
                        </div>
                    </div>

                    <div class="periodo-select-wrapper">
                        <label class="input-label">Tipo de periodo</label>
                        <select name="periodo" class="periodo-select" id="periodoSelect" required>
                            <option value="" disabled <?php echo empty($periodo) ? 'selected' : ''; ?>>Seleccione un periodo</option>
                            <option value="ANUAL" <?php echo $periodo === 'ANUAL' ? 'selected' : ''; ?>>ANUAL (365 días)</option>
                            <option value="MENSUAL" <?php echo $periodo === 'MENSUAL' ? 'selected' : ''; ?>>MENSUAL (30 días)</option>
                            <option value="QUINCENAL" <?php echo $periodo === 'QUINCENAL' ? 'selected' : ''; ?>>QUINCENAL (15 días)</option>
                            <option value="SEMANAL" <?php echo $periodo === 'SEMANAL' ? 'selected' : ''; ?>>SEMANAL (7 días)</option>
                        </select>
                    </div>

                    <div class="button-group">
                        <button type="submit" name="calcular" value="1" class="btn btn-primary" id="btnCalcular">
                            <i class="fas fa-calculator"></i> Calcular ISR
                        </button>
                        <button type="submit" name="limpiar" value="1" class="btn btn-secondary" id="btnLimpiar">
                            <i class="fas fa-redo"></i> Limpiar Todo
                        </button>
                    </div>
                </form>

                <div id="tablaRenta" class="table-container <?php echo $mostrarResultados ? 'visible' : 'hidden'; ?>">
                    <table>
                        <tr><td>Sueldo</td><td id="tdSueldo"><?php echo $tdSueldo; ?></td></tr>
                        <tr><td>Tipo</td><td id="tdTipo"><?php echo $tdTipo; ?></td></tr>
                        <tr><td>ISSS (3%)</td><td id="tdIsss"><?php echo $tdIsss; ?></td></tr>
                        <tr><td>AFP (7.25%)</td><td id="tdAfp"><?php echo $tdAfp; ?></td></tr>
                        <tr><td>Renta Gravada</td><td id="tdRentaGravada"><?php echo $tdRentaGravada; ?></td></tr>
                        <tr><td>Renta</td><td id="tdRenta"><?php echo $tdRenta; ?></td></tr>
                        <tr><td class="highlight-row">Total</td><td id="tdTotal" class="highlight-row"><?php echo $tdTotal; ?></td></tr>
                    </table>
                </div>
            </section>

            <section class="card">
                <div class="card-header">
                    <div class="card-icon"><i class="fas fa-info-circle"></i></div>
                    <h2 class="card-title">Desglose de Descuentos</h2>
                </div>

                <div class="table-container">
                    <table>
                        <thead>
                            <tr><th>Concepto</th><th>Porcentaje</th><th>Descuento</th></tr>
                        </thead>
                        <tbody id="tablaDescuentosBody">
                            <?php if ($mostrarResultados && !empty($tablaDescuentos)): ?>
                                <?php echo $tablaDescuentos; ?>
                            <?php else: ?>
                                <tr><td colspan="3" style="text-align: center; padding: 30px;">
                                    <i class="fas fa-calculator" style="font-size: 1.5rem; margin-bottom: 12px; display: block; color: var(--primary);"></i>
                                    Ingresa un sueldo y haz clic en "Calcular ISR"
                                </td></tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </section>
        </main>

        <footer class="footer">
            <p>© <?php echo date('Y'); ?> Sistema ISR | El Salvador</p>
        </footer>
    </div>

    <script>
        // Optimización para móviles
        document.addEventListener('DOMContentLoaded', function() {
            const sueldoInput = document.getElementById('sueldo');
            const periodoSelect = document.getElementById('periodoSelect');
            const btnCalcular = document.getElementById('btnCalcular');
            const formulario = document.getElementById('calculadoraForm');
            
            // Función para formatear sueldo mientras se escribe
            function formatearSueldoInput() {
                let valor = sueldoInput.value;
                
                if (valor === '') return;
                
                // Eliminar caracteres no permitidos
                valor = valor.replace(/[^0-9.,]/g, '');
                
                // Manejar múltiples puntos decimales
                const partes = valor.split('.');
                if (partes.length > 2) {
                    valor = partes[0] + '.' + partes.slice(1).join('');
                }
                
                // Formatear parte entera con comas
                if (partes[0]) {
                    partes[0] = partes[0].replace(/\B(?=(\d{3})+(?!\d))/g, ',');
                }
                
                // Unir partes
                const nuevoValor = partes.length > 1 ? partes[0] + '.' + partes[1] : partes[0];
                
                if (nuevoValor !== sueldoInput.value) {
                    sueldoInput.value = nuevoValor;
                }
                
                // Validar formulario
                validarFormulario();
            }
            
            // Validar formulario
            function validarFormulario() {
                const sueldo = convertirTextoANumero(sueldoInput.value.trim());
                const periodo = periodoSelect.value;
                
                btnCalcular.disabled = !(sueldo > 0 && periodo);
            }
            
            // Convertir texto a número
            function convertirTextoANumero(texto) {
                if (!texto) return 0;
                const limpio = texto.replace(/,/g, '');
                const numero = parseFloat(limpio);
                return isNaN(numero) ? 0 : numero;
            }
            
            // Eventos para sueldo
            sueldoInput.addEventListener('input', formatearSueldoInput);
            sueldoInput.addEventListener('focus', function() {
                this.classList.remove('error');
            });
            
            // Evento para periodo
            periodoSelect.addEventListener('change', validarFormulario);
            
            // Evento para teclado
            document.addEventListener('keydown', function(e) {
                if (e.key === 'Enter' && document.activeElement === sueldoInput) {
                    e.preventDefault();
                    if (!btnCalcular.disabled) {
                        btnCalcular.click();
                    }
                }
            });
            
            // Prevenir entrada inválida
            sueldoInput.addEventListener('keypress', function(e) {
                const char = String.fromCharCode(e.keyCode || e.which);
                if (!/[0-9.,]/.test(char) && 
                    e.keyCode !== 8 &&  
                    e.keyCode !== 9 &&  
                    e.keyCode !== 13 && 
                    e.keyCode !== 46 && 
                    (e.keyCode < 37 || e.keyCode > 40)) {
                    e.preventDefault();
                }
            });
            
            // Mejoras táctiles para botones
            [btnCalcular].forEach(btn => {
                btn.addEventListener('touchstart', function() {
                    if (!this.disabled) {
                        this.style.opacity = '0.8';
                    }
                }, { passive: true });
                
                btn.addEventListener('touchend', function() {
                    this.style.opacity = '';
                }, { passive: true });
                
                btn.addEventListener('touchcancel', function() {
                    this.style.opacity = '';
                }, { passive: true });
            });
            
            // Validación al enviar formulario
            formulario.addEventListener('submit', function(e) {
                const sueldo = convertirTextoANumero(sueldoInput.value.trim());
                const periodo = periodoSelect.value;
                
                if (!periodo) {
                    e.preventDefault();
                    periodoSelect.style.borderColor = 'var(--error)';
                    periodoSelect.focus();
                    setTimeout(() => {
                        periodoSelect.style.borderColor = '';
                    }, 2000);
                    return;
                }
                
                if (sueldo <= 0) {
                    e.preventDefault();
                    sueldoInput.classList.add('error');
                    sueldoInput.focus();
                    return;
                }
                
                // Si es el botón limpiar, no necesitamos validación
                if (e.submitter && e.submitter.name === 'limpiar') {
                    return true;
                }
            });
            
            // Auto-enfoque en sueldo al cargar en móviles
            if (window.innerWidth < 768 && !sueldoInput.value) {
                setTimeout(() => {
                    sueldoInput.focus();
                }, 300);
            }
            
            // Inicializar validación
            validarFormulario();
            
            // Scroll suave a resultados si existen
            <?php if ($mostrarResultados): ?>
                setTimeout(() => {
                    const tablaRenta = document.getElementById('tablaRenta');
                    if (tablaRenta && window.innerWidth < 768) {
                        tablaRenta.scrollIntoView({ behavior: 'smooth', block: 'center' });
                    }
                }, 100);
            <?php endif; ?>
            
            // Mejorar experiencia en iOS
            if (/iPad|iPhone|iPod/.test(navigator.userAgent)) {
                // Forzar tamaño de fuente en inputs
                sueldoInput.style.fontSize = '16px';
                periodoSelect.style.fontSize = '16px';
                
                // Prevenir zoom en focus
                sueldoInput.addEventListener('focus', function() {
                    this.style.fontSize = '16px';
                });
                
                periodoSelect.addEventListener('focus', function() {
                    this.style.fontSize = '16px';
                });
            }
            
            // Mejorar experiencia en Android
            if (/Android/.test(navigator.userAgent)) {
                // Asegurar que los botones sean fácilmente tocables
                document.querySelectorAll('.btn').forEach(btn => {
                    btn.style.minHeight = '48px';
                    btn.style.padding = '14px 18px';
                });
            }
        });
    </script>
</body>
</html>