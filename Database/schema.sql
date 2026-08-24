CREATE TABLE IF NOT EXISTS sensors (
 id BIGSERIAL PRIMARY KEY,
 device_id VARCHAR(50) NOT NULL UNIQUE,
 device_name VARCHAR(100) NOT NULL DEFAULT 'ESP32 IDS',
 ip_address VARCHAR(45),
 mac_address VARCHAR(17),
 firmware_version VARCHAR(30),
 status VARCHAR(10) NOT NULL DEFAULT 'OFFLINE' CHECK (status IN ('ONLINE','OFFLINE')),
 last_seen TIMESTAMPTZ,
 created_at TIMESTAMPTZ NOT NULL DEFAULT CURRENT_TIMESTAMP
);
CREATE INDEX IF NOT EXISTS idx_sensor_seen ON sensors(last_seen);

CREATE TABLE IF NOT EXISTS telemetry (
 id BIGSERIAL PRIMARY KEY,
 device_id VARCHAR(50) NOT NULL,
 packet_count BIGINT NOT NULL DEFAULT 0 CHECK (packet_count >= 0),
 packets_per_second NUMERIC(10,2) NOT NULL DEFAULT 0,
 management_frames INTEGER NOT NULL DEFAULT 0,
 data_frames INTEGER NOT NULL DEFAULT 0,
 control_frames INTEGER NOT NULL DEFAULT 0,
 probe_frames INTEGER NOT NULL DEFAULT 0,
 deauth_frames INTEGER NOT NULL DEFAULT 0,
 disassociation_frames INTEGER NOT NULL DEFAULT 0,
 unique_devices INTEGER NOT NULL DEFAULT 0,
 avg_rssi NUMERIC(6,2) NOT NULL DEFAULT 0,
 channel_number SMALLINT NOT NULL DEFAULT 0,
 created_at TIMESTAMPTZ NOT NULL DEFAULT CURRENT_TIMESTAMP
);
CREATE INDEX IF NOT EXISTS idx_telemetry_device_time ON telemetry(device_id,created_at DESC);
CREATE INDEX IF NOT EXISTS idx_telemetry_time ON telemetry(created_at DESC);

CREATE TABLE IF NOT EXISTS intrusion_alerts (
 id BIGSERIAL PRIMARY KEY,
 device_id VARCHAR(50) NOT NULL,
 source_ip VARCHAR(45),
 source_mac VARCHAR(17),
 destination_ip VARCHAR(45),
 source_port INTEGER CHECK (source_port IS NULL OR source_port BETWEEN 0 AND 65535),
 destination_port INTEGER CHECK (destination_port IS NULL OR destination_port BETWEEN 0 AND 65535),
 protocol VARCHAR(20),
 attack_type VARCHAR(100) NOT NULL,
 threat_level VARCHAR(10) NOT NULL CHECK (threat_level IN ('LOW','MEDIUM','HIGH','CRITICAL')),
 packet_count BIGINT NOT NULL DEFAULT 0,
 packets_per_second NUMERIC(10,2) NOT NULL DEFAULT 0,
 description VARCHAR(1000),
 status VARCHAR(20) NOT NULL DEFAULT 'NEW' CHECK (status IN ('NEW','ACKNOWLEDGED','RESOLVED')),
 created_at TIMESTAMPTZ NOT NULL DEFAULT CURRENT_TIMESTAMP
);
CREATE INDEX IF NOT EXISTS idx_alert_time ON intrusion_alerts(created_at DESC);
CREATE INDEX IF NOT EXISTS idx_alert_level ON intrusion_alerts(threat_level);
CREATE INDEX IF NOT EXISTS idx_alert_attack ON intrusion_alerts(attack_type);
