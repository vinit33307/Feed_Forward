CREATE DATABASE IF NOT EXISTS FeedForward;
USE FeedForward;
CREATE TABLE pickup (
  pickup_id INT AUTO_INCREMENT PRIMARY KEY,
  donor_name VARCHAR(100),
  food_item VARCHAR(100),
  quantity INT,
  expiry_date DATE,
  status VARCHAR(20) DEFAULT 'Pending'
);