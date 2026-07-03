<?php
require_once 'includes/store.php';
require_once 'includes/track_visit.php';

$products = [];
$storeError = '';
$storeSettings = [
    'store_notify_email_1' => '',
    'store_notify_email_2' => '',
    'store_yappy_info' => '',
    'store_ach_info' => '',
    'store_paypal_email' => '',
    'store_paypal_url' => '',
];

try {
    $db = getDB();
    store_ensure_tables($db);
    trackVisit('store');

    $products = $db->query("SELECT * FROM store_products WHERE active = 1 ORDER BY id DESC")->fetchAll(PDO::FETCH_ASSOC);
    $storeSettings = store_settings($db);
} catch (Throwable $e) {
    $storeError = 'La tienda no esta disponible en este momento. Verifica que la migracion add_store.sql este aplicada en el hosting.';
    error_log('Store page error: ' . $e->getMessage());
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Tienda Souvenir - Panda Truck Reloaded</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="assets/css/style.css">
    <style>
        :root { --primary:#e1261d; }
        .text-primary { color: var(--primary); }
        .bg-primary { background: var(--primary); }
        .product-card { transition: transform .2s ease, border-color .2s ease; }
        .product-card:hover { transform: translateY(-3px); border-color: rgba(225,38,29,.7); }
        .modal { display:none; position:fixed; inset:0; z-index:60; background:rgba(0,0,0,.78); align-items:center; justify-content:center; padding:1rem; }
        .modal.show { display:flex; }
        .modal-box { max-height:90vh; overflow:auto; }
        .cart-actions {
            position: sticky;
            bottom: -1.25rem;
            z-index: 5;
            margin-inline: -1.25rem;
            margin-bottom: -1.25rem;
            border-top: 1px solid rgba(255,255,255,.08);
            background: rgba(23,23,23,.98);
            padding: 1rem 1.25rem;
        }
    </style>
</head>
<body class="bg-neutral-950 text-white">
    <header class="sticky top-0 z-40 bg-neutral-900/95 border-b border-neutral-800">
        <div class="max-w-7xl mx-auto px-4 py-4 flex items-center justify-between gap-3">
            <a href="index.php" class="flex items-center gap-2">
                <img src="assets/img/logo.png" class="h-10" onerror="this.src='assets/img/default-avatar.jpg'">
                <span class="font-bold">Panda Truck Reloaded</span>
            </a>
            <nav class="flex items-center gap-3 text-sm">
                <a href="index.php" class="hover:text-primary">Inicio</a>
                <a href="mixes.php" class="hover:text-primary">Mixes</a>
                <button id="cartBtn" class="rounded-lg bg-primary px-4 py-2 font-semibold">
                    <i class="fas fa-cart-shopping mr-1"></i> Carrito <span id="cartCount">0</span>
                </button>
            </nav>
        </div>
    </header>

    <main class="max-w-7xl mx-auto px-4 py-8">
        <div class="mb-8 flex flex-col gap-2 md:flex-row md:items-end md:justify-between">
            <div>
                <h1 class="text-3xl font-black">Tienda Souvenir</h1>
                <p class="text-neutral-400">Productos oficiales de Panda Truck Reloaded. Entrega en 8 dias habiles despues de confirmar el pago.</p>
            </div>
        </div>

        <?php if ($storeError): ?>
        <div class="mb-6 rounded-xl border border-red-900/70 bg-red-950/40 p-5 text-red-100">
            <?php echo htmlspecialchars($storeError); ?>
        </div>
        <?php endif; ?>

        <?php if (!$products && !$storeError): ?>
        <div class="rounded-xl border border-dashed border-neutral-700 bg-neutral-900 p-10 text-center text-neutral-400">
            <i class="fas fa-shirt mb-3 text-4xl text-neutral-600"></i>
            <p>Aun no hay productos disponibles.</p>
        </div>
        <?php endif; ?>

        <section class="grid sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-6">
            <?php foreach ($products as $product): 
                $sizes = array_values(array_filter(array_map('trim', explode(',', (string)$product['sizes']))));
            ?>
            <article class="product-card overflow-hidden rounded-xl border border-neutral-800 bg-neutral-900" data-product='<?php echo htmlspecialchars(json_encode($product), ENT_QUOTES, 'UTF-8'); ?>'>
                <button class="block aspect-square w-full overflow-hidden bg-neutral-800" onclick="openProduct(this.closest('.product-card'))">
                    <img src="<?php echo htmlspecialchars($product['image'] ?: 'assets/img/default-cover.jpg'); ?>" class="h-full w-full object-cover" onerror="this.src='assets/img/default-cover.jpg'">
                </button>
                <div class="p-4">
                    <h2 class="truncate font-bold"><?php echo htmlspecialchars($product['name']); ?></h2>
                    <p class="mt-1 line-clamp-2 text-sm text-neutral-400"><?php echo htmlspecialchars($product['description']); ?></p>
                    <div class="mt-3 flex items-center justify-between">
                        <span class="text-xl font-black text-primary">$<?php echo number_format((float)$product['price'], 2); ?></span>
                        <button onclick="openProduct(this.closest('.product-card'))" class="rounded-lg bg-neutral-800 px-3 py-2 text-sm hover:bg-primary">Ver detalle</button>
                    </div>
                </div>
            </article>
            <?php endforeach; ?>
        </section>
    </main>

    <div id="productModal" class="modal">
        <div class="modal-box w-full max-w-3xl rounded-xl bg-neutral-900 border border-neutral-700">
            <div id="productModalContent"></div>
        </div>
    </div>

    <div id="cartModal" class="modal">
        <div class="modal-box w-full max-w-4xl rounded-xl bg-neutral-900 border border-neutral-700 p-5">
            <div class="mb-4 flex items-center justify-between">
                <h2 class="text-xl font-black">Carrito y pedido</h2>
                <button onclick="closeCart()" class="flex h-10 w-10 items-center justify-center rounded-full bg-neutral-800 text-2xl hover:bg-primary" title="Cerrar">&times;</button>
            </div>
            <div id="cartItems" class="space-y-3"></div>
            <div class="mt-4 rounded-lg bg-neutral-950 p-4 text-right text-xl font-black">Total: <span id="cartTotal" class="text-primary">$0.00</span></div>

            <div class="mt-4 rounded-lg border border-primary/30 bg-primary/10 p-4 text-sm text-red-50">
                <strong>Entrega:</strong> el pedido se entrega en 8 dias habiles despues de confirmar el pago. Coloca una direccion o punto de entrega claro.
            </div>

            <form id="checkoutForm" class="mt-5 grid gap-4 md:grid-cols-2" enctype="multipart/form-data">
                <div><label class="text-sm">Nombre *</label><input name="customer_name" required class="w-full rounded bg-neutral-800 p-2"></div>
                <div><label class="text-sm">WhatsApp *</label><input name="customer_phone" required class="w-full rounded bg-neutral-800 p-2"></div>
                <div><label class="text-sm">Correo</label><input name="customer_email" type="email" class="w-full rounded bg-neutral-800 p-2"></div>
                <div><label class="text-sm">Metodo de pago *</label><select name="payment_method" required class="w-full rounded bg-neutral-800 p-2"><option value="">Seleccionar</option><option value="yappy">Yappy</option><option value="ach">ACH</option><option value="paypal">PayPal / Visa</option></select></div>
                <div class="md:col-span-2 grid gap-3 md:grid-cols-3">
                    <div class="rounded-lg border border-neutral-800 bg-neutral-950 p-3 text-sm"><strong>Yappy</strong><br><?php echo nl2br(htmlspecialchars($storeSettings['store_yappy_info'] ?: 'Configurar en dashboard')); ?></div>
                    <div class="rounded-lg border border-neutral-800 bg-neutral-950 p-3 text-sm"><strong>ACH</strong><br><?php echo nl2br(htmlspecialchars($storeSettings['store_ach_info'] ?: 'Configurar en dashboard')); ?></div>
                    <div class="rounded-lg border border-neutral-800 bg-neutral-950 p-3 text-sm">
                        <strong>PayPal / Tarjeta Visa</strong><br>
                        <?php if (!empty($storeSettings['store_paypal_url'])): ?>
                        <a href="<?php echo htmlspecialchars($storeSettings['store_paypal_url']); ?>" target="_blank" class="mt-2 inline-flex rounded bg-blue-600 px-3 py-2 font-bold text-white">Pagar con PayPal / Visa</a>
                        <?php else: ?>
                        <?php echo htmlspecialchars($storeSettings['store_paypal_email'] ?: 'Configurar en dashboard'); ?>
                        <?php endif; ?>
                    </div>
                </div>
                <div class="md:col-span-2"><label class="text-sm">Direccion o punto de entrega *</label><textarea name="customer_address" rows="2" required placeholder="Ejemplo: provincia, distrito, barriada, calle, casa/local o punto de referencia" class="w-full rounded bg-neutral-800 p-2"></textarea></div>
                <div class="md:col-span-2"><label class="text-sm">Nota</label><textarea name="customer_note" rows="2" class="w-full rounded bg-neutral-800 p-2"></textarea></div>
                <div class="md:col-span-2"><label class="text-sm">Comprobante de pago * (imagen o PDF)</label><input name="payment_receipt" type="file" accept="image/*,.pdf" required class="w-full rounded bg-neutral-800 p-2"></div>
                <div class="cart-actions md:col-span-2 grid gap-3 sm:grid-cols-2">
                    <button type="button" onclick="closeCart()" class="rounded-lg bg-neutral-800 px-5 py-3 font-black hover:bg-neutral-700">Seguir comprando</button>
                    <button class="rounded-lg bg-primary px-5 py-3 font-black">Registrar pedido</button>
                </div>
            </form>
        </div>
    </div>

    <div id="toast" class="fixed bottom-5 left-1/2 z-50 hidden -translate-x-1/2 rounded-full bg-primary px-5 py-3 text-sm font-bold"></div>

    <script>
        const cartKey = 'panda_store_cart';
        let cart = JSON.parse(localStorage.getItem(cartKey) || '[]');
        let currentProduct = null;

        function money(value){ return '$' + (Number(value) || 0).toFixed(2); }
        function saveCart(){ localStorage.setItem(cartKey, JSON.stringify(cart)); updateCartCount(); }
        function updateCartCount(){ document.getElementById('cartCount').textContent = cart.reduce((sum,item)=>sum + item.quantity, 0); }
        function toast(msg){ const t=document.getElementById('toast'); t.textContent=msg; t.classList.remove('hidden'); setTimeout(()=>t.classList.add('hidden'),2500); }
        function productFromCard(card){ return JSON.parse(card.dataset.product); }

        function openProduct(card){
            const p = productFromCard(card);
            currentProduct = p;
            const sizes = (p.sizes || '').split(',').map(s=>s.trim()).filter(Boolean);
            document.getElementById('productModalContent').innerHTML = `
                <div class="grid md:grid-cols-2">
                    <img src="${p.image || 'assets/img/default-cover.jpg'}" class="h-full min-h-80 w-full object-cover" onerror="this.src='assets/img/default-cover.jpg'">
                    <div class="p-5">
                        <div class="flex justify-between gap-3"><h2 class="text-2xl font-black">${escapeHtml(p.name)}</h2><button onclick="closeProduct()" class="text-2xl hover:text-primary">&times;</button></div>
                        <p class="mt-3 text-neutral-300">${escapeHtml(p.description || '')}</p>
                        <p class="mt-4 text-3xl font-black text-primary">${money(p.price)}</p>
                        <div class="mt-4 grid grid-cols-2 gap-3">
                            <div><label class="text-sm">Talla</label><select id="detailSize" class="w-full rounded bg-neutral-800 p-2">${sizes.length ? sizes.map(s=>`<option value="${escapeHtml(s)}">${escapeHtml(s)}</option>`).join('') : '<option value="">Unica</option>'}</select></div>
                            <div><label class="text-sm">Cantidad</label><input id="detailQty" type="number" min="1" value="1" class="w-full rounded bg-neutral-800 p-2"></div>
                        </div>
                        <button onclick="addCurrentToCart()" class="mt-5 w-full rounded-lg bg-primary py-3 font-black">Agregar al carrito</button>
                    </div>
                </div>`;
            document.getElementById('productModal').classList.add('show');
        }
        function closeProduct(){ document.getElementById('productModal').classList.remove('show'); }
        function addCurrentToCart(){ if (currentProduct) addToCart(currentProduct); }
        function addToCart(p){
            const size = document.getElementById('detailSize')?.value || '';
            const quantity = Math.max(1, parseInt(document.getElementById('detailQty')?.value || '1', 10));
            const key = `${p.id}|${size}`;
            const existing = cart.find(item => item.key === key);
            if (existing) existing.quantity += quantity;
            else cart.push({ key, id: Number(p.id), name: p.name, price: Number(p.price), image: p.image, size, quantity });
            saveCart(); closeProduct(); toast('Producto agregado');
        }
        function openCart(){ renderCart(); document.getElementById('cartModal').classList.add('show'); }
        function closeCart(){ document.getElementById('cartModal').classList.remove('show'); }
        function renderCart(){
            const items = document.getElementById('cartItems');
            if (!cart.length) { items.innerHTML = '<div class="flex flex-col gap-3 rounded-lg bg-neutral-950 p-4 text-neutral-400 sm:flex-row sm:items-center sm:justify-between"><p>El carrito esta vacio.</p><button onclick="closeCart()" class="rounded-lg bg-primary px-4 py-2 font-bold text-white">Seguir comprando</button></div>'; }
            else items.innerHTML = cart.map((item,idx)=>`
                <div class="flex items-center gap-3 rounded-lg bg-neutral-950 p-3">
                    <img src="${item.image || 'assets/img/default-cover.jpg'}" class="h-16 w-16 rounded object-cover">
                    <div class="min-w-0 flex-1"><p class="font-bold truncate">${escapeHtml(item.name)}</p><p class="text-sm text-neutral-400">Talla: ${escapeHtml(item.size || 'Unica')} / ${money(item.price)}</p></div>
                    <input type="number" min="1" value="${item.quantity}" onchange="cart[${idx}].quantity=Math.max(1,parseInt(this.value||1,10)); saveCart(); renderCart();" class="w-16 rounded bg-neutral-800 p-2">
                    <button onclick="cart.splice(${idx},1); saveCart(); renderCart();" class="text-red-400"><i class="fas fa-trash"></i></button>
                </div>`).join('');
            document.getElementById('cartTotal').textContent = money(cart.reduce((sum,item)=>sum + item.price * item.quantity, 0));
        }
        document.getElementById('cartBtn').addEventListener('click', openCart);
        document.getElementById('checkoutForm').addEventListener('submit', async (e)=>{
            e.preventDefault();
            if (!cart.length) return toast('El carrito esta vacio');
            const form = new FormData(e.target);
            form.append('items', JSON.stringify(cart));
            const res = await fetch('api/save_store_order.php', { method:'POST', body:form });
            const data = await res.json();
            if (data.success) {
                cart = []; saveCart(); e.target.reset(); closeCart();
                alert('Pedido registrado: ' + data.order_code + '\nSe notifico a los socios para verificar el pago.');
            } else {
                toast(data.error || 'Error al registrar pedido');
            }
        });
        function escapeHtml(text){ const div=document.createElement('div'); div.textContent=text || ''; return div.innerHTML; }
        updateCartCount();
    </script>
</body>
</html>
