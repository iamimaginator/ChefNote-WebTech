const API = 'api.php';

// ── Init ──
async function initApp() {
    // NEW: Register Service Worker for Offline PWA
    if ('serviceWorker' in navigator) {
        navigator.serviceWorker.register('./sw.js')
            .then(() => console.log('Service Worker Registered!'))
            .catch((err) => console.log('SW Registration failed:', err));
    }
    loadTheme();
    loadProfile();
    setupAutoNumber('recipe-ingredients');
    setupAutoNumber('recipe-body');

    const titleEl = document.getElementById('recipe-title');
    if (titleEl) {
        titleEl.addEventListener('input', function () {
            if (this.value.length > 0) {
                const fc = this.value.charAt(0);
                if (fc !== fc.toUpperCase()) {
                    const pos = this.selectionStart;
                    this.value = fc.toUpperCase() + this.value.slice(1);
                    this.selectionStart = this.selectionEnd = pos;
                }
            }
        });
    }

    try { await fetch(`${API}?action=seed`); } catch (e) { }
    loadVault();
}

// ── Sidebar Toggle (Mobile) ──
function toggleSidebar() {
    document.getElementById('sidebar').classList.toggle('open');
    document.getElementById('sidebarBackdrop').classList.toggle('open');
}

// ── Settings Dropup ──
function toggleSettings(e) {
    e.preventDefault();
    e.stopPropagation();
    const menu = document.getElementById('settingsMenu');
    menu.classList.toggle('open');
}
function closeSettings() {
    document.getElementById('settingsMenu').classList.remove('open');
}
// Close settings when clicking outside
document.addEventListener('click', function(e) {
    const dropup = document.getElementById('settingsDropup');
    if (dropup && !dropup.contains(e.target)) closeSettings();
});

// ── Theme Toggle ──
function loadTheme() {
    const saved = localStorage.getItem('theme');
    if (saved === 'light') document.body.classList.add('light');
    updateThemeUI();
}
function toggleTheme(e) {
    e.stopPropagation();
    document.body.classList.toggle('light');
    localStorage.setItem('theme', document.body.classList.contains('light') ? 'light' : 'dark');
    updateThemeUI();
}
function updateThemeUI() {
    const isLight = document.body.classList.contains('light');
    const icon = document.getElementById('theme-icon');
    if (icon) icon.className = isLight ? 'bi bi-sun-fill' : 'bi bi-moon-fill';
    const toggle = document.getElementById('theme-toggle');
    if (toggle) {
        if (isLight) toggle.classList.remove('active');
        else toggle.classList.add('active');
    }
}

// ── Custom Modals ──
function openModal(id) {
    document.getElementById(id).classList.add('open');
    document.body.style.overflow = 'hidden';
}
function closeModal(id) {
    document.getElementById(id).classList.remove('open');
    document.body.style.overflow = '';
}

// ── Tabs ──
function showTab(id, el) {
    document.querySelectorAll('.tab').forEach(t => t.classList.remove('active'));
    document.getElementById(id).classList.add('active');
    document.querySelectorAll('.sidebar-nav .nav-item').forEach(l => l.classList.remove('active'));
    if (el) el.classList.add('active');
    // Close sidebar on mobile
    if (window.innerWidth <= 768) toggleSidebar();
}

// ── Auto-number textareas ──
function setupAutoNumber(id) {
    const el = document.getElementById(id);
    if (!el) return;

    el.addEventListener('focus', function () {
        if (!this.value.trim()) this.value = '1. ';
    });

    el.addEventListener('input', function () {
        const lines = this.value.split('\n');
        let changed = false;
        const newLines = lines.map(line => {
            const match = line.match(/^(\d+\.\s+)([a-z])(.*)/);
            if (match) { changed = true; return match[1] + match[2].toUpperCase() + match[3]; }
            return line;
        });
        if (changed) {
            const pos = this.selectionStart;
            this.value = newLines.join('\n');
            this.selectionStart = this.selectionEnd = pos;
        }
    });

    el.addEventListener('keydown', function (e) {
        if (e.key === 'Enter') {
            e.preventDefault();
            const pos = this.selectionStart;
            const text = this.value;
            const before = text.substring(0, pos);
            const after = text.substring(pos);
            const lines = before.split('\n');
            const lastLine = lines[lines.length - 1];
            const match = lastLine.match(/^(\d+)\.\s/);
            const next = match ? parseInt(match[1]) + 1 : lines.length + 1;
            const insert = '\n' + next + '. ';
            this.value = before + insert + after;
            this.selectionStart = this.selectionEnd = pos + insert.length;
        }
    });
}

