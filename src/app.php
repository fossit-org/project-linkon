<?php
/**
 * Main Application Interface
 * 
 * Provides a web interface for:
 * - User registration and login
 * - Creating shareable short links
 * - Managing existing links
 */

// Start session for flash messages
session_start();

// Load configuration
$config = require __DIR__ . '/../config/config.php';
$baseUrl = $config['app']['base_url'] ?? '';

// Get any flash messages
$flashMessage = $_SESSION['flash_message'] ?? null;
$flashType = $_SESSION['flash_type'] ?? 'info';
unset($_SESSION['flash_message'], $_SESSION['flash_type']);

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Linkon - Secure Link Sharing</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Oxygen, Ubuntu, Cantarell, sans-serif;
            line-height: 1.6;
            color: #333;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
        }
        
        /* Navigation */
        .navbar {
            background: rgba(255,255,255,0.1);
            backdrop-filter: blur(10px);
            padding: 1rem 2rem;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        .navbar-brand {
            color: white;
            font-size: 1.5rem;
            font-weight: bold;
            text-decoration: none;
        }
        .navbar-nav {
            display: flex;
            gap: 1rem;
            align-items: center;
        }
        .nav-link {
            color: rgba(255,255,255,0.9);
            text-decoration: none;
            padding: 0.5rem 1rem;
            border-radius: 6px;
            transition: background 0.2s;
        }
        .nav-link:hover {
            background: rgba(255,255,255,0.1);
        }
        .nav-user {
            color: white;
            font-weight: 500;
        }
        
        /* Main Container */
        .main-container {
            max-width: 1000px;
            margin: 0 auto;
            padding: 2rem;
        }
        
        /* Cards */
        .card {
            background: white;
            border-radius: 16px;
            box-shadow: 0 10px 40px rgba(0,0,0,0.2);
            margin-bottom: 2rem;
            overflow: hidden;
        }
        .card-header {
            background: #f8f9fa;
            padding: 1.5rem;
            border-bottom: 1px solid #e9ecef;
        }
        .card-header h2 {
            font-size: 1.25rem;
            color: #333;
        }
        .card-body {
            padding: 1.5rem;
        }
        
        /* Forms */
        .form-group {
            margin-bottom: 1.5rem;
        }
        label {
            display: block;
            font-weight: 600;
            margin-bottom: 0.5rem;
            color: #333;
        }
        input[type="text"],
        input[type="password"],
        input[type="email"],
        textarea,
        select {
            width: 100%;
            padding: 0.75rem 1rem;
            border: 2px solid #e1e5eb;
            border-radius: 8px;
            font-size: 1rem;
            transition: border-color 0.2s;
        }
        input:focus, textarea:focus, select:focus {
            outline: none;
            border-color: #667eea;
        }
        textarea {
            min-height: 120px;
            resize: vertical;
        }
        .form-check {
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }
        .form-check input[type="checkbox"] {
            width: auto;
        }
        .form-row {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 1rem;
        }
        
        /* Buttons */
        .btn {
            display: inline-block;
            padding: 0.75rem 1.5rem;
            border: none;
            border-radius: 8px;
            font-size: 1rem;
            font-weight: 600;
            cursor: pointer;
            transition: transform 0.2s, box-shadow 0.2s;
            text-decoration: none;
            text-align: center;
        }
        .btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(0,0,0,0.15);
        }
        .btn-primary {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
        }
        .btn-secondary {
            background: #6c757d;
            color: white;
        }
        .btn-success {
            background: #28a745;
            color: white;
        }
        .btn-danger {
            background: #dc3545;
            color: white;
        }
        .btn-outline {
            background: transparent;
            border: 2px solid #667eea;
            color: #667eea;
        }
        .btn-sm {
            padding: 0.4rem 0.8rem;
            font-size: 0.875rem;
        }
        .btn-group {
            display: flex;
            gap: 0.5rem;
        }
        
        /* Alerts */
        .alert {
            padding: 1rem;
            border-radius: 8px;
            margin-bottom: 1rem;
        }
        .alert-success {
            background: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
        }
        .alert-error {
            background: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
        }
        .alert-info {
            background: #d1ecf1;
            color: #0c5460;
            border: 1px solid #bee5eb;
        }
        
        /* Links List */
        .links-list {
            list-style: none;
        }
        .link-item {
            border-bottom: 1px solid #e9ecef;
            padding: 1rem 0;
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            gap: 1rem;
        }
        .link-item:last-child {
            border-bottom: none;
        }
        .link-info {
            flex: 1;
        }
        .link-title {
            font-weight: 600;
            color: #333;
            margin-bottom: 0.25rem;
        }
        .link-url {
            font-family: monospace;
            font-size: 0.875rem;
            color: #667eea;
            word-break: break-all;
        }
        .link-meta {
            font-size: 0.75rem;
            color: #6c757d;
            margin-top: 0.25rem;
        }
        .link-actions {
            display: flex;
            gap: 0.5rem;
            flex-shrink: 0;
        }
        
        /* Tabs */
        .tabs {
            display: flex;
            border-bottom: 2px solid #e9ecef;
            margin-bottom: 1.5rem;
        }
        .tab {
            padding: 1rem 1.5rem;
            cursor: pointer;
            border-bottom: 2px solid transparent;
            margin-bottom: -2px;
            color: #6c757d;
            font-weight: 500;
            transition: all 0.2s;
        }
        .tab:hover {
            color: #667eea;
        }
        .tab.active {
            color: #667eea;
            border-bottom-color: #667eea;
        }
        .tab-content {
            display: none;
        }
        .tab-content.active {
            display: block;
        }
        
        /* Copy button */
        .copy-btn {
            background: #f8f9fa;
            border: 1px solid #e9ecef;
            padding: 0.25rem 0.5rem;
            border-radius: 4px;
            cursor: pointer;
            font-size: 0.75rem;
        }
        .copy-btn:hover {
            background: #e9ecef;
        }
        .copy-btn.copied {
            background: #d4edda;
            border-color: #c3e6cb;
        }
        
        /* Modal */
        .modal-overlay {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: rgba(0,0,0,0.5);
            align-items: center;
            justify-content: center;
            z-index: 1000;
        }
        .modal-overlay.active {
            display: flex;
        }
        .modal {
            background: white;
            border-radius: 16px;
            max-width: 500px;
            width: 90%;
            max-height: 90vh;
            overflow-y: auto;
        }
        .modal-header {
            padding: 1.5rem;
            border-bottom: 1px solid #e9ecef;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        .modal-header h3 {
            font-size: 1.25rem;
        }
        .modal-close {
            background: none;
            border: none;
            font-size: 1.5rem;
            cursor: pointer;
            color: #6c757d;
        }
        .modal-body {
            padding: 1.5rem;
        }
        
        /* Hero section for logged out users */
        .hero {
            text-align: center;
            color: white;
            padding: 2rem 0;
        }
        .hero h1 {
            font-size: 3rem;
            margin-bottom: 1rem;
            text-shadow: 2px 2px 4px rgba(0,0,0,0.2);
        }
        .hero p {
            font-size: 1.25rem;
            opacity: 0.9;
            margin-bottom: 2rem;
            max-width: 600px;
            margin-left: auto;
            margin-right: auto;
        }
        
        /* Features grid */
        .features {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 1.5rem;
            margin: 2rem 0;
        }
        .feature {
            background: rgba(255,255,255,0.1);
            padding: 1.5rem;
            border-radius: 12px;
            text-align: center;
            color: white;
        }
        .feature h3 {
            font-size: 1.1rem;
            margin-bottom: 0.5rem;
        }
        .feature p {
            font-size: 0.9rem;
            opacity: 0.9;
            margin: 0;
        }
        
        /* Empty state */
        .empty-state {
            text-align: center;
            padding: 3rem;
            color: #6c757d;
        }
        .empty-state h3 {
            margin-bottom: 0.5rem;
        }
        
        /* Link success display */
        .link-success {
            background: #f8f9fa;
            border-radius: 8px;
            padding: 1.5rem;
            text-align: center;
            margin: 1rem 0;
        }
        .link-success-url {
            font-size: 1.25rem;
            font-family: monospace;
            color: #667eea;
            word-break: break-all;
            padding: 1rem;
            background: white;
            border-radius: 8px;
            border: 2px solid #667eea;
            margin: 1rem 0;
        }
        
        /* Responsive */
        @media (max-width: 768px) {
            .form-row {
                grid-template-columns: 1fr;
            }
            .hero h1 {
                font-size: 2rem;
            }
            .link-item {
                flex-direction: column;
            }
            .link-actions {
                width: 100%;
            }
        }
    </style>
