-- Test database schema for Cypress
CREATE TABLE users (
    id int not null,
    name text,
    email text,
    password text,
    created datetime,
    modified datetime,
    primary key (id)
  );
