# 🚗 TecDoc REST API - Enhanced Automotive Parts Search System

![logo_teal_horizontal](https://github.com/user-attachments/assets/b94cfbf5-6799-462e-bad9-36b3c3ffbe85)


<p align="center">
  <img src="https://img.shields.io/badge/Laravel-12.0-FF2D20?style=for-the-badge&logo=laravel&logoColor=white" alt="Laravel 12.0">
  <img src="https://img.shields.io/badge/PHP-8.2+-777BB4?style=for-the-badge&logo=php&logoColor=white" alt="PHP 8.2+">
  <img src="https://img.shields.io/badge/Redis-Cache-DC382D?style=for-the-badge&logo=redis&logoColor=white" alt="Redis">
  <img src="https://img.shields.io/badge/MySQL-Database-4479A1?style=for-the-badge&logo=mysql&logoColor=white" alt="MySQL">
  <img src="https://img.shields.io/badge/Bootstrap-5.3-7952B3?style=for-the-badge&logo=bootstrap&logoColor=white" alt="Bootstrap 5.3">
</p>

<p align="center">
  <strong>Developed by EnigMart</strong><br>
  Advanced automotive parts search and management system built on TecDoc database
</p>

---

## 📋 Project Overview

The **TecDoc REST API** is a comprehensive automotive parts search and management system that provides advanced search capabilities, detailed product information, and optimized performance through modern web technologies. Built specifically for automotive industry professionals and enthusiasts.

### 🎯 Key Features

- **🔍 Advanced Search**: Multi-level vehicle and part search with intelligent filtering
- **📊 Enhanced Product Details**: Complete product information including specifications, media, and barcode data
- **⚡ High Performance**: Redis caching, database optimization, and sub-2ms response times
- **🔐 Secure API**: Token-based authentication with rate limiting and scope management
- **📱 Modern UI**: Responsive design with Bootstrap 5.3 and interactive components
- **📈 Real-time Analytics**: Performance monitoring and cache statistics

---

## 🛠️ Technical Specifications

### **Backend Technologies**

| Component | Technology | Version | Purpose |
|-----------|------------|---------|---------|
| **Framework** | Laravel | 12.0 | Core application framework |
| **Language** | PHP | 8.2+ | Server-side programming |
| **Database** | MySQL | 8.0+ | TecDoc database (14M+ records) |
| **Cache** | Redis | 7.0+ | High-performance caching layer |
| **Web Server** | Apache | 2.4+ | HTTP server with mod_rewrite |

### **Frontend Technologies**

| Component | Technology | Version | Purpose |
|-----------|------------|---------|---------|
| **CSS Framework** | Bootstrap | 5.3 | Responsive UI components |
| **Icons** | Font Awesome | 6.0+ | Icon library |
| **JavaScript** | Vanilla JS | ES6+ | Interactive functionality |
| **Build Tool** | Vite | 7.0+ | Asset compilation and bundling |
| **CSS Preprocessor** | TailwindCSS | 4.0 | Utility-first CSS framework |

### **Development Tools**

| Tool | Purpose | Configuration |
|------|---------|---------------|
| **Composer** | PHP dependency management | PSR-4 autoloading |
| **NPM** | Node.js package management | ES modules support |
| **Laravel Pint** | Code formatting and linting | PSR-12 standard |
| **PHPUnit** | Unit and feature testing | Laravel test suite |

---

## 🏗️ System Architecture

### **API Structure**

```
📁 TecDoc REST API
├── 🔐 Authentication Layer (v2)
│   ├── Token-based authentication
│   ├── Rate limiting (1000 req/hour)
│   └── Scope-based permissions
├── 🚗 Vehicle Search Engine
│   ├── Optimized search algorithms
│   ├── Multi-level filtering
│   └── Real-time brand suggestions
├── 📊 Article Management
│   ├── Enhanced product details
│   ├── Barcode integration (EAN)
│   └── Media file handling
└── ⚡ Performance Layer
    ├── Redis caching (30min TTL)
    ├── Database indexing
    └── Query optimization
```

### **Database Schema**

The system utilizes the official **TecDoc 2024 Q4** database with the following key tables:

| Table | Records | Purpose |
|-------|---------|---------|
| **ARTICLES** | 2.8M+ | Core product information |
| **ART_LOOKUP** | 14M+ | Barcode and alternative numbers |
| **ARTICLE_CRITERIA** | 45M+ | Technical specifications |
| **ART_MEDIA_INFO** | 1.2M+ | Product images and documents |
| **PASSENGER_CARS** | 85K+ | Vehicle compatibility data |

---

## 🚀 Installation & Setup

### **System Requirements**

- **PHP**: 8.2 or higher
- **MySQL**: 8.0 or higher
- **Redis**: 7.0 or higher
- **Apache**: 2.4+ with mod_rewrite
- **Node.js**: 18+ (for asset compilation)
- **Memory**: 2GB+ RAM recommended
- **Storage**: 50GB+ for TecDoc database

### **Installation Steps**

1. **Clone Repository**
   ```bash
   git clone https://github.com/enigmart/TecDoc-RestApi.git
   cd TecDoc-RestApi
   ```

2. **Install Dependencies**
   ```bash
   # PHP dependencies
   composer install
   
   # Node.js dependencies
   npm install
   ```

3. **Environment Configuration**
   ```bash
   # Copy environment file
   cp .env.example .env
   
   # Generate application key
   php artisan key:generate
   ```

4. **Database Setup**
   ```bash
   # Configure database in .env file
   DB_CONNECTION=mysql
   DB_HOST=127.0.0.1
   DB_PORT=3306
   DB_DATABASE=tecdoc2024q4
   DB_USERNAME=your_username
   DB_PASSWORD=your_password
   
   # Configure Redis
   REDIS_HOST=127.0.0.1
   REDIS_PASSWORD=null
   REDIS_PORT=6379
   ```

5. **Build Assets**
   ```bash
   # Development
   npm run dev
   
   # Production
   npm run build
   ```

6. **Start Services**
   ```bash
   # Laravel development server
   php artisan serve
   
   # Or use the comprehensive dev script
   composer run dev
   ```

---

## 📚 API Documentation

### **Base URL**
```
Production: https://koupkastecdoc.gr/tecdoc-api/public/api/
Development: http://localhost:8000/api/
```

### **Authentication**

The API uses **Bearer Token** authentication for v2 endpoints:

```bash
# Create API token
curl -X POST "https://koupkastecdoc.gr/tecdoc-api/public/api/v2/auth/create-token" \
  -H "Content-Type: application/json" \
  -d '{"name": "My API Token", "scopes": ["read", "write"]}'

# Use token in requests
curl -H "Authorization: Bearer tecdoc_your_token_here" \
  "https://koupkastecdoc.gr/tecdoc-api/public/api/v2/manufacturers"
```

### **Core Endpoints**

#### **🔍 Search & Discovery**
- `GET /optimized-search/manufacturers` - Get vehicle manufacturers
- `GET /optimized-search/manufacturers/{id}/models` - Get model series
- `GET /optimized-search/models/{id}/versions` - Get vehicle versions
- `POST /optimized-search/search-articles` - Search automotive parts

#### **📊 Product Information**
- `GET /articles/{id}/details` - Enhanced product details (public)
- `GET /v2/articles/{id}/details` - Standard product details (authenticated)
- `GET /optimized-search/part-categories` - Available part categories

#### **🔐 Authentication (v2)**
- `POST /v2/auth/create-token` - Generate API token
- `GET /v2/auth/token-info` - Token information
- `POST /v2/auth/refresh-token` - Extend token expiration
- `DELETE /v2/auth/revoke-token` - Revoke token

### **Response Format**

All API responses follow a consistent structure:

```json
{
  "success": true,
  "message": "Data retrieved successfully",
  "data": {
    // Response data
  },
  "meta": {
    "pagination": {
      "total": 1500,
      "per_page": 20,
      "current_page": 1,
      "last_page": 75
    }
  },
  "performance": {
    "query_time_ms": 1.47,
    "cached": true
  },
  "api_info": {
    "version": "2.0",
    "timestamp": "2024-11-24T15:30:00Z"
  }
}
```

---

## 🎨 Frontend Components

### **Search Interface**
- **Advanced Vehicle Search**: Multi-step vehicle selection
- **Smart Filtering**: Real-time brand and category filters
- **Expandable Results**: Inline product details with + button
- **Barcode Integration**: EAN barcode display and copy functionality

### **User Interface Features**
- **Responsive Design**: Mobile-first approach with Bootstrap 5.3
- **Loading States**: Skeleton screens and progress indicators
- **Interactive Elements**: Smooth animations and hover effects
- **Accessibility**: ARIA labels and keyboard navigation support

### **Available Pages**
- `optimized-search.html` - Main search interface
- `api-docs.html` - Interactive API documentation
- `api-tester.html` - API testing interface
- `advanced-search.html` - Legacy search interface

---

## ⚡ Performance Optimizations

### **Caching Strategy**

| Cache Type | TTL | Purpose |
|------------|-----|---------|
| **Search Results** | 30 minutes | Article search responses |
| **Vehicle Data** | 4 hours | Manufacturers, models, versions |
| **Article Details** | 1 hour | Product specifications and media |
| **Brand Lists** | 2 hours | Available brand filters |

### **Database Optimizations**

- **Indexed Queries**: Strategic indexes on frequently searched columns
- **Query Optimization**: Reduced JOIN complexity and optimized WHERE clauses
- **Connection Pooling**: Efficient database connection management
- **Pagination**: Limit-based pagination for large result sets

### **Frontend Optimizations**

- **Asset Bundling**: Vite-based asset compilation and minification
- **Lazy Loading**: Progressive loading of product details
- **Image Optimization**: WebP format support for product images
- **Code Splitting**: Modular JavaScript for faster page loads

---

## 🔧 Configuration

### **Environment Variables**

```env
# Application
APP_NAME="TecDoc API"
APP_ENV=production
APP_DEBUG=false
APP_URL=https://koupkastecdoc.gr/tecdoc-api/public

# Database
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=tecdoc2024q4

# Redis Cache
REDIS_HOST=127.0.0.1
REDIS_PASSWORD=null
REDIS_PORT=6379
CACHE_DRIVER=redis

# Performance
CACHE_SEARCH_TTL=1800
CACHE_VEHICLE_TTL=14400
CACHE_ARTICLE_TTL=3600
```

### **Cache Configuration**

```php
// config/cache.php
'default' => 'redis',
'stores' => [
    'redis' => [
        'driver' => 'redis',
        'connection' => 'cache',
        'lock_connection' => 'default',
    ],
],
```

---

## 📊 Monitoring & Analytics

### **Performance Metrics**

- **Response Times**: Average 1-5ms for cached requests
- **Cache Hit Rate**: 85%+ for search operations
- **Database Queries**: Optimized to <10 queries per request
- **Memory Usage**: <512MB per request

### **Available Endpoints for Monitoring**

- `GET /optimized-search/cache/stats` - Cache statistics
- `DELETE /optimized-search/cache/clear` - Clear all caches
- `GET /health` - System health check

---

## 🧪 Testing

### **Postman Collection**

The project includes a comprehensive Postman collection with:

- **Pre-request Scripts**: Automatic token validation
- **Test Suites**: Response validation and performance testing
- **Environment Variables**: Ready-to-use configuration
- **Sample Requests**: All API endpoints with examples

**Files:**
- `TecDoc_API_v2.postman_collection.json`
- `TecDoc_API_v2.postman_environment.json`
- `POSTMAN_README.md`

### **Running Tests**

```bash
# PHP Unit Tests
php artisan test

# API Testing with Postman
# Import collection and environment files into Postman
# Run the complete test suite
```

---

## 🚀 Deployment

### **Production Checklist**

- [ ] Configure production environment variables
- [ ] Set up SSL certificates
- [ ] Configure Redis for production
- [ ] Optimize database indexes
- [ ] Set up monitoring and logging
- [ ] Configure backup strategies

### **Performance Tuning**

```bash
# Laravel optimizations
php artisan config:cache
php artisan route:cache
php artisan view:cache

# Composer optimizations
composer install --optimize-autoloader --no-dev
```

---

## 📈 Project Statistics

| Metric | Value |
|--------|-------|
| **Total Lines of Code** | 15,000+ |
| **API Endpoints** | 25+ |
| **Database Tables Used** | 12+ |
| **Frontend Components** | 8+ |
| **Cache Keys** | 50+ |
| **Test Cases** | 30+ |

---

## 🤝 Contributing

We welcome contributions to the TecDoc REST API project. Please follow these guidelines:

1. **Fork** the repository
2. **Create** a feature branch
3. **Commit** your changes with clear messages
4. **Test** your implementation thoroughly
5. **Submit** a pull request with detailed description

### **Code Standards**

- **PHP**: PSR-12 coding standard
- **JavaScript**: ES6+ with consistent formatting
- **CSS**: BEM methodology for class naming
- **Documentation**: Comprehensive inline comments

---

## 📞 Support & Contact

**EnigMart Development Team**

- **Project Repository**: [GitHub - TecDoc-RestApi](https://github.com/enigmart/TecDoc-RestApi)
- **Documentation**: Available in `/public/api-docs.html`
- **API Testing**: Use `/public/api-tester.html`

---

## 📄 License

This project is proprietary software developed by **EnigMart**. All rights reserved.

**TecDoc Database**: Licensed from TecDoc Informations System GmbH
**Laravel Framework**: Open-source under MIT License

---

<p align="center">
  <strong>Built with ❤️ by EnigMart</strong><br>
  <em>Automotive Technology Solutions</em>
</p>
