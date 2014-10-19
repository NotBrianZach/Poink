use locationData;

/* more information on phone?*/
CREATE TABLE APP_USERS (
	USER_ID INTEGER unsigned PRIMARY KEY,
	USER_LAT FLOAT NOT NULL,
	USER_LNG FLOAT NOT NULL
	) ENGINE=InnoDB;

CREATE TABLE SEQ (
    NAME VARCHAR(30) PRIMARY KEY,
    CURRENT_VALUE INTEGER NOT NULL 
)ENGINE=InnoDB;

-- The use of LAST_INSERT_ID is a MySQL-specific trick to
-- eliminate the need for an explicit transaction here.

-- From: Zaitsev, Peter. "Stored function to generate sequences". MySQL
--   Performance Blog. Pleasanton, Calif.: Percona LLC, 2008 Apr 2.
--   URL: http://www.mysqlperformanceblog.com/2008/04/02/stored-function-to-generate-sequences/

delimiter //
CREATE FUNCTION NEXT_SEQ_VALUE(SEQ_NAME VARCHAR(30))
    RETURNS INT
    MODIFIES SQL DATA
BEGIN
    UPDATE SEQ
        SET
            CURRENT_VALUE = LAST_INSERT_ID(CURRENT_VALUE+1)
        WHERE NAME = SEQ_NAME;
    RETURN LAST_INSERT_ID();
END
//
delimiter ;


INSERT INTO SEQ SELECT 'APP_USERS', MAX(USER_ID) FROM APP_USERS;

