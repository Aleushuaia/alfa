<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Tablero de Control - Sistema Judicial</title>
    <!-- Bootstrap 5 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Bootstrap Icons -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">
    <style>
        :root {
            --color-primary: #2c3e50;
            --color-secondary: #34495e;
            --color-accent: #c0392b;
            --color-success: #27ae60;
            --color-warning: #f39c12;
            --color-info: #2980b9;
        }
        
        body {
            background-color: #f8f9fa;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }
        
        .navbar {
            background: linear-gradient(135deg, var(--color-primary), var(--color-secondary));
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        
        .navbar-brand {
            font-weight: 600;
            letter-spacing: 0.5px;
        }
        
        .card {
            border: none;
            border-radius: 12px;
            box-shadow: 0 2px 15px rgba(0,0,0,0.08);
            transition: transform 0.2s ease, box-shadow 0.2s ease;
        }
        
        .card:hover {
            transform: translateY(-3px);
            box-shadow: 0 5px 25px rgba(0,0,0,0.12);
        }
        
        .card-header {
            border-radius: 12px 12px 0 0 !important;
            font-weight: 600;
            border-bottom: none;
        }
        
        .stat-card {
            border-left: 4px solid;
            padding: 1.5rem;
        }
        
        .stat-card.organismo-1 { border-left-color: var(--color-accent); }
        .stat-card.organismo-2 { border-left-color: var(--color-success); }
        .stat-card.organismo-3 { border-left-color: var(--color-warning); }
        .stat-card.organismo-4 { border-left-color: var(--color-info); }
        
        .stat-number {
            font-size: 2.5rem;
            font-weight: 700;
            line-height: 1;
        }
        
        .stat-label {
            color: #6c757d;
            font-size: 0.875rem;
            margin-top: 0.5rem;
        }
        
        .chart-container {
            position: relative;
            height: 300px;
        }
        
        .badge-total {
            font-size: 0.75rem;
            padding: 0.35rem 0.65rem;
        }
        
        footer {
            background: var(--color-primary);
            color: white;
            padding: 1rem 0;
            margin-top: 2rem;
        }
        
        .section-title {
            color: var(--color-primary);
            font-weight: 600;
            margin-bottom: 1rem;
            padding-bottom: 0.5rem;
            border-bottom: 2px solid var(--color-accent);
            display: inline-block;
        }
    </style>
</head>
<body>
    <!-- Navbar -->
    <nav class="navbar navbar-expand-lg navbar-dark">
        <div class="container-fluid">
            <a class="navbar-brand" href="#">
                <i class="bi bi-building me-2"></i>
                Sistema Judicial - Tablero de Control
            </a>
            <div class="d-flex align-items-center">
                <span class="text-white-50 me-3">
                    <i class="bi bi-calendar3 me-1"></i>
                    {{ now()->format('d/m/Y') }}
                </span>
                <span class="badge bg-light text-dark">
                    <i class="bi bi-person-circle me-1"></i>
                    Administrador
                </span>
            </div>
        </div>
    </nav>

    <div class="container-fluid py-4">
        <!-- KPIs Principales -->
        <div class="row mb-4">
            <div class="col-12">
                <h5 class="section-title">Resumen General de Expedientes</h5>
            </div>
        </div>
        
        <div class="row g-4 mb-4">
            @php
                $organismos = [
                    ['id' => 1, 'nombre' => 'Juzgado de Primera Instancia', 'total' => 1247, 'mes' => 89, 'pendientes' => 156, 'resueltos' => 1091],
                    ['id' => 2, 'nombre' => 'Cámara de Apelaciones', 'total' => 856, 'mes' => 45, 'pendientes' => 78, 'resueltos' => 778],
                    ['id' => 3, 'nombre' => 'Juzgado de Paz', 'total' => 2034, 'mes' => 123, 'pendientes' => 234, 'resueltos' => 1800],
                    ['id' => 4, 'nombre' => 'Tribunal de Trabajo', 'total' => 689, 'mes' => 34, 'pendientes' => 89, 'resueltos' => 600],
                ];
                $totalGeneral = collect($organismos)->sum('total');
                $totalPendientes = collect($organismos)->sum('pendientes');
                $totalResueltos = collect($organismos)->sum('resueltos');
            @endphp
            
            <!-- KPI Total -->
            <div class="col-md-3">
                <div class="card stat-card organismo-1">
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-start">
                            <div>
                                <div class="stat-number text-danger">{{ number_format($totalGeneral, 0, '', '.') }}</div>
                                <div class="stat-label">Total Expedientes</div>
                            </div>
                            <i class="bi bi-files text-danger" style="font-size: 2.5rem; opacity: 0.3;"></i>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- KPI Pendientes -->
            <div class="col-md-3">
                <div class="card stat-card organismo-3">
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-start">
                            <div>
                                <div class="stat-number text-warning">{{ number_format($totalPendientes, 0, '', '.') }}</div>
                                <div class="stat-label">Pendientes</div>
                            </div>
                            <i class="bi bi-hourglass-split text-warning" style="font-size: 2.5rem; opacity: 0.3;"></i>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- KPI Resueltos -->
            <div class="col-md-3">
                <div class="card stat-card organismo-2">
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-start">
                            <div>
                                <div class="stat-number text-success">{{ number_format($totalResueltos, 0, '', '.') }}</div>
                                <div class="stat-label">Resueltos</div>
                            </div>
                            <i class="bi bi-check-circle text-success" style="font-size: 2.5rem; opacity: 0.3;"></i>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- KPI Eficiencia -->
            <div class="col-md-3">
                <div class="card stat-card organismo-4">
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-start">
                            <div>
                                <div class="stat-number text-info">{{ round(($totalResueltos / $totalGeneral) * 100, 1) }}%</div>
                                <div class="stat-label">Tasa de Resolución</div>
                            </div>
                            <i class="bi bi-graph-up-arrow text-info" style="font-size: 2.5rem; opacity: 0.3;"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Gráfico Principal - Distribución por Organismo -->
        <div class="row g-4 mb-4">
            <div class="col-lg-6">
                <div class="card h-100">
                    <div class="card-header bg-white py-3">
                        <div class="d-flex justify-content-between align-items-center">
                            <h6 class="mb-0">
                                <i class="bi bi-pie-chart me-2 text-danger"></i>
                                Distribución de Expedientes por Organismo
                            </h6>
                        </div>
                    </div>
                    <div class="card-body">
                        <div class="chart-container">
                            <canvas id="chartDistribucion"></canvas>
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="col-lg-6">
                <div class="card h-100">
                    <div class="card-header bg-white py-3">
                        <div class="d-flex justify-content-between align-items-center">
                            <h6 class="mb-0">
                                <i class="bi bi-bar-chart me-2 text-success"></i>
                                Estado de Expedientes por Organismo
                            </h6>
                        </div>
                    </div>
                    <div class="card-body">
                        <div class="chart-container">
                            <canvas id="chartEstado"></canvas>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Infografías por Organismo -->
        <div class="row mb-3 mt-4">
            <div class="col-12">
                <h5 class="section-title">Infografías por Organismo Judicial</h5>
            </div>
        </div>

        @foreach($organismos as $index => $org)
        @php
            $colors = ['danger', 'success', 'warning', 'info'];
            $bgColors = ['#c0392b', '#27ae60', '#f39c12', '#2980b9'];
        @endphp
        <div class="row g-4 mb-4">
            <div class="col-12">
                <div class="card">
                    <div class="card-header bg-{{ $colors[$index] }} bg-opacity-10 py-3">
                        <div class="row align-items-center">
                            <div class="col-md-6">
                                <h5 class="mb-0 text-{{ $colors[$index] }}">
                                    <i class="bi bi-bank me-2"></i>
                                    {{ $org['nombre'] }}
                                </h5>
                            </div>
                            <div class="col-md-6 text-md-end">
                                <span class="badge bg-{{ $colors[$index] }} badge-total me-2">
                                    Este mes: {{ $org['mes'] }} nuevos
                                </span>
                                <span class="badge bg-secondary badge-total">
                                    Total: {{ number_format($org['total'], 0, '', '.') }} expedientes
                                </span>
                            </div>
                        </div>
                    </div>
                    <div class="card-body">
                        <div class="row align-items-center">
                            <!-- Mini estadísticas -->
                            <div class="col-md-3">
                                <div class="text-center p-3 bg-light rounded">
                                    <div class="display-5 fw-bold text-{{ $colors[$index] }}">{{ number_format($org['total'], 0, '', '.') }}</div>
                                    <small class="text-muted">Total Expedientes</small>
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="text-center p-3 bg-light rounded">
                                    <div class="display-5 fw-bold text-warning">{{ $org['pendientes'] }}</div>
                                    <small class="text-muted">Pendientes</small>
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="text-center p-3 bg-light rounded">
                                    <div class="display-5 fw-bold text-success">{{ number_format($org['resueltos'], 0, '', '.') }}</div>
                                    <small class="text-muted">Resueltos</small>
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="text-center p-3 bg-light rounded">
                                    <div class="display-5 fw-bold text-info">{{ round(($org['resueltos'] / $org['total']) * 100, 1) }}%</div>
                                    <small class="text-muted">Resolución</small>
                                </div>
                            </div>
                        </div>
                        
                        <!-- Barra de progreso -->
                        <div class="mt-3">
                            <div class="d-flex justify-content-between mb-1">
                                <small class="text-muted">Progreso de resolución</small>
                                <small class="text-muted">{{ round(($org['resueltos'] / $org['total']) * 100, 1) }}%</small>
                            </div>
                            <div class="progress" style="height: 10px;">
                                <div class="progress-bar bg-{{ $colors[$index] }}" 
                                     role="progressbar" 
                                     style="width: {{ round(($org['resueltos'] / $org['total']) * 100, 1) }}%"
                                     aria-valuenow="{{ $org['resueltos'] }}" 
                                     aria-valuemin="0" 
                                     aria-valuemax="{{ $org['total'] }}">
                                </div>
                            </div>
                        </div>
                        
                        <!-- Mini gráfico de tendencia mensual -->
                        <div class="mt-3" style="height: 120px;">
                            <canvas id="chartTendencia{{ $org['id'] }}"></canvas>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        @endforeach

        <!-- Gráfico de Evolución Mensual -->
        <div class="row g-4 mt-2">
            <div class="col-12">
                <div class="card">
                    <div class="card-header bg-white py-3">
                        <h6 class="mb-0">
                            <i class="bi bi-graph-up me-2 text-primary"></i>
                            Evolución Mensual de Expedientes Ingresados (Últimos 6 Meses)
                        </h6>
                    </div>
                    <div class="card-body">
                        <div class="chart-container" style="height: 350px;">
                            <canvas id="chartEvolucion"></canvas>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Footer -->
    <footer class="text-center">
        <div class="container">
            <small>
                <i class="bi bi-building me-1"></i>
                Sistema Judicial Argentino - Tablero de Control &copy; {{ date('Y') }}
            </small>
        </div>
    </footer>

    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <!-- Chart.js -->
    <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.2/dist/chart.umd.min.js"></script>
    
    <script>
        // Colores para los organismos
        const coloresOrganismos = {
            organismo1: '#c0392b',  // Rojo
            organismo2: '#27ae60',  // Verde
            organismo3: '#f39c12',  // Naranja
            organismo4: '#2980b9'   // Azul
        };

        // Datos (estos vendrían de tu controller Laravel)
        const datosOrganismos = @json($organismos);
        
        // ========================================
        // GRÁFICO 1: Distribución por Organismo
        // ========================================
        const ctxDistribucion = document.getElementById('chartDistribucion').getContext('2d');
        new Chart(ctxDistribucion, {
            type: 'doughnut',
            data: {
                labels: datosOrganismos.map(o => o.nombre),
                datasets: [{
                    data: datosOrganismos.map(o => o.total),
                    backgroundColor: [
                        coloresOrganismos.organismo1,
                        coloresOrganismos.organismo2,
                        coloresOrganismos.organismo3,
                        coloresOrganismos.organismo4
                    ],
                    borderWidth: 3,
                    borderColor: '#ffffff'
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        position: 'bottom',
                        labels: {
                            padding: 15,
                            usePointStyle: true,
                            font: { size: 12 }
                        }
                    },
                    tooltip: {
                        callbacks: {
                            label: function(context) {
                                const total = context.dataset.data.reduce((a, b) => a + b, 0);
                                const percentage = ((context.raw / total) * 100).toFixed(1);
                                return `${context.label}: ${context.raw.toLocaleString('es-AR')} (${percentage}%)`;
                            }
                        }
                    }
                }
            }
        });

        // ========================================
        // GRÁFICO 2: Estado por Organismo
        // ========================================
        const ctxEstado = document.getElementById('chartEstado').getContext('2d');
        new Chart(ctxEstado, {
            type: 'bar',
            data: {
                labels: datosOrganismos.map(o => o.nombre),
                datasets: [
                    {
                        label: 'Resueltos',
                        data: datosOrganismos.map(o => o.resueltos),
                        backgroundColor: coloresOrganismos.organismo2,
                        borderRadius: 4
                    },
                    {
                        label: 'Pendientes',
                        data: datosOrganismos.map(o => o.pendientes),
                        backgroundColor: coloresOrganismos.organismo3,
                        borderRadius: 4
                    }
                ]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                scales: {
                    y: {
                        beginAtZero: true,
                        ticks: {
                            callback: function(value) {
                                return value.toLocaleString('es-AR');
                            }
                        }
                    }
                },
                plugins: {
                    legend: {
                        position: 'bottom',
                        labels: {
                            padding: 15,
                            usePointStyle: true
                        }
                    }
                }
            }
        });

        // ========================================
        // GRÁFICOS 3-6: Tendencias por Organismo
        // ========================================
        // Datos de ejemplo para tendencias mensuales (últimos 6 meses)
        const mesesTendencia = ['Enero', 'Febrero', 'Marzo', 'Abril', 'Mayo', 'Junio'];
        const tendencias = {
            1: [45, 52, 48, 61, 55, 89],   // Juzgado Primera Instancia
            2: [32, 28, 35, 40, 38, 45],   // Cámara Apelaciones
            3: [78, 95, 102, 88, 110, 123], // Juzgado de Paz
            4: [25, 30, 28, 32, 29, 34]    // Tribunal de Trabajo
        };
        const coloresTendencia = [coloresOrganismos.organismo1, coloresOrganismos.organismo2, 
                                  coloresOrganismos.organismo3, coloresOrganismos.organismo4];

        // Crear gráfico de tendencia para cada organismo
        datosOrganismos.forEach((org, index) => {
            const ctxTendencia = document.getElementById(`chartTendencia${org.id}`);
            if (ctxTendencia) {
                new Chart(ctxTendencia.getContext('2d'), {
                    type: 'line',
                    data: {
                        labels: mesesTendencia,
                        datasets: [{
                            data: tendencias[org.id],
                            borderColor: coloresTendencia[index],
                            backgroundColor: coloresTendencia[index] + '20',
                            fill: true,
                            tension: 0.4,
                            pointRadius: 4,
                            pointHoverRadius: 6
                        }]
                    },
                    options: {
                        responsive: true,
                        maintainAspectRatio: false,
                        plugins: {
                            legend: { display: false }
                        },
                        scales: {
                            y: {
                                beginAtZero: true,
                                ticks: { font: { size: 10 } }
                            },
                            x: {
                                ticks: { font: { size: 10 } }
                            }
                        }
                    }
                });
            }
        });

        // ========================================
        // GRÁFICO 7: Evolución Mensual General
        // ========================================
        const ctxEvolucion = document.getElementById('chartEvolucion').getContext('2d');
        new Chart(ctxEvolucion, {
            type: 'line',
            data: {
                labels: mesesTendencia,
                datasets: [
                    {
                        label: 'Juzgado 1ra Instancia',
                        data: tendencias[1],
                        borderColor: coloresOrganismos.organismo1,
                        backgroundColor: 'transparent',
                        tension: 0.3,
                        pointRadius: 5
                    },
                    {
                        label: 'Cámara Apelaciones',
                        data: tendencias[2],
                        borderColor: coloresOrganismos.organismo2,
                        backgroundColor: 'transparent',
                        tension: 0.3,
                        pointRadius: 5
                    },
                    {
                        label: 'Juzgado de Paz',
                        data: tendencias[3],
                        borderColor: coloresOrganismos.organismo3,
                        backgroundColor: 'transparent',
                        tension: 0.3,
                        pointRadius: 5
                    },
                    {
                        label: 'Tribunal de Trabajo',
                        data: tendencias[4],
                        borderColor: coloresOrganismos.organismo4,
                        backgroundColor: 'transparent',
                        tension: 0.3,
                        pointRadius: 5
                    }
                ]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                interaction: {
                    intersect: false,
                    mode: 'index'
                },
                scales: {
                    y: {
                        beginAtZero: true,
                        ticks: {
                            callback: function(value) {
                                return value.toLocaleString('es-AR');
                            }
                        }
                    }
                },
                plugins: {
                    legend: {
                        position: 'bottom',
                        labels: {
                            padding: 20,
                            usePointStyle: true
                        }
                    }
                }
            }
        });
    </script>
</body>
</html>
