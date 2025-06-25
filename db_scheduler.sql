-- Additional table for SNMP Scheduler logging
-- Run this after importing the main db.sql file

CREATE TABLE `collection_logs` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `start_time` timestamp NOT NULL,
  `duration` int(11) NOT NULL COMMENT 'Duration in seconds',
  `devices_processed` int(11) NOT NULL DEFAULT 0,
  `ports_updated` int(11) NOT NULL DEFAULT 0,
  `statistics_collected` int(11) NOT NULL DEFAULT 0,
  `errors` int(11) NOT NULL DEFAULT 0,
  `options` text COMMENT 'JSON of command line options used',
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_start_time` (`start_time`),
  KEY `idx_created_at` (`created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='Logs of scheduled SNMP collection runs';

-- Create index for better performance on date-based queries
CREATE INDEX `idx_collection_logs_date` ON `collection_logs` (`start_time`, `duration`);

-- Create a view for collection statistics
CREATE VIEW `collection_stats` AS
SELECT 
    DATE(start_time) as collection_date,
    COUNT(*) as total_runs,
    SUM(devices_processed) as total_devices_processed,
    SUM(ports_updated) as total_ports_updated,
    SUM(statistics_collected) as total_statistics_collected,
    SUM(errors) as total_errors,
    AVG(duration) as avg_duration,
    MIN(duration) as min_duration,
    MAX(duration) as max_duration
FROM collection_logs 
GROUP BY DATE(start_time)
ORDER BY collection_date DESC; 