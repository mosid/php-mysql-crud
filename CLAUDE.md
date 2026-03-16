# CLAUDE.md

This file provides guidance to Claude Code (claude.ai/code) when working with code in this repository.

## Project Overview

A complete PHP MySQL CRUD application built with Docker. The application manages a simple user database with full Create, Read, Update, Delete operations. It features a modern responsive UI, PDO-based database access, and containerized development environment.

## Architecture

### Technology Stack
- **Backend**: PHP 8.2 with Apache
- **Database**: MySQL 8.0
- **Database Access**: PDO with prepared statements
- **Frontend**: HTML5, CSS3, vanilla JavaScript
- **Containerization**: Docker & Docker Compose
- **Database Management UI**: phpMyAdmin

### Directory Structure
- `src/` - Application source code
  - `index.php` - Main entry point, request routing and business logic
  - `autoload.php` - PSR-4 autoloader for App namespace
  - `config/Database.php` - PDO singleton connection manager
  - `models/User.php` - User CRUD operations
  - `views/layout.php` - HTML template with inline CSS/JS
- `database/` - Database initialization scripts
  - `init.sql` - Table schema and seed data
- `tests/` - PHPUnit tests
  - `bootstrap.php` - Test environment setup
  - `Unit/ConfigDatabaseTest.php` - Database connection tests
  - `Unit/ModelsUserTest.php` - User model CRUD tests
- `config/` - Infrastructure configuration
  - `apache.conf` - Apache vhost configuration
- `Dockerfile` - PHP/Apache container definition
- `docker-compose.yml` - Multi-container orchestration
- `composer.json` - PHP dependency management with PHPUnit
- `phpunit.xml` - PHPUnit configuration

## Common Development Tasks

### Starting the Application

```bash
# Start all containers in background
docker-compose up -d

# View logs in real-time
docker-compose logs -f

# Start with specific service logs
docker-compose logs -f web    # PHP/Apache logs
docker-compose logs -f db     # MySQL logs
```

### Accessing Services
- Web application: http://localhost:8080
- phpMyAdmin: http://localhost:8081 (crud_user / crud_password)
- MySQL: localhost:3306 (crud_user / crud_password)

### Database Operations

```bash
# Access MySQL CLI
docker-compose exec db mysql -u crud_user -p crud_app

# View logs
docker-compose logs db

# Reset database (removes all data)
docker-compose down -v
docker-compose up -d
```

### PHP Container Access

```bash
# Enter PHP/Apache container shell
docker-compose exec web bash

# Check PHP version and extensions
docker-compose exec web php -v
docker-compose exec web php -m | grep pdo
```

### Stopping and Cleanup

```bash
# Stop containers (preserves data)
docker-compose stop

# Remove containers (preserves data)
docker-compose down

# Remove containers and database (full cleanup)
docker-compose down -v

# Rebuild images after code changes
docker-compose build --no-cache
docker-compose up -d
```

## Testing

### Running Tests

All tests are managed by PHPUnit. The test suite includes unit tests for database connections and user model CRUD operations.

```bash
# Run all tests
docker-compose exec web vendor/bin/phpunit

# Run specific test file
docker-compose exec web vendor/bin/phpunit tests/Unit/ConfigDatabaseTest.php

# Run specific test class method
docker-compose exec web vendor/bin/phpunit --filter testCreateUserWithValidData

# Run tests with verbose output
docker-compose exec web vendor/bin/phpunit --verbose

# Generate coverage report
docker-compose exec web vendor/bin/phpunit --coverage-html coverage/
```

### Test Structure

- **`tests/bootstrap.php`** - Test environment initialization, autoloading, and constants
- **`tests/Unit/ConfigDatabaseTest.php`** - Tests for Database connection class
  - Connection singleton behavior
  - Environment variable configuration
  - PDO attribute validation
  - Disconnect functionality

- **`tests/Unit/ModelsUserTest.php`** - Tests for User model CRUD operations
  - Create user with valid/minimal data
  - Read user by ID
  - Update user fields
  - Delete user
  - Email validation and uniqueness checking
  - Pagination with getAll()
  - User search by name/email
  - Count total users

### Test Database

Tests use the same MySQL database as development. Tests create temporary records with unique timestamps to avoid conflicts and clean up after themselves via `delete()`.

### Running Tests from Host Machine

If you have PHP and Composer installed locally:

```bash
# Install dependencies
composer install

# Run tests
vendor/bin/phpunit

# Run tests with coverage
vendor/bin/phpunit --coverage-html coverage/
```

### Key Testing Patterns

- **Singleton Testing**: Database connection singleton verified by comparing instances
- **Data Isolation**: Tests use unique timestamps in emails to prevent collisions
- **Cleanup**: Each test deletes its created records via `User::delete()`
- **Prepared Statements**: All tests verify PDO prepared statements are enabled
- **Null Checking**: Tests verify null returns for non-existent records

