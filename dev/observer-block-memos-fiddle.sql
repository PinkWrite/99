CREATE TABLE users(
   id               INTEGER  NOT NULL PRIMARY KEY
  ,name             VARCHAR(4) NOT NULL
  ,observing JSON
  ,blocks    JSON
);
INSERT INTO users(id,name,observing,blocks) VALUES (1,'John','["3","4","5","6" ]',NULL);
INSERT INTO users(id,name,observing,blocks) VALUES (2,'Bill','["7","8","9","10" ]',NULL);
INSERT INTO users(id,name,observing,blocks) VALUES (3,'Andy',NULL,'["1","2" ]');
INSERT INTO users(id,name,observing,blocks) VALUES (4,'Hank',NULL,'["3","4","5" ]');
INSERT INTO users(id,name,observing,blocks) VALUES (5,'Alex',NULL,'["6","7","8" ]');
INSERT INTO users(id,name,observing,blocks) VALUES (6,'Joe',NULL,'["9","10" ]');
INSERT INTO users(id,name,observing,blocks) VALUES (7,'Ken',NULL,'["11","12","13" ]');
INSERT INTO users(id,name,observing,blocks) VALUES (8,'Zach',NULL,'["14","15","16" ]');
INSERT INTO users(id,name,observing,blocks) VALUES (9,'Walt',NULL,'["17","18" ]');
INSERT INTO users(id,name,observing,blocks) VALUES (10,'Mike',NULL,'["19","20","21" ]');
SELECT  * FROM users;


CREATE TABLE blocks(
   id    INTEGER  NOT NULL PRIMARY KEY
  ,name VARCHAR(8) NOT NULL
);
INSERT INTO blocks(id,name) VALUES (1,'Boston');
INSERT INTO blocks(id,name) VALUES (2,'Chicago');
INSERT INTO blocks(id,name) VALUES (3,'Cisco');
INSERT INTO blocks(id,name) VALUES (4,'Seattle');
INSERT INTO blocks(id,name) VALUES (5,'North');
INSERT INTO blocks(id,name) VALUES (6,'West');
INSERT INTO blocks(id,name) VALUES (7,'Miami');
INSERT INTO blocks(id,name) VALUES (8,'York');
INSERT INTO blocks(id,name) VALUES (9,'Tainan');
INSERT INTO blocks(id,name) VALUES (10,'Seoul');
INSERT INTO blocks(id,name) VALUES (11,'South');
INSERT INTO blocks(id,name) VALUES (12,'Tokyo');
INSERT INTO blocks(id,name) VALUES (13,'Carlisle');
INSERT INTO blocks(id,name) VALUES (14,'Fugging');
INSERT INTO blocks(id,name) VALUES (15,'Turkey');
INSERT INTO blocks(id,name) VALUES (16,'Paris');
INSERT INTO blocks(id,name) VALUES (17,'Midguard');
INSERT INTO blocks(id,name) VALUES (18,'Fugging');
INSERT INTO blocks(id,name) VALUES (19,'Madrid');
INSERT INTO blocks(id,name) VALUES (20,'Salvador');
INSERT INTO blocks(id,name) VALUES (21,'Everett');
SELECT  * FROM  blocks;

WITH RECURSIVE cte AS (
   SELECT 0 AS x
   UNION ALL
   SELECT x+1 FROM cte),
tbl_users AS (
SELECT
   id,
   name,
   -- cte.x,
   JSON_VALUE(blocks,CONCAT('$[',cte.x,']')) AS block
FROM users
CROSS JOIN cte
WHERE JSON_VALUE(blocks,CONCAT('$[',cte.x,']')) IS NOT NULL
),
tbl_observers AS (
SELECT
   id,
   name,
   -- cte.x,
   JSON_VALUE(observing,CONCAT('$[',cte.x,']')) AS writer
FROM users
CROSS JOIN cte
WHERE JSON_VALUE(observing,CONCAT('$[',cte.x,']')) IS NOT NULL
)
-- SELECT * FROM tbl_observers;
-- SELECT * FROM tbl_users;
SELECT
   o.name  AS observer,
   w.name  AS writer,
   b.name AS block,
   b.id    AS p_id
FROM tbl_observers o
LEFT JOIN tbl_users w ON w.id = o.writer
INNER JOIN blocks b ON b.id = w.block
WHERE o.id = '1'
ORDER BY block;
