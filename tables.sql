-- Criação do banco de dados
CREATE DATABASE IF NOT EXISTS ecommerce;
USE ecommerce;

-- Tabela Category
CREATE TABLE Category (
    ID INT AUTO_INCREMENT PRIMARY KEY,
    Name VARCHAR(100) NOT NULL,
    Description TEXT
);

-- Tabela Product
CREATE TABLE Product (
    ID INT AUTO_INCREMENT PRIMARY KEY,
    Name VARCHAR(100) NOT NULL,
    Description TEXT,
    Price FLOAT NOT NULL,
    Quantity INT NOT NULL,
    Image VARCHAR(255),
    Category_id INT,
    FOREIGN KEY (Category_id) REFERENCES Category(ID)
);

-- Tabela Costumer
CREATE TABLE Costumer (
    ID INT AUTO_INCREMENT PRIMARY KEY,
    Name VARCHAR(100) NOT NULL,
    Email VARCHAR(100) UNIQUE NOT NULL,
    Password VARCHAR(255) NOT NULL,
    ROLE VARCHAR(50) DEFAULT 'user',
    Updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- Tabela Cart
CREATE TABLE Cart (
    ID INT AUTO_INCREMENT PRIMARY KEY,
    Itens INT DEFAULT 0,
    Total FLOAT DEFAULT 0,
    Costumer_id INT,
    FOREIGN KEY (Costumer_id) REFERENCES Costumer(ID)
);

-- Tabela CartItem
CREATE TABLE CartItem (
    ID INT AUTO_INCREMENT PRIMARY KEY,
    Product_ID INT NOT NULL,
    Cart_ID INT NOT NULL,
    QuantityItem INT NOT NULL,
    Updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (Product_ID) REFERENCES Product(ID),
    FOREIGN KEY (Cart_ID) REFERENCES Cart(ID)
);

-- Tabela Order
CREATE TABLE `Order` (
    ID INT AUTO_INCREMENT PRIMARY KEY,
    Total FLOAT NOT NULL,
    Status ENUM('pending', 'paid', 'shipped', 'delivered', 'canceled') NOT NULL,
    Costumer_id INT,
    FOREIGN KEY (Costumer_id) REFERENCES Costumer(ID)
);

-- Tabela OrderItem
CREATE TABLE OrderItem (
    ID INT AUTO_INCREMENT PRIMARY KEY,
    Product_ID INT NOT NULL,
    Order_ID INT NOT NULL,
    QuantityItem INT NOT NULL,
    Price FLOAT NOT NULL,
    Updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (Product_ID) REFERENCES Product(ID),
    FOREIGN KEY (Order_ID) REFERENCES `Order`(ID)
);
