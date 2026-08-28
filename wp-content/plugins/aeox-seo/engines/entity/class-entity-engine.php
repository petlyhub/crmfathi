<?php
/**
 * AEOX Entity Engine
 * 
 * Extracts and analyzes entities from content for Knowledge Graph optimization.
 * Detects organizations, people, locations, products, and events.
 *
 * @package AEOX
 * @since 1.0.0
 */

namespace AEOX\Engines\Entity;

use AEOX\Core\Database;
use AEOX\Core\Cache;

class Entity_Engine {

	/**
	 * Database instance
	 *
	 * @var Database
	 */
	private $db;

	/**
	 * Cache instance
	 *
	 * @var Cache
	 */
	private $cache;

	/**
	 * Supported entity types
	 *
	 * @var array
	 */
	private $entity_types = array(
		'Organization',
		'Person',
		'LocalBusiness',
		'Product',
		'Service',
		'Location',
		'Event',
		'Course',
	);

	/**
	 * Constructor
	 */
	public function __construct() {
		global $wpdb;
		$this->db    = new Database();
		$this->cache = new Cache();
	}

	/**
	 * Analyze content for entities
	 *
	 * @param int $post_id Post ID.
	 * @return array Entity analysis results.
	 */
	public function analyze( $post_id ) {
		$post = get_post( $post_id );
		if ( ! $post ) {
			return array();
		}

		$cache_key = "aeox_entity_{$post_id}";
		$cached    = $this->cache->get( $cache_key );
		if ( false !== $cached ) {
			return $cached;
		}

		$content = $post->post_title . ' ' . $post->post_content;
		$entities = array();

		// Detect organizations.
		$organizations = $this->detect_organizations( $content );
		if ( ! empty( $organizations ) ) {
			$entities['Organization'] = $organizations;
		}

		// Detect people/authors.
		$people = $this->detect_people( $content, $post );
		if ( ! empty( $people ) ) {
			$entities['Person'] = $people;
		}

		// Detect locations.
		$locations = $this->detect_locations( $content );
		if ( ! empty( $locations ) ) {
			$entities['Location'] = $locations;
		}

		// Detect products/services.
		$products = $this->detect_products( $content );
		if ( ! empty( $products ) ) {
			$entities['Product'] = $products;
		}

		// Calculate entity clarity score.
		$clarity_score = $this->calculate_clarity_score( $entities, $content );

		$result = array(
			'entities'      => $entities,
			'count'         => count( $entities, COUNT_RECURSIVE ),
			'clarity_score' => $clarity_score,
			'issues'        => $this->detect_entity_issues( $entities, $post_id ),
		);

		$this->cache->set( $cache_key, $result, HOUR_IN_SECONDS );
		$this->save_to_database( $post_id, $result );

		return $result;
	}

	/**
	 * Detect organization mentions
	 *
	 * @param string $content Content to analyze.
	 * @return array Detected organizations.
	 */
	private function detect_organizations( $content ) {
		$organizations = array();

		// Look for company indicators.
		$patterns = array(
			'/([A-Z][a-zA-Z]+(?:\s+[A-Z][a-zA-Z]+)*\s+(?:Company|Corporation|Corp\.?|Inc\.?|LLC|Ltd\.?|Group|Agency))/i',
			'/([A-Z][a-zA-Z]+(?:\s+[A-Z][a-zA-Z]+)*\s+(?:للتدريب|للتعليم|للشركات|مؤسسة|شركة|مجموعة))/u',
		);

		foreach ( $patterns as $pattern ) {
			if ( preg_match_all( $pattern, $content, $matches ) ) {
				foreach ( $matches[1] as $match ) {
					$organizations[] = array(
						'name'       => trim( $match ),
						'type'       => 'Organization',
						'confidence' => 0.8,
					);
				}
			}
		}

		return array_values( array_unique( $organizations, SORT_REGULAR ) );
	}

	/**
	 * Detect person mentions
	 *
	 * @param string   $content Content to analyze.
	 * @param \WP_Post $post    Post object.
	 * @return array Detected people.
	 */
	private function detect_people( $content, $post ) {
		$people = array();

		// Get author info.
		$author_id   = $post->post_author;
		$author_name = get_the_author_meta( 'display_name', $author_id );
		if ( $author_name ) {
			$people[] = array(
				'name'       => $author_name,
				'type'       => 'Person',
				'role'       => 'Author',
				'user_id'    => $author_id,
				'confidence' => 1.0,
			);
		}

		// Look for person patterns in content.
		$patterns = array(
			'/(?:Dr\.|Prof\.|Mr\.|Ms\.|Mrs\.)\s+([A-Z][a-zA-Z]+(?:\s+[A-Z][a-zA-Z]+)*)/',
			'/([A-Z][a-z]+\s+[A-Z][a-z]+)/',
		);

		foreach ( $patterns as $pattern ) {
			if ( preg_match_all( $pattern, $content, $matches ) ) {
				foreach ( $matches[1] as $match ) {
					// Avoid duplicates with author.
					if ( $match !== $author_name ) {
						$people[] = array(
							'name'       => trim( $match ),
							'type'       => 'Person',
							'confidence' => 0.6,
						);
					}
				}
			}
		}

		return array_values( array_unique( $people, SORT_REGULAR ) );
	}

