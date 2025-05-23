<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>BébéLoc - Connexion</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: 'Comic Neue', cursive;
        }

        body {
            background: #f8f9fa;
            min-height: 100vh;
            display: flex;
            flex-direction: column;
            align-items: center;
            background-image: linear-gradient(to bottom right, #b3e5fc, #f8bbd0);
        }

        .header {
            width: 100%;
            padding: 20px;
            background: white;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            text-align: center;
        }

        .logo {
            font-size: 2.5rem;
            color: #2196F3;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 10px;
        }

        .form-container {
            background: white;
            padding: 40px;
            border-radius: 20px;
            box-shadow: 0 4px 20px rgba(0,0,0,0.1);
            margin-top: 50px;
            margin-bottom: 30px;
            width: 90%;
            max-width: 400px;
            position: relative;
            overflow: hidden;
        }

        .form-container::before {
            content: '';
            position: absolute;
            top: -50px;
            right: -50px;
            width: 150px;
            height: 150px;
            background: #ffcdd2;
            border-radius: 50%;
            opacity: 0.3;
        }

        h2 {
            color: #2196F3;
            margin-bottom: 30px;
            text-align: center;
            font-size: 2rem;
        }

        .form-group {
            margin-bottom: 25px;
            position: relative;
        }

        label {
            display: block;
            margin-bottom: 8px;
            color: #616161;
            font-size: 1.1rem;
        }

        input {
            width: 100%;
            padding: 12px 15px;
            border: 2px solid #e0e0e0;
            border-radius: 10px;
            font-size: 1rem;
            transition: all 0.3s ease;
        }

        input:focus {
            outline: none;
            border-color: #2196F3;
            box-shadow: 0 0 8px rgba(33,150,243,0.2);
        }

        button {
            width: 100%;
            padding: 15px;
            background: #2196F3;
            color: white;
            border: none;
            border-radius: 10px;
            font-size: 1.1rem;
            cursor: pointer;
            transition: all 0.3s ease;
            text-transform: uppercase;
            font-weight: bold;
            letter-spacing: 1px;
        }

        button:hover {
            background: #1976D2;
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(33,150,243,0.3);
        }

        .signup-link {
            text-align: center;
            margin-top: 20px;
            color: #616161;
        }

        .signup-link a {
            color: #2196F3;
            text-decoration: none;
            font-weight: bold;
        }

        .baby-icons {
            display: flex;
            justify-content: center;
            gap: 20px;
            margin: 30px 0;
        }

        .baby-icons img {
            width: 50px;
            opacity: 0.3;
        }

        @media (max-width: 480px) {
            .form-container {
                padding: 30px;
            }
            
            h2 {
                font-size: 1.8rem;
            }
        }
    </style>
    <link href="https://fonts.googleapis.com/css2?family=Comic+Neue:wght@400;700&display=swap" rel="stylesheet">
</head>
<body>
    <div class="header">
        <div class="logo">
            <i class="fas fa-baby-carriage"></i>
            MiniLoc
        </div>
    </div>

    <div class="form-container">
        <h2>Connexion 👶</h2>
        
        <form action="../Traitement/traitement_connexion.php" method="POST">
            <div class="form-group">
                <label for="email">Email</label>
                <input type="email" name="email" required placeholder="exemple@baby.com">
            </div>

            <div class="form-group">
                <label for="mot_de_passe">Mot de passe</label>
                <input type="password" name="mot_de_passe" required placeholder="••••••••">
            </div>

            <button type="submit">
                <i class="fas fa-sign-in-alt"></i> Se connecter
            </button>
        </form>

        <div class="signup-link">
            Nouveau chez nous ? <a href="inscription.php">Créez un compte</a>
        </div>
    </div>

    <script src="https://kit.fontawesome.com/your-kit-code.js"></script>
</body>
</html>