CREATE TABLE companies (
  "companyId" INTEGER PRIMARY KEY AUTOINCREMENT,
  name TEXT,
  "createdOn" TEXT DEFAULT (strftime('%Y-%m-%dT%H:%MZ', 'now'))
);

CREATE TABLE users (
  "userId" INTEGER PRIMARY KEY AUTOINCREMENT,
  name TEXT,
  "companyId" INTEGER,
  "createdOn" TIMESTAMP DEFAULT (strftime('%Y-%m-%dT%H:%MZ', 'now')),
  FOREIGN KEY("companyId") references companies("companyId")
);

INSERT INTO companies (name) values ('Company1'), ('Company2');
INSERT INTO users
    (name, "companyId")
values
    ('company1user1', 1),
    ('company1user2', 1),
    ('company2user1', 2),
    ('company2user2', 2);