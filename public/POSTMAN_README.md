# 🚀 TecDoc API v2 - Postman Collection

Complete Postman collection for testing the TecDoc API v2 with authentication, vehicle data, and article search endpoints.

## 📁 Files

- **`TecDoc_API_v2.postman_collection.json`** - Main collection with all endpoints
- **`TecDoc_API_v2.postman_environment.json`** - Environment variables
- **`POSTMAN_README.md`** - This documentation

## 🔧 Setup Instructions

### 1. Import Collection & Environment

1. Open Postman
2. Click **Import** button
3. Upload both JSON files:
   - `TecDoc_API_v2.postman_collection.json`
   - `TecDoc_API_v2.postman_environment.json`

### 2. Select Environment

1. In top-right corner, select **"TecDoc API v2 Environment"**
2. The base URL will be automatically set to: `http://koupkastecdoc.gr/tecdoc-api/public/api`

### 3. Ready to Use!

**The collection now includes a working API token**, so you can immediately test endpoints without creating a new token.

**Current Token:** `tecdoc_mNu...` (pre-configured and valid until Dec 24, 2025)

## ⚡ Quick Start (No Setup Required!)

1. **Select Environment**: Choose "TecDoc API v2 Environment"
2. **Test Immediately**: Run any request - token is already configured!
3. **Check Console**: View automatic validation and performance metrics

## 🧪 Enhanced Testing Features

### Auto-Validation & Monitoring

Every request automatically includes:

#### ✅ **Response Validation**
- Status code validation (200/201)
- Response time monitoring (< 5 seconds)
- JSON content-type verification
- API response structure validation

#### 📊 **Performance Monitoring**
- **🚀 Excellent**: < 100ms
- **⚡ Good**: 100-500ms  
- **⏱️ Acceptable**: 500-1000ms
- **⚠️ Slow**: > 1000ms

#### 🔒 **Security Monitoring**
- Token expiration warnings
- Rate limit monitoring (1000/hour)
- Authentication failure alerts
- Low rate limit warnings (< 50 remaining)

#### 🚀 **Cache Optimization**
- Cache header detection
- ETag support validation
- Performance improvement indicators

### Pre-Request Automation

Each request automatically:
- ✅ Validates API token existence
- ✅ Checks token expiration 
- ✅ Sets common headers (`Accept`, `User-Agent`)
- ✅ Adds request timestamps for tracking
- ✅ Warns about missing/expired tokens

### Console Logging

Watch the **Console** (bottom panel) for:
- **Rate Limit**: Current usage and remaining requests
- **Performance**: Response time categorization  
- **Warnings**: Token issues, rate limits, slow responses
- **Errors**: Detailed error messages with solutions

## Collection Structure

### Authentication
- **Create Token** - Generate new API token (auto-saves to environment)
- **Token Info** - Check token status and usage
- **Refresh Token** - Extend token expiration
- **Revoke Token** - Permanently disable token

### Enhanced Article Details 
- **Get Enhanced Article Details** - Complete article information from all database tables
- **Get Enhanced Details - Sample Articles** - Test with different article IDs

**Features:**
- **Complete Information**: All fields from ARTICLES, ARTICLE_CRITERIA, ART_MEDIA_INFO, SUPERSEDED_ARTICLES
- **No Authentication Required**: Public endpoint
- **Fast Performance**: ~1-5ms response time (cached)
- **Comprehensive Testing**: Built-in validation and performance monitoring

**Response Includes:**
- Basic article information (ID, part number, brand, etc.)
- Technical specifications (criteria values)
- Media files and images
- Replacement/superseded article information
- Performance metrics

### Vehicle Data
- **Vehicle Types** - Get available vehicle types (PC, CV, MC)
- **Manufacturers** - Get manufacturers by vehicle type
- **Models by Manufacturer** - Get model series
- **Vehicle Versions** - Get specific vehicle versions

### 🔧 Articles & Parts
- **Part Categories** - Get available part categories
- **Search Articles** - Search for articles/parts
- **Article Details** - Get detailed article information (v2 authenticated)

