# 🍳 ChefNote

![ChefNote Banner](https://img.shields.io/badge/Status-Active-success) ![Version](https://img.shields.io/badge/Version-1.0.0-blue) ![PHP](https://img.shields.io/badge/PHP-Backend-777BB4?logo=php&logoColor=white) ![MySQL](https://img.shields.io/badge/MySQL-Database-4479A1?logo=mysql&logoColor=white) ![Groq](https://img.shields.io/badge/Groq-AI_Integration-f55036)

**ChefNote** is a full-stack, AI-powered recipe management application. Engineered with a modular backend and a custom "Culinary Noir" interface, it provides a seamless environment for writing, storing, and discovering culinary creations. 

The system is built on a **local-first architecture**, ensuring that core recipe creation, editing, and database storage remain highly resilient and fully functional independent of external web APIs, which are utilized strictly as progressive enhancements for AI features.

---

## ✨ Key Features

### 🏗️ Local-First Reliability
* **Resilient Core:** Recipe drafting, saving, and vault retrieval prioritize local database stability before integrating cloud-based features.
* **Smart Notebook:** Custom JavaScript logic provides auto-capitalization and sequential auto-numbering inside the ingredient and instruction text areas.

### 🎨 The "Culinary Noir" UI
* **Custom Aesthetics:** A responsive, sleek interface built entirely with Vanilla CSS (no heavy frontend frameworks required).
* **Seamless Theming:** Instantaneous Light/Dark mode toggling with persistent user preference caching.

### 🤖 AI Integration (Groq Llama-3.3-70b)
* **Mood Lab:** Dynamically generates recipe suggestions based on your current emotional state (e.g., Tired, Adventurous, Stressed).
* **AI Chef:** A built-in chat assistant capable of answering culinary questions, offering ingredient substitutions, and generating meal plans on the fly.

### 🔒 Robust Security
* **Authentication:** Secure user login and registration utilizing robust password hashing (`password_hash`).
* **Database Protection:** Comprehensive defense against SQL Injection using prepared `bind_param` statements across all endpoints.
* **Safe Uploads:** Strict MIME-type validation for avatar uploads, preventing malicious file execution.
* **XSS Prevention:** Frontend DOM sanitization safely escapes all user-generated content before rendering.

---

## 🛠️ Technology Stack

* **Frontend:** Vanilla HTML5, Vanilla CSS3, Vanilla JavaScript, Bootstrap 5 (Grid & Icons only)
* **Backend:** PHP 8+ (Modular MVC-style routing)
* **Database:** MySQL (Relational architecture with cascading dependencies)
* **AI Provider:** Groq API (Llama-3.3-70b-versatile model)

---

## 📂 Project Architecture

```text
/chefnote
│
├── index.php           # Main application view & frontend assembly
├── login.php           # Secure authentication portal
├── signup.php          # User registration view
│
├── api.php             # Core routing, AI integration, and endpoint logic
├── db.php              # Database connection and dynamic table migrations
├── logout.php          # Session termination
│
├── script.js           # Frontend client logic, DOM manipulation, and API fetching
├── style.css           # "Culinary Noir" main stylesheet
├── auth.css            # Scoped styles for authentication views
│
└── /uploads            # Secure directory for validated user avatars



## 🚀 Installation & Setup

1. **Clone the repository:**
     ```bash
    git clone https://github.com/iamimaginator/chefnote.git
    cd chefnote
    ```


2. **Database Configuration:**

Ensure a local server environment (e.g., XAMPP, WAMP, or LAMP) is running.

Create a MySQL database named chefnote_db.

Note: The db.php file includes automatic table migrations and will generate the required users and recipes schemas upon the first connection.

3. **Environment Setup:**

Open api.php.

Locate the $GROQ_KEY variable and insert your active Groq API key:

(For production environments, ensure this key is stored securely as an environment variable rather than hardcoded.

4. **Launch the Application:**

Navigate to the project directory in your local server (e.g., http://localhost/chefnote/login.php).

## 🤝 Contributing
Contributions, issues, and feature requests are welcome. Feel free to check the issues page if you want to contribute.

## 📝 License
This project is open-source and available under the MIT License.





