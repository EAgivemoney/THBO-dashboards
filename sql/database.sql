DROP DATABASE IF EXISTS thbodb;
CREATE DATABASE thbodb CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE thbodb;

-- =========================
-- LOOKUP TABLES
-- =========================

CREATE TABLE Rol
(
	 Id						TINYINT			UNSIGNED		NOT NULL	PRIMARY KEY
	,Naam					VARCHAR(50)						NOT NULL	UNIQUE
    
	,IsActief				TINYINT							NOT NULL	DEFAULT 1 CHECK (IsActief IN (0,1))
    ,Opmerking				VARCHAR(250)					NULL		DEFAULT NULL
    ,AangemaaktOp			DATETIME(6)						NOT NULL	DEFAULT CURRENT_TIMESTAMP(6)
    ,GewijzigdOp			DATETIME(6)						NOT NULL	DEFAULT CURRENT_TIMESTAMP(6) ON UPDATE CURRENT_TIMESTAMP(6)
)ENGINE=InnoDB;

INSERT INTO Rol	 (Id, Naam)
VALUES			 (1, 'gebruiker')
				,(2, 'admin')
                ,(3, 'eigenaar');
                
CREATE TABLE TaakStatus
(
	 Id						TINYINT			UNSIGNED		NOT NULL	PRIMARY KEY
	,Naam					VARCHAR(50)						NOT NULL	UNIQUE
    
	,IsActief				TINYINT							NOT NULL	DEFAULT 1 CHECK (IsActief IN (0,1))
    ,Opmerking				VARCHAR(250)					NULL		DEFAULT NULL
    ,AangemaaktOp			DATETIME(6)						NOT NULL	DEFAULT CURRENT_TIMESTAMP(6)
    ,GewijzigdOp			DATETIME(6)						NOT NULL	DEFAULT CURRENT_TIMESTAMP(6) ON UPDATE CURRENT_TIMESTAMP(6)
)ENGINE=InnoDB;

INSERT INTO TaakStatus	 (Id, Naam)
VALUES					 (1, 'todo')
						,(2, 'doing')
                        ,(3, 'done');
                        
-- =========================
-- GEBRUIKER
-- =========================

CREATE TABLE Gebruiker
(
	 Id						INT				UNSIGNED		NOT NULL	PRIMARY KEY		AUTO_INCREMENT
	,RolId					TINYINT			UNSIGNED		NOT NULL
    
    ,Voornaam				VARCHAR(50)						NOT NULL
    ,Tussenvoegsel			VARCHAR(10)						NULL		DEFAULT NULL
    ,Achternaam				VARCHAR(50)						NOT NULL
    
    ,VolledigeNaam			VARCHAR(120) AS (TRIM(CONCAT_WS(' ', Voornaam, Tussenvoegsel, Achternaam))) STORED
    
    ,Email					VARCHAR(120)					NOT NULL
    ,WachtwoordHash			VARCHAR(255)					NOT NULL
     
	,IsActief				TINYINT							NOT NULL	DEFAULT 1 CHECK (IsActief IN (0,1))
    ,Opmerking				VARCHAR(250)					NULL		DEFAULT NULL
    ,AangemaaktOp			DATETIME(6)						NOT NULL	DEFAULT CURRENT_TIMESTAMP(6)
    ,GewijzigdOp			DATETIME(6)						NOT NULL	DEFAULT CURRENT_TIMESTAMP(6) ON UPDATE CURRENT_TIMESTAMP(6)
    
    ,UNIQUE (Email)
    
    ,FOREIGN KEY (RolId) REFERENCES Rol(Id)
)ENGINE=InnoDB;

-- =========================
-- PROJECT
-- =========================

CREATE TABLE Project
(
	 Id						INT				UNSIGNED		NOT NULL	PRIMARY KEY		AUTO_INCREMENT
	,GebruikerId			INT				UNSIGNED		NOT NULL
    
    ,Titel					VARCHAR(120)					NOT NULL
    ,Beschrijving			TEXT							NULL		DEFAULT NULL
    
	,IsActief				TINYINT							NOT NULL	DEFAULT 1 CHECK (IsActief IN (0,1))
    ,Opmerking				VARCHAR(250)					NULL		DEFAULT NULL
    ,AangemaaktOp			DATETIME(6)						NOT NULL	DEFAULT CURRENT_TIMESTAMP(6)
    ,GewijzigdOp			DATETIME(6)						NOT NULL	DEFAULT CURRENT_TIMESTAMP(6) ON UPDATE CURRENT_TIMESTAMP(6)
    
    ,UNIQUE	(GebruikerId, Titel)
    
    ,FOREIGN KEY (GebruikerId) REFERENCES Gebruiker(Id) ON DELETE CASCADE
)ENGINE=InnoDB;

