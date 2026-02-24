CREATE TABLE clubs(
    club_id INT PRIMARY KEY AUTO_INCREMENT,
    club_name VARCHAR(255) UNIQUE NOT NULL
);
CREATE TABLE events(
    event_id INT PRIMARY KEY auto_increment,
    event_name VARCHAR(255) NOT NULL,
    event_duration DECIMAL(4,2),
    event_date DATETIME
    event_creator VARCHAR(255),
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