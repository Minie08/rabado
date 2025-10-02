<?php
/**
 * @var db $db
 */
require "settings/init.php";

$nu = date('Y-m-d');
$kategoriId = isset($_GET['kategori']) ? $_GET['kategori'] : null;
$type = isset($_GET['type']) ? $_GET['type'] : null;

// ===== Håndter AJAX-opdatering af rabaAnvendt =====
if (!empty($_POST['id'])) {
    $id = intval($_POST['id']);
    $db->sql("UPDATE rabatkoder SET rabaAnvendt = NOW() WHERE id = :id", [":id" => $id]);

    echo json_encode([
        "success" => true,
        "time" => date("H:i d.m.Y")
    ]);
    exit;
}

// ===== Håndter AJAX-opslag efter kategorier/typer =====
if (isset($_GET['ajax'])) {
    $result = [];

    if ($kategoriId) {
        if ($type === "rabatkoder") {
            $rabatkoder = $db->sql("SELECT r.*, v.virkNavn, v.virkLogo, v.virkLink, k.kateNavn
                FROM rabatkoder r
                JOIN virksomheder v ON r.virkId = v.id
                JOIN kategorier k ON r.kateId = k.id
                WHERE r.kateId = :kateId AND r.rabaStart <= :nu AND r.rabaUdloeb >= :nu",
                [":kateId" => $kategoriId, ":nu" => $nu]
            );
            foreach ($rabatkoder as $r) $result[] = array_merge((array)$r, ["type"=>"rabat"]);
        } elseif ($type === "tilbud") {
            $tilbud = $db->sql("SELECT t.*, v.virkNavn, v.virkLogo, v.virkLink, k.kateNavn
                FROM tilbud t
                JOIN virksomheder v ON t.virkId = v.id
                JOIN kategorier k ON t.kateId = k.id
                WHERE t.kateId = :kateId AND t.tilbStart <= :nu AND t.tilbUdloeb >= :nu",
                [":kateId" => $kategoriId, ":nu" => $nu]
            );
            foreach ($tilbud as $t) $result[] = array_merge((array)$t, ["type"=>"tilbud"]);
        } else {
            $rabatkoder = $db->sql("SELECT r.*, v.virkNavn, v.virkLogo, v.virkLink, k.kateNavn
                FROM rabatkoder r
                JOIN virksomheder v ON r.virkId = v.id
                JOIN kategorier k ON r.kateId = k.id
                WHERE r.kateId = :kateId AND r.rabaStart <= :nu AND r.rabaUdloeb >= :nu",
                [":kateId" => $kategoriId, ":nu" => $nu]
            );
            $tilbud = $db->sql("SELECT t.*, v.virkNavn, v.virkLogo, v.virkLink, k.kateNavn
                FROM tilbud t
                JOIN virksomheder v ON t.virkId = v.id
                JOIN kategorier k ON t.kateId = k.id
                WHERE t.kateId = :kateId AND t.tilbStart <= :nu AND t.tilbUdloeb >= :nu",
                [":kateId" => $kategoriId, ":nu" => $nu]
            );
            foreach ($rabatkoder as $r) $result[] = array_merge((array)$r, ["type"=>"rabat"]);
            foreach ($tilbud as $t) $result[] = array_merge((array)$t, ["type"=>"tilbud"]);
        }
    }
    echo json_encode($result);
    exit;
}

// ===== Første load af kategorier og alle deals =====
$kategorier = $db->sql("SELECT * FROM kategorier ORDER BY id ASC");
$rabatkoder = $db->sql("SELECT r.*, v.virkNavn, v.virkLogo, v.virkLink, k.kateNavn
    FROM rabatkoder r
    JOIN virksomheder v ON r.virkId = v.id
    JOIN kategorier k ON r.kateId = k.id
    WHERE r.rabaStart <= :nu AND r.rabaUdloeb >= :nu",
    [":nu"=>$nu]
);
$tilbud = $db->sql("SELECT t.*, v.virkNavn, v.virkLogo, v.virkLink, k.kateNavn
    FROM tilbud t
    JOIN virksomheder v ON t.virkId = v.id
    JOIN kategorier k ON t.kateId = k.id
    WHERE t.tilbStart <= :nu AND t.tilbUdloeb >= :nu",
    [":nu"=>$nu]
);
?>
<!DOCTYPE html>
<html lang="da">
<head>
    <meta charset="utf-8">
    <title>Rabado - Forside</title>
    <meta name="robots" content="index, follow">
    <meta name="author" content="Rabado">
    <meta name="copyright" content="© 2025 Rabado. Alle rettigheder forbeholdes.">
    <link href="css/styles.css" rel="stylesheet" type="text/css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.13.1/font/bootstrap-icons.min.css">
    <meta name="viewport" content="width=device-width, initial-scale=1">
