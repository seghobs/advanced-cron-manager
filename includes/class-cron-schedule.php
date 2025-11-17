<?php

class ACM_Cron_Schedule {

    public function __construct() {
        add_filter('cron_schedules', array($this, 'add_custom_schedules'));
    }

    public function add_custom_schedules($schedules) {
        // 5 dakikada bir
        $schedules['every_5_minutes'] = array(
            'interval' => 300,
            'display' => __('Her 5 Dakikada', 'advanced-cron-manager')
        );

        // 15 dakikada bir
        $schedules['every_15_minutes'] = array(
            'interval' => 900,
            'display' => __('Her 15 Dakikada', 'advanced-cron-manager')
        );

        // 30 dakikada bir
        $schedules['every_30_minutes'] = array(
            'interval' => 1800,
            'display' => __('Her 30 Dakikada', 'advanced-cron-manager')
        );

        // 2 saatte bir
        $schedules['every_2_hours'] = array(
            'interval' => 7200,
            'display' => __('Her 2 Saatte', 'advanced-cron-manager')
        );

        // 3 saatte bir
        $schedules['every_3_hours'] = array(
            'interval' => 10800,
            'display' => __('Her 3 Saatte', 'advanced-cron-manager')
        );

        // 6 saatte bir
        $schedules['every_6_hours'] = array(
            'interval' => 21600,
            'display' => __('Her 6 Saatte', 'advanced-cron-manager')
        );

        // 12 saatte bir
        $schedules['every_12_hours'] = array(
            'interval' => 43200,
            'display' => __('Her 12 Saatte', 'advanced-cron-manager')
        );

        // Haftada bir
        $schedules['weekly'] = array(
            'interval' => 604800,
            'display' => __('Haftada Bir', 'advanced-cron-manager')
        );

        // Ayda bir (30 gün)
        $schedules['monthly'] = array(
            'interval' => 2592000,
            'display' => __('Ayda Bir', 'advanced-cron-manager')
        );

        return $schedules;
    }

    public static function get_schedule_display_name($schedule) {
        $schedules = wp_get_schedules();
        
        if (isset($schedules[$schedule])) {
            return $schedules[$schedule]['display'];
        }
        
        return $schedule === 'single' ? __('Tek Seferlik', 'advanced-cron-manager') : ucfirst($schedule);
    }

    public static function get_interval_in_words($interval) {
        if ($interval < 60) {
            return $interval . ' saniye';
        } elseif ($interval < 3600) {
            return round($interval / 60) . ' dakika';
        } elseif ($interval < 86400) {
            return round($interval / 3600) . ' saat';
        } else {
            return round($interval / 86400) . ' gün';
        }
    }
}

// Sınıfı başlat
new ACM_Cron_Schedule();
