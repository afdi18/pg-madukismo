<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <meta http-equiv="refresh" content="8;url=https://ksomadubaru.com" />
    <title>Perpindahan Website</title>
    <style>
        :root {
            --bg: #f5f7fb;
            --surface: #ffffff;
            --text: #152238;
            --muted: #4a5d7a;
            --accent: #0052cc;
            --accent-hover: #003f9e;
            --border: #d6deeb;
        }

        * {
            box-sizing: border-box;
        }

        body {
            margin: 0;
            min-height: 100vh;
            display: grid;
            place-items: center;
            font-family: "Segoe UI", Tahoma, Geneva, Verdana, sans-serif;
            color: var(--text);
            background:
                radial-gradient(circle at 10% 20%, rgba(0, 82, 204, 0.12), transparent 35%),
                radial-gradient(circle at 90% 80%, rgba(32, 156, 238, 0.1), transparent 40%),
                var(--bg);
            padding: 24px;
        }

        .card {
            width: min(680px, 100%);
            background: var(--surface);
            border: 1px solid var(--border);
            border-radius: 18px;
            box-shadow: 0 16px 45px rgba(21, 34, 56, 0.12);
            padding: 28px;
        }

        .item-center {
            text-align: center;
        }

        h1 {
            margin: 0 0 12px;
            font-size: clamp(1.5rem, 2.3vw, 2rem);
            line-height: 1.2;
        }

        p {
            margin: 0 0 14px;
            color: var(--muted);
            line-height: 1.65;
        }

        .highlight {
            color: var(--text);
            font-weight: 700;
        }

        .link-wrap {
            margin-top: 22px;
            display: flex;
            flex-wrap: wrap;
            gap: 12px;
            align-items: center;
        }

        .btn {
            display: inline-block;
            text-decoration: none;
            background: var(--accent);
            color: #fff;
            font-weight: 600;
            padding: 12px 18px;
            border-radius: 10px;
            transition: background 0.2s ease;
        }

        .btn:hover {
            background: var(--accent-hover);
        }

        .plain-link {
            color: var(--accent);
            font-weight: 600;
            text-decoration: none;
            word-break: break-all;
        }

        .plain-link:hover {
            text-decoration: underline;
        }

        .footer-note {
            margin-top: 12px;
            font-size: 0.92rem;
            color: #5d6f8b;
        }
    </style>
</head>
<body>
    <main class="card" role="main" aria-live="polite">
        <p class="item-center"><img src="{{ asset('images/logo/company-trans.png') }}" alt="Logo KSO Madubaru" width="240" /></p>
        <h1>Website Dashboard KSO Madubaru Pindah ke Domain Resmi</h1>
        <p>
            Untuk pengalaman terbaik, silakan gunakan domain resmi di
            <span class="highlight">https://ksomadubaru.com</span>.
        </p>
        <p>
            Anda akan diarahkan otomatis dalam <span id="countdown" class="highlight">8</span> detik.
            <br />
            Jika tidak, silakan klik tombol di bawah ini.
        </p>

        <div class="link-wrap">
            <a class="btn" href="https://ksomadubaru.com">Buka Website Dashboard KSO Madubaru</a>
            <a class="plain-link" href="https://ksomadubaru.com">https://ksomadubaru.com</a>
        </div>

        <p class="footer-note">© 2026 IT PGRAB - KSO Madubaru. All rights reserved.</p>
    </main>

    <script>
        (function () {
            var target = "https://ksomadubaru.com";
            var remaining = 8;
            var countdownEl = document.getElementById("countdown");

            var timer = setInterval(function () {
                remaining -= 1;

                if (countdownEl && remaining >= 0) {
                    countdownEl.textContent = String(remaining);
                }

                if (remaining <= 0) {
                    clearInterval(timer);
                    window.location.href = target;
                }
            }, 1000);
        })();
    </script>
</body>
</html>