</head>
<body>
<?php include("includes/navbar.php");?>

<div class="container-fluid hero d-flex flex-column align-items-center justify-content-center text-center">
    <h2 class="fw-bold">Hvorfor betale fuld pris?</h2>
    <h6 class="text-muted">Rabatter og tilbud samlet ét sted</h6>

    <form method="get" action="search.php" class="search-box">
        <label for="search-input" class="visually-hidden">Søg rabatkoder på siden</label>
        <input type="text" id="search-input" name="q" class="form-control">
        <button type="submit" class="btn btn-lilla shadow-sm"><i class="bi bi-search"></i></button>
    </form>

    <div id="search-results-container" class="mt-2 mx-auto">
        <div class="row" id="search-results-row"></div>
    </div>
</div>

<div>
    <div class="divider"></div>
</div>

<div class="container-fluid px-2 mb-2 mt-3">
    <div class="d-flex flex-wrap justify-content-between align-items-center header-row">
        <h5>Kategorier</h5>
        <div class="switch-container">
            <button id="btn-rabatkoder" class="btn switch-btn <?= $type==='rabatkoder' ? 'active' : '' ?>">Rabatkoder</button>
            <button id="btn-tilbud" class="btn switch-btn <?= $type==='tilbud' ? 'active' : '' ?>">Tilbud</button>
        </div>
    </div>
</div>

<div class="container-fluid categories mb-5">
    <div class="categories-scroll mt-5">
        <?php foreach ($kategorier as $kat): ?>
            <div class="category <?= $kat->id == $kategoriId ? 'active' : '' ?>" data-id="<?= $kat->id ?>">
                <i class="<?= htmlspecialchars($kat->kateIkon) ?>"></i>
                <span class="category-label"><?= htmlspecialchars($kat->kateNavn) ?></span>
            </div>
        <?php endforeach; ?>
    </div>
</div>

<div class="container deals-container mb-5">
    <div class="row g-3" id="deals-row">

        <?php foreach ($rabatkoder as $rabat): ?>
            <?php $udloebsDato = date("d.m.Y", strtotime($rabat->rabaUdloeb)); ?>
            <div class="col-6 col-md-4 col-lg-3">
                <div class="discount-card card shadow-sm border-0 rounded-4 p-3 mb-4 h-100">
                    <div class="position-relative text-center logo-wrap">
                        <img src="<?= htmlspecialchars($rabat->virkLogo) ?>" alt="<?= htmlspecialchars($rabat->virkNavn) ?> Logo" class="discount-logo">
                    </div>
                    <div class="card-body px-0">
                        <h5 class="card-title fw-bold text-center mt-3"><?= htmlspecialchars($rabat->rabaTitel) ?></h5>

                        <div class="d-flex justify-content-center gap-2 mt-3">
                            <span class="discount-code btn btn-lilla" title="Kopier" data-code="<?= htmlspecialchars($rabat->rabaKode) ?>" data-id="<?= $rabat->id ?>"><?= htmlspecialchars($rabat->rabaKode) ?></span>
                            <a href="<?= htmlspecialchars($rabat->virkLink) ?>" target="_blank" class="btn btn-outline-lilla visit-btn">Besøg</a>
                        </div>

                        <p class="text-muted small mt-3"><?= htmlspecialchars($rabat->rabaBeskrivelse) ?></p>
                        <div class="meta text-muted small">
                            <span class="udloeb"><i class="bi bi-calendar3"></i> Udløber <?= $udloebsDato ?></span>
                            <span class="anvendt" data-timestamp="<?= !empty($rabat->rabaAnvendt) ? strtotime($rabat->rabaAnvendt) : '' ?>"><i class="bi bi-clock"></i>
                                <?= !empty($rabat->rabaAnvendt) ? 'Anvendt' : 'Ikke anvendt endnu' ?>
                            </span>

                        </div>
                    </div>
                </div>
            </div>
        <?php endforeach; ?>

        <?php foreach ($tilbud as $deal): ?>
            <?php $udloebsDato = date("d.m.Y", strtotime($deal->tilbUdloeb)); ?>
            <div class="col-6 col-md-4 col-lg-3">
                <div class="discount-card card shadow-sm border-0 rounded-4 p-3 mb-4">
                    <div class="position-relative text-center mb-3">
                        <img src="<?= htmlspecialchars($deal->virkLogo) ?>" alt="<?= htmlspecialchars($deal->virkNavn) ?> Logo" class="deal-logo">
                    </div>
                    <div class="card-body px-0">
                        <h5 class="card-title fw-bold text-center mt-3"><?= htmlspecialchars($deal->tilbTitel) ?></h5>
                        <div class="d-flex justify-content-center gap-2 mt-3">
                            <a href="<?= htmlspecialchars($deal->virkLink) ?>" target="_blank" class="btn btn-outline-lilla visit-btn">Se tilbud</a>
                        </div>

                        <p class="text-muted small mt-3"><?= htmlspecialchars($deal->tilbBeskrivelse) ?></p>

                        <div class="meta d-flex justify-content-between text-muted small">
                            <span><i class="bi bi-calendar3"></i> Udløber <?= $udloebsDato ?></span>
                        </div>

                    </div>
                </div>
            </div>
        <?php endforeach; ?>
    </div>
