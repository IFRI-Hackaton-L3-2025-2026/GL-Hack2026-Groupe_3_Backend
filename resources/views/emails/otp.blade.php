<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <style>
        body { font-family: Arial, sans-serif; background: #f4f4f4; padding: 20px; }
        .container { background: #fff; padding: 30px; border-radius: 8px; max-width: 500px; margin: auto; }
        .otp { font-size: 32px; font-weight: bold; color: #2d3748; letter-spacing: 8px; text-align: center; padding: 20px; background: #f7fafc; border-radius: 8px; margin: 20px 0; }
        .footer { color: #718096; font-size: 12px; margin-top: 20px; }
    </style>
</head>
<body>
    <div class="container">
        <h2>Bonjour {{ $fullname }},</h2>
        <p>Vous avez demandé une réinitialisation de votre mot de passe. Voici votre code OTP :</p>
        <div class="otp">{{ $otp }}</div>
        <p>Ce code expire dans <strong>10 minutes</strong>.</p>
        <p>Si vous n'avez pas demandé cette réinitialisation, ignorez cet email.</p>
        <div class="footer">
            <p>BMI App — Cet email est généré automatiquement, merci de ne pas y répondre.</p>
        </div>
    </div>
</body>
</html>