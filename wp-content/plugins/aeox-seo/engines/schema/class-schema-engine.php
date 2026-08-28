<?php
/**
 * AEOX Schema Engine
 * 
 * Generates JSON-LD structured data for Knowledge Graph optimization.
 * Supports Organization, Person, Article, Product, FAQ, and more.
 *
 * @package AEOX
 * @since 1.0.0
 */

namespace AEOX\Engines\Schema;

use AEOX\Core\Cache;
use AEOX\Engines\Entity\Entity_Engine;

class Schema_Engine {

	/**
	 * Cache instance
	 *
	 * @var Cache
	 */
	private $cache;

	/**
	 * Entity engine instance
	 *
	 * @var Entity_Engine
	 */
	private $entity_engine;

	/**
	 * Constructor
	 */
	public function __construct() {
		$this->cache         = new Cache();
		$this->entity_engine = new Entity_Engine();
	}

	/**
	 * Generate schema for a post
	 *
	 * @param int $post_id Post ID.
	 * @return array Schema graph.
	 */
	public function generate( $post_id ) {
		$post = get_post( $post_id );
		if ( ! $post ) {
			return array();
		}

		$cache_key = "aeox_schema_{$post_id}";
		$cached    = $this->cache->get( $cache_key );
		if ( false !== $cached ) {
			return $cached;
		}

		$graph = array();

		// Add Organization schema.
		$org_schema = $this->get_organization_schema();
		if ( ! empty( $org_schema ) ) {
			$graph[] = $org_schema;
		}

		// Add Website schema.
		$graph[] = $this->get_website_schema();

		// Add WebPage schema.
		$graph[] = $this->get_webpage_schema( $post );

		// Add Article schema if applicable.
		if ( in_array( $post->post_type, array( 'post', 'article' ), true ) ) {
			$graph[] = $this->get_article_schema( $post );
		}

		// Add Product schema for WooCommerce.
		if ( 'product' === $post->post_type && class_exists( 'WooCommerce' ) ) {
			$graph[] = $this->get_product_schema( $post );
		}

		// Add BreadcrumbList schema.
		$graph[] = $this->get_breadcrumb_schema( $post );

		// Add FAQ schema if FAQs detected.
		$faq_schema = $this->get_faq_schema( $post->post_content );
		if ( ! empty( $faq_schema ) ) {
			$graph[] = $faq_schema;
		}

		$result = array(
			'@context' => 'https://schema.org',
			'@graph'   => $graph,
		);

		$this->cache->set( $cache_key, $result, DAY_IN_SECONDS );

		return $result;
	}

	/**
	 * Get Organization schema
	 *
	 * @return array|null Organization schema or null if not configured.
	 */
	private function get_organization_schema() {
		$name = get_bloginfo( 'name' );
		$url  = home_url();
		$logo = get_custom_logo();

		$schema = array(
			'@type' => 'Organization',
			'@id'   => $url . '#organization',
			'name'  => $name,
			'url'   => $url,
		);

		// Add logo if available.
		if ( $logo ) {
			preg_match( '/src=[\'"]([^\'"]+)/', $logo, $matches );
			if ( isset( $matches[1] ) ) {
				$schema['logo'] = $matches[1];
			}
		}

		// Add social profiles from settings (placeholder).
		$social_profiles = get_option( 'aeox_social_profiles', array() );
		if ( ! empty( $social_profiles ) ) {
			$schema['sameAs'] = array_values( $social_profiles );
		}

		return $schema;
	}

	/**
	 * Get Website schema
	 *
	 * @return array Website schema.
	 */
	private function get_website_schema() {
		return array(
			'@type'       => 'WebSite',
			'@id'         => home_url() . '#website',
			'url'         => home_url(),
			'name'        => get_bloginfo( 'name' ),
			'description' => get_bloginfo( 'description' ),
			'publisher'   => array(
				'@id' => home_url() . '#organization',
			),
		);
	}

