CREATE TABLE organization (
    id int NOT NULL AUTO_INCREMENT,
    email VARCHAR(100) NOT NULL UNIQUE,
    password VARCHAR(225) NOT NULL,
    name VARCHAR(225),
    role smallint NOT NULL,
    phone_number VARCHAR(13),
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
     email VARCHAR(50),
     date_created DATETIME NOT NULL,
     PRIMARY KEY(id),
     INDEX(user_id)
     FOREIGN KEY (user_id) REFERENCES users(id)
);

CREATE TABLE classrooms(
    id int NOT NULL AUTO_INCREMENT,
    name VARCHAR(225) NOT NULL,
    school_id int NOT NULL,
    user_id int,
    PRIMARY KEY(id),
    INDEX(school_id),
    FOREIGN KEY (school_id) REFERENCES schools(id)
);

CREATE TABLE products (
    id int NOT NULL AUTO_INCREMENT,
    name VARCHAR(50) NOT NULL,
    code int NOT NULL,
    price VARCHAR(5),
    PRIMARY KEY(id)
);

CREATE TABLE users_transactions (
    user_id in NOT NULL REFERENCES users(id)
    amount VARCHAR(50) NOT NULL,
    transaction_type boolean NOT NULL,
    date_created DATETIME NOT NULL,
    transaction VARCHAR(250),
    balance VARCHAR(50)
);

CREATE TABLE wallets (
    user_id int NOT NULL,
    amount VARCHAR(50) NOT NULL,
    FOREIGN KEY (user_id) REFERENCES users(id)
)

/**
* Activation keys used to activate the SOFTWARE
*/
CREATE TABLE activations (
    id int NOT NULL AUTO_INCREMENT,
    product_id VARCHAR(20) NOT NULL,
    activation_key VARCHAR(16) NOT NULL,
    user_identifier VARCHAR(50) NOT NULL, /** this might be email or phone number*/
    name VARCHAR(120),
    date_generated DATETIME NOT NULL,
    method VARCHAR(50),
    PRIMARY KEY(id)
);

CREATE TABLE pins (
    id int NOT NULL AUTO_INCREMENT,
    pin VARCHAR(14) NOT NULL,
    user_id int NOT NULL,
    balance VARCHAR(50) NOT NULL,
    date_created DATETIME NOT NULL,
    PRIMARY KEY(id)
);

CREATE TABLE pin_history (
    pin_id int NOT NULL,
    transaction_date DATETIME NOT NULL,
    pin_user VARCHAR(50), /** Pin user can be email or phone number */
    amount VARCHAR(50)
);

/**
* Activation transactions
*/
CREATE TABLE users_transactions (
    id int NOT NULL AUTO_INCREMENT,
    transaction VARCHAR(120),
    source_code VARCHAR(5), /** The source of the transaction might be from pin or card*/
    user_id int, /** The id for instance the pin id*/
    amount VARCHAR(6),
    balance VARCHAR(50)
);

CREATE TABLE students (
    user_id int NOT NULL,
    school_id int NOT NULL,
    classroom_id int NOT NULL
)

CREATE TABLE teachers (
    user_id int NOT NULL,
    teacher_id int NOT NULL
)

SELECT () classooms.id, classrooms.name, schools.name as school_name, schools.phone_number, schools.country, schools.city, schools.address, users.name as teacher from classrooms INNER JOIN schools ON schools.id = classrooms.school_id LEFT JOIN users ON classrooms.`user_id` = users.id;