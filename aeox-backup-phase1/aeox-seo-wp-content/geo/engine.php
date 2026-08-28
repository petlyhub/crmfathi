<?php
/**
 * AEOX GEO Engine Core
 *
 * The core logic for Generative Engine Optimization.
 * Analyzes content for citation potential, factual clarity, and AI readability.
 *
 * @package AEOX_SEO
 * @since 1.0.0
 */

if (!defined('ABSPATH')) {
    exit;
}

class AEOX_GEO_Engine {

    /**
     * Run full GEO analysis on post content.
     *
     * @param int $post_id Post ID.
     * @return array Analysis results including scores and issues.
     */
    public function analyze($post_id) {
        $post = get_post($post_id);
        if (!$post) {
            return ['error' => 'Post not found'];
        }

        $content = $post->post_content;
        $title   = $post->post_title;
        $meta    = get_post_meta($post_id);

        // Extract plain text for analysis
        $plain_text = wp_strip_all_tags($content);
        $word_count = str_word_count($plain_text);

        // Initialize Scores
        $scores = [
            'citation_readiness' => 0,
            'factual_clarity'    => 0,
            'entity_density'     => 0,
            'answerability'      => 0,
            'trust_signals'      => 0,
            'total_geo_score'    => 0
        ];

        $issues = [];
        $opportunities = [];
        $data = [];

        // 1. Analyze Citation Readiness
        $citation_result = $this->analyze_citation_readiness($content, $post_id);
        $scores['citation_readiness'] = $citation_result['score'];
        $issues = array_merge($issues, $citation_result['issues']);
        $opportunities = array_merge($opportunities, $citation_result['opportunities']);

        // 2. Analyze Factual Clarity
        $factual_result = $this->analyze_factual_clarity($plain_text);
        $scores['factual_clarity'] = $factual_result['score'];
        $issues = array_merge($issues, $factual_result['issues']);

        // 3. Analyze Entity Density (Basic Heuristic)
        $entity_result = $this->analyze_entity_density($plain_text, $title);
        $scores['entity_density'] = $entity_result['score'];
        $data['entities_detected'] = $entity_result['entities'];

        // 4. Analyze Answerability (Question/Answer Pairs)
        $answer_result = $this->analyze_answerability($content);
        $scores['answerability'] = $answer_result['score'];
        $data['qa_pairs'] = $answer_result['qa_pairs'];
        $opportunities = array_merge($opportunities, $answer_result['opportunities']);

        // 5. Analyze Trust Signals
        $trust_result = $this->analyze_trust_signals($post_id, $meta);
        $scores['trust_signals'] = $trust_result['score'];
        $issues = array_merge($issues, $trust_result['issues']);

        // Calculate Weighted Total Score
        $weights = [
            'citation_readiness' => 0.25,
            'factual_clarity'    => 0.20,
            'entity_density'     => 0.15,
            'answerability'      => 0.25,
            'trust_signals'      => 0.15
        ];

        $total_score = 0;
        foreach ($scores as $key => $score) {
            if ($key !== 'total_geo_score') {
                $total_score += ($score * $weights[$key]);
            }
        }
        
        $scores['total_geo_score'] = round($total_score);

        return [
            'scores'        => $scores,
            'issues'        => $issues,
            'opportunities' => $opportunities,
            'data'          => $data,
            'word_count'    => $word_count
        ];
    }