</head>
<body>
    <nav class="navbar">
        <a href="/" class="navbar-brand">🔗 Linkon</a>
        <div class="navbar-nav" id="navbar-nav">
            <!-- Will be populated by JavaScript based on auth state -->
        </div>
    </nav>
    
    <div class="main-container">
        <?php if ($flashMessage): ?>
            <div class="alert alert-<?php echo htmlspecialchars($flashType); ?>">
                <?php echo htmlspecialchars($flashMessage); ?>
            </div>
        <?php endif; ?>
        
        <!-- Content will be dynamically loaded based on auth state -->
        <div id="app-content">
            <!-- Loading state -->
            <div class="card">
                <div class="card-body" style="text-align: center; padding: 3rem;">
                    <p>Loading...</p>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Create Link Modal -->
    <div class="modal-overlay" id="createLinkModal">
        <div class="modal">
            <div class="modal-header">
                <h3>Create New Link</h3>
                <button class="modal-close" onclick="closeModal('createLinkModal')">&times;</button>
            </div>
            <div class="modal-body">
                <form id="createLinkForm">
                    <div class="form-group">
                        <label for="linkTitle">Title</label>
                        <input type="text" id="linkTitle" name="title" required placeholder="My awesome link">
                    </div>
                    <div class="form-group">
                        <label for="linkContent">Content</label>
                        <textarea id="linkContent" name="content" required placeholder="Enter your content here... This can be text, notes, a URL, or any information you want to share."></textarea>
                    </div>
                    <div class="form-group">
                        <div class="form-check">
                            <input type="checkbox" id="linkPublic" name="is_public" checked>
                            <label for="linkPublic" style="margin: 0; font-weight: normal;">Make this link publicly accessible</label>
                        </div>
                    </div>
                    <div class="btn-group">
                        <button type="submit" class="btn btn-primary">Create Link</button>
                        <button type="button" class="btn btn-secondary" onclick="closeModal('createLinkModal')">Cancel</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    
    <!-- Link Created Success Modal -->
    <div class="modal-overlay" id="linkSuccessModal">
        <div class="modal">
            <div class="modal-header">
                <h3>🎉 Link Created!</h3>
                <button class="modal-close" onclick="closeModal('linkSuccessModal')">&times;</button>
            </div>
            <div class="modal-body">
                <p>Your shareable link has been created:</p>
                <div class="link-success">
                    <div class="link-success-url" id="createdLinkUrl"></div>
                    <button class="btn btn-primary" onclick="copyCreatedLink()">📋 Copy Link</button>
                </div>
                <p style="text-align: center; color: #6c757d; margin-top: 1rem;">
                    Share this link with anyone to give them access to your content.
                </p>
            </div>
        </div>
    </div>

    <script>
        const BASE_URL = <?php echo json_encode($baseUrl); ?>;
        let authToken = localStorage.getItem('linkon_token');
        let currentUser = null;
        
        // Initialize app
        document.addEventListener('DOMContentLoaded', () => {
            if (authToken) {
                checkAuth();
            } else {
                showLoggedOutView();
            }
        });
        
        // Check if token is valid
        async function checkAuth() {
            try {
                const response = await fetch('/api/user/profile', {
                    headers: {
                        'Authorization': `Bearer ${authToken}`
                    }
                });
                
                if (response.ok) {
                    currentUser = await response.json();
                    showLoggedInView();
                } else {
                    logout();
                }
            } catch (error) {
                logout();
            }
        }
        
        // Show logged out view
        function showLoggedOutView() {
            document.getElementById('navbar-nav').innerHTML = `
                <a href="#" class="nav-link" onclick="showTab('login'); return false;">Login</a>
                <a href="#" class="nav-link" onclick="showTab('register'); return false;">Register</a>
            `;
            
            document.getElementById('app-content').innerHTML = `
                <div class="hero">
                    <h1>🔗 Secure Link Sharing</h1>
                    <p>Create encrypted, shareable short links for your content. Perfect for social media bios, resumes, notes, and more.</p>
                </div>
                
                <div class="features">
                    <div class="feature">
                        <h3>🔒 Secure</h3>
                        <p>AES-256-GCM encryption for all stored content</p>
                    </div>
                    <div class="feature">
                        <h3>🔑 Private</h3>
                        <p>Only username and password required</p>
                    </div>
                    <div class="feature">
                        <h3>🔗 Short Links</h3>
                        <p>Easy to share shortened URLs</p>
                    </div>
                </div>
                
                <div class="card">
                    <div class="card-body">
                        <div class="tabs">
                            <div class="tab active" data-tab="login" onclick="showTab('login')">Login</div>
                            <div class="tab" data-tab="register" onclick="showTab('register')">Register</div>
                        </div>
                        
                        <div id="loginTab" class="tab-content active">
                            <form id="loginForm" onsubmit="handleLogin(event)">
                                <div class="form-group">
                                    <label for="loginUsername">Username</label>
                                    <input type="text" id="loginUsername" name="username" required>
                                </div>
                                <div class="form-group">
                                    <label for="loginPassword">Password</label>
                                    <input type="password" id="loginPassword" name="password" required>
                                </div>
                                <div id="loginError" class="alert alert-error" style="display: none;"></div>
                                <button type="submit" class="btn btn-primary" style="width: 100%;">Login</button>
                            </form>
                        </div>
                        
                        <div id="registerTab" class="tab-content">
                            <form id="registerForm" onsubmit="handleRegister(event)">
                                <div class="form-group">
                                    <label for="registerUsername">Username</label>
                                    <input type="text" id="registerUsername" name="username" required minlength="3" maxlength="30" pattern="[a-zA-Z0-9_]+">
                                    <small style="color: #6c757d;">3-30 characters, letters, numbers, and underscores only</small>
                                </div>
                                <div class="form-group">
                                    <label for="registerPassword">Password</label>
                                    <input type="password" id="registerPassword" name="password" required minlength="8">
                                    <small style="color: #6c757d;">At least 8 characters with uppercase, lowercase, and numbers</small>
                                </div>
                                <div class="form-group">
                                    <label for="registerPasswordConfirm">Confirm Password</label>
                                    <input type="password" id="registerPasswordConfirm" name="password_confirm" required>
                                </div>
                                <div id="registerError" class="alert alert-error" style="display: none;"></div>
                                <button type="submit" class="btn btn-primary" style="width: 100%;">Create Account</button>
                            </form>
                        </div>
                    </div>
                </div>
            `;
        }
        
        // Show logged in view
        function showLoggedInView() {
            document.getElementById('navbar-nav').innerHTML = `
                <span class="nav-user">👤 ${escapeHtml(currentUser.username)}</span>
                <a href="#" class="nav-link" onclick="logout(); return false;">Logout</a>
            `;
            
            document.getElementById('app-content').innerHTML = `
                <div class="card">
                    <div class="card-header" style="display: flex; justify-content: space-between; align-items: center;">
                        <h2>📎 Create Shareable Link</h2>
                    </div>
                    <div class="card-body">
                        <form id="quickCreateForm" onsubmit="handleQuickCreate(event)">
                            <div class="form-group">
                                <label for="quickTitle">Title</label>
                                <input type="text" id="quickTitle" name="title" required placeholder="Give your link a title">
                            </div>
                            <div class="form-group">
                                <label for="quickContent">Content</label>
                                <textarea id="quickContent" name="content" required placeholder="Enter your content here... This can be text, notes, a URL, or any information you want to share."></textarea>
                            </div>
                            <div class="form-row">
                                <div class="form-group">
                                    <div class="form-check">
                                        <input type="checkbox" id="quickPublic" name="is_public" checked>
                                        <label for="quickPublic" style="margin: 0; font-weight: normal;">Make publicly accessible</label>
                                    </div>
                                </div>
                            </div>
                            <div id="quickCreateError" class="alert alert-error" style="display: none;"></div>
                            <button type="submit" class="btn btn-primary">🔗 Generate Short Link</button>
                        </form>
                    </div>
                </div>
                
                <div class="card">
                    <div class="card-header">
                        <h2>📋 Your Links</h2>
                    </div>
                    <div class="card-body">
                        <div id="linksList">
                            <div class="empty-state">
                                <p>Loading your links...</p>
                            </div>
                        </div>
                    </div>
                </div>
            `;
            
            loadLinks();
        }
        
        // Tab switching
        function showTab(tabName) {
            document.querySelectorAll('.tab').forEach(tab => {
                tab.classList.toggle('active', tab.dataset.tab === tabName);
            });
            document.querySelectorAll('.tab-content').forEach(content => {
                content.classList.toggle('active', content.id === tabName + 'Tab');
            });
        }
        
        // Handle login
        async function handleLogin(event) {
            event.preventDefault();
            const form = event.target;
            const errorDiv = document.getElementById('loginError');
            errorDiv.style.display = 'none';
            
            try {
                const response = await fetch('/api/user/login', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json'
                    },
                    body: JSON.stringify({
                        username: form.username.value,
                        password: form.password.value
                    })
                });
                
                const data = await response.json();
                
                if (response.ok) {
                    authToken = data.token;
                    localStorage.setItem('linkon_token', authToken);
                    currentUser = { id: data.user_id, username: form.username.value };
                    showLoggedInView();
                } else {
                    errorDiv.textContent = data.error || 'Login failed';
                    errorDiv.style.display = 'block';
                }
            } catch (error) {
                errorDiv.textContent = 'An error occurred. Please try again.';
                errorDiv.style.display = 'block';
            }
        }
        
        // Handle registration
        async function handleRegister(event) {
            event.preventDefault();
            const form = event.target;
            const errorDiv = document.getElementById('registerError');
            errorDiv.style.display = 'none';
            
            if (form.password.value !== form.password_confirm.value) {
                errorDiv.textContent = 'Passwords do not match';
                errorDiv.style.display = 'block';
                return;
            }
            
            try {
                const response = await fetch('/api/user/register', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json'
                    },
                    body: JSON.stringify({
                        username: form.username.value,
                        password: form.password.value
                    })
                });
                
                const data = await response.json();
                
                if (response.ok) {
                    authToken = data.token;
                    localStorage.setItem('linkon_token', authToken);
                    currentUser = { id: data.user_id, username: form.username.value };
                    showLoggedInView();
                } else {
                    errorDiv.textContent = data.error || 'Registration failed';
                    errorDiv.style.display = 'block';
                }
            } catch (error) {
                errorDiv.textContent = 'An error occurred. Please try again.';
                errorDiv.style.display = 'block';
            }
        }
        
        // Logout
        async function logout() {
            try {
                await fetch('/api/user/logout', {
                    method: 'POST',
                    headers: {
                        'Authorization': `Bearer ${authToken}`
                    }
                });
            } catch (e) {
                // Ignore errors
            }
            
            authToken = null;
            currentUser = null;
            localStorage.removeItem('linkon_token');
            showLoggedOutView();
        }
        
        // Load user's links
        async function loadLinks() {
            try {
                const response = await fetch('/api/links', {
                    headers: {
                        'Authorization': `Bearer ${authToken}`
                    }
                });
                
                if (response.ok) {
                    const data = await response.json();
                    displayLinks(data.links);
                } else {
                    document.getElementById('linksList').innerHTML = `
                        <div class="alert alert-error">Failed to load links</div>
                    `;
                }
            } catch (error) {
                document.getElementById('linksList').innerHTML = `
                    <div class="alert alert-error">Failed to load links</div>
                `;
            }
        }
        
        // Display links
        function displayLinks(links) {
            const container = document.getElementById('linksList');
            
            if (!links || links.length === 0) {
                container.innerHTML = `
                    <div class="empty-state">
                        <h3>No links yet</h3>
                        <p>Create your first shareable link using the form above!</p>
                    </div>
                `;
                return;
            }
            
            container.innerHTML = `
                <ul class="links-list">
                    ${links.map(link => `
                        <li class="link-item">
                            <div class="link-info">
                                <div class="link-title">${escapeHtml(link.title)}</div>
                                <div class="link-url">
                                    <a href="${escapeHtml(link.url)}" target="_blank">${escapeHtml(link.url)}</a>
                                    <button class="copy-btn" onclick="copyToClipboard('${escapeHtml(link.url)}', this)">📋 Copy</button>
                                </div>
                                <div class="link-meta">
                                    ${link.is_public ? '🌐 Public' : '🔒 Private'} • 
                                    👁️ ${link.view_count || 0} views • 
                                    Created ${formatDate(link.created_at)}
                                </div>
                            </div>
                            <div class="link-actions">
                                <a href="${escapeHtml(link.url)}" target="_blank" class="btn btn-sm btn-outline">View</a>
                                <button class="btn btn-sm btn-danger" onclick="deleteLink(${link.id})">Delete</button>
                            </div>
                        </li>
                    `).join('')}
                </ul>
            `;
        }
        
        // Quick create link
        async function handleQuickCreate(event) {
            event.preventDefault();
            const form = event.target;
            const errorDiv = document.getElementById('quickCreateError');
            errorDiv.style.display = 'none';
            
            try {
                const response = await fetch('/api/links', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'Authorization': `Bearer ${authToken}`
                    },
                    body: JSON.stringify({
                        title: form.title.value,
                        content: form.content.value,
                        is_public: form.is_public.checked
                    })
                });
                
                const data = await response.json();
                
                if (response.ok) {
                    // Show success modal
                    document.getElementById('createdLinkUrl').textContent = data.url;
                    openModal('linkSuccessModal');
                    
                    // Reset form and reload links
                    form.reset();
                    form.is_public.checked = true;
                    loadLinks();
                } else {
                    errorDiv.textContent = data.error || 'Failed to create link';
                    errorDiv.style.display = 'block';
                }
            } catch (error) {
                errorDiv.textContent = 'An error occurred. Please try again.';
                errorDiv.style.display = 'block';
            }
        }
        
        // Delete link
        async function deleteLink(id) {
            if (!confirm('Are you sure you want to delete this link?')) {
                return;
            }
            
            try {
                const response = await fetch(`/api/links/${id}`, {
                    method: 'DELETE',
                    headers: {
                        'Authorization': `Bearer ${authToken}`
                    }
                });
                
                if (response.ok) {
                    loadLinks();
                } else {
                    alert('Failed to delete link');
                }
            } catch (error) {
                alert('Failed to delete link');
            }
        }
        
        // Modal functions
        function openModal(id) {
            document.getElementById(id).classList.add('active');
        }
        
        function closeModal(id) {
            document.getElementById(id).classList.remove('active');
        }
        
        // Copy to clipboard
        function copyToClipboard(text, button) {
            navigator.clipboard.writeText(text).then(() => {
                const originalText = button.textContent;
                button.textContent = '✓ Copied!';
                button.classList.add('copied');
                setTimeout(() => {
                    button.textContent = originalText;
                    button.classList.remove('copied');
                }, 2000);
            });
        }
        
        function copyCreatedLink() {
            const url = document.getElementById('createdLinkUrl').textContent;
            navigator.clipboard.writeText(url).then(() => {
                alert('Link copied to clipboard!');
            });
        }
        
        // Utility functions
        function escapeHtml(text) {
            const div = document.createElement('div');
            div.textContent = text;
            return div.innerHTML;
        }
        
        function formatDate(dateString) {
            const date = new Date(dateString);
            return date.toLocaleDateString();
        }
        
        // Close modals on outside click
        document.querySelectorAll('.modal-overlay').forEach(overlay => {
            overlay.addEventListener('click', (e) => {
                if (e.target === overlay) {
                    overlay.classList.remove('active');
                }
            });
        });
    </script>
</body>
</html>
