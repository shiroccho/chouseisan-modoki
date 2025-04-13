-- PostgreSQLデータベース設定スクリプト
-- データベースの作成
-- PostgreSQLコマンドラインで以下を実行: createdb schedule_app

-- テーブル作成
CREATE TABLE events (
    id SERIAL PRIMARY KEY,
    event_id VARCHAR(32) UNIQUE NOT NULL,
    title VARCHAR(255) NOT NULL,
    description TEXT,
    creator_name VARCHAR(100) NOT NULL,
    creator_email VARCHAR(100) NOT NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE date_options (
    id SERIAL PRIMARY KEY,
    event_id VARCHAR(32) REFERENCES events(event_id) ON DELETE CASCADE,
    date DATE NOT NULL,
    start_time TIME,
    end_time TIME,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE responses (
    id SERIAL PRIMARY KEY,
    event_id VARCHAR(32) REFERENCES events(event_id) ON DELETE CASCADE,
    date_option_id INTEGER REFERENCES date_options(id) ON DELETE CASCADE,
    participant_name VARCHAR(100) NOT NULL,
    participant_email VARCHAR(100) NOT NULL,
    availability VARCHAR(20) NOT NULL CHECK (availability IN ('available', 'maybe', 'unavailable')),
    comment TEXT,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    UNIQUE(date_option_id, participant_email)
);

-- インデックス作成
CREATE INDEX idx_events_event_id ON events(event_id);
CREATE INDEX idx_date_options_event_id ON date_options(event_id);
CREATE INDEX idx_responses_event_id ON responses(event_id);
CREATE INDEX idx_responses_date_option_id ON responses(date_option_id);