-- =========================
-- TAAK
-- =========================

CREATE TABLE Taak
(
	 Id						INT				UNSIGNED		NOT NULL	PRIMARY KEY		AUTO_INCREMENT
	,ProjectId				INT				UNSIGNED		NOT NULL
    ,StatusId				TINYINT			UNSIGNED		NOT NULL
    
    ,Titel					VARCHAR(120)					NOT NULL
    ,Beschrijving			TEXT							NULL		DEFAULT NULL
    
    ,Prioriteit				TINYINT			UNSIGNED		NOT NULL	DEFAULT 3
    ,Deadline				DATETIME						NULL		DEFAULT NULL
    
	,IsActief				TINYINT							NOT NULL	DEFAULT 1 CHECK (IsActief IN (0,1))
    ,Opmerking				VARCHAR(250)					NULL		DEFAULT NULL
    ,AangemaaktOp			DATETIME(6)						NOT NULL	DEFAULT CURRENT_TIMESTAMP(6)
    ,GewijzigdOp			DATETIME(6)						NOT NULL	DEFAULT CURRENT_TIMESTAMP(6) ON UPDATE CURRENT_TIMESTAMP(6)
    
    ,UNIQUE (ProjectId, Titel)
    
    ,FOREIGN KEY (ProjectId) REFERENCES Project(Id) ON DELETE CASCADE
    ,FOREIGN KEY (StatusId) REFERENCES TaakStatus(Id)
)ENGINE=InnoDB;

-- =========================
-- INDEXEN
-- =========================

CREATE INDEX idx_project_gebruiker ON Project(GebruikerId);
CREATE INDEX idx_taak_project ON Taak(ProjectId);
CREATE INDEX idx_taak_status ON Taak(StatusId);

-- =========================
-- VIEW
-- =========================

CREATE OR REPLACE VIEW VW_ProjectOverzicht AS
SELECT
			p.Id
			,p.Titel
			,g.VolledigeNaam AS Eigenaar
			,COUNT(t.Id) AS AantakTaken
			,SUM(t.StatusId = 3) AS AantalVoltooid
FROM		Project P
INNER JOIN	Gebruiker g ON g.Id = p.GebruikerId
LEFT JOIN	Taak t ON t.ProjectId = p.Id
GROUP BY	p.Id, p.Titel, g.VolledigeNaam;

-- =========================
-- FUNCTION
-- =========================

DELIMITER //

CREATE FUNCTION FN_ProjectVoortgang(p_ProjectId INT)
RETURNS INT
READS SQL DATA
BEGIN
	DECLARE totaal INT DEFAULT 0;
    DECLARE doneTaken INT DEFAULT 0;
    
    SELECT
		 COUNT(*)
        ,SUM(StatusId = 3)
	INTO
			totaal
			,doneTaken
	FROM	Taak
    WHERE ProjectId = p_ProjectId
    AND IsActief = 1;
    
    IF totaal = 0 THEN
		RETURN 0;
	END IF;
    
    RETURN ROUND((doneTaken / totaal) * 100);
END //

-- =========================
-- PROCEDURE
-- =========================

CREATE PROCEDURE SP_ProjectAanmaken
(
	 IN p_GebruikerId INT
	,IN p_Titel VARCHAR(120)
    ,IN p_Beschrijving TEXT
)
BEGIN
	DECLARE EXIT HANDLER FOR SQLEXCEPTION
    BEGIN
		ROLLBACK;
        SIGNAL SQLSTATE '45000'
        SET MESSAGE_TEXT = 'Project aanmaken mislukt';
	END;
    
    START TRANSACTION;
    
    INSERT INTO Project
    (
		 GebruikerId
		,Titel
        ,Beschrijving
	)
    VALUES
    (
		 P_GebruikerId
		,p_Titel
        ,p_Beschrijving
	);
    
    COMMIT;
END //

DELIMITER ;