    /**
     * 1. Citation Readiness Analysis
     * Checks if claims are supported and structure allows extraction.
     */
    private function analyze_citation_readiness($content, $post_id) {
        $score = 50; // Base score
        $issues = [];
        $opportunities = [];

        // Check for external links (Sources)
        preg_match_all('/<a\s+(?:[^>]*?\s+)?href="([^"]*)"/', $content, $matches);
        $external_links = 0;
        if (!empty($matches[1])) {
            foreach ($matches[1] as $url) {
                if (strpos($url, home_url()) === false) {
                    $external_links++;
                }
            }
        }

        if ($external_links === 0) {
            $score -= 20;
            $issues[] = [
                'severity' => 'warning',
                'message'  => __('No external sources cited. AI models prioritize content with verifiable references.', 'aeox-seo'),
                'fix'      => 'add_sources'
            ];
        } elseif ($external_links >= 3) {
            $score += 20;
        } else {
            $score += 10;
        }

        // Check for Lists/Tables (Easy to parse)
        if (strpos($content, '<ul') !== false || strpos($content, '<ol') !== false) {
            $score += 10;
        }
        if (strpos($content, '<table') !== false) {
            $score += 10;
        }

        // Check for "Definition" patterns
        if (preg_match('/(is|are|refers to|means|defined as)/i', $content)) {
            $score += 10;
        } else {
            $opportunities[] = [
                'message' => __('Add clear definitions for key terms to improve extractability.', 'aeox-seo'),
                'action'  => 'add_definition'
            ];
        }

        return [
            'score'         => min(100, max(0, $score)),
            'issues'        => $issues,
            'opportunities' => $opportunities
        ];
    }

    /**
     * 2. Factual Clarity Analysis
     * Detects potential factual statements that lack context or dates.
     */
    private function analyze_factual_clarity($text) {
        $score = 60;
        $issues = [];

        // Simple heuristic: Look for statistical patterns without nearby dates
        // Pattern: Number + % or Number + Million/Billion
        if (preg_match_all('/\d+(\.\d+)?(\s*%|\s*(million|billion))/i', $text, $stats)) {
            // Check if there is a year (e.g., 2023, 2024) nearby in the same sentence roughly
            $sentences = preg_split('/[.!?]+/', $text);
            $unsupported_claims = 0;

            foreach ($sentences as $sentence) {
                if (preg_match('/\d+(\.\d+)?(\s*%|\s*(million|billion))/i', $sentence)) {
                    if (!preg_match('/\b(19|20)\d{2}\b/', $sentence)) {
                        $unsupported_claims++;
                    }
                }
            }

            if ($unsupported_claims > 0) {
                $score -= ($unsupported_claims * 5);
                $issues[] = [
                    'severity' => 'info',
                    'message'  => sprintf(__('Detected %d statistical claim(s) without a specific year/context. Add dates to improve factual reliability.', 'aeox-seo'), $unsupported_claims),
                    'fix'      => 'add_context_to_stats'
                ];
            } else {
                $score += 15;
            }
        }

        // Sentence length check (AI prefers concise sentences)
        $avg_sentence_length = str_word_count($text) / (substr_count($text, '.') + 1);
        if ($avg_sentence_length > 25) {
            $score -= 10;
            $issues[] = [
                'severity' => 'warning',
                'message'  => __('Average sentence length is high. Shorter sentences improve AI parsing accuracy.', 'aeox-seo'),
                'fix'      => 'simplify_sentences'
            ];
        }

        return [
            'score'  => min(100, max(0, $score)),
            'issues' => $issues
        ];
    }

    /**
     * 3. Entity Density Analysis
     * Checks for proper nouns and consistency.
     */
    private function analyze_entity_density($text, $title) {
        $score = 50;
        $entities = [];

        // Very basic entity detection (Capitalized words, excluding start of sentences)
        // In a real production env, this would use NLP API or PHP-ML
        preg_match_all('/\b[A-Z][a-z]+\b/', $text, $matches);
        $potential_entities = array_count_values($matches[0]);

        // Filter common stop words that are capitalized
        $stop_words = ['The', 'And', 'But', 'Or', 'For', 'Nor', 'On', 'At', 'To', 'From', 'By', 'With', 'Is', 'Are', 'Was', 'Were'];
        foreach ($stop_words as $stop) {
            unset($potential_entities[$stop]);
        }

        $unique_entities = count($potential_entities);
        $word_count = str_word_count($text);
        
        if ($word_count > 0) {
            $density = ($unique_entities / $word_count) * 100;
            
            // Ideal density range for entities is roughly 2-5%
            if ($density >= 2 && $density <= 8) {
                $score = 90;
            } elseif ($density < 2) {
                $score = 40;
            } else {
                $score = 60; // Too many might be noise
            }
        }

        // Check if Title Keyword appears as an entity
        $title_words = explode(' ', $title);
        foreach ($title_words as $word) {
            if (strlen($word) > 3 && isset($potential_entities[$word])) {
                $score += 10;
                break;
            }
        }

        // Return top 5 detected entities
        arsort($potential_entities);
        $entities = array_slice(array_keys($potential_entities), 0, 5);

        return [
            'score'    => min(100, max(0, $score)),
            'entities' => $entities
        ];
    }

