# CLAUDE.md

This file provides guidance to Claude Code (claude.ai/code) when working with code in this repository.

## Project Overview
This is a Laravel 12-based fintech backend application called "Lynk" with a modular architecture. The application provides financial services including payment processing, trading integration, wallet management, and identity verification services.

## Development Commands

### PHP/Laravel Commands
- **Start development server**: `php artisan serve`
- **Run database migrations**: `php artisan migrate`
- **Clear application cache**: `php artisan cache:clear && php artisan config:clear && php artisan route:clear && php artisan view:clear`
- **Generate IDE helpers**: `php artisan ide-helper:generate && php artisan ide-helper:models && php artisan ide-helper:meta`
- **Run code formatting**: `./vendor/bin/pint`
- **Run tests**: `php artisan test` or `./vendor/bin/phpunit`
- **Start queue workers**: `php artisan queue:work`
- **Start Laravel Horizon**: `php artisan horizon`

### Frontend Assets
- **Development build**: `npm run dev`
- **Watch for changes**: `npm run watch`
- **Production build**: `npm run prod`
- **Hot reload**: `npm run hot`

### Module Commands
- **Create new module**: `php artisan module:make ModuleName`
- **Enable module**: `php artisan module:enable ModuleName`
- **Disable module**: `php artisan module:disable ModuleName`

## Architecture

### Core Structure
The application uses Laravel's standard MVC pattern with additional architectural patterns:

- **Modular Design**: Uses `nwidart/laravel-modules` for feature-based organization
  - `Modules/Grantify/`: Identity verification and grant management services
  - `Modules/Otpify/`: OTP and SMS verification services

- **Multi-tenancy**: Implements `stancl/tenancy` for multi-tenant architecture
- **Action Pattern**: Uses `app/Actions/` for business logic encapsulation
- **Service Layer**: `app/Services/` contains core business services
- **Transformer Pattern**: `app/Transformers/` for API response transformation using Fractal

### Key Directories
- `app/Actions/`: Domain-specific business actions
- `app/Enums/`: Type-safe enumerations for business constants
- `app/Jobs/`: Background job classes
- `app/Models/`: Eloquent models with relationships
- `app/Observers/`: Model event observers
- `app/Providers/`: Custom service providers
- `app/Rules/`: Custom validation rules
- `app/Services/`: Core application services
- `app/Support/`: Utility classes and helpers
- `app/Transformers/`: API response transformers

### Database Configuration
The application supports multiple database connections:
- Main application database
- Separate wallet database (`lynk_wallet_testing` for tests)
- Uses MySQL as primary database

### Key Integrations
- **Payment Processing**: Multiple trader integrations (configured via `DEFAULT_TRADER` env)
- **SMS Services**: Multi-driver SMS system (`SMS_DEFAULT_DRIVER`)
- **Identity Verification**: Absher integration for national ID verification
- **Media Management**: Spatie Media Library for file handling
- **Background Jobs**: Laravel Horizon for queue management
- **API Authentication**: JWT-based authentication with Laravel Sanctum

### Helper Functions
Global helper functions are auto-loaded from `app/Helpers/HelperFunctions.php`, including:
- Saudi ID validation (`validate_said()`)
- Duration conversion utilities
- Financial calculation helpers

### Code Quality Tools
- **Laravel Pint**: Configured with Laravel preset and method chaining indentation
- **Pre-commit hooks**: Automatically formats staged files using Pint
- **PHPUnit**: Test suite with separate Unit and Feature test directories
- **StyleCI**: External code style checking via `.styleci.yml`

### Environment Considerations
- **Multi-environment support**: Uses separate databases for testing
- **Feature flags**: Various driver configurations for different environments
- **Security**: Implements secure headers via `bepsvpt/secure-headers`
- **Debugging**: Laravel Telescope available (disabled in testing)

## Testing
- Run full test suite: `php artisan test`
- Run specific test: `php artisan test --filter TestName`
- Test database: Uses `lynk_testing` and `lynk_wallet_testing` databases
- Uses fake drivers for external services in testing environment

## Module Development
When working with modules:
1. Each module follows Laravel's structure within its directory
2. Modules have their own Controllers, Models, Routes, Views, and Config
3. Use module-specific namespaces: `Modules\ModuleName\`
4. Modules can be independently enabled/disabled

## Security Notes
- Uses JWT authentication with rotating tokens
- Implements rate limiting and secure headers
- National ID validation for Saudi Arabia
- Multi-factor authentication via OTP services
- Host whitelist configuration for security