// ── Save Recipe ──
async function saveRecipe(editId = null) {
    const title = document.getElementById('recipe-title').value.trim();
    const ingredients = document.getElementById('recipe-ingredients').value.trim();
    const instructions = document.getElementById('recipe-body').value.trim();
    const time = document.getElementById('recipe-time').value.trim();
    const mood = document.getElementById('recipe-mood').value;
    
    // Grab the image file
    const imageInput = document.getElementById('recipe-image');
    const imageFile = imageInput ? imageInput.files[0] : null;

    if (!title || !time) return popToast('Fill in name and time');

    const url = editId ? `${API}?action=update_recipe` : `${API}?action=create`;
    
    // Use FormData instead of JSON so we can send the image file
    const formData = new FormData();
    formData.append('title', title);
    formData.append('ingredients', ingredients);
    formData.append('instructions', instructions);
    formData.append('time', time);
    formData.append('mood', mood);
    if (editId) formData.append('id', editId);
    if (imageFile) formData.append('cover_image', imageFile);

    try {
        const res = await fetch(url, {
            method: 'POST',
            body: formData // The browser automatically sets the correct hidden headers for files!
        });
        const d = await res.json();
        
        if (d.success) {
            popToast(editId ? 'Recipe Updated!' : 'Saved: ' + title);
            
            // Clear all fields including the image input
            ['recipe-title', 'recipe-ingredients', 'recipe-body', 'recipe-time', 'recipe-image'].forEach(id => {
                const el = document.getElementById(id);
                if (el) el.value = '';
            });
            document.getElementById('recipe-mood').value = '';

            // Reset save button
            const saveBtn = document.querySelector("button[onclick^='saveRecipe']");
            if (saveBtn) {
                saveBtn.innerHTML = '<i class="bi bi-save"></i> Save Recipe';
                saveBtn.setAttribute('onclick', 'saveRecipe()');
            }
            loadVault();
        } else popToast(d.error || 'Operation failed');
    } catch (e) { popToast('Network connection error'); }
}

// ── Load Recipes ──
let recipes = [];

async function loadVault() {
    try {
        const res = await fetch(`${API}?action=read`);
        recipes = await res.json();
        renderGrid();
        renderMini();
    } catch (e) { }
}

function renderGrid() {
    const el = document.getElementById('recipe-grid');
    if (!recipes.length) { el.innerHTML = '<div class="text-soft" style="text-align:center;padding:48px 0">No recipes yet. Head to Kitchen to create one.</div>'; return; }

    el.innerHTML = recipes.map((r, i) => {
        const ic = r.ingredients ? r.ingredients.split('\n').filter(l => l.trim()).length : 0;
        const preview = r.instructions ? esc(r.instructions).substring(0, 90) + '…' : '';
        const moodLabel = r.mood ? `<span class="badge-soft badge-mood">${moodEmoji(r.mood)}</span>` : '';
        return `<div class="recipe-card" onclick="openRecipe(${i})">
            <h6>${esc(r.title)}</h6>
            <span class="badge-soft badge-time">${esc(r.time)}</span>
            ${ic ? `<span class="badge-soft badge-count" style="margin-left:4px">${ic} items</span>` : ''}
            ${moodLabel ? `<span style="margin-left:4px">${moodLabel}</span>` : ''}
            ${preview ? `<p class="recipe-preview">${preview}</p>` : ''}
        </div>`;
    }).join('');
}

function moodEmoji(mood) {
    const map = { tired: '😴', happy: '😄', stressed: '😤', adventurous: '🤠', lazy: '🛋️', romantic: '💕' };
    return (map[mood] || '') + ' ' + (mood ? mood.charAt(0).toUpperCase() + mood.slice(1) : '');
}

function renderMini() {
    const el = document.getElementById('mini-list');
    if (!recipes.length) { el.innerHTML = '<p class="text-soft" style="font-size:.8rem">Nothing yet.</p>'; return; }
    el.innerHTML = recipes.slice(0, 5).map((r, i) =>
        `<div class="mini-item" onclick="openRecipe(${i})">
            <div><strong>${esc(r.title)}</strong></div>
            <span class="mini-arrow"><i class="bi bi-chevron-right"></i></span>
        </div>`
    ).join('');
}

