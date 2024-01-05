<?php
session_start();

// Connexion à la base de données
$conn = mysqli_connect('localhost', 'pharmacie', 'Pharmacie2024/%', 'pharmacie', 3307) or die('Erreur SQL : ' . mysqli_error($conn));
$conn->query('SET NAMES UTF8');

// Vérifier la connexion
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Initialiser le tableau des médicaments filtrés
$filteredMedicines = array();

// Réinitialiser le panier et les médicaments si le paramètre reset est présent
if (isset($_GET['reset']) && $_GET['reset'] == 'true') {
    // Supprimer le contenu de la table "panier"
    $deleteCartQuery = "DELETE FROM panier WHERE user_id = 1";
    $conn->query($deleteCartQuery);

    // Réinitialiser le tableau de session "cart"
    $_SESSION['cart'] = array();
}else {
    // Récupérer les médicaments depuis la base de données
    $sql = "SELECT brand_name, drug_code Stock, prix FROM dataset250";
    $result = $conn->query($sql);

    // Vérifier si la requête a réussi
    if ($result === false) {
        die("Error: " . $sql . "<br>" . $conn->error);
    }

    // Ajouter au panier
    if (isset($_GET['add'])) {
        $medicineName = urldecode($_GET['add']);
        $addToCartQuery = "SELECT * FROM panier WHERE user_id = 1 AND medicine_name = '$medicineName'";
        
        // Exécutez la requête pour obtenir le prix du médicament
        $prixQuery = "SELECT prix FROM dataset250 WHERE brand_name = '$medicineName'";
        $prixResult = $conn->query($prixQuery);

        if ($prixResult->num_rows > 0) {
            $prixRow = $prixResult->fetch_assoc();
            $prix = $prixRow['prix'];
        } else {
            // Si le prix n'est pas trouvé, vous devrez définir une valeur par défaut ou gérer l'erreur selon vos besoins.
            $prix = 0;
        }

        $existingItem = $conn->query($addToCartQuery);

        if ($existingItem->num_rows > 0) {
            // Le médicament existe déjà, mettez à jour la quantité
            $updateQuantityQuery = "UPDATE panier SET quantity = quantity + 1 WHERE user_id = 1 AND medicine_name = '$medicineName'";
            $conn->query($updateQuantityQuery);
        } else {
            // Le médicament n'existe pas, ajoutez-le au panier
            $addToCartQuery = "INSERT INTO panier (user_id, medicine_name, quantity, prix) VALUES (1, '$medicineName', 1, '$prix')";
            $conn->query($addToCartQuery);
        }

        if (!in_array($medicineName, $_SESSION['cart'])) {
            $_SESSION['cart'][] = $medicineName;
            // Réinitialiser le tableau des médicaments filtrés
            $filteredMedicines = array();
        }
    }


    // Effectuer la recherche
    if (isset($_POST['searchButton'])) {
        $searchTerm = $_POST['searchInput'];

        // Réinitialiser le pointeur des résultats à zéro
        $result->data_seek(0);

        while ($row = $result->fetch_assoc()) {
            if (stripos($row['brand_name'], $searchTerm) !== false) {
                $filteredMedicines[] = array(
                    'brand_name' => $row['brand_name'],
                    'Stock' => $row['Stock'],
                    'prix' => $row['prix']
                );
            }
        }
    } else {
        // Réinitialiser le pointeur des résultats à zéro
        $result->data_seek(0);

        while ($row = $result->fetch_assoc()) {
            $filteredMedicines[] = array(
                'brand_name' => $row['brand_name'],
                'Stock' => $row['Stock'],
                'prix' => $row['prix']
            );
        }
    }
}


// Initialize $totalAmount to 0 by default
$totalAmount = 0;

// Vérifier si le panier existe et n'est pas vide
if (isset($_SESSION['cart']) && !empty($_SESSION['cart'])) {
    foreach ($_SESSION['cart'] as $item) {
        // Retrieve quantity and price from the database based on medicine_name
        $itemDetailsQuery = "SELECT medicine_name, quantity, prix FROM panier WHERE user_id = 1 AND medicine_name = '$item'";
        $itemDetailsResult = $conn->query($itemDetailsQuery);

        if ($itemDetailsResult->num_rows > 0) {
            $itemDetails = $itemDetailsResult->fetch_assoc();
            $itemQuantity = $itemDetails['quantity'];
            $itemPrice = $itemDetails['prix'];

            // Update total amount
            $totalAmount += $itemQuantity * $itemPrice;
        }
    }
}


