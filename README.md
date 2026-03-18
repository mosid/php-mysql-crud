# PHP MySQL CRUD Application

A simple yet complete CRUD application built with PHP, MySQL, and Docker. Features a clean modern UI, MySQLi prepared statements, CI/CD with GitHub Actions, and Kubernetes deployment manifests.

## Features

- Create, Read, Update, Delete users
- Pagination support
- Input validation and error handling
- MySQLi with prepared statements
- Docker containerization (dev + production)
- MySQL 8.0 database
- phpMyAdmin for database management
- Responsive modern UI
- GitHub Actions CI/CD (PHPStan, PHPUnit, ECR build, K8s deploy)

## Prerequisites

- Docker
- Docker Compose

## Quick Start

### 1. Clone and Setup

```bash
cd php-mysql-crud
cp .env.example .env
```

### 2. Start Docker Containers

```bash
docker-compose up -d
```

This will start:
- PHP 8.2 Apache web server on `http://localhost:8080`
- MySQL 8.0 database on `localhost:3306`
- phpMyAdmin on `http://localhost:8081`

### 3. Wait for Database to be Ready

The application will automatically wait for the database to be healthy before starting.

## Usage

### Web Interface

- **Main App**: http://localhost:8080
- **phpMyAdmin**: http://localhost:8081
  - Username: `crud_user`
  - Password: `crud_password`

### Common Tasks

#### View all users
Navigate to http://localhost:8080 - you'll see a list of users with pagination

#### Create a new user
1. Click "Add New User" button
2. Fill in Name and Email (required), Phone (optional)
3. Click "Create User"

#### Edit a user
1. Click "Edit" button next to a user
2. Modify the details
3. Click "Update User"

#### Delete a user
1. Click "Delete" button next to a user
2. Confirm in the modal dialog

### Database

The MySQL database is automatically initialized with the `init.sql` script. It creates:
- `users` table with columns: id, name, email, phone, created_at, updated_at
- Sample data with 3 users

To access the database directly:

```bash
docker-compose exec db mysql -u crud_user -p crud_app
# Password: crud_password
```

## Project Structure

```
php-mysql-crud/
├── .github/
│   └── workflows/
│       ├── pr-checks.yml          # PHPStan + PHPUnit on pull requests
│       ├── build.yml              # Docker build + push to ECR
│       └── deploy.yml             # Deploy to Kubernetes (EKS)
├── k8s/
│   ├── deployment.yaml            # App deployment (2 replicas, rolling update)
│   ├── service.yaml               # ClusterIP service
│   ├── ingress.yaml               # ALB ingress with HTTPS
│   ├── configmap.yaml             # Non-sensitive config (DB_HOST, DB_NAME)
│   ├── secret.yaml                # Sensitive config template (DB_PASSWORD)
│   └── hpa.yaml                   # Horizontal Pod Autoscaler (2-10 pods)
├── config/
│   └── apache.conf                # Apache virtual host configuration
├── database/
│   └── init.sql                   # Database schema and seed data
├── src/
│   ├── config/
│   │   └── Database.php           # MySQLi singleton connection
│   ├── models/
│   │   └── User.php               # User model with CRUD operations
│   ├── views/
│   │   └── layout.php             # Main HTML template
│   ├── autoload.php               # PSR-4 autoloader
│   └── index.php                  # Application entry point
├── tests/
│   ├── bootstrap.php              # Test environment setup
│   └── Unit/
│       ├── ConfigDatabaseTest.php # Database connection tests
│       └── ModelsUserTest.php     # User model CRUD tests
├── Dockerfile                     # Development Docker image
├── Dockerfile.prod                # Production Docker image (multi-stage)
├── docker-compose.yml             # Local development stack
├── composer.json                  # PHP dependencies (PHPUnit, PHPStan)
├── phpunit.xml                    # PHPUnit configuration
├── phpstan.neon                   # PHPStan static analysis config (level 6)
└── README.md
```

## Database Schema

### Users Table

```sql
CREATE TABLE users (
    id INT PRIMARY KEY AUTO_INCREMENT,
    name VARCHAR(100) NOT NULL,
    email VARCHAR(100) NOT NULL UNIQUE,
    phone VARCHAR(20),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);
```

## Architecture

The application uses a simple MVC pattern:

- **Model** (`User.php`): Database operations with MySQLi prepared statements
- **View** (`layout.php`): HTML template with inline CSS and JavaScript
- **Controller** (`index.php`): Request handling and business logic

### MySQLi Connection