## Code Architecture Patterns

### Database Connection (src/config/Database.php)
- **Pattern**: Singleton for single DB connection instance
- **Key Features**:
  - PDO with error mode exception throwing
  - Prepared statements prevent SQL injection
  - Environment-based configuration
  - Connection pooling through singleton
  - UTF-8 support via charset parameter

### User Model (src/models/User.php)
- **Pattern**: Active Record-style model
- **Core Methods**:
  - `getAll()` - List users with pagination
  - `getById()` - Fetch single user
  - `create()` - Insert new user
  - `update()` - Modify existing user
  - `delete()` - Remove user
  - `emailExists()` - Validation helper
  - `search()` - Query users by name/email
- **All queries use prepared statements with parameter binding**

### Request Handling (src/index.php)
- **Flow**: Request → Validation → Model Operation → Response
- **Actions**: list, create (GET form), create (POST save), edit (GET form), edit (POST save), delete
- **Error Handling**: Form validation with user-friendly messages
- **Pagination**: 10 items per page with limit/offset

### Views (src/views/layout.php)
- **Single Template**: Handles all page states (list, create, edit)
- **Conditional Rendering**: Uses action variable to switch between views
- **Inline Styling**: CSS3 gradients, flexbox, grid layout
- **UX Features**:
  - Modals for delete confirmation
  - Auto-hiding success/error alerts
  - Responsive pagination
  - Empty states

## Key Development Considerations

### Input Validation
- Email format validation using `filter_var(FILTER_VALIDATE_EMAIL)`
- Duplicate email prevention with `User::emailExists()`
- Required field checks (name, email)
- SQL injection prevention via prepared statements
- XSS prevention via `htmlspecialchars()` on output

### Database Schema (database/init.sql)
- `users` table with auto-increment ID
- UNIQUE constraint on email to prevent duplicates
- TIMESTAMP columns with auto-update on modification
- Index on email for faster lookups
- UTF-8 collation support

### Configuration
- All DB credentials in `docker-compose.yml` environment section
- Optional `.env` file override (see `.env.example`)
- Database config reads from `$_ENV` or `getenv()`
- Localhost development setup: no authentication needed

### Error Handling
- PDO exceptions caught and displayed to user
- Form validation with specific error messages
- Null checks for optional fields
- User-friendly alerts in UI
- Server-side validation only (no client-side trust)

## Testing Workflow

### Manual Testing Steps
1. Navigate to http://localhost:8080
2. Verify initial users display
3. Create new user with valid data
4. Try invalid inputs (missing fields, invalid email)
5. Edit existing user
6. Delete user and confirm modal
7. Verify pagination if >10 users exist
8. Check phpMyAdmin data matches UI

### Database Verification
```bash
# Check users table
docker-compose exec db mysql -u crud_user -p crud_app -e "SELECT * FROM users;"

# Check table structure
docker-compose exec db mysql -u crud_user -p crud_app -e "DESCRIBE users;"
```

## Performance & Scalability Notes

- **Pagination**: Implemented to handle large datasets
- **Indexes**: Email column indexed for search performance
- **Connection Pool**: Singleton ensures single DB connection
- **Prepared Statements**: Protect against injection and improve performance

## Security Considerations

This is a demonstration app. Production deployment requires:
- Environment-based secrets (never hardcode credentials)
- HTTPS/TLS encryption
- Authentication & authorization layer
- CSRF token protection on forms
- Rate limiting on database operations
- Input length validation
- SQL query logging and monitoring
- Session management and timeout

## Modifying the Database Schema

To add new tables or columns:

1. Edit `database/init.sql`
2. Rebuild and restart containers:
   ```bash
   docker-compose down -v
   docker-compose build --no-cache
   docker-compose up -d
   ```
3. Update `User.php` model methods to match schema
4. Update `layout.php` form fields and table display

## Debugging Tips

### Enable PHP Error Display
Already enabled in `index.php`:
```php
ini_set('display_errors', 1);
error_reporting(E_ALL);
```

### Check Container Logs
```bash
# All services
docker-compose logs

# Follow logs in real-time
docker-compose logs -f

# Specific service
docker-compose logs -f web
docker-compose logs -f db
```

### Check if Database is Healthy
```bash
# View container status
docker-compose ps

# Manual connection test
docker-compose exec db mysqladmin ping -h localhost
```

### Verify PDO Connection
Add temporary debug in `src/index.php`:
```php
try {
    $db = Database::connect();
    echo "Database connected successfully";
} catch (Exception $e) {
    echo "Connection failed: " . $e->getMessage();
}
```