// Directly include the logic for calculating remaining amount
$remainingAmount = 0;

if (isset($_POST['calculateChange'])) {
    $clientAmount = floatval($_POST['clientAmount']);
    $remainingAmount =  $clientAmount - $totalAmount ;
    $remainingAmount = number_format($remainingAmount, 2);
}


?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="style.css">
    <title>Gestion de Stock Pharmacie</title>
</head>
<body>
    <a href="index.php?reset=true" class="reset-button">Réinitialiser</a>
    <div class="header">
        <h1><img src="logo_pharmacie.png" alt="Logo Pharmacie"> Gestion de Stock Pharmacie</h1>
    </div>



    <div class="container">
        
        <div class="medicine-list">
        <div class="search-section">
            <form action="index.php" method="post">
                
                    <input type="text" name="searchInput" placeholder="Rechercher un médicament">
                    <button type="submit" name="searchButton">Chercher</button>
               
            </form>
         </div>
            <div class="articles">
                
                    <table>
                        <thead>
                            <tr>
                                <th>Nom du Médicament</th>
                                <th>Stock disponible</th>
                                <th>Ajouter au panier</th>
                            </tr>
                        </thead>
                        
                        <tbody>
                            
                            <?php
                            foreach ($filteredMedicines as $medicine) {
                                echo '<tr>';
                                echo '<td>' . $medicine['brand_name'] . '</td>';
                                echo '<td>' . $medicine['Stock'] . '</td>';
                                echo '<td><a class="modern-link" href="index.php?add=' . urlencode($medicine['brand_name']) . '">Ajouter</a></td>';
                                echo '</tr>';
                            }
                            ?>
                            
                        </tbody>
                    
                    </table>
                
            </div>
        </div>



        <div class="authentication-section">
            <form action="login.php" method="post">
                <label for="username">Nom d'utilisateur:</label>
                <input type="text" id="username" name="username" required>

                <label for="password">Mot de passe:</label>
                <input type="password" id="password" name="password" required>

                <button type="submit">Se connecter</button>
            </form>
        </div>

        <div class="cart-section">
            <h2>Panier</h2>
            <ul>
            <?php
                // Vérifier si le panier existe et n'est pas vide
                if (isset($_SESSION['cart']) && !empty($_SESSION['cart'])) {
                    foreach ($_SESSION['cart'] as $item) {
                        // Retrieve quantity and price from the database based on medicine_name
                        $itemDetailsQuery = "SELECT medicine_name, quantity, prix FROM panier WHERE user_id = 1 AND medicine_name = '$item'";
                        $itemDetailsResult = $conn->query($itemDetailsQuery);

                        if ($itemDetailsResult->num_rows > 0) {
                            $itemDetails = $itemDetailsResult->fetch_assoc();
                            $itemName = $itemDetails['medicine_name'];
                            $itemQuantity = $itemDetails['quantity'];
                            $itemPrice = $itemDetails['prix'];

                            // Display the item details
                            echo '<li>' . $itemName . '    - Quantité:  ' . $itemQuantity . ', Prix total: ' . ($itemQuantity * $itemPrice) . '</li>';
                        }
                    }

                    // Calculer le montant total dans le panier directement
                    $userId = 1; // Remplacez par l'ID de l'utilisateur actuel
                    $totalAmountQuery = "SELECT SUM(quantity * prix) AS total FROM panier WHERE user_id = $userId";
                    $result = $conn->query($totalAmountQuery);

                    if ($result->num_rows > 0) {
                        $row = $result->fetch_assoc();
                        $totalAmount = $row['total'];
                    } else {
                        $totalAmount = 0;
                    }
                } else {
                    echo '<li>Le panier est vide.</li>';
                    $totalAmount = 0;
                }
            ?>

            </ul>
            <label for="totalAmount">Montant Total:</label>
            <input type="text" id="totalAmount" value="<?= number_format($totalAmount, 2) ?>" readonly>

        </div>




        <div class="total-section">
            <label for="clientAmount">Montant donné par le client:</label>
            <form method="post" action="index.php">
                <input type="text" name="clientAmount" id="clientAmount">
                <button type="submit" name="calculateChange">Calculer le reste</button>
            </form>
            <div id="changeAmount">
                Montant restant: <?php echo $remainingAmount; ?>
            </div>
        </div>

    </div>

    <?php 
        // Fermer la connexion à la base de données
        $conn->close();
    ?>
</body>
</html>

