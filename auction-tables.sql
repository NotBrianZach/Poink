-- auction-tables.sql -- SQL table definitions
--
-- C S 105: PHP/SQL, Spring 2014, J. Thywissen
-- The University of Texas at Austin
--

CREATE TABLE PERSON (
	PERSON_ID INTEGER PRIMARY KEY,
	SURNAME VARCHAR(96) NOT NULL,
	FORENAME VARCHAR(96),
	EMAIL_ADDRESS VARCHAR(255) NOT NULL );

CREATE TABLE AUCTION_STATUS (
	AUCTION_STATUS_ID INTEGER PRIMARY KEY,
	NAME VARCHAR(20) NOT NULL );

CREATE TABLE ITEM_CATEGORY (
	ITEM_CATEGORY_ID INTEGER PRIMARY KEY,
	NAME VARCHAR(78) NOT NULL );

CREATE TABLE AUCTION (
	AUCTION_ID INTEGER PRIMARY KEY,
	STATUS INTEGER NOT NULL,
	SELLER INTEGER NOT NULL,
	RESERVE NUMERIC NOT NULL,
	OPEN_TIME TIMESTAMP,
	CLOSE_TIME TIMESTAMP,
	ITEM_CATEGORY INTEGER,
	ITEM_CAPTION VARCHAR(78),
	ITEM_DESCRIPTION VARCHAR(998),
	ITEM_PHOTO MEDIUMBLOB );

CREATE TABLE BID (
	BID_ID INTEGER PRIMARY KEY,
	BIDDER INTEGER NOT NULL,
	AUCTION INTEGER NOT NULL,
	BID_TIME TIMESTAMP NOT NULL,
	AMOUNT NUMERIC NOT NULL );

CREATE INDEX AUCTION_STATUS_NAME_INDEX ON AUCTION_STATUS (NAME);

CREATE INDEX ITEM_CATEGORY_NAME_INDEX ON ITEM_CATEGORY (NAME);

CREATE INDEX AUCTION_STATUS_INDEX ON AUCTION (STATUS);

CREATE INDEX AUCTION_SELLER_INDEX ON AUCTION (SELLER);

CREATE INDEX AUCTION_ITEM_CATEGORY_INDEX ON AUCTION (ITEM_CATEGORY);

CREATE INDEX BID_AUCTION_INDEX ON BID (AUCTION);

CREATE INDEX BID_BIDDER_INDEX ON BID (BIDDER);

ALTER TABLE AUCTION ADD FOREIGN KEY (STATUS) REFERENCES AUCTION_STATUS(AUCTION_STATUS_ID);

ALTER TABLE AUCTION ADD FOREIGN KEY (SELLER) REFERENCES PERSON(PERSON_ID);

ALTER TABLE AUCTION ADD FOREIGN KEY (ITEM_CATEGORY) REFERENCES ITEM_CATEGORY(ITEM_CATEGORY_ID);

ALTER TABLE BID ADD FOREIGN KEY (BIDDER) REFERENCES PERSON(PERSON_ID);

ALTER TABLE BID ADD FOREIGN KEY (AUCTION) REFERENCES AUCTION(AUCTION_ID);

INSERT INTO PERSON VALUES (1, 'Dent', 'Arthur', 'a.dent@example.com');
INSERT INTO PERSON VALUES (2, 'McMillan', 'Tricia', 'trillian@example.com');
INSERT INTO PERSON VALUES (3, 'Prefect', 'Ford', 'ix@example.com');

INSERT INTO AUCTION_STATUS VALUES (1, 'Open');
INSERT INTO AUCTION_STATUS VALUES (2, 'Cancelled');
INSERT INTO AUCTION_STATUS VALUES (3, 'Won');
INSERT INTO AUCTION_STATUS VALUES (4, 'Failed');

INSERT INTO ITEM_CATEGORY VALUES (1, 'Antiques');
INSERT INTO ITEM_CATEGORY VALUES (2, 'Art & Collectibles');
INSERT INTO ITEM_CATEGORY VALUES (3, 'Books & Movies & Music');
INSERT INTO ITEM_CATEGORY VALUES (4, 'Cars');
INSERT INTO ITEM_CATEGORY VALUES (5, 'Clothing');
INSERT INTO ITEM_CATEGORY VALUES (6, 'Computers & Electronics');
INSERT INTO ITEM_CATEGORY VALUES (7, 'Jewelry');
INSERT INTO ITEM_CATEGORY VALUES (8, 'Musical Instruments');
INSERT INTO ITEM_CATEGORY VALUES (9, 'Tools');
INSERT INTO ITEM_CATEGORY VALUES (10, 'Toys');

INSERT INTO AUCTION VALUES (1, 1, 1, 5.0, '2014-02-02 15:37:00', '2014-02-05 23:00:00', 5, 'Towels', 'Slightly used towels, set of 42', NULL);
INSERT INTO AUCTION VALUES (2, 1, 1, 5.0, '2014-02-02 17:05:00', '2014-02-05 23:00:00', 3, '"My Favourite Bathtime Gurgles" by Grunthos the Flatulent', 'Grunthos'' 12-book epic.  Not to be missed.  Was to be presented at Mid-Galactic Arts Nobbling Council, but the poet''s death prevented its presentation.', NULL);
INSERT INTO AUCTION VALUES (3, 1, 2, 5.0, '2014-02-02 17:06:00', '2014-02-05 23:00:00', 9, 'Toasting knife', 'Bread knife that toasts the bread as you cut it.', NULL);