// ── Recipe Modal ──
function openRecipe(i) { showRecipeModal(recipes[i]); }

let activeRecipeInModal = null;

function showRecipeModal(r) {
    if (!r) return;
    activeRecipeInModal = r;
    
    // --- NEW IMAGE DISPLAY LOGIC ---
    const imgEl = document.getElementById('modal-cover-image');
    if (r.image_url) {
        imgEl.src = r.image_url;
        imgEl.style.display = 'block';
    } else {
        imgEl.src = '';
        imgEl.style.display = 'none';
    }

    document.getElementById('recipeModalLabel').textContent = r.title;
    document.getElementById('modal-time').textContent = r.time || '—';

    const moodBadge = document.getElementById('modal-mood-badge');
    moodBadge.innerHTML = r.mood ? `<span class="badge-soft badge-mood">${moodEmoji(r.mood)}</span>` : '';

    const ing = document.getElementById('modal-ingredients');
    ing.innerHTML = r.ingredients?.trim()
        ? '<ul>' + r.ingredients.split('\n').filter(l => l.trim()).map(l => `<li>${esc(l.trim())}</li>`).join('') + '</ul>'
        : '<p class="text-soft">None listed.</p>';

    const ins = document.getElementById('modal-instructions');
    ins.innerHTML = r.instructions?.trim()
        ? r.instructions.split('\n').filter(l => l.trim()).map(s => `<p style="margin-bottom:.4rem;font-size:.86rem">${esc(s.trim())}</p>`).join('')
        : '<p class="text-soft">None provided.</p>';

    const editBtn = document.getElementById('modal-edit-btn');
    const deleteBtn = document.getElementById('modal-delete-btn');

    if (r.id) {
        editBtn.style.display = 'inline-flex';
        deleteBtn.style.display = 'inline-flex';
        editBtn.onclick = () => triggerEditMode(r);
        deleteBtn.onclick = () => confirmDeleteRecipe(r.id);
    } else {
        editBtn.style.display = 'none';
        deleteBtn.style.display = 'none';
    }

    openModal('recipeModal');
}

function triggerEditMode(recipe) {
    closeModal('recipeModal');
    showTab('home', document.getElementById('nav-home'));

    document.getElementById('recipe-title').value = recipe.title;
    document.getElementById('recipe-ingredients').value = recipe.ingredients;
    document.getElementById('recipe-body').value = recipe.instructions;
    document.getElementById('recipe-time').value = recipe.time.replace(' Mins', '');
    document.getElementById('recipe-mood').value = recipe.mood;

    const saveBtn = document.querySelector("button[onclick^='saveRecipe']");
    if (saveBtn) {
        saveBtn.innerHTML = '<i class="bi bi-save"></i> Update Recipe';
        saveBtn.setAttribute('onclick', `saveRecipe(${recipe.id})`);
    }
}

async function confirmDeleteRecipe(id) {
    if (!confirm('Are you sure you want to permanently delete this recipe?')) return;
    try {
        const res = await fetch(`${API}?action=delete_recipe&id=${id}`);
        const d = await res.json();
        if (d.success) {
            popToast('Recipe Deleted.');
            closeModal('recipeModal');
            loadVault();
        } else popToast(d.error || 'Could not delete');
    } catch (e) { popToast('Network error'); }
}

