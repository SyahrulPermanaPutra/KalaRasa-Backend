<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Registrasi Berhasil</title>
    <link rel="icon" href="/images/logo-jtvhub.png" type="image/png">
    <link rel="apple-touch-icon" href="/images/logo-jtvhub.png">
    <style>
        body { font-family: ui-sans-serif, system-ui, sans-serif; background: #fff; color: #1b1b18; margin: 0; }
        .center-box { min-height: 200px; display: flex; flex-direction: column; align-items: center; justify-content: center; padding: 2rem 1rem; }
        .logo-jtvhub { width: 120px; max-width: 90vw; height: auto; margin-bottom: 2rem; border-radius: 24px; box-shadow: 0 2px 12px #0001; }
        .success-title { font-size: 2rem; font-weight: 700; color: #14326b; margin-bottom: 1rem; text-align: center; }
        .success-desc { font-size: 1.1rem; color: #333; text-align: center; margin-bottom: 2rem; }
        .btn-login { display: inline-block; background: #14326b; color: #fff; border-radius: 16px; padding: 0.8rem 2.2rem; font-size: 1.1rem; font-weight: 600; text-align: center; text-decoration: none; box-shadow: 0 2px 8px #0001; transition: background 0.2s, transform 0.1s; }
        .btn-login:hover, .btn-login:focus { background: #1d417f; }
    </style>
</head>
<body>
    <div class="center-box" style="background: #fff; border-radius: 24px; box-shadow: 0 2px 12px #0001; padding: 2rem 1.5rem; max-width: 500px; margin: 2rem auto; border: 1px solid #e5e7eb;">
        <img src="/images/logo-jtvhub.png" alt="JTV Hub" class="logo-jtvhub" />
        <div class="success-title" style="color: #14326b;">Selamat! Akun JTVHub Anda berhasil dibuat</div>
        <div class="success-desc" style="color: #333;">Anda dapat melakukan login pada aplikasi milik JTV untuk menggunakan layanan.</div>
    </div>
    <style>
        @media (prefers-color-scheme: dark) {
            body { background: #0a0a0a; }
            .center-box { background: #18181b !important; border-color: #27272a !important; }
            .success-title { color: #fff !important; }
            .success-desc { color: #e5e5e5 !important; }
            .btn-login { background: #14326b; color: #fff; }
        }
    </style>
    <script>
        window.sessionStorage.removeItem('registerSuccessShown');
        if (window.sessionStorage.getItem('registerSuccessShown')) {
            window.location.replace('/');
        } else {
            window.sessionStorage.setItem('registerSuccessShown', '1');
        }
    </script>
</body>
</html>
