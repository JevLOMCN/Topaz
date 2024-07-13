-- Rename index to match table prefix - T306473
DROP INDEX /*i*/echo_push_subscription_user_id ON /*_*/echo_push_subscription;
CREATE INDEX /*i*/eps_user ON /*_*/echo_push_subscription (eps_user);