// ── Mood Lab ──
const moods = {
    tired: [
        { title: '🍳 Quick Cheese Omelet', desc: 'Fluffy, fast, and filling.', time: '5 Mins', ingredients: '1. Eggs\n2. Butter\n3. Cheddar\n4. Salt\n5. Pepper', instructions: '1. Whisk eggs.\n2. Melt butter in pan.\n3. Pour eggs, add cheese.\n4. Fold and serve.' },
        { title: '🥣 Instant Oatmeal', desc: 'Warm and zero effort.', time: '3 Mins', ingredients: '1. Oats\n2. Milk\n3. Honey\n4. Banana', instructions: '1. Mix oats and milk.\n2. Microwave 90 seconds.\n3. Top with banana and honey.' },
        { title: '🍞 PB Banana Toast', desc: 'Sweet energy boost.', time: '4 Mins', ingredients: '1. Bread\n2. Peanut Butter\n3. Banana\n4. Honey', instructions: '1. Toast bread.\n2. Spread peanut butter.\n3. Add banana slices and honey.' }
    ],
    happy: [
        { title: '🍝 Garlic Parmesan Pasta', desc: 'Rich and indulgent.', time: '20 Mins', ingredients: '1. Pasta\n2. Cream\n3. Garlic\n4. Parmesan\n5. Butter', instructions: '1. Cook pasta al dente.\n2. Sauté garlic in butter.\n3. Add cream, simmer.\n4. Toss pasta, add Parmesan.' },
        { title: '🍕 Margherita Pizza', desc: 'Fresh and classic.', time: '25 Mins', ingredients: '1. Dough\n2. Tomato Sauce\n3. Mozzarella\n4. Basil', instructions: '1. Roll dough, spread sauce.\n2. Add torn mozzarella.\n3. Bake 250°C, 10 min.\n4. Add basil.' },
        { title: '🍰 Celebration Cupcakes', desc: 'Bite-sized joy.', time: '30 Mins', ingredients: '1. Flour\n2. Sugar\n3. Butter\n4. Eggs\n5. Vanilla\n6. Frosting', instructions: '1. Mix dry + wet ingredients.\n2. Pour into liners.\n3. Bake 180°C, 18 min.\n4. Cool and frost.' }
    ],
    stressed: [
        { title: '🍲 Tomato Soup', desc: 'Warm comfort in a bowl.', time: '25 Mins', ingredients: '1. Tomatoes\n2. Onion\n3. Butter\n4. Cream\n5. Basil', instructions: '1. Sauté onion in butter.\n2. Add tomatoes, simmer 15 min.\n3. Blend smooth.\n4. Stir in cream.' },
        { title: '🫖 Honey Ginger Tea', desc: 'Calming and aromatic.', time: '5 Mins', ingredients: '1. Ginger\n2. Lemon\n3. Honey\n4. Water', instructions: '1. Slice ginger.\n2. Steep in hot water 3 min.\n3. Add lemon and honey.' },
        { title: '🍫 Chocolate Mousse', desc: 'Silky mood-lifter.', time: '15 Mins', ingredients: '1. Dark Chocolate\n2. Eggs\n3. Sugar\n4. Vanilla', instructions: '1. Melt chocolate.\n2. Whisk yolks with sugar.\n3. Fold chocolate in.\n4. Beat whites, fold. Chill 2h.' }
    ],
    adventurous: [
        { title: '🌮 Korean Tacos', desc: 'Bold fusion street food.', time: '35 Mins', ingredients: '1. Beef\n2. Gochujang\n3. Tortillas\n4. Kimchi\n5. Lime', instructions: '1. Marinate beef.\n2. Grill until charred.\n3. Load tortillas with beef and kimchi.' },
        { title: '🍣 DIY Sushi Rolls', desc: 'Roll your own.', time: '40 Mins', ingredients: '1. Sushi Rice\n2. Nori\n3. Salmon\n4. Avocado\n5. Cucumber', instructions: '1. Season rice.\n2. Spread on nori.\n3. Add fillings, roll tightly.\n4. Slice and serve.' },
        { title: '🥘 Thai Green Curry', desc: 'Aromatic coconut heat.', time: '30 Mins', ingredients: '1. Coconut Milk\n2. Curry Paste\n3. Chicken\n4. Basil', instructions: '1. Fry paste 1 min.\n2. Add coconut milk.\n3. Add chicken, simmer 15 min.\n4. Serve over rice.' }
    ],
    lazy: [
        { title: '🥪 Loaded Nachos', desc: 'Maximum crunch, zero effort.', time: '5 Mins', ingredients: '1. Chips\n2. Cheese\n3. Beans\n4. Salsa', instructions: '1. Layer chips and cheese.\n2. Add beans and salsa.\n3. Microwave 2 min.' },
        { title: '🌯 Quick Wrap', desc: 'Whatever is in the fridge.', time: '5 Mins', ingredients: '1. Tortilla\n2. Deli Meat\n3. Cheese\n4. Lettuce', instructions: '1. Layer everything on tortilla.\n2. Roll tight.\n3. Cut in half.' },
        { title: '🍌 Frozen Banana Bites', desc: 'Three-ingredient treat.', time: '10 Mins', ingredients: '1. Bananas\n2. Chocolate\n3. Sprinkles', instructions: '1. Slice bananas.\n2. Dip in melted chocolate.\n3. Add sprinkles, freeze 1h.' }
    ],
    romantic: [
        { title: '🥂 Pan-Seared Salmon', desc: 'Restaurant quality at home.', time: '25 Mins', ingredients: '1. Salmon\n2. Asparagus\n3. Honey\n4. Garlic\n5. Lemon\n6. Butter', instructions: '1. Sear salmon skin-down 4 min.\n2. Flip, add butter and garlic.\n3. Baste 3 min.\n4. Roast asparagus alongside.' },
        { title: '🍷 Bruschetta Platter', desc: 'Elegant and effortless.', time: '15 Mins', ingredients: '1. Baguette\n2. Tomatoes\n3. Basil\n4. Garlic\n5. Balsamic', instructions: '1. Toast baguette slices.\n2. Mix diced tomatoes, basil, garlic.\n3. Spoon onto toasts.\n4. Drizzle balsamic.' },
        { title: '🍓 Chocolate Strawberries', desc: 'Classic romantic dessert.', time: '10 Mins', ingredients: '1. Strawberries\n2. Dark Chocolate\n3. White Chocolate', instructions: '1. Melt dark chocolate.\n2. Dip strawberries.\n3. Drizzle white chocolate.\n4. Chill until set.' }
    ]
};

