<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin - Location d'Objets Bébé</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: 'Segoe UI', Arial, sans-serif;
        }

        body {
            background: #f0f9ff;
            min-height: 100vh;
            display: flex;
            justify-content: center;
            align-items: center;
            background-image: linear-gradient(to bottom right, #e6f4ff, #ffffff);
        }

        .branding {
            text-align: center;
            margin-bottom: 2.5rem;
            position: relative;
            padding-bottom: 30px;
            
        }

        .logo {
            font-size: 2.4rem;
            font-weight: 800;
            background: linear-gradient(20deg, #5aa5f8, #ff9eb7);
            -webkit-background-clip: text;
            background-clip: text;
            color: transparent;
            letter-spacing: -1.5px;
            display: inline-block;
            margin-bottom: 0.5rem;
            font-family: 'Arial Rounded MT Bold', system-ui;
            position: absolute;
            transform: translateX(-50%);
        }

        
        .login-container {
            background: white;
            padding: 2.5rem;
            border-radius: 15px;
            box-shadow: 0 8px 30px rgba(0, 68, 114, 0.1);
            width: 100%;
            max-width: 400px;
            transition: transform 0.3s ease;
        }

        .login-container:hover {
            transform: translateY(-5px);
        }

        h2 {
            margin-top: 20px;
            color: #2a4d6d;
            text-align: center;
            margin-bottom: 2rem;
            font-size: 1.8rem;
            position: relative;
        }

        h2::after {
            content: '';
            display: block;
            width: 50px;
            height: 3px;
            background: #ffb6c1;
            margin: 0.5rem auto;
        }

        .form-group {
            margin-bottom: 1.5rem;
        }

        label {
            display: block;
            color: #4a667e;
            margin-bottom: 0.5rem;
            font-weight: 500;
        }

        input {
            width: 100%;
            padding: 12px;
            border: 2px solid #e1ecf4;
            border-radius: 8px;
            font-size: 1rem;
            transition: border-color 0.3s ease;
        }

        input:focus {
            outline: none;
            border-color: #89c2f5;
            box-shadow: 0 0 0 3px rgba(137, 194, 245, 0.2);
        }

        button {
            width: 100%;
            padding: 12px;
            background: #5aa5f8;
            color: white;
            border: none;
            border-radius: 8px;
            font-size: 1rem;
            font-weight: 600;
            cursor: pointer;
            transition: background 0.3s ease;
        }

        button:hover {
            background: #3d8cd6;
        }

        .branding {
            text-align: center;
            margin-bottom: 2rem;
        }

        .branding img {
            width: 80px;
            margin-bottom: 1rem;
        }

        @media (max-width: 480px) {
            .login-container {
                margin: 1rem;
                padding: 1.5rem;
            }
        }
    </style>
</head>
<body>
    <div class="login-container">
    <div class="branding">
            <div class="logo">miniloc</div>
            
        </div>
        
        <h2>Espace Administrateur</h2>

        <form action="../Traitement/traitement_connexion_admin.php" method="POST">
            <div class="form-group">
                <label for="email">Email</label>
                <input type="email" id="email" name="email" required 
                       placeholder="admin@gmail.com">
            </div>

            <div class="form-group">
                <label for="mot_pass">Mot de passe</label>
                <input type="password" id="mot_pass" name="mot_pass" required 
                       placeholder="••••••••">
            </div>

            <button type="submit">Se connecter</button>
        </form>
    </div>
</body>
</html>