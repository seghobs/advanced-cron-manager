<?php

class ACM_Cron_Tags {

    public function __construct() {
        // Hook'lar
    }

    /**
     * Cron'a favori ekle/çıkar
     */
    public function toggle_favorite($hook) {
        $favorites = get_option('acm_favorites', array());
        
        if (in_array($hook, $favorites)) {
            // Favorilerden çıkar
            $favorites = array_diff($favorites, array($hook));
            $action = 'removed';
        } else {
            // Favorilere ekle
            $favorites[] = $hook;
            $action = 'added';
        }
        
        update_option('acm_favorites', array_values($favorites));
        
        return array(
            'success' => true,
            'action' => $action,
            'favorites' => $favorites
        );
    }

    /**
     * Tüm favorileri getir
     */
    public function get_favorites() {
        return get_option('acm_favorites', array());
    }

    /**
     * Cron favori mi kontrol et
     */
    public function is_favorite($hook) {
        $favorites = $this->get_favorites();
        return in_array($hook, $favorites);
    }

    /**
     * Cron'a etiket ekle
     */
    public function add_tag($hook, $tag) {
        $tags = get_option('acm_cron_tags', array());
        
        if (!isset($tags[$hook])) {
            $tags[$hook] = array();
        }
        
        $tag = sanitize_text_field($tag);
        
        if (!in_array($tag, $tags[$hook])) {
            $tags[$hook][] = $tag;
        }
        
        update_option('acm_cron_tags', $tags);
        
        return true;
    }

    /**
     * Cron'dan etiket çıkar
     */
    public function remove_tag($hook, $tag) {
        $tags = get_option('acm_cron_tags', array());
        
        if (isset($tags[$hook])) {
            $tags[$hook] = array_diff($tags[$hook], array($tag));
            
            // Eğer hiç etiket kalmadıysa hook'u kaldır
            if (empty($tags[$hook])) {
                unset($tags[$hook]);
            }
        }
        
        update_option('acm_cron_tags', $tags);
        
        return true;
    }

    /**
     * Cron'un etiketlerini getir
     */
    public function get_cron_tags($hook) {
        $tags = get_option('acm_cron_tags', array());
        return isset($tags[$hook]) ? $tags[$hook] : array();
    }

    /**
     * Tüm benzersiz etiketleri getir
     */
    public function get_all_tags() {
        $tags = get_option('acm_cron_tags', array());
        $all_tags = array();
        
        foreach ($tags as $hook => $hook_tags) {
            $all_tags = array_merge($all_tags, $hook_tags);
        }
        
        return array_unique($all_tags);
    }

    /**
     * Belirli etikete sahip cronları getir
     */
    public function get_crons_by_tag($tag) {
        $tags = get_option('acm_cron_tags', array());
        $crons = array();
        
        foreach ($tags as $hook => $hook_tags) {
            if (in_array($tag, $hook_tags)) {
                $crons[] = $hook;
            }
        }
        
        return $crons;
    }

    /**
     * Cron'a notlar ekle
     */
    public function save_note($hook, $note) {
        $notes = get_option('acm_cron_notes', array());
        $notes[$hook] = sanitize_textarea_field($note);
        update_option('acm_cron_notes', $notes);
        return true;
    }

    /**
     * Cron'un notunu getir
     */
    public function get_note($hook) {
        $notes = get_option('acm_cron_notes', array());
        return isset($notes[$hook]) ? $notes[$hook] : '';
    }

    /**
     * Cron'a renk ata
     */
    public function save_color($hook, $color) {
        $colors = get_option('acm_cron_colors', array());
        $colors[$hook] = sanitize_hex_color($color);
        update_option('acm_cron_colors', $colors);
        return true;
    }

    /**
     * Cron'un rengini getir
     */
    public function get_color($hook) {
        $colors = get_option('acm_cron_colors', array());
        return isset($colors[$hook]) ? $colors[$hook] : '';
    }

    /**
     * Etiket renklerini yönet
     */
    public function save_tag_color($tag, $color) {
        $tag_colors = get_option('acm_tag_colors', array());
        $tag_colors[$tag] = sanitize_hex_color($color);
        update_option('acm_tag_colors', $tag_colors);
        return true;
    }

    /**
     * Etiket rengini getir
     */
    public function get_tag_color($tag) {
        $tag_colors = get_option('acm_tag_colors', array());
        return isset($tag_colors[$tag]) ? $tag_colors[$tag] : '#007cba';
    }

    /**
     * İstatistikler
     */
    public function get_stats() {
        return array(
            'total_favorites' => count($this->get_favorites()),
            'total_tags' => count($this->get_all_tags()),
            'total_tagged_crons' => count(get_option('acm_cron_tags', array()))
        );
    }
}
