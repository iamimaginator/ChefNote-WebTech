<?php
session_start();
if (!isset($_SESSION['user_id'])) { header('Location: login.php'); exit; }
$user = htmlspecialchars($_SESSION['user_name'] ?? 'Chef');
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ChefNote | SP24-BCS-061</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
    <link rel="stylesheet" href="style.css">
</head>
<body onload="initApp()">

<!-- Sidebar -->
<aside class="sidebar" id="sidebar">
    <div class="sidebar-brand">
        <img src="logo.png" alt="ChefNote" class="brand-logo">
        <div>
            <div class="brand-name">ChefNote</div>
            <div class="brand-tagline">Premium Kitchen</div>
        </div>
    </div>

    <nav class="sidebar-nav">
        <a class="nav-item active" href="#" onclick="showTab('home',this);return false" id="nav-home">
            <i class="bi bi-fire"></i> Kitchen
        </a>
        <a class="nav-item" href="#" onclick="showTab('vault',this);return false" id="nav-vault">
            <i class="bi bi-journal-text"></i> Recipes
        </a>
        <a class="nav-item" href="#" onclick="showTab('mood-lab',this);return false" id="nav-mood">
            <i class="bi bi-emoji-smile"></i> Mood Lab
        </a>
        <a class="nav-item" href="#" onclick="showTab('ai-chat',this);return false" id="nav-ai">
            <i class="bi bi-robot"></i> AI Chef
        </a>
    </nav>



    <div class="sidebar-footer">
        <div class="settings-dropup" id="settingsDropup">
            <a class="nav-item" href="#" onclick="toggleSettings(event)">
                <i class="bi bi-gear"></i> Settings
            </a>
            <div class="dropup-menu" id="settingsMenu">
                <a class="dropup-item" href="#" onclick="openEditProfile();closeSettings();return false">
                    <i class="bi bi-pencil-square"></i> Edit Profile
                </a>
                <a class="dropup-item" href="#" onclick="openAbout();closeSettings();return false">
                    <i class="bi bi-info-circle"></i> About
                </a>
                <div class="dropup-item dropup-toggle-row" onclick="toggleTheme(event)">
                    <span><i class="bi bi-moon-fill" id="theme-icon"></i> Dark Mode</span>
                    <div class="toggle-switch" id="theme-toggle"><div class="toggle-knob"></div></div>
                </div>
                <hr class="dropup-divider">
                <a class="dropup-item dropup-danger" href="logout.php">
                    <i class="bi bi-box-arrow-right"></i> Sign Out
                </a>
            </div>
        </div>
    </div>
</aside>

<!-- Mobile Toggle -->
<button class="sidebar-toggle" onclick="toggleSidebar()"><i class="bi bi-list"></i></button>
<div class="sidebar-backdrop" id="sidebarBackdrop" onclick="toggleSidebar()"></div>

<!-- Content -->
<main class="content">

    <!-- Kitchen Counter -->
    <section id="home" class="tab active">
        <input type="text" id="recipe-title" class="kitchen-title-input" placeholder="Recipe Name…">
        <div class="kitchen-layout">
            <div class="kitchen-editors">
                <div class="panel">
                    <h6 class="panel-label panel-label-rose"><i class="bi bi-journal-text"></i> Ingredients</h6>
                    <textarea id="recipe-ingredients" class="notebook" placeholder="• 2 cups flour&#10;• 1 tsp salt…" rows="8"></textarea>
                </div>
                <div class="panel">
                    <h6 class="panel-label panel-label-rose"><i class="bi bi-list-ol"></i> Instructions</h6>
                    <textarea id="recipe-body" class="notebook" placeholder="1. Preheat oven to 350°F.&#10;2. Mix dry ingredients…" rows="8"></textarea>
                </div>
            </div>
            <div class="kitchen-meta">
                <div class="panel">
                    <div class="meta-row">
                        <div>
                            <label class="field-label">Est. Time</label>
                            <input type="text" id="recipe-time" class="field" placeholder="45 mins">
                        </div>
                        <div>
                            <label class="field-label">Mood Tag</label>
                            <select id="recipe-mood" class="field">
                                <option value="">— Select —</option>
                                <option value="tired">😴 Tired</option>
                                <option value="happy">😄 Happy</option>
                                <option value="stressed">😤 Stressed</option>
                                <option value="adventurous">🤠 Adventurous</option>
                                <option value="lazy">🛋️ Lazy</option>
                                <option value="romantic">💕 Romantic</option>
                            </select>
                        </div>
                    </div>
                    <button class="btn-rose" onclick="saveRecipe()"><i class="bi bi-save"></i> Save Recipe</button>
                </div>
                <div class="panel">
                    <h6 class="panel-label"><i class="bi bi-clock-history"></i> Recent Drafts</h6>
                    <div id="mini-list"></div>
                </div>
            </div>
        </div>
    </section>

    <!-- Recipes -->
    <section id="vault" class="tab">
        <h2 class="page-title">Saved Recipes</h2>
        <p class="page-sub">Tap any card to see the full recipe</p>
        <div class="recipe-grid" id="recipe-grid"></div>
    </section>

    <!-- Mood Lab -->
    <section id="mood-lab" class="tab">
        <h2 class="page-title">Mood Lab</h2>
        <p class="page-sub">Select a mood to dynamically filter recipes and generate new culinary ideas tailored to your current state of mind.</p>
        <div class="mood-bar">
            <button class="mood-pill" data-mood="tired" onclick="selectMood(this)">😴 Tired</button>
            <button class="mood-pill" data-mood="happy" onclick="selectMood(this)">😄 Happy</button>
            <button class="mood-pill" data-mood="stressed" onclick="selectMood(this)">😤 Stressed</button>
            <button class="mood-pill" data-mood="adventurous" onclick="selectMood(this)">🤠 Adventurous</button>
            <button class="mood-pill" data-mood="lazy" onclick="selectMood(this)">🛋️ Lazy</button>
            <button class="mood-pill" data-mood="romantic" onclick="selectMood(this)">💕 Romantic</button>
        </div>
        <div id="mood-recipes"></div>
    </section>

    <!-- AI Chef -->
    <section id="ai-chat" class="tab">
        <h2 class="page-title">AI Chef</h2>
        <p class="page-sub">Powered by Groq — ask anything about cooking</p>
        <div class="ai-layout">
            <div class="panel ai-box">
                <div id="ai-messages" class="ai-messages">
                    <div class="msg msg-bot"><div class="msg-avatar"><i class="bi bi-robot"></i></div><div class="msg-bubble">Welcome to your culinary command center. I'm ready to assist with menu planning, flavor pairing, or executing complex techniques. What are we creating today?</div></div>
                </div>
                <div class="ai-bar">
                    <input type="text" id="ai-input" class="field" placeholder="Ask for recipes, techniques, or pairings…" onkeydown="if(event.key==='Enter')sendAI()">
                    <button class="btn-send" onclick="sendAI()"><i class="bi bi-send-fill"></i></button>
                </div>
            </div>
            <div class="ai-sidebar">
                <div class="panel">
                    <h6 class="panel-label">✨ AI Tips & Suggestions</h6>
                    <div class="chip" onclick="askSuggestion(this)">Quick pizza dough recipe</div>
                    <div class="chip" onclick="askSuggestion(this)">Healthy lunch ideas under 500 cal</div>
                    <div class="chip" onclick="askSuggestion(this)">Substitute for heavy cream</div>
                    <div class="chip" onclick="askSuggestion(this)">Wine pairing for ribeye steak</div>
                </div>

            </div>
        </div>
    </section>

