CREATE TABLE entity (
  id INTEGER PRIMARY KEY,
  name VARCHAR(64),
  mean_response_time INTEGER,
  probability_reask REAL
);
-- An "empty" ask table makes our id-based ORM work more easily.
CREATE TABLE ask (
  id INTEGER PRIMARY KEY
);
CREATE TABLE action (
  id INTEGER PRIMARY KEY,
  ask INTEGER NOT NULL,
  entity_from INTEGER NOT NULL,
  entity_to INTEGER NOT NULL,
  time_start INT,
  time_stop INT,
  action_type VARCHAR(16),

  FOREIGN KEY(ask) REFERENCES ask(id)
  FOREIGN KEY(entity_from) REFERENCES entity(id),
  FOREIGN KEY(entity_to) REFERENCES entity(id)
);
