# User Management Information System (UMIS)

## Overview

The User Management Information System (UMIS) is a project developed by the IISU under the company of Zamboanga City Medical Center. Its primary goal is to deliver better HR management while establishing a central user management system for all current and future systems to be used by the company. By successfully delivering this project, it will enhance the functionality of the company and provide better service to its employees.

## Branching Strategy

This repository follows a structured branching strategy to ensure stability in the `main` branch while allowing for thorough testing and development.

### Branches

- **main**: The production branch. Code in this branch is considered stable and ready for deployment.
- **staging**: The pre-production branch. This branch is used for final testing before code is merged into the `main` branch. Code in this branch should be stable and as close to production-ready as possible.
- **development** (optional): The integration branch for combining features and bug fixes before they are moved to the `staging` branch.
- **feature/feature-name**: Branches for individual features or bug fixes. These are merged into the `development` branch (or directly into `staging` if you skip the `development` branch).

### Workflow

1. **Feature Development**: Create a new branch from `development` (or `staging`) for each feature or bug fix.
    ```sh
    git checkout -b feature/feature-name development
    ```
   
2. **Feature Completion**: Once the feature or bug fix is complete and tested locally, merge it back into the `development` branch.
    ```sh
    git checkout development
    git merge feature/feature-name
    git branch -d feature/feature-name
    ```

3. **Integration Testing**: After all features and bug fixes are integrated into the `development` branch, perform integration testing.

4. **Staging**: When the `development` branch is stable and all tests pass, merge it into the `staging` branch for final pre-production testing.
    ```sh
    git checkout staging
    git merge development
    ```

5. **Production Deployment**: After successful testing in the `staging` branch, merge the `staging` branch into the `main` branch for production deployment.
    ```sh
    git checkout main
    git merge staging
    ```

## Getting Started

To set up the Laravel API, follow these steps:

### Requirements

Make sure you have the following installed:
- Laravel 8
- PHP (minimum version 7.3)
- Composer
- MySQL Server

### Setup Process

1. **Clone the Project**: Clone the repository to your local machine.
    ```sh
    git clone https://github.com/yourusername/your-repo-name.git
    cd your-repo-name
    ```

2. **Install Dependencies**: Use Composer to install the necessary packages.
    ```sh
    composer install
    ```

3. **Environment Configuration**: 
    - Rename the `.env.example` file to `.env`.
    - Update the `.env` file with your database credentials and other configuration settings.
    ```sh
    mv .env.example .env
    ```

4. **Generate Application Key**: Generate a new application key.
    ```sh
    php artisan key:generate
    ```

5. **Database Setup**: Run the migrations and seed the database.
    ```sh
    php artisan migrate --seed
    ```

6. **Start the Development Server**: Serve the application locally.
    ```sh
    php artisan serve
    ```

Your Laravel API should now be up and running.

## Contributing

In terms of the current setup, this project will only be allowed to be used by the employee IT team.

## License

A license will be attached here if the company plans to publish this system for ownership.

## Contact

For support or questions, you can contact the company via email at [ciis.zcmc@gmail.com](mailto:ciis.zcmc@gmail.com).
