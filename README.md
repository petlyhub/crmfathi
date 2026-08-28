# AEOX SEO - AI Search Optimization Platform for WordPress

[![WordPress Plugin](https://img.shields.io/badge/WordPress-6.0%2B-blue)](https://wordpress.org/)
[![PHP Version](https://img.shields.io/badge/PHP-7.4%2B-purple)](https://php.net/)
[![License](https://img.shields.io/badge/License-GPL%20v2-green)](https://www.gnu.org/licenses/gpl-2.0.html)

**AEOX SEO** is a comprehensive AI-powered search optimization platform for WordPress that combines traditional SEO with Answer Engine Optimization (AEO), Generative Engine Optimization (GEO), and Entity-Based Optimization to maximize your content's visibility across all search platforms.

## 🚀 Features

### Multi-Engine Optimization
- **SEO Engine**: Traditional search engine optimization analysis
  - Title & meta description optimization
  - Heading structure analysis
  - Content quality scoring
  - Internal & external link analysis
  - Image optimization checks

- **AEO Engine**: Answer Engine Optimization for AI assistants
  - Question detection & analysis
  - Answer quality scoring
  - Featured snippet optimization
  - Voice search readiness
  - Conversational content structuring

- **GEO Engine**: Generative Engine Optimization for AI models
  - Content entity extraction
  - Knowledge graph optimization
  - Citation & fact verification
  - Authority signal enhancement

### Core Capabilities

#### 📊 Advanced Scoring System
Weighted scoring algorithm that evaluates:
- Content quality & relevance
- Entity density & relationships
- Answer completeness
- Technical SEO factors
- User experience signals

#### 🗄️ Database Integration
Comprehensive database tables for tracking:
- Content analysis results
- Entity relationships
- Schema markup status
- Keyword performance
- Questions & answers
- Facts & citations
- Link profiles
- AI visibility metrics

#### 🎯 Meta Box Integration
In-page optimization interface providing:
- Real-time content analysis
- Optimization suggestions
- Score visualization
- Quick-fix recommendations

#### ⚙️ Settings & Configuration
Flexible admin settings for:
- Engine activation/deactivation
- Scoring weight customization
- Analysis thresholds
- Cache management

#### 💾 Caching System
Performance-optimized caching with:
- WordPress object cache integration
- Transient-based fallback
- Configurable expiration times
- Automatic cache invalidation

## 📦 Installation

### Requirements
- WordPress 6.0 or higher
- PHP 7.4 or higher
- MySQL 5.6 or higher / MariaDB 10.1 or higher

### Manual Installation
1. Download the plugin ZIP file
2. Upload to `/wp-content/plugins/aeox-seo/` directory
3. Activate the plugin through the 'Plugins' menu in WordPress
4. Navigate to **AEOX SEO** in your admin dashboard

### WP-CLI Installation
```bash
wp plugin install aeox-seo --activate
```

## 🔧 Usage

### Dashboard
Access the main dashboard at **AEOX SEO → Dashboard** for:
- Overall optimization scores
- Content analysis overview
- Priority recommendations
- Performance metrics

### Content Analysis
1. Edit any post or page
2. Find the **AEOX SEO Meta Box** below the content editor
3. Review real-time analysis scores
4. Implement suggested optimizations

### Settings
Configure plugin behavior at **AEOX SEO → Settings**:
- Enable/disable specific engines
- Adjust scoring weights
- Set analysis thresholds
- Manage cache settings

## 🏗️ Architecture

```
aeox-seo/
├── aeox-seo.php              # Main plugin file
├── core/
│   ├── class-aeox-cache.php     # Caching system
│   ├── class-aeox-database.php  # Database operations
│   └── class-aeox-scorer.php    # Scoring algorithms
└── engines/
    ├── aeo/
    │   └── class-aeox-aeo-engine.php    # Answer Engine Optimization
    └── seo/
        └── class-aeox-seo-engine.php    # Traditional SEO
```

## 🛠️ Development

### Local Development Setup
```bash
# Clone the repository
git clone https://github.com/your-org/aeox-seo.git

# Install dependencies (if using Composer)
composer install

# Set up WordPress local environment
#推荐使用 LocalWP, DDEV, or Docker
```

### Running Tests
```bash
# PHPUnit tests
vendor/bin/phpunit

# WordPress PHP_CodeSniffer
vendor/bin/phpcs --standard=WordPress
```

### Code Standards
This project follows:
- [WordPress Coding Standards](https://developer.wordpress.org/coding-standards/wordpress-coding-standards/)
- PSR-4 for autoloading
- PSR-12 for code style

## 📝 API Reference

### Database Class
```php
$db = AEOX_Database::get_instance();
$results = $db->query('SELECT * FROM wp_aeox_analysis WHERE post_id = %d', $post_id);
```

### Cache Class
```php
$cache = AEOX_Cache::get_instance();
$data = $cache->get('analysis_' . $post_id);
$cache->set('analysis_' . $post_id, $data, 3600);
```

### Scorer Class
```php
$scorer = AEOX_Scorer::get_instance();
$score = $scorer->calculate($content_data, $weights);
```

## 🤝 Contributing

Contributions are welcome! Please follow these steps:

1. Fork the repository
2. Create a feature branch (`git checkout -b feature/amazing-feature`)
3. Commit your changes (`git commit -m 'Add amazing feature'`)
4. Push to the branch (`git push origin feature/amazing-feature`)
5. Open a Pull Request

### Contribution Guidelines
- Follow WordPress coding standards
- Write unit tests for new features
- Update documentation as needed
- Ensure compatibility with minimum PHP/WordPress versions

## 📄 License

This project is licensed under the GPL v2 or later - see the [LICENSE](LICENSE) file for details.

## 🙏 Credits

- **Author**: AEOX Team
- **Website**: [https://aeoxseo.com](https://aeoxseo.com)
- **Contributors**: See contributors list

## 📞 Support

- **Documentation**: [https://aeoxseo.com/docs](https://aeoxseo.com/docs)
- **Support Forum**: [WordPress.org Support](https://wordpress.org/support/plugin/aeox-seo)
- **Email**: support@aeoxseo.com

## 🗺️ Roadmap

- [ ] REST API endpoints
- [ ] Schema.org markup generation
- [ ] Entity recognition engine
- [ ] Multi-language support
- [ ] WooCommerce integration
- [ ] Custom post type support
- [ ] Bulk analysis tools
- [ ] Export/Import functionality
- [ ] White-label options

---

**Version:** 0.1.0  
**Last Updated:** 2025  
**Tested up to:** WordPress 6.7