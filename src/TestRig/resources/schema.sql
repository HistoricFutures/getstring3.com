CREATE TABLE entity (
  id INTEGER PRIMARY KEY,
  name VARCHAR(64),
  -- Three times in the Raw Responsive Data table.
  mean_ack_time INTEGER,
  mean_answer_time INTEGER,
  mean_routing_time INTEGER,
  -- The probability we terminate the chain here.
  probability_answer REAL
);
-- An "empty" question table makes our id-based ORM work more easily.
CREATE TABLE question (
  id INTEGER PRIMARY KEY
);
CREATE TABLE ask (
  id INTEGER PRIMARY KEY,
  question INTEGER NOT NULL,
  entity_from INTEGER NOT NULL,
  entity_to INTEGER NOT NULL,
  time_start INT,
  time_ack INT,
  time_answer INT,

  FOREIGN KEY(question) REFERENCES question(id),
  FOREIGN KEY(entity_from) REFERENCES entity(id),
  FOREIGN KEY(entity_to) REFERENCES entity(id)
);
