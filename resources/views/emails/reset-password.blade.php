<!DOCTYPE html>
<html>
<head>
    <title>Reinicio de contraseña</title>
</head>
<body>
    <h1>Te saluda UwU company - Tema: Reinicio de contraseña</h1>
    <p>Hola {{ $user->firstName }},</p>
    <p>Tu código de verificación es: <strong>{{ $code }}</strong></p>
    <p>Este código expira en 15 minutos.</p>
    <p>Si no solicitaste este cambio, ignora este correo.</p>
</body>
</html>