</main>

<!-- Recipe Modal -->
<div class="modal-overlay" id="recipeModal" onclick="if(event.target===this)closeModal('recipeModal')">
    <div class="modal-box modal-lg">
        <div class="modal-head">
            <h5 id="recipeModalLabel"></h5>
            <div class="modal-actions">
                <button class="modal-btn modal-btn-warn" id="modal-edit-btn"><i class="bi bi-pencil"></i></button>
                <button class="modal-btn modal-btn-danger" id="modal-delete-btn"><i class="bi bi-trash"></i></button>
                <button class="modal-btn" onclick="closeModal('recipeModal')"><i class="bi bi-x-lg"></i></button>
            </div>
        </div>
        <div class="modal-body">
            <div class="modal-grid">
                <div>
                    <div class="modal-time-badge"><i class="bi bi-stopwatch"></i> <span id="modal-time">—</span></div>
                    <div id="modal-mood-badge" class="mt-1"></div>
                    <h6 class="modal-section-label mt-2">Ingredients</h6>
                    <div id="modal-ingredients"></div>
                </div>
                <div>
                    <h6 class="modal-section-label">Instructions</h6>
                    <div id="modal-instructions"></div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Edit Profile Modal -->
<div class="modal-overlay" id="profileModal" onclick="if(event.target===this)closeModal('profileModal')">
    <div class="modal-box">
        <div class="modal-head">
            <h5>Edit Profile</h5>
            <div class="modal-actions">
                <button class="modal-btn" onclick="closeModal('profileModal')"><i class="bi bi-x-lg"></i></button>
            </div>
        </div>
        <div class="modal-body">
            <div class="text-center mb-3">
                <div class="profile-avatar-wrap" onclick="document.getElementById('avatar-input').click()">
                    <img id="profile-avatar-img" src="" class="profile-avatar" style="display:none">
                    <i class="bi bi-person-circle" id="profile-avatar-icon" style="font-size:3.5rem;opacity:.3;color:var(--text-soft)"></i>
                    <div class="profile-avatar-overlay"><i class="bi bi-camera"></i></div>
                </div>
                <input type="file" id="avatar-input" accept="image/*" style="display:none" onchange="previewAvatar(this)">
            </div>
            <div class="mb-3">
                <label class="field-label">Name</label>
                <input type="text" id="profile-name" class="field">
            </div>
            <div class="mb-3">
                <label class="field-label">Email</label>
                <input type="email" id="profile-email" class="field">
            </div>
            <button class="btn-rose mb-2" onclick="saveProfile()">Save Changes</button>
            <button class="btn-danger w-full" onclick="deleteAccount()">Delete Account</button>
        </div>
    </div>
</div>

<!-- About Modal -->
<div class="modal-overlay" id="aboutModal" onclick="if(event.target===this)closeModal('aboutModal')">
    <div class="modal-box" style="max-width:360px">
        <div class="modal-body text-center" style="padding:32px">
            <div style="font-size:2.5rem">🍳</div>
            <h4 style="font-weight:700;margin-top:10px">ChefNote</h4>
            <p class="text-soft" style="margin-bottom:18px">Your AI-powered recipe manager</p>
            <hr style="border-color:var(--border);margin:16px 0">
            <p style="font-weight:600;margin-bottom:4px">Muhammad Saad Khalid</p>
            <p class="text-soft" style="margin-bottom:2px">SP24-BCS-061</p>
            <p class="text-soft" style="font-size:.8rem">Web Technologies Semester Project</p>
            <hr style="border-color:var(--border);margin:16px 0">
            <p class="text-soft" style="font-size:.78rem;margin:0">Built with PHP · MySQL · Vanilla CSS · Groq AI</p>
        </div>
    </div>
</div>

<div id="toast" class="toast-msg"></div>
<script src="script.js"></script>
</body>
</html>
