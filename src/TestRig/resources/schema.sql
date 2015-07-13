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
  ask INTEGER,
  entity_from INTEGER,
  entity_to INTEGER,
  time_taken INT,
  action_type VARCHAR(16),

  FOREIGN KEY(ask) REFERENCES ask(id)
  FOREIGN KEY(entity_from) REFERENCES entity(id),
  FOREIGN KEY(entity_to) REFERENCES entity(id)
);
