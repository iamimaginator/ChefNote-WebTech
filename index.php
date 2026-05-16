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
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Indie+Flower&family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="style.css">
</head>
<body onload="initApp()">

<nav class="navbar navbar-expand-lg sticky-top" id="topnav">
    <div class="container">
        <a class="navbar-brand" href="#" onclick="showTab('home',document.getElementById('nav-home'));return false">🍳 Chef<span class="text-accent">Note</span></a>
        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navMenu"><i class="bi bi-list"></i></button>
        <div class="collapse navbar-collapse" id="navMenu">
            <ul class="navbar-nav ms-auto gap-1" id="nav-tabs">
                <li class="nav-item"><a class="nav-link active" href="#" onclick="showTab('home',this)" id="nav-home">Kitchen</a></li>
                <li class="nav-item"><a class="nav-link" href="#" onclick="showTab('vault',this)" id="nav-vault">Recipes</a></li>
                <li class="nav-item"><a class="nav-link" href="#" onclick="showTab('mood-lab',this)" id="nav-mood">Mood Lab</a></li>
                <li class="nav-item"><a class="nav-link" href="#" onclick="showTab('ai-chat',this)" id="nav-ai"><i class="bi bi-stars me-1"></i>AI Chef</a></li>
            </ul>
            <div class="dropdown ms-lg-3">
                <button class="btn btn-user dropdown-toggle" data-bs-toggle="dropdown">
                    <img id="nav-avatar" src="" class="nav-avatar" style="display:none" alt="">
                    <i class="bi bi-person-circle me-1" id="nav-avatar-icon"></i>
                    <span id="nav-username"><?=$user?></span>
                </button>
                <ul class="dropdown-menu dropdown-menu-end" id="user-dropdown">
                    <li><a class="dropdown-item" href="#" onclick="openEditProfile()"><i class="bi bi-pencil-square me-2"></i>Edit Profile</a></li>
                    <li><a class="dropdown-item" href="#" onclick="openAbout()"><i class="bi bi-info-circle me-2"></i>About</a></li>
                    <li><div class="dropdown-item d-flex align-items-center justify-content-between">
                        Theme <button class="btn btn-sm btn-theme-toggle" onclick="toggleTheme(event)"><i class="bi bi-moon-fill" id="theme-icon"></i></button>
                    </div></li>
                    <li><hr class="dropdown-divider"></li>
                    <li><a class="dropdown-item" href="logout.php"><i class="bi bi-box-arrow-right me-2"></i>Sign Out</a></li>
                </ul>
            </div>
        </div>
    </div>
</nav>

<main class="container py-4">

    <!-- Kitchen -->
    <section id="home" class="tab active">
        <h2 class="page-title">Kitchen Counter</h2>
        <p class="page-sub">Write and save your recipes</p>
        <div class="row g-3">
            <div class="col-lg-3">
                <div class="panel panel-mint">
                    <h6 class="panel-label">Ingredients</h6>
                    <textarea id="recipe-ingredients" class="notebook" placeholder="Start typing… (auto-numbered)" rows="15"></textarea>
                </div>
            </div>
            <div class="col-lg-6">
                <div class="panel panel-rose">
                    <input type="text" id="recipe-title" class="notebook notebook-title" placeholder="Recipe Name…">
                    <textarea id="recipe-body" class="notebook" placeholder="Start typing steps… (auto-numbered)" rows="13"></textarea>
                </div>
            </div>
            <div class="col-lg-3">
                <div class="panel mb-3">
                    <label class="panel-label">Time</label>
                    <input type="text" id="recipe-time" class="field" placeholder="e.g. 30">
                </div>
                <div class="panel mb-3">
                    <label class="panel-label">Mood Tag</label>
                    <select id="recipe-mood" class="field">
                        <option value="">— Select mood —</option>
                        <option value="tired">😴 Tired</option>
                        <option value="happy">😄 Happy</option>
                        <option value="stressed">😤 Stressed</option>
                        <option value="adventurous">🤠 Adventurous</option>
                        <option value="lazy">🛋️ Lazy</option>
                        <option value="romantic">💕 Romantic</option>
                    </select>
                </div>
                <button class="btn-primary-custom w-100 mb-3" onclick="saveRecipe()">Save Recipe</button>
                <div class="panel">
                    <h6 class="panel-label">Recent</h6>
                    <div id="mini-list"></div>
                </div>
            </div>
        </div>
    </section>

    <!-- Recipes -->
    <section id="vault" class="tab">
        <h2 class="page-title">Recipes</h2>
        <p class="page-sub">Tap any card to see the full recipe</p>
        <div class="row g-3" id="recipe-grid"></div>
    </section>

    <!-- Mood Lab -->
    <section id="mood-lab" class="tab">
        <h2 class="page-title">Mood Lab</h2>
        <p class="page-sub">How are you feeling? Pick one.</p>
        <div class="mood-bar">
            <button class="mood-pill" data-mood="tired" onclick="selectMood(this)">😴 Tired</button>
            <button class="mood-pill" data-mood="happy" onclick="selectMood(this)">😄 Happy</button>
            <button class="mood-pill" data-mood="stressed" onclick="selectMood(this)">😤 Stressed</button>
            <button class="mood-pill" data-mood="adventurous" onclick="selectMood(this)">🤠 Adventurous</button>
            <button class="mood-pill" data-mood="lazy" onclick="selectMood(this)">🛋️ Lazy</button>
            <button class="mood-pill" data-mood="romantic" onclick="selectMood(this)">💕 Romantic</button>
        </div>
        <div id="mood-recipes" class="mt-4"></div>
    </section>

    <!-- AI Chef -->
    <section id="ai-chat" class="tab">
        <h2 class="page-title">AI Chef</h2>
        <p class="page-sub">Powered by Groq — ask anything about cooking</p>
        <div class="ai-layout">
            <div class="panel ai-box">
                <div id="ai-messages" class="ai-messages">
                    <div class="msg msg-bot"><div class="msg-avatar">🤖</div><div class="msg-bubble">Hi <?=$user?>! Ask me anything — recipes, tips, substitutions, or meal ideas.</div></div>
                </div>
                <div class="ai-bar">
                    <input type="text" id="ai-input" class="field" placeholder="Ask something…" onkeydown="if(event.key==='Enter')sendAI()">
                    <button class="btn-primary-custom btn-send" onclick="sendAI()"><i class="bi bi-send-fill"></i></button>
                </div>
            </div>
            <div class="panel ai-tips">
                <h6 class="panel-label">Try asking</h6>
                <div class="chip" onclick="askSuggestion(this)">🍕 Quick pizza recipe</div>
                <div class="chip" onclick="askSuggestion(this)">🥗 Healthy lunch under 30 min</div>
                <div class="chip" onclick="askSuggestion(this)">🍰 Easy dessert for beginners</div>
                <div class="chip" onclick="askSuggestion(this)">🌶️ Substitute for chili flakes?</div>
            </div>
        </div>
    </section>
