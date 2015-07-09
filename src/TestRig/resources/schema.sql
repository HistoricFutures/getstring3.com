CREATE TABLE entity (
  id INTEGER PRIMARY KEY,
  name VARCHAR(64),
  mean_response_time INTEGER,
  probability_reask REAL
);
CREATE TABLE action (
  entity_from INTEGER,
  entity_to INTEGER,
  time_taken INT,
  action_type VARCHAR(16),

  FOREIGN KEY(entity_from) REFERENCES entity(id),
  FOREIGN KEY(entity_to) REFERENCES entity(id)
);
