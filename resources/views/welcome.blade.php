<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>SSO-JTVHub-Account</title>
    <link rel="icon" href="images/logo-jtvhub.png" type="image/png">
    <link rel="apple-touch-icon" href="images/logo-jtvhub.png">
    <style>
        body { font-family: ui-sans-serif, system-ui, sans-serif; background: #fff; color: #1b1b18; margin: 0; }
        .center-box { min-height: 100vh; display: flex; flex-direction: column; align-items: center; justify-content: center; padding: 2rem 1rem; }
        .logo-jtvhub { width: 260px; max-width: 90vw; height: auto; margin-bottom: 2.5rem; border-radius: 32px; box-shadow: 0 2px 12px #0001; }
        .btn-jtvhub { display: block; width: 100%; max-width: 600px; background: #14326b; color: #fff; border-radius: 20px; padding: 1.2rem 0; font-size: 2rem; font-weight: 700; text-align: center; text-decoration: none; box-shadow: 0 2px 8px #0001; transition: background 0.2s, transform 0.1s; letter-spacing: 0.5px; }
        .btn-jtvhub:hover, .btn-jtvhub:focus { background: #1d417f; }
        @media (max-width: 600px) {
            .logo-jtvhub { width: 140px; margin-bottom: 1.5rem; }
            .btn-jtvhub { font-size: 1.1rem; padding: 0.85rem 0; }
        }
    </style>
</head>
<body>
    <div class="center-box">
        <img src="images/logo-jtvhub.png" alt="JTV Hub" class="logo-jtvhub" />
        <a href="{{ route('register') }}" class="btn-jtvhub" style="margin-bottom: 1.2rem;">Daftar Akun JTVHub</a>
        <a href="{{ route('password.request') }}" class="btn-jtvhub">Lupa Kata Sandi Akun JTVHub</a>
    </div>
</body>
</html>