	/**
	 * Detect location mentions
	 *
	 * @param string $content Content to analyze.
	 * @return array Detected locations.
	 */
	private function detect_locations( $content ) {
		$locations = array();

		// Common city/country patterns.
		$patterns = array(
			'/(Riyadh|Jeddah|Dammam|Makkah|Madinah|Dubai|Abu Dhabi|Cairo|Alexandria)/i',
			'/(الرياض|جدة|الدمام|مكة|المدينة|دبي|أبو ظبي|القاهرة|الإسكندرية)/u',
			'/(Saudi Arabia|Kingdom of Saudi Arabia|UAE|Egypt)/i',
			'/(المملكة العربية السعودية|الإمارات|مصر)/u',
		);

		foreach ( $patterns as $pattern ) {
			if ( preg_match_all( $pattern, $content, $matches ) ) {
				foreach ( $matches[1] as $match ) {
					$locations[] = array(
						'name'       => trim( $match ),
						'type'       => 'Location',
						'confidence' => 0.9,
					);
				}
			}
		}

		return array_values( array_unique( $locations, SORT_REGULAR ) );
	}

	/**
	 * Detect product/service mentions
	 *
	 * @param string $content Content to analyze.
	 * @return array Detected products/services.
	 */
	private function detect_products( $content ) {
		$products = array();

		// Look for product indicators.
		if ( preg_match_all( '/(?:product|service|solution|platform|software|أداة|منصة|حل|برنامج)\s*:?\s*([A-Za-z0-9\s\-]+)/i', $content, $matches ) ) {
			foreach ( $matches[1] as $match ) {
				$products[] = array(
					'name'       => trim( $match ),
					'type'       => 'Product',
					'confidence' => 0.7,
				);
			}
		}

		return array_values( array_unique( $products, SORT_REGULAR ) );
	}

	/**
	 * Calculate entity clarity score
	 *
	 * @param array  $entities Detected entities.
	 * @param string $content  Content text.
	 * @return int Score 0-100.
	 */
	private function calculate_clarity_score( $entities, $content ) {
		$score = 50; // Base score.

		// Bonus for having multiple entity types.
		$type_count = count( $entities );
		$score     += min( $type_count * 10, 30 );

		// Bonus for organization presence.
		if ( isset( $entities['Organization'] ) && ! empty( $entities['Organization'] ) ) {
			$score += 10;
		}

		// Bonus for author presence.
		if ( isset( $entities['Person'] ) && ! empty( $entities['Person'] ) ) {
			$score += 10;
		}

		// Penalty for no entities.
		if ( empty( $entities ) || 0 === count( $entities, COUNT_RECURSIVE ) ) {
			$score = 20;
		}

		return min( max( $score, 0 ), 100 );
	}

	/**
	 * Detect entity-related issues
	 *
	 * @param array $entities Detected entities.
	 * @param int   $post_id  Post ID.
	 * @return array Issues found.
	 */
	private function detect_entity_issues( $entities, $post_id ) {
		$issues = array();

		// Check for missing organization.
		if ( empty( $entities['Organization'] ) ) {
			$issues[] = array(
				'code'     => 'missing_organization',
				'severity' => 'warning',
				'message'  => __( 'No organization entity detected. Consider mentioning your organization name.', 'aeox' ),
			);
		}

		// Check for missing author.
		$post = get_post( $post_id );
		if ( $post && empty( $entities['Person'] ) ) {
			$issues[] = array(
				'code'     => 'missing_author',
				'severity' => 'info',
				'message'  => __( 'Author information could be clearer. Ensure author byline is visible.', 'aeox' ),
			);
		}

		return $issues;
	}

	/**
	 * Save entity data to database
	 *
	 * @param int   $post_id Post ID.
	 * @param array $data    Entity data.
	 */
	private function save_to_database( $post_id, $data ) {
		global $wpdb;

		$table = $wpdb->prefix . 'aeox_entities';

		$wpdb->replace(
			$table,
			array(
				'post_id'    => $post_id,
				'entity_data' => wp_json_encode( $data ),
				'updated_at' => current_time( 'mysql' ),
			)
		);
	}

	/**
	 * Get entity profile for a post
	 *
	 * @param int $post_id Post ID.
	 * @return array|null Entity profile or null if not found.
	 */
	public function get_entity_profile( $post_id ) {
		global $wpdb;

		$table = $wpdb->prefix . 'aeox_entities';

		$result = $wpdb->get_row(
			$wpdb->prepare(
				"SELECT entity_data FROM {$table} WHERE post_id = %d",
				$post_id
			),
			ARRAY_A
		);

		if ( $result ) {
			return json_decode( $result['entity_data'], true );
		}

		return null;
	}
}
