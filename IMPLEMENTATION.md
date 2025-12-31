# Project Linkon - PHP Implementation

An app which allows you to securely store data online, create archives and make publicly shareable links (for social media bio, resumes, storing information / notes, etc).

No extra information is needed from the user, only a unique username and a strong password. Everything else is completely decentralised as per the user's wishes.

## Features

- **Secure Storage**: All user content is encrypted using AES-256-GCM authenticated encryption
- **Short Links**: Generate unique, short URLs for easy sharing
- **Minimal User Data**: Only username and password required
- **REST API**: Full API for integration with any client
- **Portable Design**: Modular classes designed for easy porting to other languages

## Technical Requirements

- PHP 8.0 or higher
- MySQL/MariaDB, PostgreSQL, or SQLite
- Apache with mod_rewrite (or nginx with equivalent configuration)
- OpenSSL extension
- PDO extension

## Project Structure

```
project-linkon/
├── config/
│   └── config.php          # Configuration file
├── database/
│   └── schema.sql          # Database schema
├── public/
│   ├── .htaccess           # Apache URL rewriting
│   ├── index.php           # Front controller
│   └── link.php            # Public link access
├── src/
│   ├── api/
│   │   ├── user.php        # User API endpoint
│   │   └── links.php       # Links API endpoint
│   ├── classes/
│   │   ├── DatabaseConnector.php  # PDO database wrapper
│   │   ├── Encryption.php         # AES-256-GCM encryption
│   │   └── LinkGenerator.php      # Short link generation
│   ├── models/
│   │   ├── User.php        # User model
│   │   └── Link.php        # Link model
│   └── bootstrap.php       # Application bootstrap
├── composer.json
└── README.md
```

## Installation

### 1. Clone the Repository

```bash
git clone https://github.com/fossit-org/project-linkon.git
cd project-linkon
```

### 2. Configure Database

Create your MySQL database and import the schema:

```bash
mysql -u root -p < database/schema.sql
```

Or run the SQL manually in your database client.

### 3. Configure Application

Edit `config/config.php` with your settings:

```php
return [
    'database' => [
        'driver' => 'mysql',
        'host' => 'localhost',
        'port' => '3306',
        'database' => 'linkon',
        'username' => 'your_username',
        'password' => 'your_password',
        'charset' => 'utf8mb4',
    ],
    'encryption' => [
        'method' => 'aes-256-gcm',
        'key' => 'YOUR_SECURE_32_BYTE_KEY_HERE',  // Generate with: bin2hex(random_bytes(32))
    ],
    'app' => [
        'base_url' => 'https://your-domain.com',
        'link_length' => 8,
    ],
];
```

### 4. Configure Web Server

#### Apache

Point your document root to the `public/` directory. The included `.htaccess` handles URL rewriting.

```apache
<VirtualHost *:80>
    ServerName your-domain.com
    DocumentRoot /path/to/project-linkon/public
    
    <Directory /path/to/project-linkon/public>
        AllowOverride All
        Require all granted
    </Directory>
</VirtualHost>
```

#### Nginx

```nginx
server {
    listen 80;
    server_name your-domain.com;
    root /path/to/project-linkon/public;
    index index.php;

    location / {
        try_files $uri $uri/ /index.php?$query_string;
    }

    location ~ \.php$ {
        fastcgi_pass unix:/var/run/php/php8.0-fpm.sock;
        fastcgi_param SCRIPT_FILENAME $realpath_root$fastcgi_script_name;
        include fastcgi_params;
    }
}
```

### 5. Set Permissions

```bash
chmod 755 public/
chmod 644 config/config.php
```

## API Reference

### Authentication

All authenticated endpoints require a Bearer token in the Authorization header:

```
Authorization: Bearer <token>
```

### User Endpoints

#### Register
```http
POST /api/user/register
Content-Type: application/json

{
    "username": "johndoe",
    "password": "SecurePass123!"
}
```

Response:
```json
{
    "message": "User registered successfully",
    "user_id": 1,
    "token": "abc123..."
}
```

#### Login
```http
POST /api/user/login
Content-Type: application/json

{
    "username": "johndoe",
    "password": "SecurePass123!"
}
```

#### Logout
```http
POST /api/user/logout
Authorization: Bearer <token>
```

#### Get Profile
```http
GET /api/user/profile
Authorization: Bearer <token>
```

### Link Endpoints

#### Create Link
```http
POST /api/links
Authorization: Bearer <token>
Content-Type: application/json

{
    "title": "My Notes",
    "content": "This is my secret content...",
    "is_public": true,
    "expires_at": null
}
```

Response:
```json
{
    "id": 1,
    "short_code": "abc12345",
    "title": "My Notes",
    "is_public": true,
    "url": "https://your-domain.com/l/abc12345"
}
```

#### Get All Links
```http
GET /api/links?page=1&per_page=20
Authorization: Bearer <token>
```

#### Get Specific Link
```http
GET /api/links/{id}
Authorization: Bearer <token>
```

#### Update Link
```http
PUT /api/links/{id}
Authorization: Bearer <token>
Content-Type: application/json

{
    "title": "Updated Title",
    "content": "Updated content",
    "is_public": false
}
```

#### Delete Link
```http
DELETE /api/links/{id}
Authorization: Bearer <token>
```

### Public Access

#### View Public Link
```http
GET /l/{short_code}
```

Returns HTML page or JSON (based on Accept header).

## Security

- **Password Hashing**: Argon2id with configurable cost factors
- **Content Encryption**: AES-256-GCM authenticated encryption
- **Session Tokens**: Cryptographically secure random tokens
- **SQL Injection Prevention**: PDO prepared statements
- **XSS Protection**: Output encoding in HTML views
- **CSRF Protection**: Stateless API with token authentication
- **Security Headers**: X-Frame-Options, X-XSS-Protection, CSP

## Class Design (For Porting)

The core classes are designed to be easily portable to other languages:

### DatabaseConnector
- PDO-based database abstraction
- Supports MySQL, PostgreSQL, SQLite
- Connection pooling via singleton pattern
- Prepared statement execution

### Encryption
- AES-256-GCM authenticated encryption
- Unique IV per encryption operation
- Base64 encoding for storage
- Password hashing with Argon2id

### LinkGenerator
- Cryptographically secure random generation
- URL-safe Base62 character set
- Configurable link length
- Collision detection

## Environment Variables

You can use environment variables for configuration:

- `DB_HOST` - Database host
- `DB_PORT` - Database port
- `DB_NAME` - Database name
- `DB_USER` - Database username
- `DB_PASSWORD` - Database password
- `ENCRYPTION_KEY` - Encryption key (32 bytes, hex encoded)
- `APP_BASE_URL` - Application base URL
- `APP_DEBUG` - Enable debug mode

## License

MIT License - see [LICENSE](LICENSE) file.

## Project Lead

[@realyuvishere](https://github.com/realyuvishere)
