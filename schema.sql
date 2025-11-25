CREATE DATABASE IF NOT EXISTS car_sharing;
USE car_sharing;

-- -- Users
CREATE TABLE Users (
    id INT PRIMARY KEY,
    name VARCHAR(255) NOT NULL,
    age INT NOT NULL,
    license_year INT NOT NULL
);

-- Vehicles
CREATE TABLE Vehicles (
    id INT PRIMARY KEY,
    type ENUM('Compact', 'MidSize', 'SUV', 'Truck', 'Electric') NOT NULL,
    registered_at DATE
);

-- Insurance_Plan
CREATE TABLE Insurance_Plan (
    id INT PRIMARY KEY,
    type VARCHAR(255),
    daily_fee INT NOT NULL,
    deductible_amount INT NOT NULL DEFAULT 0,
    vehicle_class ENUM('Compact', 'MidSize', 'SUV', 'Truck', 'Electric'),
    min_driver_age INT,
    min_license_years INT,
    name TEXT
);

-- Rental_Reservation
CREATE TABLE Rental_Reservation (
    id INT PRIMARY KEY,
    uid INT NOT NULL,
    vid INT NOT NULL,
    iid INT NOT NULL,
    start_date DATE,
    end_date DATE,

    FOREIGN KEY (uid) REFERENCES Users(id)
        ON UPDATE CASCADE ON DELETE RESTRICT,
    FOREIGN KEY (vid) REFERENCES Vehicles(id)
        ON UPDATE CASCADE ON DELETE RESTRICT,
    FOREIGN KEY (iid) REFERENCES Insurance_Plan(id)
        ON UPDATE CASCADE ON DELETE RESTRICT
);

-- Payment
CREATE TABLE Payment (
    iid INT NOT NULL,
    rid INT NOT NULL,
    fee INT NOT NULL,

    PRIMARY KEY (iid, rid),

    FOREIGN KEY (iid) REFERENCES Insurance_Plan(id)
        ON UPDATE CASCADE ON DELETE RESTRICT,
    FOREIGN KEY (rid) REFERENCES Rental_Reservation(id)
        ON UPDATE CASCADE ON DELETE RESTRICT
);


-- Insert Dummies
INSERT INTO Users (id, name, age, license_year)
VALUES
(1, 'JS JANG', 24, 2025),
(2, 'HS JANG', 28, 2020),
(3, 'JK JANG', 58, 1999),
(4, 'SS PARK', 57, 2001),
(5, 'MS JOO', 23, 2024),
(6, 'SJ KIM', 29, 2019),
(7, 'SM YOON', 61, 1995),
(8, 'DY PARK', 33, 2015),
(9, 'DH KIM', 26, 2021),
(10, 'MS KANG', 43, 2000),
(11, 'SJ LIM', 19, 2025); 

INSERT INTO Vehicles (id, type, registered_at)
VALUES
(1, 'Compact', '2019-01-15'),
(2, 'MidSize', '2019-01-20'),
(3, 'SUV', '2019-03-11'),
(4, 'Truck', '2020-09-05'),
(5, 'SUV', '2021-02-18'),
(6, 'Compact', '2022-07-01'),
(7, 'Electric', '2023-12-25'),
(8, 'MidSize', '2024-05-30'),
(9, 'Electric', '2025-04-02'),
(10, 'Compact', '2025-10-10'); 

INSERT INTO Insurance_Plan
(id, type, daily_fee, deductible_amount, vehicle_class, min_driver_age, min_license_years, name)
VALUES
(1, 'Basic', 10000, 500000, 'Compact', 21, 1, 'Compact Basic Coverage'),
(2, 'Standard', 15000, 300000, 'MidSize', 23, 2, 'MidSize Standard Coverage'),
(3, 'Premium', 20000, 200000, 'SUV', 25, 3, 'SUV Premium Coverage'),
(4, 'Electric Basic', 18000, 300000, 'Electric', 21, 1, 'EV Basic Protection'),
(5, 'Premium+', 25000, 100000, 'SUV', 28, 5, 'SUV Premium Plus'),
(6, 'Standard', 16000, 250000, 'Compact', 22, 2, 'Compact Standard Plan'),
(7, 'Business', 30000, 150000, 'MidSize', 30, 5, 'MidSize Business Package'),
(8, 'Premium EV', 28000, 150000, 'Electric', 25, 3, 'Electric Premium Coverage'),
(9, 'Truck Premium', 22000, 200000, 'Truck', 27, 4, 'Truck Premium Coverage'),
(10, 'Compact Plus', 14000, 400000, 'Compact', 21, 2, 'Compact Extended Coverage');

INSERT INTO Rental_Reservation (id, uid, vid, iid, start_date, end_date)
VALUES
(1, 2, 3, 3, '2024-02-11', '2024-02-14'),
(2, 3, 5, 5, '2024-03-02', '2024-03-06'),
(3, 4, 7, 4, '2024-03-20', '2024-03-22'),
(4, 5, 2, 2, '2024-04-10', '2024-04-13'),
(5, 6, 4, 9, '2024-05-01', '2024-05-04'),
(6, 7, 10, 10, '2024-06-18', '2024-06-21'),
(7, 8, 8, 7, '2024-07-05', '2024-07-10'),
(8, 9, 6, 6, '2024-08-12', '2024-08-15'),
(9, 10, 9, 4, '2024-09-08', '2024-09-12'),
(10, 3, 4, 9, '2024-11-20', '2024-11-22');