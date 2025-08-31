# QMS API

Secure messenger API with end-to-end encryption built with Laravel 12.

## Features

- End-to-end encryption for all messages
- JWT authentication
- WebRTC support for voice and video calls
- File uploads with encryption
- PostgreSQL database with optimized configuration

## Installation

1. Clone the repository
2. Run `composer install`
3. Copy `.env.example` to `.env`
4. Generate application key: `php artisan key:generate`
5. Generate JWT secret: `php artisan jwt:secret`
6. Run migrations: `php artisan migrate`
7. Start the server: `php artisan serve`

## Docker Setup

Use `docker-compose up -d` to start the development environment.

## API Documentation

See API documentation at `/api/docs` when the server is running.
