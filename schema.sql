CREATE TABLE clubs(
    club_id INT PRIMARY KEY AUTO_INCREMENT,
    club_name VARCHAR(255) UNIQUE NOT NULL
);
CREATE TABLE events(
    event_id INT PRIMARY KEY auto_increment,
    event_name VARCHAR(255) NOT NULL,
    event_duration DECIMAL(4,2),
    event_date DATETIME,
    event_creator VARCHAR(255),
    event_availablity BOOLEAN,
    Foreign Key (event_creator) REFERENCES clubs(club_name)

);
Create table students(
    student_id INT(10) UNIQUE NOT NULL,
    full_name VARCHAR(255) NOT NULL,
    semester INT(2) NOT NULL,
    student_email VARCHAR(255) NOT NULL,
    address VARCHAR(255) NOT NULL,
    DOB DATE,
    contact VARCHAR(15),
    department VARCHAR(255)
);

Create table volunteer_req(
    req_ID INT PRIMARY KEY AUTO_INCREMENT,
    full_name VARCHAR(255) NOT NULL,
    student_id INT(10) NOT NULL,
    student_email VARCHAR(255),
    event_id INT,
    Foreign Key (student_id) REFERENCES students(student_id),
    Foreign Key (event_id) REFERENCES events(event_id)
);

INSERT INTO `clubs` (`club_id`, `club_name`) VALUES (NULL, 'NSUSS'), (NULL, 'NSU YES');
INSERT INTO `events` (`event_id`, `event_name`, `event_duration`, `event_date`, `event_creator`, `event_availablity`) VALUES (NULL, 'Boshonto Utshob 2026', '7.5', '2026-02-25 08:00:00', 'NSUSS', '1');
INSERT INTO `students` (`student_id`, `full_name`, `semester`, `student_email`, `address`, `DOB`, `contact`, `department`) VALUES ('2233440', 'Moontashir Azim', '8', 'moontashir.azim@northsouth.edu', '157/1 Lutfur Rahman Lane, Dhaka-1000', '2003-12-30', '0195599406', 'ECE'), ('223341', 'Nasiruddin Patwary', '12', 'derby.patwary@northsouth.edu', 'mirza abbas st, Dhaka-1200', '1990-01-01', '01994449087', 'BBA');
INSERT INTO `events` (`event_id`, `event_name`, `event_duration`, `event_date`, `event_creator`, `event_availablity`) VALUES (NULL, 'Excelsor', '2.5', '2026-02-27 13:00:00', 'NSU YES', '1');