</div>

<div>
    <div class="divider"></div>
</div>

<div class="text-center">
    <h5>
        Fandt du ikke hvad du søgte? <br>
        Tilføj din rabatkode og del den med andre.
    </h5>

    <button class="btn btn-outline-lilla mt-3 mb-5" data-bs-toggle="modal" data-bs-target="#addDiscountModal">
        Tilføj
    </button>
</div>

<?php include("includes/footer.php");?>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script>
    document.addEventListener('DOMContentLoaded', () => {
        const btnRabatkoder = document.getElementById('btn-rabatkoder');
        const btnTilbud = document.getElementById('btn-tilbud');
        const categories = document.querySelectorAll('.category');

        const searchInput = document.getElementById('search-input');
        const searchForm = document.querySelector('.search-box');
        const searchContainer = document.getElementById('search-results-container');

        // Dropdown til dynamisk live-search
        const resultsBox = document.createElement('div');
        resultsBox.className = "search-results";
        searchInput.parentNode.appendChild(resultsBox);

        let timer;
        let currentType = "<?= isset($type) ? $type : '' ?>";
        let currentCategory = "<?= isset($kategoriId) ? $kategoriId : '' ?>";

        // Skjuler søgeresultat, når der klikke inde i inputfeltet //
        searchInput.addEventListener('focus', () => {
            resultsBox.style.display = "none"; // skjul dropdown
            searchContainer.innerHTML = "";    // skjul søgeresultat-kortene
        });

        // Viser brugeren hvornår rabatkoden sidst blev anvendt //
        function timeSince(ms) {
            if (!ms) return 'Ikke anvendt endnu';
            const seconds = Math.floor((Date.now() - ms) / 1000);
            if (seconds < 60) return 'Anvendt få sekunder siden';
            const minutes = Math.floor(seconds / 60);
            if (minutes < 60) return `Anvendt ${minutes} minut${minutes > 1 ? 'ter' : ''} siden`;
            const hours = Math.floor(minutes / 60);
            if (hours < 24) return `Anvendt ${hours} time${hours > 1 ? 'r' : ''} siden`;
            const days = Math.floor(hours / 24);
            if (days < 365) return `Anvendt ${days} dag${days > 1 ? 'e' : ''} siden`;
            const years = Math.floor(days / 365);
            return `Anvendt ${years} år siden`;
        }

        // Opdater elementer med klassen "anvendt" //
        function updateUsedLabels(context = document) {
            context.querySelectorAll('.anvendt').forEach(el => {
                const ts = el.dataset.timestamp ? parseInt(el.dataset.timestamp, 10) : null;
                el.innerHTML = `<i class="bi bi-clock"></i> ${timeSince(ts)}`;
            });
        }

        // ===== loadDeals(): henter via AJAX fra samme PHP-fil (ajax=1) =====
        async function loadDeals() {
            const params = new URLSearchParams({ ajax: 1 });
            if (currentType) params.set('type', currentType);
            if (currentCategory) params.set('kategori', currentCategory);

            try {
                const res = await fetch(`<?= basename(__FILE__) ?>?${params.toString()}`);
                const data = await res.json();
                const dealsContainer = document.getElementById('deals-row');
                dealsContainer.innerHTML = "";

                if (!data.length) {
                    dealsContainer.innerHTML = `<p class="text-muted text-center mt-3">Ingen resultater fundet</p>`;
                    return;
                }

                data.forEach(item => {
                    const col = document.createElement('div');
                    col.className = "col-6 col-md-4 col-lg-3";

                    const card = document.createElement('div');
                    card.className = "discount-card card shadow-sm border-0 rounded-4 p-3 mb-4";

                    if (item.type === 'rabat') {
                        card.innerHTML = `
                        <div class="position-relative text-center logo-wrap">
                            <img src="${item.virkLogo || ''}" alt="${item.virkNavn || ''} Logo" class="discount-logo">
                        </div>
                        <div class="card-body px-0 d-flex flex-column">
                            <h5 class="card-title fw-bold text-center mt-3">${item.rabaTitel || item.titel || ''}</h5>
                            <div class="d-flex justify-content-center gap-2 mt-3">
                                <button class="discount-code btn btn-lilla" data-code="${item.rabaKode || item.kode || ''}" data-id="${item.id || ''}">${item.rabaKode || item.kode || ''}</button>
                                <a href="${item.virkLink || '#'}" target="_blank" class="btn btn-outline-lilla visit-btn">Besøg</a>
                            </div>
                            <p class="text-muted small mt-3 mb-2">${item.rabaBeskrivelse || item.beskrivelse || ''}</p>
                            <div class="meta d-flex justify-content-between text-muted small mt-auto">
                                ${item.rabaUdloeb ? `<span class="udloeb"><i class="bi bi-calendar3"></i> Udløber ${item.rabaUdloeb}</span>` : ''}
                                <span class="anvendt" data-timestamp="${item.rabaAnvendt ? (new Date(item.rabaAnvendt).getTime()) : ''}"></span>
                            </div>
                        </div>
                    `;

                        // copy + mark used
                        card.querySelector('.discount-code').addEventListener('click', async (ev) => {
                            const btn = ev.currentTarget;
                            const code = btn.getAttribute('data-code');
                            const id = btn.getAttribute('data-id');

                            navigator.clipboard.writeText(code);
                            const old = btn.textContent;
                            btn.textContent = '✔';
                            setTimeout(() => btn.textContent = old, 2000);

                            if (id) {
                                try {
                                    const res = await fetch('updateLastUsed.php', {
                                        method: 'POST',
                                        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                                        body: `id=${encodeURIComponent(id)}`
                                    });
                                    const d = await res.json();
                                    if (d.success && d.timestamp) {
                                        const label = card.querySelector('.anvendt');
                                        // updateLastUsed.php returns timestamp in seconds — standardiser til ms
                                        label.dataset.timestamp = d.timestamp * 1000;
                                        label.innerHTML = `<i class="bi bi-clock"></i> ${timeSince(d.timestamp * 1000)}`;
                                    }
                                } catch (err) {
                                    console.error('Fejl ved updateLastUsed:', err);
                                }
                            }
                        });

                    } else { // tilbud
                        card.innerHTML = `
                        <div class="position-relative text-center mb-3">
                            <img src="${item.virkLogo || ''}" alt="${item.virkNavn || ''} Logo" class="deal-logo">
                        </div>
                        <div class="card-body px-0 d-flex flex-column">
                            <h5 class="card-title fw-bold text-center mt-3">${item.tilbTitel || item.titel || ''}</h5>
                            <div class="d-flex justify-content-center gap-2 mt-3">
                                <a href="${item.virkLink || '#'}" target="_blank" class="btn btn-outline-lilla visit-btn">Se tilbud</a>
                            </div>
                            <p class="text-muted small mt-3 mb-2">${item.tilbBeskrivelse || item.beskrivelse || ''}</p>
                            <div class="meta d-flex justify-content-between text-muted small mt-auto">
                                ${item.tilbUdloeb ? `<span><i class="bi bi-calendar3"></i> Udløber ${item.tilbUdloeb}</span>` : ''}
                            </div>
                        </div>
                    `;
                    }

                    col.appendChild(card);
                    dealsContainer.appendChild(col);
                });

                // opdater anvendt-tekster i de nye cards
                updateUsedLabels(dealsContainer);
            } catch (err) {
                console.error("Fejl ved indlæsning af deals:", err);
            }
        }

        // ===== Switch-knapper med toggle =====
        function toggleType(type) {
            currentType = (currentType === type ? '' : type);
            btnRabatkoder.classList.toggle('active', currentType === 'rabatkoder');
            btnTilbud.classList.toggle('active', currentType === 'tilbud');
            // hvis user aktivt søger, lad dropdownen være og stadig hent deals (men vi ændrer kun deals)
            loadDeals();
        }
        btnRabatkoder.addEventListener('click', () => toggleType('rabatkoder'));
        btnTilbud.addEventListener('click', () => toggleType('tilbud'));

        // ===== Kategori-knapper =====
        categories.forEach(cat => {
            cat.addEventListener('click', () => {
                currentCategory = (currentCategory === cat.dataset.id ? '' : cat.dataset.id);
                categories.forEach(c => c.classList.toggle('active', c.dataset.id === currentCategory));
                loadDeals();
            });
        });

        // ===== Live-search (dropdown) =====
        searchInput.addEventListener('input', () => {
            clearTimeout(timer);
            const q = searchInput.value.trim();
            resultsBox.style.display = "none";
            resultsBox.innerHTML = "";
            if (!q) return;

            timer = setTimeout(async () => {
                try {
                    const res = await fetch("search.php?q=" + encodeURIComponent(q));
                    const data = await res.json();
                    if (!data.length) return;

                    data.forEach(item => {
                        const row = document.createElement('div');
                        row.className = 'search-result';

                        const logo = document.createElement('img');
                        logo.src = item.virkLogo || '';
                        logo.alt = item.virkNavn || '';
                        logo.className = 'search-logo';

                        const title = document.createElement('span');
                        title.className = 'search-title';
                        title.textContent = item.titel || '';

                        row.appendChild(logo);
                        row.appendChild(title);

                        if (item.type === 'rabat') {
                            const copyBtn = document.createElement('button');
                            copyBtn.type = 'button';
                            copyBtn.className = 'btn btn-outline-lilla copy-btn';
                            copyBtn.textContent = item.kode || '';
                            copyBtn.addEventListener('click', async (ev) => {
                                ev.stopPropagation();
                                navigator.clipboard.writeText(item.kode || '');
                                const old = copyBtn.textContent;
                                copyBtn.textContent = '✔';
                                setTimeout(() => copyBtn.textContent = old, 2000);

                                // Hvis id findes, markér anvendt i db
                                if (item.id) {
                                    try {
                                        const res2 = await fetch('updateLastUsed.php', {
                                            method: 'POST',
                                            headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                                            body: `id=${encodeURIComponent(item.id)}`
                                        });
                                        const d2 = await res2.json();
                                        // dropdown small — vi opdaterer ikke et label her
                                    } catch (err) {
                                        console.error('Fejl ved updateLastUsed (dropdown):', err);
                                    }
                                }
                            });
                            row.appendChild(copyBtn);
                        } else {
                            const a = document.createElement('a');
                            a.href = item.virkLink || '#';
                            a.target = '_blank';
                            a.className = 'btn btn-outline-lilla';
                            a.textContent = 'Se tilbud';
                            row.appendChild(a);
                        }

                        resultsBox.appendChild(row);
                    });

                    resultsBox.style.display = 'block';
                } catch (err) {
                    console.error('Search error:', err);
                }
            }, 300);
        });

        // Luk dropdown ved klik udenfor
        document.addEventListener('click', (e) => {
            if (!resultsBox.contains(e.target) && e.target !== searchInput) resultsBox.style.display = 'none';
        });

        // ===== Search (submit) - vis resultater som cards (2/3/4 per række) =====
        searchForm.addEventListener('submit', async (e) => {
            e.preventDefault();
            const q = searchInput.value.trim();
            if (!q) return;

            try {
                const res = await fetch("search.php?q=" + encodeURIComponent(q));
                const data = await res.json();
                searchContainer.innerHTML = "";

                if (!data.length) {
                    searchContainer.innerHTML = `<p class="text-muted text-center mt-3">Ingen resultater fundet.</p>`;
                    return;
                }

                const rowWrapper = document.createElement('div');
                rowWrapper.className = 'row g-3';

                data.forEach(item => {
                    const col = document.createElement('div');
                    col.className = 'col-6 col-md-4 col-lg-3';

                    const card = document.createElement('div');
                    card.className = 'discount-card card shadow-sm border-0 rounded-4 p-3 mt-4 ';

                    if (item.type === 'rabat') {
                        card.innerHTML = `
                        <div class="position-relative text-center logo-wrap">
                            <img src="${item.virkLogo || ''}" alt="${item.virkNavn || ''} Logo" class="discount-logo">
                        </div>
                        <div class="card-body px-0 d-flex flex-column">
                            <h5 class="card-title fw-bold text-center mt-3">${item.titel || ''}</h5>
                            <p class="text-muted small text-center mt-1 mb-1">${item.beskrivelse || ''}</p>
                            <div class="d-flex justify-content-center gap-2 mt-3">
                                <button class="discount-code btn btn-lilla copy-btn" data-code="${item.kode || ''}" data-id="${item.id || ''}">${item.kode || ''}</button>
                                <a href="${item.virkLink || '#'}" target="_blank" class="btn btn-outline-lilla visit-btn">Besøg</a>
                            </div>
                        </div>
                    `;
                    } else {
                        card.innerHTML = `
                        <div class="position-relative text-center logo-wrap">
                            <img src="${item.virkLogo || ''}" alt="${item.virkNavn || ''} Logo" class="deal-logo">
                        </div>
                        <div class="card-body px-0 d-flex flex-column">
                            <h5 class="card-title fw-bold text-center mt-3">${item.titel || ''}</h5>
                            <p class="text-muted small text-center mt-1 mb-1">${item.beskrivelse || ''}</p>
                            <div class="d-flex justify-content-center gap-2 mt-3">
                                <a href="${item.virkLink || '#'}" target="_blank" class="btn btn-outline-lilla visit-btn">Se tilbud</a>
                            </div>
                        </div>
                    `;
                    }

                    col.appendChild(card);
                    rowWrapper.appendChild(col);
                });

                searchContainer.appendChild(rowWrapper);

                // Tilføj copy-event listeners til de nye knapper
                searchContainer.querySelectorAll('.copy-btn').forEach(btn => {
                    btn.addEventListener('click', async (ev) => {
                        const code = btn.getAttribute('data-code');
                        const id = btn.getAttribute('data-id');

                        navigator.clipboard.writeText(code);
                        const old = btn.textContent;
                        btn.textContent = '✔';
                        setTimeout(() => btn.textContent = old, 2000);

                        if (id) {
                            try {
                                const res2 = await fetch('updateLastUsed.php', {
                                    method: 'POST',
                                    headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                                    body: `id=${encodeURIComponent(id)}`
                                });
                                const d2 = await res2.json();
                                if (d2.success && d2.timestamp) {
                                    // find tilhørende label i samme card og opdatér
                                    const parentCard = btn.closest('.discount-card');
                                    if (parentCard) {
                                        const label = parentCard.querySelector('.anvendt');
                                        if (label) {
                                            label.dataset.timestamp = d2.timestamp * 1000;
                                            label.innerHTML = `<i class="bi bi-clock"></i> ${timeSince(d2.timestamp * 1000)}`;
                                        }
                                    }
                                }
                            } catch (err) {
                                console.error('Fejl ved updateLastUsed (search submit):', err);
                            }
                        }
                    });
                });

                // opdater anvendt labels i search-result
                updateUsedLabels(searchContainer);
            } catch (err) {
                console.error('Search error:', err);
            }
        });

        loadDeals();

        updateUsedLabels();
    });
</script>
</body>
</html>