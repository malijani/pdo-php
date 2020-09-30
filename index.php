<?php
require_once "class/db.php";

// Create db instance
$db = new \db\DB();

// Ways of binding parameters:
# 1. Friendly method
$db->bind("Firstname", "Bob");
$db->bind("Age", '21');

# 2. Bind more than one column
$db->bindMore(['Firstname'=>'Bob', 'Age'=> '21']);

# 3. The hard way : using query method
//$db->query("SELECT * FROM persons WHERE Firstname = :firstname AND Age = :age", ["firstname"=>"Bob", "age"=>'40']);




// Fetching data
$persons = $db->query("SELECT * FROM persons");

# Fetch with parameters
$personsNum = $db->query("SELECT * FROM persons", null, PDO::FETCH_NUM);

# Fetch single value
$firstName = $db->query("SELECT Firstname FROM persons WHERE Id = :id", ["id"=>"3"]);

# Fetch single row
$idAge = $db->row("SELECT Id, Age FROM persons WHERE Firstname = :f", ["f"=>'Zoe']);

# Fetch single row with numeric fetch mode
$idAgeNum = $db->row("SELECT Id, Age FROM persons WHERE Firstname = :f", ["f"=>"Bob"], \PDO::FETCH_NUM);


// Columns
$ages = $db->column("SELECT Age FROM persons");

// Update
$update = $db->query("UPDATE persons SET Firstname = :f WHERE Id = :id", ["f"=>"Mohammad", "id"=>'1']);

// Insert
$insert = $db->query("INSERT INTO persons (Firstname, Age) VALUES (:f, :age)", ["f"=>"Mehdi", "age"=>'21']);

// Delete
$delete = $db->query("DELETE FROM persons WHERE Id = :id", ["id"=>"6"]);


function dumper($value, $title = "")
{
    echo "<pre>";
    echo "<h1>" . $title . "</h1>";
    var_dump($value);
    echo "</pre>";
}

dumper($idAge, "Single Row, ID and Age");
dumper($firstName, "Fetch Single Value, The firstname");
dumper($ages, "Fetch Column, Numeric Index");