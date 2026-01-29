# Code-One — Laravel Blogging Platform

<div align="center">

![Laravel](https://img.shields.io/badge/Laravel-10.x-FF2D20?style=for-the-badge&logo=laravel)
![Docker](https://img.shields.io/badge/Docker-Ready-2496ED?style=for-the-badge&logo=docker)
![Redis](https://img.shields.io/badge/Redis-Storage-DC382D?style=for-the-badge&logo=redis)
![Tailwind](https://img.shields.io/badge/Tailwind-UI-06B6D4?style=for-the-badge&logo=tailwindcss)
![Vite](https://img.shields.io/badge/Vite-Bundler-646CFF?style=for-the-badge&logo=vite)
![Pest](https://img.shields.io/badge/Pest-Security_Tests-000000?style=for-the-badge&logo=pest)
![Status](https://img.shields.io/badge/Status-100%25_Complete-success?style=for-the-badge)

</div>

Code-One is a production-grade, Medium-inspired blogging ecosystem built with **Laravel**. It is engineered with a "performance-first" mindset, utilizing **Redis-backed authentication caching**, a fully containerized architecture, and a specialized security testing suite designed to mitigate modern web vulnerabilities.

---

## 📖 Table of Contents
1.  [Project Overview & Goals](#-project-overview--goals)
2.  [Features Roadmap](#-features-roadmap)
3.  [Architecture & Stack](#-architecture--stack)
4.  [Quickstart Guide](#-quickstart-guide)
5.  [Auth & Redis Deep Dive](#-auth--redis-deep-dive)
6.  [DevOps & CI/CD Enhancements](#-devops--cicd-enhancements)
7.  [Testing & Security](#%EF%B8%8F-testing--security)
8.  [Debugging & Monitoring](#-debugging--monitoring)
9.  [Troubleshooting & FAQ](#-troubleshooting--faq)
10. [Contribution Guidelines](#-contribution-guidelines)

---

## 🚀 Project Overview & Goals
The Code-One platform is designed to bridge the gap between simple CMS tools and high-scale publishing platforms.
* **Performance:** Drastically reduced MySQL overhead by offloading frequent `auth()->user()` lookups to Redis.
* **Security:** A comprehensive suite of 93+ automated tests ensures protection against XSS, SQLi, and CSRF.
* **DX (Developer Experience):** A fully reproducible environment via Docker ensures "it works on my machine" for every contributor.

---

## ✨ Features Roadmap
* **🌙 UI/UX:** Full Dark Mode implementation with system preference detection and LocalStorage persistence.
* **⚡ Optimization:** Core Web Vitals optimization (Lighthouse score 90+), including lazy-loading and WebP conversions.
* **🛠 Infrastructure:** Multi-service Docker setup (App, Nginx, DB, Redis, Queue, Mailpit).
* **📝 API:** Fully documented OpenAPI (Swagger) specifications with a built-in UI.

---

## 🛠 Architecture & Stack
| Service | Technology | Description |
| :--- | :--- | :--- |
| **Backend** | Laravel 10 (PHP 8.3) | Core application logic and API. |
| **Frontend** | Blade + Tailwind CSS | Highly responsive, utility-first UI. |
| **Cache** | Redis | Auth caching, session storage, and queue broker. |
| **Database** | MySQL 8.0 | Relational data persistence. |
| **Testing** | Pest | Modern testing framework for Unit, Feature, and Security tests. |

---

## 🚦 Quickstart Guide

### 🐳 Docker (Recommended)
1.  **Clone Repository:**
    ```bash
    git clone [https://github.com/ahmedsalah-tech/Code-One.git](https://github.com/ahmedsalah-tech/Code-One.git)
    cd "Code-One/Blogging Platform"
    ```
2.  **Environment Setup:** `cp .env.example .env`
3.  **One-Command Initialization:**
    ```bash
    chmod +x docker-setup.sh && ./docker-setup.sh
    docker-compose up -d
    ```
4.  **Database Migration:**
    ```bash
    docker exec -it blogging_app php artisan migrate --seed
    ```

---

## 🔐 Auth & Redis Deep Dive
To avoid redundant database queries on every authenticated request, Code-One implements a custom `CachedUserProvider`.

### How it works:
When the application calls `Auth::user()`, the provider checks Redis for the key `auth:user:{id}`.
* **Hit:** Returns the serialized model immediately (approx. 1-2ms).
* **Miss:** Queries MySQL, caches the result for 300 seconds (configurable via `AUTH_CACHE_TTL`), and returns the user.

> **Note:** To prevent stale data, ensure you use a **Model Observer** to `forget` the cache key whenever a user profile is updated.

---

## 🏗 DevOps & CI/CD Enhancements
Our infrastructure is built for scale. Future iterations target a more granular Docker strategy:

* **Environment-Specific Images:**
    * **Development:** Includes Xdebug, hot-reloading tools, and non-optimized assets for better debugging.
    * **Production:** Multi-stage builds that produce lean, immutable images containing only optimized PHP-FPM code and minified assets.
* **Pipeline Health Checks:** * Integrate automated **container health checks** within the CI/CD pipeline to ensure services (DB/Redis) are ready before running migrations or tests.
    * Integration of automated rollback triggers if a deployment fails health checks.
* **Multi-Stage Asset Delivery:** Leveraging Vite during the build phase to ensure the production image is ready for immediate horizontal scaling.

---

## 🛡️ Testing & Security
Code-One treats security as a first-class citizen with **93 dedicated tests**.

* **Run All Security Tests:**
    ```bash
    docker exec -it blogging_app php artisan test --group=security
    ```
* **Coverage Includes:**
    * **XSS Protection:** Validates that script tags and malicious event handlers are escaped.
    * **SQL Injection:** Ensures Eloquent/PDO properly parametrizes all inputs.
    * **File Security:** Blocks malicious uploads (.php, .sh, .exe) and validates MIME types.
    * **Rate Limiting:** Protects sensitive routes (login/api) from brute-force attacks.

---

## 📋 Debugging & Monitoring

### 🔒 Protected Debug Routes
The following routes are available for developers (requires `auth` middleware):
* `GET /debug/auth-cache`: Detailed JSON output showing session ID, User ID, and DB query counts to verify cache hits.
* `GET /api/documentation`: Interactive Swagger UI for API testing.

### 📊 Monitoring Tools
* **Mailpit:** Access locally captured emails at `http://localhost:8025`.
* **Redis CLI:** Monitor auth keys: `redis-cli -n 1 KEYS "*auth:user:*"`
* **Query Logs:** Enable `DB_LOG_QUERIES=true` in `.env` to profile database performance.

---

## 🛠 Troubleshooting & FAQ

**Q: Why are my profile updates not reflecting?**
**A:** This is due to the Auth Cache. You can clear it via Tinker: `Cache::store('redis')->forget('auth:user:1')` or wait for the TTL to expire.

**Q: Docker container fails to connect to MySQL.**
**A:** Ensure the `DB_HOST` in your `.env` is set to the service name (usually `db` or `mysql`) rather than `127.0.0.1`.

---

## 🤝 Contribution Guidelines
1.  Fork the project.
2.  Create a feature branch (`git checkout -b feature/AmazingFeature`).
3.  Ensure all tests pass (`php artisan test`).
4.  Open a Pull Request with a comprehensive description of your changes.

---
