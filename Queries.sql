CREATE TABLE organization (
    id int NOT NULL AUTO_INCREMENT,
    email VARCHAR(100) NOT NULL UNIQUE,
    password VARCHAR(225) NOT NULL,
    name VARCHAR(225),
    PRIMARY KEY(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE schools (
     id int NOT NULL AUTO_INCREMENT,
     user_id int NOT NULL,
     name VARCHAR(225) NOT NULL,
     phone_number VARCHAR(50),
     country VARCHAR(50),
     city VARCHAR(20),
     address VARCHAR(100),
     PRIMARY KEY(id),
     INDEX(user_id)
     FOREIGN KEY (user_id) REFERENCES users(id)
);


CREATE TABLE classrooms(
    id int NOT NULL AUTO_INCREMENT,
    name VARCHAR(225) NOT NULL,
    school_id int NOT NULL,
    PRIMARY KEY(id),
    INDEX(school_id),
    FOREIGN KEY (school_id) REFERENCES schools(id)
)