	/**
	 * Get WebPage schema
	 *
	 * @param \WP_Post $post Post object.
	 * @return array WebPage schema.
	 */
	private function get_webpage_schema( $post ) {
		$schema = array(
			'@type'      => 'WebPage',
			'@id'        => get_permalink( $post ) . '#webpage',
			'url'        => get_permalink( $post ),
			'name'       => get_the_title( $post ),
			'isPartOf'   => array(
				'@id' => home_url() . '#website',
			),
			'datePublished' => get_the_date( 'c', $post ),
			'dateModified'  => get_the_modified_date( 'c', $post ),
		);

		// Add author if exists.
		$author_id = $post->post_author;
		if ( $author_id ) {
			$schema['author'] = array(
				'@id' => get_author_posts_url( $author_id ) . '#person',
			);
		}

		return $schema;
	}

	/**
	 * Get Article schema
	 *
	 * @param \WP_Post $post Post object.
	 * @return array Article schema.
	 */
	private function get_article_schema( $post ) {
		return array(
			'@type'         => 'Article',
			'@id'           => get_permalink( $post ) . '#article',
			'headline'      => get_the_title( $post ),
			'datePublished' => get_the_date( 'c', $post ),
			'dateModified'  => get_the_modified_date( 'c', $post ),
			'author'        => array(
				'@id' => get_author_posts_url( $post->post_author ) . '#person',
			),
			'publisher'     => array(
				'@id' => home_url() . '#organization',
			),
			'mainEntityOfPage' => array(
				'@id' => get_permalink( $post ) . '#webpage',
			),
		);
	}

	/**
	 * Get Product schema (WooCommerce)
	 *
	 * @param \WP_Post $post Post object.
	 * @return array Product schema.
	 */
	private function get_product_schema( $post ) {
		if ( ! class_exists( 'WooCommerce' ) || ! class_exists( 'WC_Product' ) ) {
			return array();
		}

		$product = wc_get_product( $post->ID );
		if ( ! $product ) {
			return array();
		}

		$schema = array(
			'@type'       => 'Product',
			'@id'         => get_permalink( $post ) . '#product',
			'name'        => $product->get_name(),
			'description' => $product->get_description(),
			'image'       => wp_get_attachment_image_url( $product->get_image_id(), 'full' ),
			'sku'         => $product->get_sku(),
			'offers'      => array(
				'@type'         => 'Offer',
				'price'         => $product->get_price(),
				'priceCurrency' => get_woocommerce_currency(),
				'availability'  => 'instock' === $product->get_stock_status() ? 'https://schema.org/InStock' : 'https://schema.org/OutOfStock',
			),
		);

		return $schema;
	}

	/**
	 * Get BreadcrumbList schema
	 *
	 * @param \WP_Post $post Post object.
	 * @return array Breadcrumb schema.
	 */
	private function get_breadcrumb_schema( $post ) {
		$breadcrumbs = array();
		$trail       = array();

		// Build breadcrumb trail.
		if ( function_exists( 'yoast_breadcrumb' ) || function_exists( 'rank_math_the_breadcrumbs' ) ) {
			// Use existing breadcrumb if available from other plugins.
			// For now, create simple trail.
			$trail[] = array(
				'name' => get_bloginfo( 'name' ),
				'id'   => home_url(),
			);

			// Add category if exists.
			$categories = get_the_category( $post->ID );
			if ( ! empty( $categories ) ) {
				$category = $categories[0];
				$trail[]  = array(
					'name' => $category->name,
					'id'   => get_category_link( $category->term_id ),
				);
			}

			// Add current page.
			$trail[] = array(
				'name' => get_the_title( $post ),
				'id'   => get_permalink( $post ),
			);
		} else {
			// Simple fallback.
			$trail = array(
				array(
					'name' => get_bloginfo( 'name' ),
					'id'   => home_url(),
				),
				array(
					'name' => get_the_title( $post ),
					'id'   => get_permalink( $post ),
				),
			);
		}

		// Format for schema.
		foreach ( $trail as $index => $crumb ) {
			$breadcrumbs[] = array(
				'@type'    => 'ListItem',
				'position' => $index + 1,
				'name'     => $crumb['name'],
				'item'     => $crumb['id'],
			);
		}

		return array(
			'@type'           => 'BreadcrumbList',
			'@id'             => get_permalink( $post ) . '#breadcrumb',
			'itemListElement' => $breadcrumbs,
		);
	}

