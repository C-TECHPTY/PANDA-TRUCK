<?php
// mixes.php - Todos los mixes
require_once 'includes/config.php';

$db = getDB();
$mixes = $db->query("SELECT * FROM mixes WHERE active = 1 ORDER BY date DESC")->fetchAll();
$genres = $db->query("SELECT DISTINCT genre FROM mixes WHERE active = 1 ORDER BY genre")->fetchAll();
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Todos los Mixes - Panda Truck Reloaded</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>.mix-card{transition:transform .3s;}.mix-card:hover{transform:translateY(-4px);}</style>
</head>
<body class="bg-neutral-950 text-white">
    <header class="sticky top-0 bg-neutral-900/90 border-b border-neutral-800">
        <div class="max-w-7xl mx-auto px-4 py-4 flex justify-between">
            <a href="index.php" class="flex items-center gap-2">
                <img src="assets/img/logo.png" class="h-10" onerror="this.src='data:image/svg+xml,%3Csvg xmlns=%22http://www.w3.org/2000/svg%22 viewBox=%220 0 100 100%22%3E%3Ccircle cx=%2250%22 cy=%2250%22 r=%2250%22 fill=%22%23e1261d%22/%3E%3Ctext x=%2250%22 y=%2265%22 font-size=%2240%22 text-anchor=%22middle%22 fill=%22white%22%3EP%3C/text%3E%3C/svg%3E'">
                <span class="font-bold">Panda Truck Reloaded</span>
            </a>
            <nav class="hidden md:flex gap-6">
                <a href="index.php" class="hover:text-primary">Inicio</a>
                <a href="mixes.php" class="text-primary">Todos los Mixes</a>
                <a href="GuíaDJs.php" class="px-4 py-2 bg-primary rounded-lg">Sube tu mix</a>
            </nav>
        </div>
    </header>
    <main class="max-w-7xl mx-auto px-4 py-8">
        <h1 class="text-3xl font-bold mb-2">Todos los Mixes</h1>
        <p class="text-neutral-400 mb-6"><?php echo count($mixes); ?> mixes disponibles</p>
        
        <div class="bg-neutral-900 rounded-xl p-4 mb-6">
            <div class="grid md:grid-cols-2 gap-4">
                <input type="text" id="search" placeholder="Buscar..." class="px-4 py-2 bg-neutral-800 rounded-lg">
                <select id="genre" class="px-4 py-2 bg-neutral-800 rounded-lg">
                    <option value="">Todos los géneros</option>
                    <?php foreach($genres as $g):?>
                    <option value="<?php echo htmlspecialchars($g['genre']); ?>"><?php echo htmlspecialchars($g['genre']); ?></option>
                    <?php endforeach;?>
                </select>
            </div>
        </div>
        
        <div id="mixes-grid" class="grid sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-6">
            <?php foreach($mixes as $mix):?>
            <div class="mix-card bg-neutral-900 rounded-xl overflow-hidden border border-neutral-800" data-title="<?php echo strtolower($mix['title']); ?>" data-dj="<?php echo strtolower($mix['dj']); ?>" data-genre="<?php echo strtolower($mix['genre']); ?>">
                <div class="aspect-square relative">
                    <img src="<?php echo $mix['cover']??'assets/img/default-cover.jpg'; ?>" class="w-full h-full object-cover">
                    <div class="absolute inset-0 bg-black/50 opacity-0 hover:opacity-100 transition flex items-center justify-center gap-3">
                        <a href="player/index.php?id=<?php echo $mix['id']; ?>" class="w-12 h-12 rounded-full bg-primary flex items-center justify-center">▶</a>
                        <a href="<?php echo $mix['url']; ?>" download class="w-12 h-12 rounded-full bg-neutral-800 flex items-center justify-center">⬇️</a>
                    </div>
                    <div class="absolute top-2 right-2 px-2 py-1 rounded-full bg-black/70 text-xs"><?php echo $mix['duration']??'00:00'; ?></div>
                </div>
                <div class="p-4">
                    <h3 class="font-semibold truncate"><?php echo htmlspecialchars($mix['title']); ?></h3>
                    <p class="text-sm text-neutral-400">Por <?php echo htmlspecialchars($mix['dj']); ?></p>
                    <div class="flex justify-between mt-2 text-xs text-neutral-500">
                        <span>▶️ <?php echo $mix['plays']??0; ?></span>
                        <span>⬇️ <?php echo $mix['downloads']??0; ?></span>
                        <span><?php echo $mix['sizeMB']??0; ?> MB</span>
                    </div>
                </div>
            </div>
            <?php endforeach;?>
        </div>
        <div id="noResults" class="hidden text-center py-12"><div class="text-4xl mb-4">🎧</div><h3 class="text-xl">No se encontraron mixes</h3></div>
    </main>
    <script>
        const search=document.getElementById("search"),genre=document.getElementById("genre"),grid=document.getElementById("mixes-grid"),noResults=document.getElementById("noResults");
        function filter(){const s=search.value.toLowerCase(),g=genre.value.toLowerCase();let visible=0;document.querySelectorAll(".mix-card").forEach(card=>{const t=card.dataset.title,d=card.dataset.dj,ge=card.dataset.genre;if((!s||t.includes(s)||d.includes(s))&&(!g||ge===g)){card.classList.remove("hidden");visible++;}else{card.classList.add("hidden");}});noResults.classList.toggle("hidden",visible>0);}
        search.addEventListener("input",filter);genre.addEventListener("change",filter);
    </script>
</body>
</html>