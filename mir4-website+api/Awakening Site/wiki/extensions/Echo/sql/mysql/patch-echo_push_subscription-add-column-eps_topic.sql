ALTER TABLE /*_*/echo_push_subscription
ADD COLUMN eps_topic TINYINT UNSIGNED;
CREATE INDEX /*i*/eps_topic ON /*_*/echo_push_subscription (eps_topic);