function selectMood(el) {
    document.querySelectorAll('.mood-pill').forEach(p => p.classList.remove('selected'));
    el.classList.add('selected');
    const mood = el.dataset.mood;
    const list = moods[mood];
    if (!list) return;

    const dbMatches = recipes.filter(r => r.mood === mood);
    const emoji = { tired: '😴', happy: '😄', stressed: '😤', adventurous: '🤠', lazy: '🛋️', romantic: '💕' };

    let html = `<div class="mood-heading">
        <h3>Curated for ${emoji[mood] || ''} ${mood.charAt(0).toUpperCase() + mood.slice(1)}</h3>
    </div>`;

    html += '<div class="mood-grid">';
    html += list.map((r, i) => `<div class="mood-card" onclick='openMoodRecipe("${mood}",${i})'>
        <h6>${r.title}</h6><p>${r.desc}</p>
        <span class="badge-soft badge-time">${r.time}</span>
    </div>`).join('');

    dbMatches.forEach(r => {
        const idx = recipes.indexOf(r);
        html += `<div class="mood-card" onclick="openRecipe(${idx})">
            <h6>📌 ${esc(r.title)}</h6><p class="text-soft">From your collection</p>
            <span class="badge-soft badge-time">${esc(r.time)}</span>
        </div>`;
    });

    html += '</div>';
    document.getElementById('mood-recipes').innerHTML = html;
}

function openMoodRecipe(mood, i) { showRecipeModal(moods[mood][i]); }

// ── AI Chat ──
async function sendAI() {
    const input = document.getElementById('ai-input');
    const msg = input.value.trim();
    if (!msg) return;

    const box = document.getElementById('ai-messages');
    box.innerHTML += `<div class="msg msg-user"><div class="msg-avatar"><i class="bi bi-person-fill"></i></div><div class="msg-bubble">${esc(msg)}</div></div>`;
    input.value = '';
    scrollChat();

    const tid = 'typing-' + Date.now();
    box.innerHTML += `<div class="msg msg-bot typing" id="${tid}"><div class="msg-avatar"><i class="bi bi-robot"></i></div><div class="msg-bubble">Thinking</div></div>`;
    scrollChat();

    try {
        const res = await fetch(`${API}?action=ai`, {
            method: 'POST', headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ message: msg })
        });
        const d = await res.json();
        document.getElementById(tid)?.remove();
        addBot(d.error ? '⚠️ ' + d.error : formatAI(d.reply), d.error ? '' : d.reply);
    } catch (e) {
        document.getElementById(tid)?.remove();
        addBot('Could not reach the AI.');
    }
}

function addBot(html, rawText = '') {
    let saveBtn = '';
    if (rawText && rawText.toLowerCase().includes('ingredients') && rawText.toLowerCase().includes('instructions')) {
        saveBtn = `<br><button onclick="parseAndEditAI(this)" style="display:inline-flex; align-items:center; gap:6px; background:var(--rose-light); color:var(--rose); border:1px solid rgba(201,120,110,0.12); border-radius:20px; padding:6px 14px; font-size:.78rem; font-weight:600; margin-top:10px; cursor:pointer; transition:all var(--ease);"><i class="bi bi-save"></i> Save to Kitchen</button>`;
    }
    document.getElementById('ai-messages').innerHTML += `<div class="msg msg-bot" data-raw="${esc(rawText)}"><div class="msg-avatar"><i class="bi bi-robot"></i></div><div class="msg-bubble">${html}${saveBtn}</div></div>`;
    scrollChat();
}