	/**
	 * Get FAQ schema from content
	 *
	 * @param string $content Post content.
	 * @return array|null FAQ schema or null if no FAQs found.
	 */
	private function get_faq_schema( $content ) {
		$faqs = array();

		// Look for FAQ patterns (question followed by answer).
		$patterns = array(
			'/<h[2-3]>([^<]*\?[^<]*)<\/h[2-3]>\s*<p>(.*?)<\/p>/s',
			'/<strong>([^<]*\?[^<]*)<\/strong>\s*<p>(.*?)<\/p>/s',
		);

		foreach ( $patterns as $pattern ) {
			if ( preg_match_all( $pattern, $content, $matches, PREG_SET_ORDER ) ) {
				foreach ( $matches as $match ) {
					$faqs[] = array(
						'questionName'    => strip_tags( $match[1] ),
						'acceptedAnswer'  => array(
							'@type'      => 'Answer',
							'text'       => wp_strip_all_tags( $match[2] ),
						),
					);
				}
			}
		}

		if ( empty( $faqs ) ) {
			return null;
		}

		return array(
			'@type'           => 'FAQPage',
			'@id'             => get_permalink( get_queried_object() ) . '#faq',
			'mainEntity'      => $faqs,
		);
	}

	/**
	 * Output schema to frontend
	 *
	 * @param int $post_id Post ID.
	 */
	public function output_schema( $post_id ) {
		$schema = $this->generate( $post_id );

		if ( empty( $schema ) || empty( $schema['@graph'] ) ) {
			return;
		}

		// Check for conflicts with other SEO plugins.
		if ( $this->has_conflicting_schema() ) {
			// Skip output if conflict detected.
			return;
		}

		echo '<script type="application/ld+json">' . wp_json_encode( $schema ) . '</script>' . "\n";
	}

	/**
	 * Check for conflicting schema from other plugins
	 *
	 * @return bool True if conflict detected.
	 */
	private function has_conflicting_schema() {
		// Check if Yoast, Rank Math, or AIOSEO are active.
		$conflicting_plugins = array(
			'wordpress-seo/wp-seo.php',
			'seo-by-rank-math/rank-math.php',
			'all-in-one-seo-pack/all_in_one_seo_pack.php',
		);

		foreach ( $conflicting_plugins as $plugin ) {
			if ( is_plugin_active( $plugin ) ) {
				// Check if they output schema on this page type.
				// For now, return true to avoid duplicates.
				return true;
			}
		}

		return false;
	}

	/**
	 * Validate schema structure
	 *
	 * @param array $schema Schema array.
	 * @return array Validation results.
	 */
	public function validate( $schema ) {
		$errors = array();

		if ( empty( $schema['@context'] ) ) {
			$errors[] = 'Missing @context';
		}

		if ( empty( $schema['@graph'] ) || ! is_array( $schema['@graph'] ) ) {
			$errors[] = 'Missing or invalid @graph';
		} else {
			foreach ( $schema['@graph'] as $index => $item ) {
				if ( empty( $item['@type'] ) ) {
					$errors[] = "Item {$index} missing @type";
				}
				if ( empty( $item['@id'] ) ) {
					$errors[] = "Item {$index} missing @id";
				}
			}
		}

		return array(
			'valid'  => empty( $errors ),
			'errors' => $errors,
		);
	}
}
