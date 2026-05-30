<?php
header('Content-Type: application/json; charset=UTF-8');
require_once 'db.php';

// Enforce login for all operational actions
if (!isset($_SESSION['user_id'])) { 
    http_response_code(401); 
    echo json_encode(['error' => 'Unauthorized access. Please log in.']); 
    exit; 
}

$uid = $_SESSION['user_id'];

// Environment Config (Move key here out of the switch block)
$GROQ_KEY = 'YOUR API HERE'; 
$action = $_GET['action'] ?? '';

switch ($action) {

    case 'create':
        // Now using $_POST since we are sending FormData
        $t = trim($_POST['title'] ?? '');
        $g = trim($_POST['ingredients'] ?? '');
        $s = trim($_POST['instructions'] ?? '');
        $m = trim($_POST['time'] ?? '');
        $mood = trim($_POST['mood'] ?? '');

        if ($m && !preg_match('/min/i', $m)) $m .= ' Mins';
        if (!$t || !$m) { http_response_code(400); echo json_encode(['error'=>'Title and time required.']); exit; }

        // --- NEW IMAGE UPLOAD LOGIC ---
        $image_url = null;
        if (isset($_FILES['cover_image']) && $_FILES['cover_image']['error'] === UPLOAD_ERR_OK) {
            $ext = strtolower(pathinfo($_FILES['cover_image']['name'], PATHINFO_EXTENSION));
            if (in_array($ext, ['jpg','jpeg','png','webp'])) {
                $mime = mime_content_type($_FILES['cover_image']['tmp_name']);
                if (strpos($mime, 'image/') === 0) {
                    if (!is_dir('uploads/recipes')) mkdir('uploads/recipes', 0755, true);
                    $fname = 'recipe_'.$uid.'_'.time().'.'.$ext;
                    $dest = 'uploads/recipes/'.$fname;
                    if (move_uploaded_file($_FILES['cover_image']['tmp_name'], $dest)) {
                        $image_url = $dest;
                    }
                }
            }
        }
        // ------------------------------

        // Securely bound to current user (Now includes image_url)
        $q = $conn->prepare("INSERT INTO recipes (user_id, title, ingredients, instructions, time, mood, image_url) VALUES (?, ?, ?, ?, ?, ?, ?)");
        $q->bind_param('issssss', $uid, $t, $g, $s, $m, $mood, $image_url);
        echo json_encode($q->execute() ? ['success'=>true, 'id'=>$q->insert_id] : ['error'=>'Save failed.']);
        $q->close();
        break;

    case 'read':
        // Isolated selection criteria
        $q = $conn->prepare("SELECT * FROM recipes WHERE user_id = ? ORDER BY created_at DESC");
        $q->bind_param('i', $uid);
        $q->execute();
        $r = $q->get_result();
        $out = [];
        while ($row = $r->fetch_assoc()) $out[] = $row;
        echo json_encode($out);
        $q->close();
        break;

    case 'update_recipe':
        // Now using $_POST since we are sending FormData
        $id = intval($_POST['id'] ?? 0);
        $t = trim($_POST['title'] ?? '');
        $g = trim($_POST['ingredients'] ?? '');
        $s = trim($_POST['instructions'] ?? '');
        $m = trim($_POST['time'] ?? '');
        $mood = trim($_POST['mood'] ?? '');

        if ($m && !preg_match('/min/i', $m)) $m .= ' Mins';
        if (!$id || !$t || !$m) { http_response_code(400); echo json_encode(['error'=>'Missing data.']); exit; }

        // --- NEW IMAGE UPLOAD LOGIC ---
        $image_url = null;
        $image_update_sql = ""; // Only update image if a new one is uploaded
        
        if (isset($_FILES['cover_image']) && $_FILES['cover_image']['error'] === UPLOAD_ERR_OK) {
            $ext = strtolower(pathinfo($_FILES['cover_image']['name'], PATHINFO_EXTENSION));
            if (in_array($ext, ['jpg','jpeg','png','webp'])) {
                $mime = mime_content_type($_FILES['cover_image']['tmp_name']);
                if (strpos($mime, 'image/') === 0) {
                    if (!is_dir('uploads/recipes')) mkdir('uploads/recipes', 0755, true);
                    $fname = 'recipe_'.$uid.'_'.time().'.'.$ext;
                    $dest = 'uploads/recipes/'.$fname;
                    if (move_uploaded_file($_FILES['cover_image']['tmp_name'], $dest)) {
                        $image_url = $dest;
                        $image_update_sql = ", image_url=?";
                    }
                }
            }
        }
        // ------------------------------

        // Verifies the recipe belongs to this user before updating
        if ($image_url) {
            $q = $conn->prepare("UPDATE recipes SET title=?, ingredients=?, instructions=?, time=?, mood=? $image_update_sql WHERE id=? AND user_id=?");
            $q->bind_param('ssssssii', $t, $g, $s, $m, $mood, $image_url, $id, $uid);
        } else {
            $q = $conn->prepare("UPDATE recipes SET title=?, ingredients=?, instructions=?, time=?, mood=? WHERE id=? AND user_id=?");
            $q->bind_param('sssssii', $t, $g, $s, $m, $mood, $id, $uid);
        }
        
        echo json_encode($q->execute() ? ['success'=>true] : ['error'=>'Update failed.']);
        $q->close();
        break;

    case 'delete_recipe':
        $id = intval($_GET['id'] ?? 0);
        if (!$id) { http_response_code(400); echo json_encode(['error'=>'Invalid ID']); exit; }

        // Verifies ownership before removing
        $q = $conn->prepare("DELETE FROM recipes WHERE id=? AND user_id=?");
        $q->bind_param('ii', $id, $uid);
        echo json_encode($q->execute() ? ['success'=>true] : ['error'=>'Delete failed.']);
        $q->close();
        break;

    case 'toggle_favorite':
        $id = intval($_GET['id'] ?? 0);
        if (!$id) { http_response_code(400); echo json_encode(['error'=>'Invalid ID']); exit; }
        
        $q = $conn->prepare("UPDATE recipes SET is_favorite = NOT is_favorite WHERE id=? AND user_id=?");
        $q->bind_param('ii', $id, $uid);
        echo json_encode($q->execute() ? ['success'=>true] : ['error'=>'Update failed.']);
        $q->close();
        break;

    case 'ai':
        $in = json_decode(file_get_contents('php://input'), true);
        $msg = trim($in['message'] ?? '');
        if (!$msg) { http_response_code(400); echo json_encode(['error'=>'Message required.']); exit; }

        $payload = json_encode([
            'model' => 'llama-3.3-70b-versatile',
            'messages' => [
                ['role'=>'system','content'=>'You are ChefNote AI, a friendly cooking assistant. Give concise, practical advice. CRITICAL: When providing a recipe, use this exact format to allow automatic saving:\nTitle: [Recipe Name]\nIngredients:\n- [Item 1]\n- [Item 2]\nInstructions:\n1. [Step 1]\n2. [Step 2]'],
                ['role'=>'user','content'=>$msg]
            ],
            'temperature' => 0.7,
            'max_tokens' => 1024
        ]);

        $ch = curl_init('https://api.groq.com/openai/v1/chat/completions');
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => $payload,
            CURLOPT_HTTPHEADER => ['Content-Type: application/json', 'Authorization: Bearer '.$GROQ_KEY],
            CURLOPT_TIMEOUT => 30,
            CURLOPT_SSL_VERIFYPEER => false
        ]);

        $res = curl_exec($ch);
        $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $err = curl_error($ch);
        curl_close($ch);

        if ($err) { http_response_code(500); echo json_encode(['error'=>'API unreachable: '.$err]); exit; }
        $data = json_decode($res, true);
        if ($code !== 200) { http_response_code($code); echo json_encode(['error'=>$data['error']['message'] ?? 'Groq error']); exit; }
        echo json_encode(['reply' => $data['choices'][0]['message']['content'] ?? 'No response.']);
        break;

    case 'update_profile':
        // Secure image upload logic
        if (isset($_FILES['avatar']) && $_FILES['avatar']['error'] === UPLOAD_ERR_OK) {
            $ext = strtolower(pathinfo($_FILES['avatar']['name'], PATHINFO_EXTENSION));
            if (!in_array($ext, ['jpg','jpeg','png','gif','webp'])) {
                echo json_encode(['error'=>'Invalid image format.']); exit;
            }
            
            // Validate actual file type content signature
            $mime = mime_content_type($_FILES['avatar']['tmp_name']);
            if (strpos($mime, 'image/') !== 0) {
                echo json_encode(['error'=>'File content is not a real image.']); exit;
            }

            if (!is_dir('uploads')) mkdir('uploads', 0755, true);
            $fname = 'avatar_'.$uid.'_'.time().'.'.$ext;
            move_uploaded_file($_FILES['avatar']['tmp_name'], 'uploads/'.$fname);
            
            $q = $conn->prepare("UPDATE users SET avatar=? WHERE id=?");
            $q->bind_param('si', $fname, $uid);
            $q->execute();
            $q->close();
        }

        $name = trim($_POST['name'] ?? '');
        $email = trim($_POST['email'] ?? '');
        if ($name && $email) {
            $q = $conn->prepare("UPDATE users SET full_name=?, email=? WHERE id=?");
            $q->bind_param('ssi', $name, $email, $uid);
            $q->execute();
            $q->close();
            $_SESSION['user_name'] = $name;
        }

        $q = $conn->prepare("SELECT full_name, email, avatar FROM users WHERE id=?");
        $q->bind_param('i', $uid); $q->execute();
        $user = $q->get_result()->fetch_assoc();
        echo json_encode(['success'=>true, 'user'=>$user]);
        $q->close();
        break;

    case 'get_profile':
        $q = $conn->prepare("SELECT full_name, email, avatar FROM users WHERE id=?");
        $q->bind_param('i', $uid); $q->execute();
        echo json_encode($q->get_result()->fetch_assoc());
        $q->close();
        break;

    case 'delete_account':
        $q = $conn->prepare("DELETE FROM users WHERE id=?");
        $q->bind_param('i', $uid);
        $q->execute(); $q->close();
        session_destroy();
        echo json_encode(['success'=>true]);
        break;

    case 'seed':
        // Verify current user count
        $q = $conn->prepare("SELECT COUNT(*) as n FROM recipes WHERE user_id=?");
        $q->bind_param('i', $uid); $q->execute();
        $c = $q->get_result()->fetch_assoc()['n'];
        $q->close();

        if ($c >= 6) { echo json_encode(['success'=>true,'count'=>$c]); break; }

        $recipes = [
            ['Classic Spaghetti Carbonara', "1. Spaghetti\n2. Guanciale\n3. Egg Yolks\n4. Pecorino Romano\n5. Black Pepper\n6. Salt", "1. Cook spaghetti al dente.\n2. Crisp guanciale strips in a pan.\n3. Whisk yolks with grated Pecorino.\n4. Toss hot pasta with guanciale (off heat).\n5. Pour egg mixture over, toss quickly.\n6. Add pasta water for creaminess.\n7. Serve with extra pepper.", '25 Mins', 'happy'],
            ['Chicken Tikka Masala', "1. Chicken Breast\n2. Yogurt\n3. Tikka Paste\n4. Canned Tomatoes\n5. Heavy Cream\n6. Onion\n7. Garlic\n8. Ginger\n9. Cilantro\n10. Butter", "1. Marinate chicken in yogurt and tikka paste.\n2. Grill until charred.\n3. Sauté onion, garlic, ginger in butter.\n4. Add tomatoes, simmer 15 min, blend.\n5. Add cream and chicken, simmer 10 min.\n6. Serve with rice or naan.", '50 Mins', 'adventurous'],
            ['Avocado Toast Supreme', "1. Sourdough Bread\n2. Avocado\n3. Eggs\n4. Lemon Juice\n5. Chili Flakes\n6. Sea Salt\n7. Olive Oil", "1. Toast sourdough.\n2. Mash avocado with lemon, salt, pepper.\n3. Poach eggs 3 minutes.\n4. Spread avocado on toast, top with egg.\n5. Drizzle oil, add chili flakes.", '15 Mins', 'tired'],
            ['Japanese Beef Ramen', "1. Ramen Noodles\n2. Beef Slices\n3. Bone Broth\n4. Soft-Boiled Eggs\n5. Soy Sauce\n6. Miso Paste\n7. Green Onions\n8. Nori\n9. Sesame Oil", "1. Soft-boil eggs 6.5 min, marinate in soy.\n2. Sear beef in sesame oil.\n3. Simmer broth with soy, miso, garlic, ginger.\n4. Cook noodles separately.\n5. Assemble: noodles, broth, beef, egg, nori.", '40 Mins', 'stressed'],
            ['Chocolate Lava Cake', "1. Dark Chocolate\n2. Butter\n3. Eggs\n4. Sugar\n5. Flour\n6. Vanilla\n7. Cocoa Powder", "1. Preheat 220°C. Grease ramekins with cocoa.\n2. Melt chocolate and butter together.\n3. Whisk eggs, sugar, vanilla until pale.\n4. Fold in chocolate, then flour.\n5. Bake 12-14 min (center should jiggle).\n6. Invert onto plates, serve with ice cream.", '30 Mins', 'romantic'],
            ['Greek Salad Bowl', "1. Tomatoes\n2. Cucumber\n3. Red Onion\n4. Feta Cheese\n5. Olives\n6. Bell Pepper\n7. Olive Oil\n8. Oregano", "1. Chop tomatoes, cucumber, pepper, onion.\n2. Combine in bowl with olives.\n3. Dress with olive oil, vinegar, oregano.\n4. Top with a slab of feta.\n5. Serve with bread.", '10 Mins', 'lazy']
        ];

        $q = $conn->prepare("INSERT INTO recipes (user_id, title, ingredients, instructions, time, mood) VALUES (?, ?, ?, ?, ?, ?)");
        foreach ($recipes as $r) { 
            $q->bind_param('isssss', $uid, $r[0], $r[1], $r[2], $r[3], $r[4]); 
            $q->execute(); 
        }
        $q->close();
        echo json_encode(['success'=>true]);
        break;

    default:
        http_response_code(400);
        echo json_encode(['error'=>'Invalid action.']);
}

$conn->close();