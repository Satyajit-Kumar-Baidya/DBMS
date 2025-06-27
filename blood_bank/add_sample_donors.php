<?php
require_once 'db_connect.php';

try {
    // Sample donor data
    $sample_donors = [
        [
            'first_name' => 'Rakib',
            'last_name' => 'Hassan',
            'blood_group' => 'A+',
            'age' => 25,
            'gender' => 'male',
            'phone' => '01712345678',
            'email' => 'rakib@example.com',
            'address' => 'Mirpur-10, Dhaka'
        ],
        [
            'first_name' => 'Fatima',
            'last_name' => 'Rahman',
            'blood_group' => 'B+',
            'age' => 30,
            'gender' => 'female',
            'phone' => '01812345678',
            'email' => 'fatima@example.com',
            'address' => 'Dhanmondi, Dhaka'
        ],
        [
            'first_name' => 'Kamal',
            'last_name' => 'Hossain',
            'blood_group' => 'O+',
            'age' => 28,
            'gender' => 'male',
            'phone' => '01912345678',
            'email' => 'kamal@example.com',
            'address' => 'Uttara, Dhaka'
        ],
        [
            'first_name' => 'Nusrat',
            'last_name' => 'Jahan',
            'blood_group' => 'AB+',
            'age' => 22,
            'gender' => 'female',
            'phone' => '01612345678',
            'email' => 'nusrat@example.com',
            'address' => 'Gulshan, Dhaka'
        ],
        [
            'first_name' => 'Imran',
            'last_name' => 'Khan',
            'blood_group' => 'O-',
            'age' => 35,
            'gender' => 'male',
            'phone' => '01512345678',
            'email' => 'imran@example.com',
            'address' => 'Banani, Dhaka'
        ]
    ];

    // First clear existing donors
    $pdo->exec("TRUNCATE TABLE blood_donors");
    echo "Cleared existing donors<br>";

    // Reset blood stock
    $pdo->exec("UPDATE blood_stock SET quantity = 0");
    echo "Reset blood stock<br>";

    // Insert sample donors
    $stmt = $pdo->prepare("INSERT INTO blood_donors (first_name, last_name, blood_group, age, gender, phone, email, address) 
                          VALUES (:first_name, :last_name, :blood_group, :age, :gender, :phone, :email, :address)");

    foreach ($sample_donors as $donor) {
        $stmt->execute($donor);
        
        // Update blood stock
        $update_stock = $pdo->prepare("UPDATE blood_stock SET quantity = quantity + 1 WHERE blood_group = ?");
        $update_stock->execute([$donor['blood_group']]);
    }

    echo "<div style='background-color: #d4edda; color: #155724; padding: 15px; margin: 10px; border-radius: 5px;'>";
    echo "Successfully added 5 sample donors!<br>";
    echo "You can now go back to the <a href='donors.php'>donors list</a> to see them.";
    echo "</div>";

} catch(PDOException $e) {
    echo "<div style='background-color: #f8d7da; color: #721c24; padding: 15px; margin: 10px; border-radius: 5px;'>";
    echo "Error: " . $e->getMessage();
    echo "</div>";
}
?> 