<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>WebRadio</title>
    <link rel="stylesheet" href="style.css">
    <style>
        /* Стили для раскрывающихся категорий */
        .category-header {
            cursor: pointer;
            padding: 10px;
            background-color: #3d3d3d;
            margin-top: 10px;
            border-radius: 5px;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        .category-header:hover {
            background-color: #4d4d4d;
        }
        .category-list {
            padding-left: 20px;
            display: none;
        }
        .toggle-icon {
            font-weight: bold;
            margin-right: 10px;
        }
        .delete-btn {
            background: none;
            border: none;
            cursor: pointer;
            color: #aaa;
            font-size: 16px;
            margin-right: 10px;
        }
        .delete-btn:hover {
            color: #fff;
        }
        /* Стили для логотипов */
        .category-list li img {
            width: 100px;
            height: auto;
        }
        /* Панель плеера внизу экрана */
        .player-bar {
            position: fixed;
            bottom: 0;
            left: 0;
            width: 100%;
            background-color: #2d2d2d;
            padding: 10px;
            text-align: center;
            font-size: 16px;
            box-shadow: 0 -2px 5px rgba(0, 0, 0, 0.5);
        }
        .player-bar button {
            background: none;
            border: none;
            cursor: pointer;
            color: #007bff;
            font-size: 18px;
            margin-left: 10px;
        }
        .player-bar button:hover {
            color: #fff;
        }
        /* Элементы списка в строку */
        li {
            display: flex;
            align-items: center;
            gap: 10px;
            margin-bottom: 8px;
        }
        /* Адаптивные стили для мобильных устройств */
        @media (max-width: 600px) {
            .container {
                max-width: 90%;
                padding: 10px;
            }
            h1 {
                font-size: 20px;
            }
            .category-header, .player-bar {
                font-size: 14px;
                padding: 8px;
            }
            .delete-btn {
                font-size: 18px;
                margin-right: 5px;
            }
            li {
                flex-direction: row;
                flex-wrap: nowrap;
                align-items: center;
            }
            .toggle-icon {
                font-size: 18px;
            }
        }
    </style>
    <script>
        document.addEventListener("DOMContentLoaded", function() {
            var headers = document.getElementsByClassName("category-header");
            Array.prototype.forEach.call(headers, function(header) {
                header.addEventListener("click", function(e) {
                    if (e.target.classList.contains("delete-btn")) return;
                    var list = this.nextElementSibling;
                    if (list.style.display === "none" || list.style.display === "") {
                        list.style.display = "block";
                        this.querySelector(".toggle-icon").textContent = "▼";
                    } else {
                        list.style.display = "none";
                        this.querySelector(".toggle-icon").textContent = "►";
                    }
                });
            });
        });
    </script>
</head>
<body>
    <div class="container">
        <h1>Список радиостанций</h1>
        <p><a href="add_station.html">Добавить новую станцию</a></p>
        <?php
        $jsonFile = 'radio.json';
        $data = file_exists($jsonFile) ? json_decode(file_get_contents($jsonFile), true) : array('stations' => array());
        $stations = $data['stations'];
        
        // Группировка по категориям
        $grouped = array();
        foreach ($stations as $station) {
            $category = !empty($station['category']) ? $station['category'] : 'Без категории';
            if (!isset($grouped[$category])) {
                $grouped[$category] = array();
            }
            $grouped[$category][] = $station;
        }
        foreach ($grouped as $category => $stationsInCategory):
        ?>
            <div class="category-header">
                <span class="toggle-icon">►</span>
                <span><?php echo htmlspecialchars($category); ?></span>
                <button class="delete-btn" title="Удалить категорию" onclick="event.stopPropagation(); if(confirm('Удалить категорию <?php echo htmlspecialchars($category); ?> и все связанные станции?')) { window.location.href='delete_category.php?category=<?php echo urlencode($category); ?>'; }">✖</button>
            </div>
            <ul class="category-list">
                <?php foreach ($stationsInCategory as $station): ?>
                    <li>
                        <button class="delete-btn" title="Удалить станцию" onclick="if(confirm('Удалить радиостанцию <?php echo htmlspecialchars($station['name']); ?>?')) { window.location.href='delete_station.php?id=<?php echo urlencode($station['id']); ?>'; }">✖</button>
                        <?php if (!empty($station['logo'])): ?>
                            <img src="logos/<?php echo htmlspecialchars($station['logo']); ?>" alt="<?php echo htmlspecialchars($station['name']); ?>">
                        <?php endif; ?>
                        <strong><?php echo htmlspecialchars($station['name']); ?></strong>
                        <a href="play.php?id=<?php echo urlencode($station['id']); ?>" title="Воспроизвести">▶</a>
                    </li>
                <?php endforeach; ?>
            </ul>
        <?php endforeach; ?>
    </div>
    <?php
    $currentFile = 'current_station.json';
    $currentStation = file_exists($currentFile) ? json_decode(file_get_contents($currentFile), true) : null;
    ?>
    <div class="player-bar">
        <?php if ($currentStation): ?>
            Сейчас играет: <strong><?php echo htmlspecialchars($currentStation['name']); ?></strong>
            <button title="Остановить" onclick="window.location.href='stop.php';">⏹</button>
        <?php else: ?>
            Плеер не активен.
        <?php endif; ?>
    </div>
</body>
</html>

