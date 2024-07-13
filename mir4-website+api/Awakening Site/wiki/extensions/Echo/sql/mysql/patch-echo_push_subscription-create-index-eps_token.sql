-- Rename index to match table prefix - T306473
CREATE INDEX /*i*/eps_token ON /*_*/echo_push_subscription (eps_token(10));
