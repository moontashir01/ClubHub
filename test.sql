INSERT INTO students (student_id, full_name, semester, student_email, address, DOB, contact, department) VALUES ('223341264', 'test', '12', 'test@northsouth.edu', '111/1 St', '2026-03-24', '01954858434', 'ECO');

INSERT INTO user (ID, email, Name, Password, created_at) VALUES (NULL, 'test@northsouth.edu', 'test', '$2a$12$i4.RoVJ06n.VA1DNAUF/keOZGn7272L7H/fNBEdIA6MDO4elduET2', current_timestamp());

INSERT INTO club_members (member_id, student_id, club_id, Role, Skills, active) VALUES (NULL, '223341264', '1', 'EB-President', NULL, '1');


INSERT INTO `students` (`student_id`, `full_name`, `semester`, `student_email`, `address`, `DOB`, `contact`, `department`) VALUES
(2234501, 'Rahim Mahmud', 5, 'rahim.mahmud@northsouth.edu', 'Banani, Dhaka-1213', '2003-04-12', '01723456781', 'CSE'),
(2234502, 'Tania Sultana', 3, 'tania.sultana@northsouth.edu', 'Uttara, Dhaka-1230', '2004-07-19', '01834567892', 'EEE'),
(2234503, 'Fahim Chowdhury', 7, 'fahim.chowdhury@northsouth.edu', 'Dhanmondi, Dhaka-1209', '2002-02-25', '01945678903', 'BBA'),
(2234504, 'Nusrat Jahan', 2, 'nusrat.jahan@northsouth.edu', 'Mirpur, Dhaka-1216', '2004-11-03', '01656789014', 'CSE'),
(2234505, 'Imran Hossain', 9, 'imran.hossain@northsouth.edu', 'Mohammadpur, Dhaka-1207', '2001-09-15', '01767890125', 'ECO'),
(2234506, 'Sadia Karim', 6, 'sadia.karim@northsouth.edu', 'Bashundhara, Dhaka-1229', '2003-06-30', '01878901236', 'CSE'),
(2234507, 'Arif Hasan', 4, 'arif.hasan@northsouth.edu', 'Tejgaon, Dhaka-1208', '2004-01-10', '01989012347', 'EEE');


INSERT INTO `club_members` (`student_id`, `club_id`, `Role`) VALUES
(2234501, 1, 'Member'),
(2234502, 1, 'Incharge'),
(2234503, 2, 'Member'),
(2234504, 2, 'Member'),
(2234505, 1, 'Member'),
(2234506, 2, 'Incharge');

