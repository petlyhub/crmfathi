<?php
/**
 * AEO Engine for AEOX SEO
 * Answer Engine Optimization - Optimizes content for AI answer extraction
 */

if (!defined('ABSPATH')) {
    exit;
}

final class AEOX_AEO_Engine {
    
    private static $instance = null;
    
    public static function get_instance() {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    /**
     * Constructor
     */
    private function __construct() {
        add_action('aeox_analyze_post', array($this, 'analyze_post'), 20, 2);
    }
    
    /**
     * Analyze a post for AEO factors
     */
    public function analyze_post($post_id, $force = false) {
        $post = get_post($post_id);
        
        if (!$post) {
            return false;
        }
        
        $content = $post->post_content;
        $title = $post->post_title;
        
        // Extract questions from content
        $questions = $this->extract_questions($content);
        
        // Check for direct answers
        $has_direct_answer = $this->has_direct_answer($content);
        $answer_blocks = $this->find_answer_blocks($content);
        
        // Analyze content structure
        $structure = $this->analyze_structure($content);
        
        // Calculate readability
        $readability = $this->calculate_readability($content);
        
        // Detect search intent
        $intent = $this->detect_intent($title, $content);
        
        $analysis = array(
            'questions_count' => count($questions),
            'questions' => $questions,
            'questions_answered' => $this->count_answered_questions($questions, $content),
            'questions_answered_ratio' => count($questions) > 0 ? $this->count_answered_questions($questions, $content) / count($questions) : 0,
            'has_direct_answer' => $has_direct_answer,
            'answer_blocks' => $answer_blocks,
            'has_introduction' => $this->has_introduction($content),
            'has_summary' => $this->has_summary($content),
            'has_lists' => $this->has_lists($content),
            'has_tables' => $this->has_tables($content),
            'readability_score' => $readability['score'],
            'readability_data' => $readability,
            'intent' => $intent,
            'structure' => $structure
        );
        
        // Calculate AEO score
        $scorer = AEOX_Scorer::get_instance();
        $aeo_score = $scorer->calculate_aeo_score($analysis);
        
        $analysis['aeo_score'] = $aeo_score;
        
        // Detect issues
        $this->detect_aeo_issues($post_id, $analysis);
        
        // Save to database
        $database = AEOX_Database::get_instance();
        $database->save_analysis($post_id, array('aeo' => $aeo_score));
        
        // Cache results
        $cache = AEOX_Cache::get_instance();
        $cache->set('aeo', $analysis, 86400, $post_id);
        
        return $analysis;
    }
    
    /**
     * Extract questions from content
     */
    private function extract_questions($content) {
        $questions = array();
        
        // Match common question patterns
        $patterns = array(
            '/\b(what|why|how|when|where|who|which)\s+[a-z][^.?!]*[?]/i',
            '/<h[2-6][^>]*>([^<]*(?:what|why|how|when|where|who|which)[^<]*)<\/h[2-6]>/i'
        );
        
        foreach ($patterns as $pattern) {
            preg_match_all($pattern, $content, $matches);
            
            if (!empty($matches[0])) {
                foreach ($matches[0] as $match) {
                    // Clean up the question
                    $question = trim(strip_tags($match));
                    $question = rtrim($question, '?') . '?';
                    
                    // Determine question type
                    $type = 'other';
                    if (preg_match('/^(what)/i', $question)) $type = 'what';
                    elseif (preg_match('/^(why)/i', $question)) $type = 'why';
                    elseif (preg_match('/^(how)/i', $question)) $type = 'how';
                    elseif (preg_match('/^(when)/i', $question)) $type = 'when';
                    elseif (preg_match('/^(where)/i', $question)) $type = 'where';
                    elseif (preg_match('/^(who)/i', $question)) $type = 'who';
                    
                    $questions[] = array(
                        'text' => $question,
                        'type' => $type,
                        'has_answer' => false // Will be determined later
                    );
                }
            }
        }
        
        return $questions;
    }
    
    /**
     * Check if content has a direct answer
     */
    private function has_direct_answer($content) {
        // Look for patterns that indicate direct answers
        $patterns = array(
            '/is\s+(?:a|an|the)\s+\w+/i',  // "X is a..."
            '/refers?\s+to/i',              // "refers to"
            '/defined\s+as/i',              // "defined as"
            '/means\s+(?:that)?/i',         // "means" or "means that"
            '/(?:first|second|third|finally)\s*,/i',  // Structured lists
            '/in\s+(?:summary|conclusion|brief)/i'    // Summary indicators
        );
        
        foreach ($patterns as $pattern) {
            if (preg_match($pattern, $content)) {
                return true;
            }
        }
        
        return false;
    }
    
    /**
     * Find answer blocks in content
     */
    private function find_answer_blocks($content) {
        $blocks = array();
        
        // Look for paragraphs after headings that contain questions
        preg_match_all('/<h[2-6][^>]*>(.*?)<\/h[2-6]>\s*<p>(.*?)<\/p>/s', $content, $matches, PREG_SET_ORDER);
        
        foreach ($matches as $match) {
            $heading = strip_tags($match[1]);
            $paragraph = strip_tags($match[2]);
            
            // If heading is a question, mark the paragraph as a potential answer
            if (preg_match('/^(what|why|how|when|where|who|which)/i', $heading)) {
                $blocks[] = array(
                    'question' => $heading,
                    'answer' => substr($paragraph, 0, 300),
                    'length' => strlen($paragraph)
                );
            }
        }
        
        return $blocks;
    }
    
    /**
     * Analyze content structure
     */
    private function analyze_structure($content) {
        $structure = array(
            'has_h1' => preg_match('/<h1[^>]*>.*?<\/h1>/', $content),
            'has_h2' => preg_match('/<h2[^>]*>.*?<\/h2>/', $content),
            'has_h3' => preg_match('/<h3[^>]*>.*?<\/h3>/', $content),
            'heading_count' => preg_match_all('/<h[1-6][^>]*>.*?<\/h[1-6]>/', $content),
            'paragraph_count' => preg_match_all('/<p[^>]*>.*?<\/p>/', $content),
            'list_count' => preg_match_all('/<(ul|ol)[^>]*>.*?<\/\1>/s', $content),
            'table_count' => preg_match_all('/<table[^>]*>.*?<\/table>/s', $content),
            'blockquote_count' => preg_match_all('/<blockquote[^>]*>.*?<\/blockquote>/s', $content),
            'code_count' => preg_match_all('/<pre[^>]*>.*?<\/pre>/s', $content),
            'avg_paragraph_length' => 0
        );
        
        // Calculate average paragraph length
        preg_match_all('/<p[^>]*>(.*?)<\/p>/s', $content, $para_matches);
        if (!empty($para_matches[1])) {
            $total_length = 0;
            foreach ($para_matches[1] as $para) {
                $total_length += str_word_count(strip_tags($para));
            }
            $structure['avg_paragraph_length'] = round($total_length / count($para_matches[1]));
        }
        
        return $structure;
    }
    
    /**
     * Calculate readability score
     */
    private function calculate_readability($content) {
        $text = strip_tags($content);
        $words = str_word_count($text);
        $sentences = preg_match_all('/[.!?]+/', $text);
        $syllables = $this->count_syllables($text);
        
        if ($sentences == 0 || $words == 0) {
            return array('score' => 50, 'grade' => 'average');
        }
        
        // Flesch Reading Ease
        $flesch_score = 206.835 - (1.015 * ($words / $sentences)) - (84.6 * ($syllables / $words));
        $flesch_score = max(0, min(100, round($flesch_score)));
        
        $grade = 'average';
        if ($flesch_score >= 90) $grade = 'very_easy';
        elseif ($flesch_score >= 80) $grade = 'easy';
        elseif ($flesch_score >= 70) $grade = 'fairly_easy';
        elseif ($flesch_score >= 60) $grade = 'average';
        elseif ($flesch_score >= 50) $grade = 'fairly_difficult';
        elseif ($flesch_score >= 30) $grade = 'difficult';
        else $grade = 'very_difficult';
        
        return array(
            'score' => $flesch_score,
            'grade' => $grade,
            'words' => $words,
            'sentences' => $sentences,
            'syllables' => $syllables
        );
    }
    
    /**
     * Count syllables in text (simplified)
     */
    private function count_syllables($text) {
        // This is a simplified implementation
        // For production, use a more accurate syllable counter
        preg_match_all('/[aeiouy]+/i', strtolower($text), $matches);
        return count($matches[0]);
    }
    
    /**
     * Detect search intent
     */
    private function detect_intent($title, $content) {
        $title_lower = strtolower($title);
        $content_lower = strtolower($content);
        
        $intents = array(
            'informational' => 0,
            'navigational' => 0,
            'transactional' => 0,
            'commercial' => 0
        );
        
        // Informational indicators
        $informational_keywords = array('what', 'how', 'why', 'when', 'where', 'guide', 'tutorial', 'learn', 'understand');
        foreach ($informational_keywords as $keyword) {
            if (strpos($title_lower, $keyword) !== false || strpos($content_lower, $keyword) !== false) {
                $intents['informational'] += 2;
            }
        }
        
        // Transactional indicators
        $transactional_keywords = array('buy', 'purchase', 'order', 'price', 'cost', 'discount', 'deal');
        foreach ($transactional_keywords as $keyword) {
            if (strpos($title_lower, $keyword) !== false || strpos($content_lower, $keyword) !== false) {
                $intents['transactional'] += 2;
            }
        }
        
        // Commercial indicators
        $commercial_keywords = array('best', 'top', 'review', 'compare', 'vs', 'versus', 'alternative');
        foreach ($commercial_keywords as $keyword) {
            if (strpos($title_lower, $keyword) !== false || strpos($content_lower, $keyword) !== false) {
                $intents['commercial'] += 2;
            }
        }
        
        // Navigational indicators
        $navigational_keywords = array('login', 'signin', 'contact', 'about', 'homepage');
        foreach ($navigational_keywords as $keyword) {
            if (strpos($title_lower, $keyword) !== false) {
                $intents['navigational'] += 3;
            }
        }
        
        // Determine primary intent
        arsort($intents);
        $primary_intent = key($intents);
        $confidence = current($intents) * 20; // Convert to percentage
        
        return array(
            'primary' => $primary_intent,
            'confidence' => min(100, $confidence),
            'all_intents' => $intents
        );
    }
    
    /**
     * Check if content has introduction
     */
    private function has_introduction($content) {
        // Check if first paragraph introduces the topic
        preg_match('/<p[^>]*>(.*?)<\/p>/s', $content, $match);
        
        if (!empty($match[1])) {
            $first_para = strip_tags($match[1]);
            $intro_indicators = array('in this', 'this article', 'we will', 'learn about', 'explore', 'discover');
            
            foreach ($intro_indicators as $indicator) {
                if (stripos($first_para, $indicator) !== false) {
                    return true;
                }
            }
            
            // Also check if it's a reasonable length for an intro
            if (str_word_count($first_para) >= 20 && str_word_count($first_para) <= 100) {
                return true;
            }
        }
        
        return false;
    }
    
    /**
     * Check if content has summary
     */
    private function has_summary($content) {
        $summary_indicators = array(
            'in conclusion', 'in summary', 'to summarize', 'in brief',
            'overall', 'key takeaways', 'key points', 'final thoughts'
        );
        
        foreach ($summary_indicators as $indicator) {
            if (stripos($content, $indicator) !== false) {
                return true;
            }
        }
        
        return false;
    }
    
    /**
     * Check if content has lists
     */
    private function has_lists($content) {
        return preg_match('/<(ul|ol)[^>]*>/i', $content) > 0;
    }
    
    /**
     * Check if content has tables
     */
    private function has_tables($content) {
        return preg_match('/<table[^>]*>/i', $content) > 0;
    }
    
    /**
     * Count answered questions
     */
    private function count_answered_questions($questions, $content) {
        $answered = 0;
        
        foreach ($questions as $question) {
            // Simple heuristic: check if there's content after the question
            $question_text = rtrim($question['text'], '?');
            
            if (strpos($content, $question_text) !== false) {
                // Found the question, now check if there's an answer nearby
                $pos = strpos($content, $question_text);
                $after_question = substr($content, $pos + strlen($question_text), 500);
                
                // Check for answer indicators
                if (preg_match('/<(p|li)[^>]*>[^<]{50,}<\/(p|li)>/s', $after_question)) {
                    $answered++;
                }
            }
        }
        
        return $answered;
    }
    
    /**
     * Detect AEO issues
     */
    private function detect_aeo_issues($post_id, $data) {
        $database = AEOX_Database::get_instance();
        
        // No direct answer
        if (!$data['has_direct_answer']) {
            $database->add_issue(array(
                'post_id' => $post_id,
                'issue_type' => 'no_direct_answer',
                'severity' => 'warning',
                'category' => 'aeo',
                'detected_value' => 'No clear answer block',
                'expected_value' => 'At least one direct answer',
                'description' => __('Content lacks a clear, direct answer that AI can extract', 'aeox-seo'),
                'how_to_fix' => __('Add a clear definition or direct answer near the beginning of your content', 'aeox-seo'),
                'is_resolved' => 0
            ));
        }
        
        // No questions detected
        if ($data['questions_count'] == 0) {
            $database->add_issue(array(
                'post_id' => $post_id,
                'issue_type' => 'no_questions',
                'severity' => 'notice',
                'category' => 'aeo',
                'detected_value' => '0 questions',
                'expected_value' => 'At least 1 question',
                'description' => __('No questions detected in content', 'aeox-seo'),
                'how_to_fix' => __('Consider adding FAQ sections with clear questions and answers', 'aeox-seo'),
                'is_resolved' => 0
            ));
        }
        
        // Low readability
        if ($data['readability_score'] < 60) {
            $database->add_issue(array(
                'post_id' => $post_id,
                'issue_type' => 'low_readability',
                'severity' => 'warning',
                'category' => 'aeo',
                'detected_value' => $data['readability_score'],
                'expected_value' => '60+',
                'description' => __('Content readability is below optimal for AI extraction', 'aeox-seo'),
                'how_to_fix' => __('Simplify sentences and use clearer language', 'aeox-seo'),
                'is_resolved' => 0
            ));
        }
        
        // No introduction
        if (!$data['has_introduction']) {
            $database->add_issue(array(
                'post_id' => $post_id,
                'issue_type' => 'no_introduction',
                'severity' => 'notice',
                'category' => 'aeo',
                'detected_value' => 'No introduction detected',
                'expected_value' => 'Clear introduction paragraph',
                'description' => __('Content lacks a clear introduction', 'aeox-seo'),
                'how_to_fix' => __('Add an introductory paragraph that sets context', 'aeox-seo'),
                'is_resolved' => 0
            ));
        }
    }
}
