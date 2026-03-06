INSERT INTO `clubs` (`club_id`, `club_name`) VALUES (NULL, 'NSU CDC');

INSERT INTO students (student_id, full_name, student_email, address, DOB, contact) VALUES ('223341264', 'test', 'test@northsouth.edu', '111/1 St', '2026-03-24', '01954858434');

INSERT INTO club_members (member_id, student_id, club_id,Role, active) VALUES (NULL, '223341264', '1', 'EB-President', '1');



INSERT INTO `students` (`student_id`, `full_name`, `student_email`, `address`, `DOB`, `contact`) VALUES
(2234501, 'Rahim Mahmud', 'rahim.mahmud@northsouth.edu', 'Banani, Dhaka-1213', '2003-04-12', '01723456781'),
(2234502, 'Tania Sultana', 'tania.sultana@northsouth.edu', 'Uttara, Dhaka-1230', '2004-07-19', '01834567892'),
(2234503, 'Fahim Chowdhury', 'fahim.chowdhury@northsouth.edu', 'Dhanmondi, Dhaka-1209', '2002-02-25', '01945678903'),
(2234504, 'Nusrat Jahan', 'nusrat.jahan@northsouth.edu', 'Mirpur, Dhaka-1216', '2004-11-03', '01656789014'),
(2234505, 'Imran Hossain', 'imran.hossain@northsouth.edu', 'Mohammadpur, Dhaka-1207', '2001-09-15', '01767890125'),
(2234506, 'Sadia Karim', 'sadia.karim@northsouth.edu', 'Bashundhara, Dhaka-1229', '2003-06-30', '01878901236'),
(2234507, 'Arif Hasan', 'arif.hasan@northsouth.edu', 'Tejgaon, Dhaka-1208', '2004-01-10', '01989012347');


INSERT INTO `club_members` (`student_id`, `club_id`, `Role`) VALUES
(2234501, 1, 'Member'),
(2234502, 1, 'Incharge'),
(2234503, 2, 'Member'),
(2234504, 2, 'Member'),
(2234505, 1, 'Member'),
(2234506, 2, 'Incharge');

INSERT INTO `students` (`student_id`, `full_name`, `student_email`, `address`, `DOB`, `contact`) VALUES
(2234601, 'Mehedi Hasan', 'mehedi.hasan@northsouth.edu', 'Mirpur, Dhaka-1216', '2004-03-11', '01711112221'),
(2234602, 'Farzana Rahman', 'farzana.rahman@northsouth.edu', 'Uttara, Dhaka-1230', '2003-05-21', '01711112222'),
(2234603, 'Tanvir Alam', 'tanvir.alam@northsouth.edu', 'Dhanmondi, Dhaka-1209', '2004-01-15', '01711112223'),
(2234604, 'Sabina Yasmin', 'sabina.yasmin@northsouth.edu', 'Mohammadpur, Dhaka-1207', '2002-09-09', '01711112224'),
(2234605, 'Rakib Ahmed', 'rakib.ahmed@northsouth.edu', 'Banani, Dhaka-1213', '2004-07-18', '01711112225'),
(2234606, 'Jannat Ara', 'jannat.ara@northsouth.edu', 'Badda, Dhaka-1212', '2003-12-01', '01711112226'),
(2234607, 'Sabbir Hossain', 'sabbir.hossain@northsouth.edu', 'Rampura, Dhaka-1219', '2002-02-14', '01711112227'),
(2234608, 'Nabila Islam', 'nabila.islam@northsouth.edu', 'Khilgaon, Dhaka-1219', '2005-06-25', '01711112228'),
(2234609, 'Shakil Khan', 'shakil.khan@northsouth.edu', 'Farmgate, Dhaka-1215', '2001-10-10', '01711112229'),
(2234610, 'Mariam Akter', 'mariam.akter@northsouth.edu', 'Malibagh, Dhaka-1217', '2003-04-04', '01711112230'),
(2234611, 'Hasib Rahman', 'hasib.rahman@northsouth.edu', 'Tejgaon, Dhaka-1208', '2004-11-19', '01711112231'),
(2234612, 'Priya Dutta', 'priya.dutta@northsouth.edu', 'Shyamoli, Dhaka-1207', '2002-08-08', '01711112232'),
(2234613, 'Rifat Karim', 'rifat.karim@northsouth.edu', 'Banasree, Dhaka-1219', '2004-02-22', '01711112233'),
(2234614, 'Nafisa Anjum', 'nafisa.anjum@northsouth.edu', 'Gulshan, Dhaka-1212', '2004-09-30', '01711112234'),
(2234615, 'Omar Faruk', 'omar.faruk@northsouth.edu', 'Kafrul, Dhaka-1206', '2002-06-06', '01711112235'),
(2234616, 'Tasnim Chowdhury', 'tasnim.chowdhury@northsouth.edu', 'Baridhara, Dhaka-1212', '2003-01-12', '01711112236'),
(2234617, 'Mahmudul Hasan', 'mahmudul.hasan@northsouth.edu', 'Mirpur DOHS, Dhaka', '2003-03-17', '01711112237'),
(2234618, 'Sadia Noor', 'sadia.noor@northsouth.edu', 'Uttara Sector 7, Dhaka', '2005-05-05', '01711112238'),
(2234619, 'Ashiqur Rahman', 'ashiqur.rahman@northsouth.edu', 'Banani DOHS, Dhaka', '2002-12-12', '01711112239'),
(2234620, 'Tanzila Haque', 'tanzila.haque@northsouth.edu', 'Badda, Dhaka', '2005-08-18', '01711112240'),
(2234621, 'Rezaul Karim', 'rezaul.karim@northsouth.edu', 'Mohakhali, Dhaka', '2001-01-01', '01711112241'),
(2234622, 'Fariha Islam', 'fariha.islam@northsouth.edu', 'Rampura, Dhaka', '2004-10-10', '01711112242'),
(2234623, 'Sajid Hasan', 'sajid.hasan@northsouth.edu', 'Shantinagar, Dhaka', '2004-06-16', '01711112243'),
(2234624, 'Lamia Rahman', 'lamia.rahman@northsouth.edu', 'Elephant Road, Dhaka', '2003-09-09', '01711112244'),
(2234625, 'Nayeem Ahmed', 'nayeem.ahmed@northsouth.edu', 'Mirpur 10, Dhaka', '2003-07-07', '01711112245');

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