INSERT INTO `students` (`student_id`, `full_name`, `semester`, `student_email`, `address`, `DOB`, `contact`, `department`) VALUES
(2234601, 'Mehedi Hasan', 2, 'mehedi.hasan@northsouth.edu', 'Mirpur, Dhaka-1216', '2004-03-11', '01711112221', 'CSE'),
(2234602, 'Farzana Rahman', 6, 'farzana.rahman@northsouth.edu', 'Uttara, Dhaka-1230', '2003-05-21', '01711112222', 'BBA'),
(2234603, 'Tanvir Alam', 4, 'tanvir.alam@northsouth.edu', 'Dhanmondi, Dhaka-1209', '2004-01-15', '01711112223', 'EEE'),
(2234604, 'Sabina Yasmin', 7, 'sabina.yasmin@northsouth.edu', 'Mohammadpur, Dhaka-1207', '2002-09-09', '01711112224', 'ECO'),
(2234605, 'Rakib Ahmed', 3, 'rakib.ahmed@northsouth.edu', 'Banani, Dhaka-1213', '2004-07-18', '01711112225', 'CSE'),
(2234606, 'Jannat Ara', 5, 'jannat.ara@northsouth.edu', 'Badda, Dhaka-1212', '2003-12-01', '01711112226', 'BBA'),
(2234607, 'Sabbir Hossain', 8, 'sabbir.hossain@northsouth.edu', 'Rampura, Dhaka-1219', '2002-02-14', '01711112227', 'EEE'),
(2234608, 'Nabila Islam', 1, 'nabila.islam@northsouth.edu', 'Khilgaon, Dhaka-1219', '2005-06-25', '01711112228', 'CSE'),
(2234609, 'Shakil Khan', 9, 'shakil.khan@northsouth.edu', 'Farmgate, Dhaka-1215', '2001-10-10', '01711112229', 'ECO'),
(2234610, 'Mariam Akter', 6, 'mariam.akter@northsouth.edu', 'Malibagh, Dhaka-1217', '2003-04-04', '01711112230', 'BBA'),
(2234611, 'Hasib Rahman', 2, 'hasib.rahman@northsouth.edu', 'Tejgaon, Dhaka-1208', '2004-11-19', '01711112231', 'CSE'),
(2234612, 'Priya Dutta', 7, 'priya.dutta@northsouth.edu', 'Shyamoli, Dhaka-1207', '2002-08-08', '01711112232', 'EEE'),
(2234613, 'Rifat Karim', 3, 'rifat.karim@northsouth.edu', 'Banasree, Dhaka-1219', '2004-02-22', '01711112233', 'CSE'),
(2234614, 'Nafisa Anjum', 4, 'nafisa.anjum@northsouth.edu', 'Gulshan, Dhaka-1212', '2004-09-30', '01711112234', 'BBA'),
(2234615, 'Omar Faruk', 8, 'omar.faruk@northsouth.edu', 'Kafrul, Dhaka-1206', '2002-06-06', '01711112235', 'EEE'),
(2234616, 'Tasnim Chowdhury', 5, 'tasnim.chowdhury@northsouth.edu', 'Baridhara, Dhaka-1212', '2003-01-12', '01711112236', 'CSE'),
(2234617, 'Mahmudul Hasan', 6, 'mahmudul.hasan@northsouth.edu', 'Mirpur DOHS, Dhaka', '2003-03-17', '01711112237', 'ECO'),
(2234618, 'Sadia Noor', 2, 'sadia.noor@northsouth.edu', 'Uttara Sector 7, Dhaka', '2005-05-05', '01711112238', 'BBA'),
(2234619, 'Ashiqur Rahman', 7, 'ashiqur.rahman@northsouth.edu', 'Banani DOHS, Dhaka', '2002-12-12', '01711112239', 'CSE'),
(2234620, 'Tanzila Haque', 1, 'tanzila.haque@northsouth.edu', 'Badda, Dhaka', '2005-08-18', '01711112240', 'EEE'),
(2234621, 'Rezaul Karim', 9, 'rezaul.karim@northsouth.edu', 'Mohakhali, Dhaka', '2001-01-01', '01711112241', 'BBA'),
(2234622, 'Fariha Islam', 4, 'fariha.islam@northsouth.edu', 'Rampura, Dhaka', '2004-10-10', '01711112242', 'CSE'),
(2234623, 'Sajid Hasan', 3, 'sajid.hasan@northsouth.edu', 'Shantinagar, Dhaka', '2004-06-16', '01711112243', 'EEE'),
(2234624, 'Lamia Rahman', 6, 'lamia.rahman@northsouth.edu', 'Elephant Road, Dhaka', '2003-09-09', '01711112244', 'ECO'),
(2234625, 'Nayeem Ahmed', 5, 'nayeem.ahmed@northsouth.edu', 'Mirpur 10, Dhaka', '2003-07-07', '01711112245', 'CSE');

INSERT INTO `club_members` (`student_id`, `club_id`, `Role`) VALUES
(2234601, 1, 'Member'),
(2234602, 1, 'Member'),
(2234603, 2, 'Member'),
(2234604, 2, 'Member'),
(2234605, 1, 'Incharge'),
(2234606, 1, 'Member'),
(2234607, 2, 'Member'),
(2234608, 2, 'Member'),
(2234609, 1, 'Member'),
(2234610, 1, 'Member'),
(2234611, 2, 'Incharge'),
(2234612, 2, 'Member'),
(2234613, 1, 'Member'),
(2234614, 1, 'Member'),
(2234615, 2, 'Member'),
(2234616, 2, 'Member'),
(2234617, 1, 'Member'),
(2234618, 1, 'Member'),
(2234619, 2, 'Member'),
(2234620, 2, 'Member'),
(2234621, 1, 'Incharge'),
(2234622, 1, 'Member'),
(2234623, 2, 'Member'),
(2234624, 2, 'Member');