function parseAndEditAI(btn) {
    const msgDiv = btn.closest('.msg-bot');
    const rawText = msgDiv.getAttribute('data-raw');
    if (!rawText) return;

    let title = '';
    let ingredients = '';
    let instructions = '';

    const titleMatch = rawText.match(/Title:\s*(.+)/i);
    if (titleMatch) title = titleMatch[1].trim();

    const ingMatch = rawText.match(/Ingredients:([\s\S]*?)(Instructions:|$)/i);
    if (ingMatch) {
        ingredients = ingMatch[1].trim()
            .split('\n')
            .map(line => line.replace(/^-\s*/, '• ').trim())
            .filter(line => line.length > 0)
            .join('\n');
    }

    const instMatch = rawText.match(/Instructions:([\s\S]*)/i);
    if (instMatch) {
        instructions = instMatch[1].trim()
            .split('\n')
            .map(line => line.trim())
            .filter(line => line.length > 0)
            .join('\n');
    }

    showTab('home', document.getElementById('nav-home'));

    if (title) document.getElementById('recipe-title').value = title;
    if (ingredients) document.getElementById('recipe-ingredients').value = ingredients;
    if (instructions) document.getElementById('recipe-body').value = instructions;
    document.getElementById('recipe-time').value = '30 Mins'; 

    popToast('Recipe loaded into Kitchen! Review and click Save.');
}

function scrollChat() {
    const el = document.getElementById('ai-messages');
    setTimeout(() => el.scrollTop = el.scrollHeight, 30);
}

function askSuggestion(chip) {
    document.getElementById('ai-input').value = chip.textContent.trim();
    sendAI();
}

function formatAI(text) {
    let h = esc(text);
    h = h.replace(/\*\*(.+?)\*\*/g, '<strong>$1</strong>');
    h = h.replace(/\n/g, '<br>');
    return h;
}

// ── Profile ──
async function loadProfile() {
    try {
        const res = await fetch(`${API}?action=get_profile`);
        const d = await res.json();
        // No longer updating sidebar avatar since it was removed
    } catch (e) { }
}

function openEditProfile() {
    fetch(`${API}?action=get_profile`).then(r => r.json()).then(d => {
        document.getElementById('profile-name').value = d.full_name || '';
        document.getElementById('profile-email').value = d.email || '';
        if (d.avatar) {
            const img = document.getElementById('profile-avatar-img');
            img.src = 'uploads/' + d.avatar;
            img.style.display = 'block';
            document.getElementById('profile-avatar-icon').style.display = 'none';
        }
        openModal('profileModal');
    });
}

function previewAvatar(input) {
    if (input.files && input.files[0]) {
        const reader = new FileReader();
        reader.onload = e => {
            const img = document.getElementById('profile-avatar-img');
            img.src = e.target.result;
            img.style.display = 'block';
            document.getElementById('profile-avatar-icon').style.display = 'none';
        };
        reader.readAsDataURL(input.files[0]);
    }
}

async function saveProfile() {
    const fd = new FormData();
    fd.append('name', document.getElementById('profile-name').value);
    fd.append('email', document.getElementById('profile-email').value);
    const fileInput = document.getElementById('avatar-input');
    if (fileInput.files[0]) fd.append('avatar', fileInput.files[0]);

    try {
        const res = await fetch(`${API}?action=update_profile`, { method: 'POST', body: fd });
        const d = await res.json();
        if (d.success) {
            popToast('Profile updated');
            closeModal('profileModal');
        } else popToast(d.error || 'Update failed');
    } catch (e) { popToast('Network error'); }
}

async function deleteAccount() {
    if (!confirm('Delete your account permanently? This cannot be undone.')) return;
    try {
        const res = await fetch(`${API}?action=delete_account`);
        const d = await res.json();
        if (d.success) window.location.href = 'login.php';
    } catch (e) { popToast('Error'); }
}

function openAbout() { openModal('aboutModal'); }

// ── Helpers ──
function popToast(msg) {
    const el = document.getElementById('toast');
    el.textContent = msg;
    el.classList.add('show');
    setTimeout(() => el.classList.remove('show'), 2500);
}

function esc(s) {
    if (!s) return '';
    const d = document.createElement('div');
    d.textContent = s;
    return d.innerHTML;
}