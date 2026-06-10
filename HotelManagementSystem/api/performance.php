<?php
/**
 * Performance testing utility class
 * Used to measure database query and API execution time
 */

class PerformanceTimer {
    private static $timers = [];
    
    /**
     * Start timer
     * @param string $name Timer name
     */
    public static function start($name = 'default') {
        self::$timers[$name] = microtime(true);
    }
    
    /**
     * Stop timer and return execution time (seconds)
     * @param string $name Timer name
     * @return float Execution time (seconds)
     */
    public static function stop($name = 'default') {
        if (!isset(self::$timers[$name])) {
            return 0;
        }
        $elapsed = microtime(true) - self::$timers[$name];
        unset(self::$timers[$name]);
        return $elapsed;
    }
    
    /**
     * Stop timer and return formatted time string
     * @param string $name Timer name
     * @return string Formatted execution time
     */
    public static function stopFormatted($name = 'default') {
        $elapsed = self::stop($name);
        if ($elapsed < 0.001) {
            return number_format($elapsed * 1000000, 2) . ' μs';
        } elseif ($elapsed < 1) {
            return number_format($elapsed * 1000, 2) . ' ms';
        } else {
            return number_format($elapsed, 3) . ' s';
        }
    }
    
    /**
     * Get current timer running time (without stopping)
     * @param string $name Timer name
     * @return float Current running time (seconds)
     */
    public static function elapsed($name = 'default') {
        if (!isset(self::$timers[$name])) {
            return 0;
        }
        return microtime(true) - self::$timers[$name];
    }
}

/**
 * Shortcut function: Start timer
 */
function perf_start($name = 'default') {
    PerformanceTimer::start($name);
}

/**
 * Shortcut function: Stop timer and return seconds
 */
function perf_stop($name = 'default') {
    return PerformanceTimer::stop($name);
}

/**
 * Shortcut function: Stop timer and return formatted string
 */
function perf_end($name = 'default') {
    return PerformanceTimer::stopFormatted($name);
}
