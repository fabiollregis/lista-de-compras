<?php
session_start();
require_once 'config/database.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

// Obter ano e mês selecionados (padrão: ano e mês atual)
$selected_year = isset($_GET['year']) ? (int)$_GET['year'] : date('Y');
$selected_month = isset($_GET['month']) ? (int)$_GET['month'] : date('n');

// Se o ano selecionado for o atual, usar o mês atual como padrão
if ($selected_year == date('Y') && !isset($_GET['month'])) {
    $selected_month = date('n');
} else if (!isset($_GET['month'])) {
    // Se for um ano diferente e não tiver mês na URL, mostrar todos os meses
    $selected_month = null;
}

// Buscar anos disponíveis no histórico
$stmt = $pdo->prepare("SELECT DISTINCT YEAR(purchase_date) as year FROM shopping_history WHERE user_id = ? ORDER BY year DESC");
$stmt->execute([$_SESSION['user_id']]);
$available_years = $stmt->fetchAll(PDO::FETCH_COLUMN);

// Se não houver anos disponíveis, usar ano atual
if (empty($available_years)) {
    $available_years = [date('Y')];
}

// Buscar dados mensais do ano selecionado
$stmt = $pdo->prepare("
    SELECT 
        MONTH(purchase_date) as month,
        COUNT(*) as purchase_count,
        SUM(total_amount) as total_amount
    FROM shopping_history 
    WHERE user_id = ? 
    AND YEAR(purchase_date) = ?
    " . ($selected_month ? "AND MONTH(purchase_date) = ?" : "") . "
    GROUP BY MONTH(purchase_date)
    ORDER BY month
");

$params = [$_SESSION['user_id'], $selected_year];
if ($selected_month) {
    $params[] = $selected_month;
}
$stmt->execute($params);
$monthly_data = $stmt->fetchAll();

// Buscar dados diários do mês selecionado
if ($selected_month) {
    $stmt = $pdo->prepare("
        SELECT 
            DAY(purchase_date) as day,
            COUNT(*) as purchase_count,
            SUM(total_amount) as total_amount
        FROM shopping_history 
        WHERE user_id = ? 
        AND YEAR(purchase_date) = ? 
        AND MONTH(purchase_date) = ?
        GROUP BY DAY(purchase_date)
        ORDER BY day
    ");
    $stmt->execute([$_SESSION['user_id'], $selected_year, $selected_month]);
    $daily_data = $stmt->fetchAll();
}

// Buscar itens mais comprados
$stmt = $pdo->prepare("
    SELECT 
        item_name,
        SUM(quantity) as total_quantity,
        AVG(price) as avg_price,
        COUNT(*) as purchase_count
    FROM shopping_history_items hi
    JOIN shopping_history h ON hi.history_id = h.id
    WHERE h.user_id = ? 
    AND YEAR(h.purchase_date) = ?
    " . ($selected_month ? "AND MONTH(h.purchase_date) = ?" : "") . "
    GROUP BY item_name
    ORDER BY total_quantity DESC
    LIMIT 10
");

$params = [$_SESSION['user_id'], $selected_year];
if ($selected_month) {
    $params[] = $selected_month;
}
$stmt->execute($params);
$top_items = $stmt->fetchAll();

// Preparar dados para os gráficos
$months = [];
$monthly_amounts = [];
$monthly_counts = [];
for ($i = 1; $i <= 12; $i++) {
    $months[] = date('F', mktime(0, 0, 0, $i, 1));
    $monthly_amounts[$i] = 0;
    $monthly_counts[$i] = 0;
}

foreach ($monthly_data as $data) {
    $monthly_amounts[$data['month']] = (float)$data['total_amount'];
    $monthly_counts[$data['month']] = (int)$data['purchase_count'];
}

// Preparar dados para o gráfico diário
$days = [];
$daily_amounts = [];
if ($selected_month) {
    $days_in_month = cal_days_in_month(CAL_GREGORIAN, $selected_month, $selected_year);
    for ($i = 1; $i <= $days_in_month; $i++) {
        $days[] = $i;
        $daily_amounts[$i] = 0;
    }

    foreach ($daily_data as $data) {
        $daily_amounts[$data['day']] = (float)$data['total_amount'];
    }
}

// Função para retornar o nome do mês em português
function getMesPortugues($numero_mes) {
    $meses = array(
        1 => 'Janeiro',
        2 => 'Fevereiro',
        3 => 'Março',
        4 => 'Abril',
        5 => 'Maio',
        6 => 'Junho',
        7 => 'Julho',
        8 => 'Agosto',
        9 => 'Setembro',
        10 => 'Outubro',
        11 => 'Novembro',
        12 => 'Dezembro'
    );
    return $meses[$numero_mes];
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Relatórios - Lista de Compras</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <style>
        body {
            background-color: #f8f9fa;
            padding: 20px 0;
        }
        .report-container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 1.5rem;
        }
        .report-card {
            background: #fff;
            border-radius: 8px;
            padding: 20px;
            margin-bottom: 20px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }
        .purchase-item {
            border-bottom: 1px solid #dee2e6;
            padding: 10px 0;
        }
        .purchase-item:last-child {
            border-bottom: none;
        }
        .chart-container {
            position: relative;
            height: 250px;
            margin-bottom: 40px;
        }
        .chart-section {
            margin-bottom: 50px;
        }
        .chart-row {
            margin-bottom: 30px;
        }
        
        /* Estilos responsivos */
        @media (max-width: 768px) {
            .chart-container {
                height: 300px;
                margin-bottom: 30px;
            }
            .filters-container {
                flex-direction: column;
                gap: 15px;
            }
            .filter-group {
                width: 100%;
            }
            .filter-group select {
                width: 100% !important;
            }
            .table-responsive {
                margin-top: 15px;
            }
            .btn-group-mobile {
                display: flex;
                flex-direction: column;
                gap: 10px;
            }
            .btn-group-mobile .btn {
                width: 100%;
            }
            .stats-card {
                margin-bottom: 15px;
            }
            .chart-title {
                font-size: 1.1rem;
                margin-bottom: 15px;
            }
        }
    </style>
</head>
<body>
    <nav class="navbar navbar-expand-lg navbar-dark bg-primary mb-4">
        <div class="container">
            <a class="navbar-brand" href="index.php">Lista de Compras</a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav me-auto">
                    <li class="nav-item">
                        <a class="nav-link" href="index.php">Minha Lista</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="default_products.php">Lista Padrão</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link active" href="reports.php">Relatórios</a>
                    </li>
                </ul>
                <?php if (isset($_SESSION['username'])): ?>
                    <span class="navbar-text text-white">
                        Olá, <?php echo htmlspecialchars($_SESSION['username']); ?>!
                        <a href="logout.php" class="btn btn-outline-light btn-sm ms-2">Sair</a>
                    </span>
                <?php endif; ?>
            </div>
        </div>
    </nav>
    <div class="report-container">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h2>Relatórios</h2>
            <div>
                <a href="index.php" class="btn btn-outline-primary me-2">Voltar para Lista</a>
                <a href="logout.php" class="btn btn-danger">Sair</a>
            </div>
        </div>

        <!-- Resumo Anual -->
        <div class="report-card">
            <div class="d-flex justify-content-between align-items-start flex-wrap filters-container mb-3">
                <h4 class="mb-3 mb-md-0">Resumo <?php echo $selected_month ? getMesPortugues($selected_month) . ' / ' : ''; ?><?php echo $selected_year; ?></h4>
                <form class="d-flex gap-2 align-items-start flex-wrap filters-container">
                    <div class="d-flex align-items-center filter-group">
                        <label class="form-label me-2 mb-0">Ano:</label>
                        <select name="year" class="form-select" style="width: auto;" onchange="this.form.submit()">
                            <?php foreach ($available_years as $year): ?>
                                <option value="<?php echo $year; ?>" <?php echo $year == $selected_year ? 'selected' : ''; ?>>
                                    <?php echo $year; ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="d-flex align-items-center filter-group">
                        <label class="form-label me-2 mb-0">Mês:</label>
                        <select name="month" class="form-select" style="width: auto;" onchange="this.form.submit()">
                            <option value="">Todos</option>
                            <?php for ($i = 1; $i <= 12; $i++): ?>
                                <option value="<?php echo $i; ?>" <?php echo $i == $selected_month ? 'selected' : ''; ?>>
                                    <?php echo getMesPortugues($i); ?>
                                </option>
                            <?php endfor; ?>
                        </select>
                    </div>
                </form>
            </div>
            
            <div class="row g-3">
                <div class="col-12 col-sm-6 col-md-3">
                    <div class="stats-card p-3 bg-light rounded">
                        <h6 class="text-muted mb-2">Total Gasto</h6>
                        <h4 class="mb-0">R$ <?php echo number_format(array_sum($monthly_amounts), 2, ',', '.'); ?></h4>
                    </div>
                </div>
                <div class="col-12 col-sm-6 col-md-3">
                    <div class="stats-card p-3 bg-light rounded">
                        <h6 class="text-muted mb-2">Compras Realizadas</h6>
                        <h4 class="mb-0"><?php echo array_sum($monthly_counts); ?></h4>
                    </div>
                </div>
                <div class="col-12 col-sm-6 col-md-3">
                    <div class="stats-card p-3 bg-light rounded">
                        <h6 class="text-muted mb-2">Média por Compra</h6>
                        <h4 class="mb-0">R$ <?php 
                                    $active_months = count(array_filter($monthly_amounts));
                                    echo number_format(
                                        $active_months ? array_sum($monthly_amounts) / $active_months : 0, 
                                        2, ',', '.'
                                    ); 
                                ?></h4>
                    </div>
                </div>
                <div class="col-12 col-sm-6 col-md-3">
                    <div class="stats-card p-3 bg-light rounded">
                        <h6 class="text-muted mb-2">Total de Itens</h6>
                        <h4 class="mb-0"><?php echo array_sum(array_column($top_items, 'total_quantity')); ?></h4>
                    </div>
                </div>
            </div>
        </div>

        <!-- Gráficos -->
        <div class="chart-section">
            <div class="row chart-row">
                <div class="col-12 col-lg-6">
                    <div class="chart-container">
                        <h4 class="chart-title">Gastos Mensais</h4>
                        <canvas id="monthlyChart"></canvas>
                    </div>
                </div>
                <div class="col-12 col-lg-6">
                    <div class="chart-container">
                        <h4 class="chart-title">Top 10 Itens Mais Comprados</h4>
                        <canvas id="topItemsChart"></canvas>
                    </div>
                </div>
            </div>

            <?php if ($selected_month): ?>
                <div class="row chart-row">
                    <div class="col-12">
                        <div class="chart-container">
                            <h4 class="chart-title">Gastos Diários em <?php echo getMesPortugues($selected_month); ?></h4>
                            <canvas id="dailyChart"></canvas>
                        </div>
                    </div>
                </div>
            <?php else: ?>
                <div class="row">
                    <div class="col-12 mb-4">
                        <h4 class="chart-title">Gastos Diários por Mês</h4>
                    </div>
                </div>
                <?php
                // Preparar dados para todos os meses
                $monthly_charts_data = [];
                for ($month = 1; $month <= 12; $month++) {
                    $stmt = $pdo->prepare("
                        SELECT 
                            DAY(purchase_date) as day,
                            SUM(total_amount) as total_amount
                        FROM shopping_history 
                        WHERE user_id = ? 
                        AND YEAR(purchase_date) = ? 
                        AND MONTH(purchase_date) = ?
                        GROUP BY DAY(purchase_date)
                        ORDER BY day
                    ");
                    $stmt->execute([$_SESSION['user_id'], $selected_year, $month]);
                    $month_data = $stmt->fetchAll();
                    
                    // Preparar dados do mês
                    $days_in_month = cal_days_in_month(CAL_GREGORIAN, $month, $selected_year);
                    $daily_amounts = array_fill(1, $days_in_month, 0);
                    
                    foreach ($month_data as $data) {
                        $daily_amounts[$data['day']] = (float)$data['total_amount'];
                    }
                    
                    $monthly_charts_data[$month] = [
                        'days' => range(1, $days_in_month),
                        'amounts' => array_values($daily_amounts)
                    ];
                }
                ?>
                
                <div class="row">
                    <?php for ($month = 1; $month <= 12; $month++): ?>
                        <div class="col-12 col-md-6">
                            <div class="chart-container">
                                <h5 class="chart-title"><?php echo getMesPortugues($month); ?></h5>
                                <canvas id="monthChart<?php echo $month; ?>"></canvas>
                            </div>
                        </div>
                    <?php endfor; ?>
                </div>
            <?php endif; ?>
        </div>

        <!-- Top Itens -->
        <div class="report-card">
            <h4 class="chart-title mb-4">Itens Mais Comprados em <?php echo $selected_month ? getMesPortugues($selected_month) . ' / ' : ''; ?><?php echo $selected_year; ?></h4>
            <div class="table-responsive">
                <table class="table table-striped">
                    <thead>
                        <tr>
                            <th>Item</th>
                            <th>Quantidade Total</th>
                            <th>Preço Médio</th>
                            <th>Frequência</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($top_items as $item): ?>
                            <tr>
                                <td>
                                    <?php echo htmlspecialchars($item['item_name']); ?>
                                    <button type="button" class="btn btn-outline-primary btn-sm ms-2" onclick="addToMainList('<?php echo htmlspecialchars($item['item_name']); ?>')">
                                        <i class="fas fa-plus"></i> Add à Lista
                                    </button>
                                </td>
                                <td><?php echo $item['total_quantity']; ?></td>
                                <td>R$ <?php echo number_format($item['avg_price'], 2, ',', '.'); ?></td>
                                <td><?php echo $item['purchase_count']; ?> vezes</td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Lista de Compras -->
        <div class="report-card">
            <h4 class="chart-title mb-4">Histórico de Compras</h4>
            <div class="table-responsive">
                <table class="table table-striped">
                    <thead>
                        <tr>
                            <th>Data</th>
                            <th>Quantidade de Itens</th>
                            <th>Total</th>
                            <th>Ações</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php
                        // Buscar todas as compras
                        $stmt = $pdo->prepare("
                            SELECT h.*, 
                                   COUNT(hi.id) as item_count
                            FROM shopping_history h
                            LEFT JOIN shopping_history_items hi ON h.id = hi.history_id
                            WHERE h.user_id = ?
                            AND YEAR(h.purchase_date) = ?
                            " . ($selected_month ? "AND MONTH(h.purchase_date) = ?" : "") . "
                            GROUP BY h.id
                            ORDER BY h.purchase_date DESC
                        ");
                        
                        $params = [$_SESSION['user_id'], $selected_year];
                        if ($selected_month) {
                            $params[] = $selected_month;
                        }
                        $stmt->execute($params);
                        $purchases = $stmt->fetchAll();
                        ?>

                        <?php foreach ($purchases as $purchase): ?>
                            <tr>
                                <td><?php echo date('d/m/Y H:i', strtotime($purchase['purchase_date'])); ?></td>
                                <td>
                                    <span class="badge bg-primary"><?php echo $purchase['item_count']; ?> itens</span>
                                </td>
                                <td>R$ <?php echo number_format($purchase['total_amount'], 2, ',', '.'); ?></td>
                                <td>
                                    <div class="d-flex gap-2 btn-group-mobile">
                                        <button class="btn btn-sm btn-outline-primary" 
                                                onclick="viewPurchaseDetails(<?php echo $purchase['id']; ?>)"
                                                data-bs-toggle="modal" 
                                                data-bs-target="#purchaseModal">
                                            <i class="fas fa-eye"></i> Ver Detalhes
                                        </button>
                                        <form action="delete_purchase.php" method="POST" 
                                              onsubmit="return confirm('Tem certeza que deseja excluir esta compra? Esta ação não pode ser desfeita.');">
                                            <input type="hidden" name="purchase_id" value="<?php echo $purchase['id']; ?>">
                                            <button type="submit" class="btn btn-sm btn-outline-danger w-100">
                                                <i class="fas fa-trash"></i> Excluir
                                            </button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>

                        <?php if (empty($purchases)): ?>
                            <tr>
                                <td colspan="4" class="text-center">Nenhuma compra registrada.</td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Modal de Detalhes da Compra -->
        <div class="modal fade" id="purchaseModal" tabindex="-1">
            <div class="modal-dialog">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title">Detalhes da Compra</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body">
                        <div id="purchaseDetails">
                            Carregando...
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Fechar alertas automaticamente após 5 segundos
        document.addEventListener('DOMContentLoaded', function() {
            setTimeout(function() {
                var alerts = document.getElementsByClassName('alert');
                for(var i = 0; i < alerts.length; i++) {
                    alerts[i].style.display = 'none';
                }
            }, 5000);
        });

        // Configuração do gráfico mensal
        const monthlyCtx = document.getElementById('monthlyChart').getContext('2d');
        new Chart(monthlyCtx, {
            type: 'bar',
            data: {
                labels: <?php echo json_encode($months); ?>,
                datasets: [{
                    label: 'Gastos Mensais (R$)',
                    data: <?php echo json_encode(array_values($monthly_amounts)); ?>,
                    backgroundColor: 'rgba(54, 162, 235, 0.2)',
                    borderColor: 'rgba(54, 162, 235, 1)',
                    borderWidth: 1
                }]
            },
            options: {
                responsive: true,
                plugins: {
                    tooltip: {
                        callbacks: {
                            label: function(context) {
                                return 'R$ ' + context.raw.toFixed(2);
                            }
                        }
                    }
                },
                scales: {
                    y: {
                        beginAtZero: true,
                        ticks: {
                            callback: function(value) {
                                return 'R$ ' + value.toFixed(2);
                            }
                        }
                    }
                }
            }
        });

        // Configuração do gráfico diário
        <?php if ($selected_month): ?>
            const dailyCtx = document.getElementById('dailyChart').getContext('2d');
            new Chart(dailyCtx, {
                type: 'line',
                data: {
                    labels: <?php echo json_encode($days); ?>,
                    datasets: [{
                        label: 'Gastos Diários (R$)',
                        data: <?php echo json_encode(array_values($daily_amounts)); ?>,
                        backgroundColor: 'rgba(75, 192, 192, 0.2)',
                        borderColor: 'rgba(75, 192, 192, 1)',
                        borderWidth: 2,
                        tension: 0.1,
                        fill: true
                    }]
                },
                options: {
                    responsive: true,
                    plugins: {
                        tooltip: {
                            callbacks: {
                                label: function(context) {
                                    return 'R$ ' + context.raw.toFixed(2);
                                }
                            }
                        }
                    },
                    scales: {
                        y: {
                            beginAtZero: true,
                            ticks: {
                                callback: function(value) {
                                    return 'R$ ' + value.toFixed(2);
                                }
                            }
                        }
                    }
                }
            });
        <?php endif; ?>

        // Configuração do gráfico de top itens
        const topItemsCtx = document.getElementById('topItemsChart').getContext('2d');
        new Chart(topItemsCtx, {
            type: 'bar',
            data: {
                labels: <?php echo json_encode(array_column($top_items, 'item_name')); ?>,
                datasets: [{
                    label: 'Quantidade Comprada',
                    data: <?php echo json_encode(array_column($top_items, 'total_quantity')); ?>,
                    backgroundColor: 'rgba(153, 102, 255, 0.2)',
                    borderColor: 'rgba(153, 102, 255, 1)',
                    borderWidth: 1
                }]
            },
            options: {
                responsive: true,
                indexAxis: 'y',
                plugins: {
                    tooltip: {
                        callbacks: {
                            afterLabel: function(context) {
                                const index = context.dataIndex;
                                const item = <?php echo json_encode($top_items); ?>[index];
                                return [
                                    'Preço Médio: R$ ' + parseFloat(item.avg_price).toFixed(2),
                                    'Comprado ' + item.purchase_count + ' vezes'
                                ];
                            }
                        }
                    }
                }
            }
        });

        <?php if (!$selected_month): ?>
            // Criar gráficos para todos os meses
            <?php for ($month = 1; $month <= 12; $month++): ?>
            new Chart(document.getElementById('monthChart<?php echo $month; ?>').getContext('2d'), {
                type: 'line',
                data: {
                    labels: <?php echo json_encode($monthly_charts_data[$month]['days']); ?>,
                    datasets: [{
                        label: 'Gastos Diários (R$)',
                        data: <?php echo json_encode($monthly_charts_data[$month]['amounts']); ?>,
                        backgroundColor: 'rgba(75, 192, 192, 0.2)',
                        borderColor: 'rgba(75, 192, 192, 1)',
                        borderWidth: 2,
                        tension: 0.1,
                        fill: true
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        tooltip: {
                            callbacks: {
                                label: function(context) {
                                    return 'R$ ' + context.raw.toFixed(2);
                                }
                            }
                        }
                    },
                    scales: {
                        y: {
                            beginAtZero: true,
                            ticks: {
                                callback: function(value) {
                                    return 'R$ ' + value.toFixed(2);
                                }
                            }
                        }
                    }
                }
            });
            <?php endfor; ?>
        <?php endif; ?>

        // Função para adicionar item à lista principal
        function addToMainList(itemName) {
            fetch('add_to_main_list.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({
                    item_name: itemName
                })
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    alert('Item adicionado à lista principal com sucesso!');
                } else {
                    alert('Erro ao adicionar item: ' + data.message);
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert('Erro ao adicionar item à lista principal');
            });
        }

        // Função para carregar detalhes da compra
        function viewPurchaseDetails(purchaseId) {
            fetch(`get_purchase_details.php?id=${purchaseId}`)
                .then(response => response.text())
                .then(html => {
                    document.getElementById('purchaseDetails').innerHTML = html;
                })
                .catch(error => {
                    document.getElementById('purchaseDetails').innerHTML = 
                        'Erro ao carregar detalhes: ' + error.message;
                });
        }
    </script>
</body>
</html>