    /**
     * 4. Answerability Analysis
     * Looks for Q&A structures and direct answers.
     */
    private function analyze_answerability($content) {
        $score = 50;
        $qa_pairs = [];
        $opportunities = [];

        // Check for FAQ Schema presence (indirectly via blocks or shortcodes)
        if (has_block('core/faq', $content) || strpos($content, '[faq]') !== false) {
            $score += 30;
        }

        // Check for Heading followed immediately by paragraph (Potential Q&A)
        // Pattern: H2/H3 -> P
        if (preg_match_all('/<h[2-3]>(.*?)<\/h[2-3]>\s*<p>(.*?)<\/p>/s', $content, $matches, PREG_SET_ORDER)) {
            foreach ($matches as $match) {
                $heading = strip_tags($match[1]);
                $paragraph = strip_tags($match[2]);
                
                // If heading looks like a question
                if (strpos($heading, '?') !== false || preg_match('/^(What|How|Why|When|Where|Who|Is|Can|Does)/i', $heading)) {
                    $qa_pairs[] = [
                        'question' => $heading,
                        'answer_preview' => wp_trim_words($paragraph, 15)
                    ];
                }
            }
        }

        if (count($qa_pairs) > 0) {
            $score += 20;
        } else {
            $opportunities[] = [
                'message' => __('No clear Question & Answer structures found. Use headings phrased as questions followed by direct answers.', 'aeox-seo'),
                'action'  => 'create_qa_blocks'
            ];
        }

        return [
            'score'         => min(100, max(0, $score)),
            'qa_pairs'      => $qa_pairs,
            'opportunities' => $opportunities
        ];
    }

    /**
     * 5. Trust Signals Analysis
     * Checks for Author, Date, and Updates.
     */
    private function analyze_trust_signals($post_id, $meta) {
        $score = 40;
        $issues = [];

        // Author
        $author_id = get_post_field('post_author', $post_id);
        $author_name = get_the_author_meta('display_name', $author_id);
        $author_bio = get_the_author_meta('description', $author_id);

        if ($author_name) {
            $score += 20;
            if (!empty($author_bio)) {
                $score += 10; // Expertise signal
            } else {
                $issues[] = [
                    'severity' => 'info',
                    'message'  => __('Author bio is empty. Adding expertise details improves E-E-A-T and AI trust.', 'aeox-seo'),
                    'fix'      => 'update_author_bio'
                ];
            }
        } else {
            $issues[] = [
                'severity' => 'critical',
                'message'  => __('No author assigned. AI models deprioritize anonymous content.', 'aeox-seo'),
                'fix'      => 'assign_author'
            ];
        }

        // Freshness (Updated Date)
        $updated = get_post_meta($post_id, '_edit_last', true); 
        // Or use a specific plugin meta for "Last Updated" date if available
        $modified_date = get_the_modified_date('', $post_id);
        $publish_date = get_the_date('', $post_id);

        if ($modified_date !== $publish_date) {
            $score += 20; // Content has been reviewed/updated
        } else {
            $issues[] = [
                'severity' => 'warning',
                'message'  => __('Content shows no signs of recent updates. Regularly updating content boosts GEO scores.', 'aeox-seo'),
                'fix'      => 'refresh_content'
            ];
        }

        return [
            'score'  => min(100, max(0, $score)),
            'issues' => $issues
        ];
    }
}