### 📊 Testing & Examples
- **Complete Workflow Test** - End-to-end test with known IDs
- **Rate Limit Test** - Check rate limiting headers
- **Cache Test** - Test ETag conditional requests

## 🚀 How to Use Enhanced Article Details

### Quick Start
1. **Import** both collection and environment files
2. **No Setup Required** - The enhanced endpoint is public (no token needed)
3. **Run "Get Enhanced Article Details"** - Uses sample article ID 102225
4. **Check Console** for detailed output and validation results

### Sample Article IDs Available
The environment includes several sample article IDs for testing:
- `102225` - POLMO oil filter (default)
- `102226` - Alternative sample 
- `102227` - Alternative sample
- `102228` - Alternative sample
- `102229` - Alternative sample

### What You'll See
**Console Output:**
```
📋 Article Number: 00.254
🏭 Brand: POLMO
🔧 Specifications count: 12
📷 Media files count: 2
⚡ Query time: 1.47ms
💾 Cached: true
🔓 Public endpoint works without authentication
```

**Response Structure:**
- `basic_info` - Complete article data from ARTICLES table
- `specifications` - Technical criteria from ARTICLE_CRITERIA table
- `media` - Images/files from ART_MEDIA_INFO table
- `replacements` - Superseded articles from SUPERSEDED_ARTICLES table
- `performance` - Query metrics and cache status

### Testing Different Articles
1. **Change article_id** environment variable
2. **Or manually edit** the URL in individual requests
3. **Run tests** to validate response structure

## 🎯 Key Features

### Auto-Variable Management
- **Token auto-save** after creation
- **ID extraction** from responses for chained requests
- **Environment switching** support

### Light vs Full Modes
- **Light Mode** (`?mode=light`) - Essential data only
- **Full Mode** (`?mode=full`) - Complete data with metadata

### Built-in Tests
- **Response validation** for all requests
- **Rate limit monitoring** in console
- **API version checking**

## 🚀 Quick Start Workflow

1. **Create Token**: Run "Create Token" request
2. **Get Vehicle Types**: Run "Vehicle Types (Light Mode)"
3. **Get Manufacturers**: Run "Manufacturers (Light Mode)" 
4. **Get Models**: Run "Models by Manufacturer"
5. **Get Versions**: Run "Vehicle Versions by Model"
6. **Search Articles**: Run "Search Articles (Full Mode)"
7. **Get Details**: Run "Article Details (Full Mode)"

## 📝 Environment Variables

| Variable | Description | Auto-Set |
|----------|-------------|----------|
| `base_url` | API base URL | ✅ |
| `api_token` | Authentication token | ✅ |
| `manufacturer_id` | Selected manufacturer | ✅ |
| `model_id` | Selected model series | ✅ |
| `version_id` | Selected vehicle version | ✅ |
| `article_id` | Selected article | ✅ |

## 🔍 Testing Features

### Rate Limiting
- Monitor `X-RateLimit-Remaining` header
- 1000 requests per hour limit
- Auto-logged in console

### Caching
- ETag support for conditional requests
- Cache headers validation
- Performance monitoring

### Error Handling
- Comprehensive error responses
- Validation error details
- Authentication failures

## 💡 Tips

1. **Start with Light Mode** for faster responses
2. **Use Full Mode** when you need complete data
3. **Check Console** for auto-extracted IDs
4. **Monitor Rate Limits** in response headers
5. **Use Environment Variables** for dynamic requests

## 🆘 Troubleshooting

### Token Issues
- Ensure token is created first
- Check token expiration in environment
- Verify `Authorization` header format

### Missing Data
- Check if IDs are auto-extracted correctly
- Manually set environment variables if needed
- Verify API responses contain expected data

### Rate Limiting
- Check `X-RateLimit-Remaining` header
- Wait for rate limit reset if exceeded
- Consider using refresh token to reset counter

## 📞 Support

For API documentation and support:
- **API Docs**: http://koupkastecdoc.gr/tecdoc-api/public/api-docs.html
- **Base URL**: http://koupkastecdoc.gr/tecdoc-api/public/api/v2

---

**Version**: 2.0 | **Last Updated**: November 2025