</main>

<!-- Recipe Modal -->
<div class="modal fade" id="recipeModal" tabindex="-1">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content modal-styled">
            <div class="modal-header border-0 d-flex justify-content-between align-items-center">
                <h5 class="modal-title fw-bold" id="recipeModalLabel"></h5>
                <div>
                    <!-- Dynamic control actions -->
                    <button type="button" class="btn btn-sm btn-outline-warning me-1" id="modal-edit-btn"><i class="bi bi-pencil"></i></button>
                    <button type="button" class="btn btn-sm btn-outline-danger me-2" id="modal-delete-btn"><i class="bi bi-trash"></i></button>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
            </div>
            <div class="modal-body">
                <div class="row g-4">
                    <div class="col-md-4">
                        <div class="modal-time-badge"><i class="bi bi-stopwatch me-1"></i><span id="modal-time">—</span></div>
                        <div id="modal-mood-badge" class="mt-2"></div>
                        <h6 class="modal-section-label mt-3">Ingredients</h6>
                        <div id="modal-ingredients"></div>
                    </div>
                    <div class="col-md-8">
                        <h6 class="modal-section-label">Instructions</h6>
                        <div id="modal-instructions"></div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Edit Profile Modal -->
<div class="modal fade" id="profileModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered modal-sm">
        <div class="modal-content modal-styled">
            <div class="modal-header border-0">
                <h5 class="modal-title fw-bold">Edit Profile</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div class="text-center mb-3">
                    <div class="profile-avatar-wrap" onclick="document.getElementById('avatar-input').click()">
                        <img id="profile-avatar-img" src="" class="profile-avatar" style="display:none">
                        <i class="bi bi-person-circle profile-avatar-placeholder" id="profile-avatar-icon" style="font-size:4rem;opacity:.4"></i>
                        <div class="profile-avatar-overlay"><i class="bi bi-camera"></i></div>
                    </div>
                    <input type="file" id="avatar-input" accept="image/*" style="display:none" onchange="previewAvatar(this)">
                </div>
                <div class="mb-3">
                    <label class="panel-label">Name</label>
                    <input type="text" id="profile-name" class="field">
                </div>
                <div class="mb-3">
                    <label class="panel-label">Email</label>
                    <input type="email" id="profile-email" class="field">
                </div>
                <button class="btn-primary-custom w-100 mb-2" onclick="saveProfile()">Save Changes</button>
                <button class="btn-danger-custom w-100" onclick="deleteAccount()">Delete Account</button>
            </div>
        </div>
    </div>
</div>

<!-- About Modal -->
<div class="modal fade" id="aboutModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered modal-sm">
        <div class="modal-content modal-styled text-center">
            <div class="modal-body py-4">
                <div style="font-size:2.5rem">🍳</div>
                <h4 class="fw-bold mt-2">ChefNote</h4>
                <p class="text-soft mb-3">Your AI-powered recipe manager</p>
                <hr class="my-3" style="opacity:.1">
                <p class="fw-600 mb-1">Muhammad Saad Khalid</p>
                <p class="text-soft mb-1">SP24-BCS-061</p>
                <p class="text-soft small">Web Technologies Semester Project</p>
                <hr class="my-3" style="opacity:.1">
                <p class="text-soft small mb-0">Built with PHP · MySQL · Bootstrap 5 · Groq AI</p>
            </div>
        </div>
    </div>
</div>

<div id="toast" class="toast-msg"></div>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script src="script.js"></script>
</body>
</html>
