CREATE TABLE entity (
  id INTEGER PRIMARY KEY,
  name VARCHAR(64)
);
CREATE TABLE action (
  entity_from INTEGER,
  entity_to INTEGER,
  time_taken INT,
  action_type VARCHAR(16),

  FOREIGN KEY(entity_from) REFERENCES entity(id),
  FOREIGN KEY(entity_to) REFERENCES entity(id)
);