Database connection is managed in `src/config/Database.php`:
- Uses prepared statements with `bind_param()` to prevent SQL injection
- Singleton pattern for connection management
- Environment-based configuration
- Exception-based error reporting (`MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT`)
- UTF-8mb4 charset

## Testing

```bash
# Run all tests via Docker
docker-compose exec web vendor/bin/phpunit

# Run specific test file
docker-compose exec web vendor/bin/phpunit tests/Unit/ModelsUserTest.php

# Run with verbose output
docker-compose exec web vendor/bin/phpunit --testdox

# Run locally (requires PHP 8.2+ and mysqli extension)
composer install
vendor/bin/phpunit
```

### Static Analysis

```bash
# Run PHPStan (level 6)
composer install
vendor/bin/phpstan analyse
```

## CI/CD

### Pull Request Checks (`.github/workflows/pr-checks.yml`)

Runs on every PR targeting `main`/`master`:

| Job | What it does |
|-----|-------------|
| **PHPStan** | Static analysis at level 6 against `src/` |
| **PHPUnit** | Runs test suite with a MySQL 8.0 service container |

### Build (`.github/workflows/build.yml`)

Runs on push to `main`/`master`:

1. Authenticates to AWS via OIDC
2. Builds production image from `Dockerfile.prod` (multi-stage)
3. Tags with `<commit-sha>` and `latest`
4. Pushes to Amazon ECR

### Deploy (`.github/workflows/deploy.yml`)

Triggers automatically after a successful build (or via manual dispatch):

1. Configures `kubectl` for the EKS cluster
2. Substitutes the image tag into `k8s/deployment.yaml`
3. Applies all Kubernetes manifests
4. Waits for rollout to complete

### Required GitHub Secrets

| Secret | Description |
|--------|-------------|
| `AWS_ROLE_ARN` | IAM role ARN for OIDC authentication (used by build and deploy) |

### Required Configuration

Update these values in the workflow files to match your environment:

| Variable | File | Default |
|----------|------|---------|
| `AWS_REGION` | `build.yml`, `deploy.yml` | `us-east-1` |
| `ECR_REPOSITORY` | `build.yml`, `deploy.yml` | `php-mysql-crud` |
| `EKS_CLUSTER` | `deploy.yml` | `php-crud-cluster` |
| `K8S_NAMESPACE` | `deploy.yml` | `php-crud` |

Also update in `k8s/ingress.yaml`:
- `spec.rules[0].host` — your domain
- `alb.ingress.kubernetes.io/certificate-arn` — your ACM certificate ARN

And in `k8s/secret.yaml`:
- Replace placeholder `DB_PASSWORD` with a real secret management solution (External Secrets Operator, Sealed Secrets, or Vault)

## Development

### Start in Development Mode

```bash
docker-compose up -d
```

### View Logs

```bash
docker-compose logs -f web
docker-compose logs -f db
```

### Stop Services

```bash
docker-compose down
```

### Stop and Remove Data

```bash
docker-compose down -v
```

### Enter PHP Container Shell

```bash
docker-compose exec web bash
```

## Environment Variables

Configuration is controlled via environment variables defined in `docker-compose.yml`:

- `DB_HOST`: MySQL hostname (default: `db`)
- `DB_NAME`: Database name (default: `crud_app`)
- `DB_USER`: Database user (default: `crud_user`)
- `DB_PASSWORD`: Database password (default: `crud_password`)

You can override these by creating a `.env` file (see `.env.example`).

## Docker Services

### Web Service
- Image: PHP 8.2 with Apache
- Port: 8080
- Volume: `./` → `/var/www/html`

### Database Service
- Image: MySQL 8.0
- Port: 3306
- Volume: `./database/init.sql` → initialization script

### phpMyAdmin Service
- Image: phpMyAdmin latest
- Port: 8081
- Connects to MySQL service

## Troubleshooting

### Database Connection Failed
1. Check if MySQL container is running: `docker-compose ps`
2. Check logs: `docker-compose logs db`
3. Ensure ports aren't in use: `lsof -i :3306`

### Permission Denied Errors
```bash
sudo chmod -R 755 logs/
```

### Port Already in Use
Modify ports in `docker-compose.yml`:
```yaml
ports:
  - "8090:80"    # Use 8090 instead of 8080
```

### Rebuild Images After Changes
```bash
docker-compose build --no-cache
docker-compose up -d
```

## Security Notes

This is a demonstration application. For production:
- Use environment variables or a secret manager for sensitive data
- Implement authentication and authorization
- Add HTTPS/SSL
- Use stronger input validation
- Implement CSRF protection
- Add logging and monitoring
- Use a proper session management system

## License

MIT License
