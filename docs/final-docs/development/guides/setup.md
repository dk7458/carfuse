# Development Environment Setup

*Last updated: 2023-11-15*

This guide provides step-by-step instructions for setting up a development environment for CarFuse. Following these steps will ensure that you have all the necessary tools and configurations to work effectively on the project.

## Table of Contents
- [Prerequisites](#prerequisites)
- [System Requirements](#system-requirements)
- [Installing Dependencies](#installing-dependencies)
- [Project Setup](#project-setup)
- [Configuration](#configuration)
- [Development Tools](#development-tools)
- [Running the Application](#running-the-application)
- [Testing Setup](#testing-setup)
- [Troubleshooting](#troubleshooting)
- [Related Documentation](#related-documentation)

## Prerequisites

Before starting, ensure you have the following installed:

- **Git**: Version control system
- **PHP**: Version 8.1 or higher
- **Composer**: PHP dependency manager
- **Node.js**: Version 16 or higher
- **npm**: JavaScript package manager
- **MySQL**: Version 8.0 or higher (or MariaDB 10.5+)

## System Requirements

Recommended system specifications:

- **CPU**: 4+ cores
- **Memory**: 8GB RAM or higher
- **Disk**: 20GB free space
- **OS**: macOS, Windows 10/11, or Linux (Ubuntu 20.04+ recommended)

## Installing Dependencies

### PHP Extensions

The following PHP extensions are required:

```bash
# Ubuntu/Debian
sudo apt-get update
sudo apt-get install php8.1-cli php8.1-common php8.1-curl \
    php8.1-mbstring php8.1-mysql php8.1-xml php8.1-zip \
    php8.1-bcmath php8.1-gd php8.1-intl

# macOS (using Homebrew)
brew install php@8.1
brew install php@8.1-intl php@8.1-xdebug
```

### Composer Packages

```bash
# Install Composer if not installed
php -r "copy('https://getcomposer.org/installer', 'composer-setup.php');"
php composer-setup.php --install-dir=/usr/local/bin --filename=composer
php -r "unlink('composer-setup.php');"
```

### Node.js and npm

```bash
# Using NVM (recommended)
curl -o- https://raw.githubusercontent.com/nvm-sh/nvm/v0.39.3/install.sh | bash
source ~/.bashrc  # or source ~/.zshrc
nvm install 16
nvm use 16

# Directly
# For Ubuntu/Debian
curl -fsSL https://deb.nodesource.com/setup_16.x | sudo -E bash -
sudo apt-get install -y nodejs

# For macOS
brew install node@16
```

## Project Setup

### Cloning the Repository

```bash
# Clone the repository
git clone https://github.com/carfuse/carfuse.git
cd carfuse

# Install PHP dependencies
composer install

# Install JavaScript dependencies
npm install
```

### Setting Up the Database

```bash
# Create a new MySQL database
mysql -u root -p
```

```sql
CREATE DATABASE carfuse;
CREATE USER 'carfuse_user'@'localhost' IDENTIFIED BY 'your_password';
GRANT ALL PRIVILEGES ON carfuse.* TO 'carfuse_user'@'localhost';
FLUSH PRIVILEGES;
EXIT;
```

### Initializing the Application

```bash
# Copy environment file
cp .env.example .env

# Edit .env file with your database credentials
nano .env  # or use any text editor

# Generate application key
php artisan key:generate

# Run database migrations
php artisan migrate

# Seed the database with initial data
php artisan db:seed
```

## Configuration

### Environment Configuration

Edit the `.env` file to configure:

- Database connection details
- Mail settings
- API keys for third-party services
- Application URL and environment

Key settings to configure:

```
APP_ENV=local
APP_DEBUG=true
APP_URL=http://localhost:8000

DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=carfuse
DB_USERNAME=carfuse_user
DB_PASSWORD=your_password

MAIL_MAILER=smtp
MAIL_HOST=mailhog
MAIL_PORT=1025
MAIL_USERNAME=null
MAIL_PASSWORD=null
MAIL_ENCRYPTION=null
MAIL_FROM_ADDRESS=noreply@carfuse.com
MAIL_FROM_NAME="${APP_NAME}"
```

### Frontend Configuration

Configure frontend settings in `config/frontend.js`:

```javascript
// Default configuration
module.exports = {
  devServer: {
    port: 3000,
    proxy: {
      '/api': 'http://localhost:8000'
    }
  },
  themes: {
    default: 'light'
  },
  features: {
    darkMode: true,
    analytics: false
  }
};
```

## Development Tools

### VS Code Extensions

Recommended extensions for Visual Studio Code:

- PHP Intelephense
- ESLint
- Prettier
- Tailwind CSS IntelliSense
- Alpine.js IntelliSense
- GitLens
- EditorConfig

### Browser Extensions

- Vue.js DevTools (if using Vue)
- React Developer Tools (if using React)
- Redux DevTools (if using Redux)

### Command-line Tools

```bash
# Install global development tools
npm install -g @vue/cli  # if using Vue
npm install -g create-react-app  # if using React
npm install -g tailwindcss
npm install -g eslint
```

## Running the Application

### Backend Server

```bash
# Start the PHP development server
php artisan serve  # Runs on http://localhost:8000
```

### Frontend Development

```bash
# Run the frontend development server
npm run dev  # Runs on http://localhost:3000

# Build for production
npm run build
```

### Using Docker (Alternative)

If you prefer using Docker:

```bash
# Start all services
docker-compose up -d

# Stop all services
docker-compose down

# View logs
docker-compose logs -f
```

## Testing Setup

### Setting Up PHP Tests

```bash
# Create a test database
mysql -u root -p -e "CREATE DATABASE carfuse_test;"
mysql -u root -p -e "GRANT ALL PRIVILEGES ON carfuse_test.* TO 'carfuse_user'@'localhost';"

# Run PHP tests
php artisan test

# Run specific test
php artisan test --filter=UserTest
```

### Setting Up JavaScript Tests

```bash
# Run JavaScript tests
npm test

# Run with coverage report
npm test -- --coverage

# Run specific test file
npm test -- src/components/Button.test.js
```

### End-to-End Testing

```bash
# Install Cypress
npm install -g cypress

# Open Cypress test runner
npx cypress open

# Run Cypress tests headlessly
npx cypress run
```

## Troubleshooting

### Common Issues

#### PHP Extensions Missing

If you encounter errors about missing PHP extensions:

```bash
# Check installed extensions
php -m

# Install missing extensions (example for Ubuntu/Debian)
sudo apt-get install php8.1-[extension-name]
```

#### Database Connection Issues

If you can't connect to the database:

1. Verify database credentials in `.env`
2. Ensure MySQL service is running
3. Check if the database user has proper permissions

#### Node.js and npm Issues

If you encounter Node.js errors:

```bash
# Clear npm cache
npm cache clean --force

# Check for outdated packages
npm outdated

# Update packages
npm update
```

#### Permission Issues

If you encounter permission errors:

```bash
# Fix directory permissions
sudo chown -R $USER:$USER .

# Fix permission on storage directory
chmod -R 775 storage bootstrap/cache
```

## Related Documentation

- [Coding Standards](../standards/code-style.md)
- [Git Workflow](../standards/git-workflow.md)
- [API Documentation](../../api/overview.md)
- [Architecture Overview](../../architecture/frontend/overview.md)
