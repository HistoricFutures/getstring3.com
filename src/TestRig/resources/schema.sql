-- @file
-- Schema for all datasets.
CREATE TABLE entity (
  id INTEGER PRIMARY KEY,
  name VARCHAR(64) NOT NULL,

  -- Internal structure: tiers now in their own table.
  population VARCHAR(64),
  is_sourcing INTEGER(1),

  -- Three times in the Raw Responsive Data table.
  mean_ack_time INTEGER,
  mean_answer_time INTEGER,
  mean_routing_time INTEGER,
  -- Ratio for multiplying times when responding to self.
  self_time_ratio INTEGER,

  -- Bifurcating questions with extra suppliers.
  mean_extra_suppliers INTEGER,

  -- Probabilities for agent control flow.
  probability_no_ack REAL,
  probability_no_answer REAL
);
CREATE TABLE entity_tier (
  entity INTEGER NOT NULL,
  tier INTEGER NOT NULL,

  FOREIGN KEY(entity) REFERENCES entity(id)
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
  time_start INT NOT NULL,
  time_ack INT,
  time_answer INT,

  FOREIGN KEY(question) REFERENCES question(id),
  FOREIGN KEY(entity_from) REFERENCES entity(id),
  FOREIGN KEY(entity_to) REFERENCES entity(id)
);
